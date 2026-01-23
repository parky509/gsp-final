<?php
/**
 * User handler for GlobalSwiftPay Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSP_User {
    private static $balances_table_checked = false;
    private const BALANCE_EPSILON = 0.01;
    /**
     * Known wallet balance meta keys from common wallet plugins.
     */
    private const WALLET_META_KEYS = array(
        'wps_wallet',
        'wps_wallet_balance',
        'wps_wsfw_wallet',
        'wps_wsfw_wallet_balance',
        'woo_wallet_balance',
        'wallet_balance',
        '_wallet_balance'
    );
    
    /**
     * Get user balance
     */
    public static function get_balance($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gsp_user_balances';

        if (!self::$balances_table_checked) {
            $table_exists = $wpdb->get_var($wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($table_name)
            ));
            if (!$table_exists) {
                GSP_Database::create_tables();
            }
            self::$balances_table_checked = true;
        }

        $balance = $wpdb->get_row($wpdb->prepare(
            "SELECT wallet_balance, savings_balance, created_at, updated_at FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));
        
        if (!$balance) {
            $wallet_balance = self::calculate_wallet_balance_from_transactions($user_id);
            // Create initial balance record
            $wpdb->insert($table_name, array(
                'user_id' => $user_id,
                'wallet_balance' => $wallet_balance,
                'savings_balance' => 0
            ));
            
            return (object) array(
                'wallet_balance' => $wallet_balance,
                'savings_balance' => 0
            );
        }

        if (abs((float) $balance->wallet_balance) < self::BALANCE_EPSILON) {
            $wallet_balance = self::calculate_wallet_balance_from_transactions($user_id);
            if (abs((float) $wallet_balance) >= self::BALANCE_EPSILON) {
                $wpdb->update(
                    $table_name,
                    array('wallet_balance' => $wallet_balance),
                    array('user_id' => $user_id)
                );
                $balance->wallet_balance = $wallet_balance;
            }
        }
        
        return $balance;
    }

    public static function detect_wallet_sources() {
        global $wpdb;
        $sources = array();

        $prefix = preg_replace('/[^A-Za-z0-9_]/', '', $wpdb->prefix);
        $table_candidates = array(
            $prefix . 'wps_wsfw_wallet',
            $prefix . 'wps_wsfw_wallet_balance',
            $prefix . 'wps_wallet',
            $prefix . 'woo_wallet_balance'
        );
        $wallet_tables = $wpdb->get_col($wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $wpdb->esc_like($prefix) . '%wallet%'
        ));
        $table_candidates = array_unique(array_merge($table_candidates, $wallet_tables));
        $balance_columns = array('wallet_balance', 'balance', 'total_balance');

        foreach ($table_candidates as $table) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                continue;
            }
            $table_name = $table;
            $table_exists = $wpdb->get_var($wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($table_name)
            ));
            if (!$table_exists) {
                continue;
            }

            $columns = $wpdb->get_col($wpdb->prepare(
                'SHOW COLUMNS FROM `%s`',
                $table_name
            ));
            if (!in_array('user_id', $columns, true)) {
                continue;
            }

            foreach ($balance_columns as $column) {
                if (in_array($column, $columns, true)) {
                    $sources[] = array(
                        'id' => sprintf('table|%s|user_id|%s', $table_name, $column),
                        'type' => 'table',
                        'label' => sprintf(__('Table %1$s (%2$s)', 'globalswiftpay-dashboard'), $table_name, $column),
                        'table' => $table_name,
                        'user_column' => 'user_id',
                        'balance_column' => $column
                    );
                    break;
                }
            }
        }

        $wallet_like = '%' . $wpdb->esc_like('wallet') . '%';
        $meta_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            $wallet_like
        ));
        $meta_keys = array_unique(array_merge(self::WALLET_META_KEYS, $meta_keys));

        foreach ($meta_keys as $meta_key) {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s",
                $meta_key
            ));
            if ($count > 0) {
                $sources[] = array(
                    'id' => sprintf('meta|%s', $meta_key),
                    'type' => 'meta',
                    'label' => sprintf(__('User meta %s', 'globalswiftpay-dashboard'), $meta_key),
                    'meta_key' => $meta_key
                );
            }
        }

        return $sources;
    }

    public static function migrate_wallet_balances($source_id) {
        global $wpdb;
        $sources = self::detect_wallet_sources();
        $selected = null;

        foreach ($sources as $source) {
            if ($source['id'] === $source_id) {
                $selected = $source;
                break;
            }
        }

        if (!$selected) {
            return array('updated' => 0, 'total' => 0);
        }

        $rows = array();
        if ($selected['type'] === 'meta') {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id, meta_value AS balance FROM {$wpdb->usermeta} WHERE meta_key = %s",
                $selected['meta_key']
            ));
        } elseif ($selected['type'] === 'table') {
            $table = $selected['table'];
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                return array('updated' => 0, 'total' => 0);
            }
            $columns = $wpdb->get_col($wpdb->prepare(
                'SHOW COLUMNS FROM `%s`',
                $table
            ));
            $user_column = preg_replace('/[^A-Za-z0-9_]/', '', $selected['user_column']);
            $balance_column = preg_replace('/[^A-Za-z0-9_]/', '', $selected['balance_column']);
            if (!in_array($user_column, $columns, true) || !in_array($balance_column, $columns, true)) {
                return array('updated' => 0, 'total' => 0);
            }
            $rows = $wpdb->get_results($wpdb->prepare(
                'SELECT `%1$s` AS user_id, `%2$s` AS balance FROM `%3$s`',
                $user_column,
                $balance_column,
                $table
            ));
        }

        $total = 0;
        $updated = 0;
        foreach ($rows as $row) {
            $user_id = (int) $row->user_id;
            $balance = is_numeric($row->balance) ? (float) $row->balance : 0.0;
            if ($user_id <= 0) {
                continue;
            }

            $total++;
            if (self::set_wallet_balance($user_id, $balance)) {
                $updated++;
            }
        }

        return array('updated' => $updated, 'total' => $total);
    }

    private static function calculate_wallet_balance_from_transactions($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gsp_transactions';

        $conversion_like = $wpdb->esc_like('conversion_') . '%';
        $wallet_balance = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CASE
                WHEN type IN ('deposit', 'transfer_in') THEN amount
                WHEN type IN ('withdrawal', 'transfer_out') THEN -amount
                WHEN type LIKE %s THEN -amount
                ELSE 0
            END)
            FROM {$table_name}
            WHERE user_id = %d AND status = %s",
            $conversion_like,
            $user_id,
            'approved'
        ));

        if ($wallet_balance === null) {
            return 0.0;
        }

        return (float) $wallet_balance;
    }
    
    /**
     * Update user wallet balance
     */
    public static function update_wallet_balance($user_id, $amount, $operation = 'add') {
        global $wpdb;
        $table = $wpdb->prefix . 'gsp_user_balances';
        
        // Ensure user has a balance record
        self::get_balance($user_id);
        
        if ($operation === 'add') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET wallet_balance = wallet_balance + %f WHERE user_id = %d",
                $amount,
                $user_id
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET wallet_balance = wallet_balance - %f WHERE user_id = %d",
                $amount,
                $user_id
            ));
        }
        
        return true;
    }
    
    /**
     * Update user savings balance
     */
    public static function update_savings_balance($user_id, $amount, $operation = 'add') {
        global $wpdb;
        $table = $wpdb->prefix . 'gsp_user_balances';
        
        // Ensure user has a balance record
        self::get_balance($user_id);
        
        if ($operation === 'add') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET savings_balance = savings_balance + %f WHERE user_id = %d",
                $amount,
                $user_id
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET savings_balance = savings_balance - %f WHERE user_id = %d",
                $amount,
                $user_id
            ));
        }
        
        return true;
    }
    
    /**
     * Set exact wallet balance
     */
    public static function set_wallet_balance($user_id, $amount) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsp_user_balances';
        
        // Ensure user has a balance record
        self::get_balance($user_id);
        
        $wpdb->update(
            $table,
            array('wallet_balance' => $amount),
            array('user_id' => $user_id)
        );
        
        return true;
    }
    
    /**
     * Get user by email
     */
    public static function get_user_by_email($email) {
        return get_user_by('email', $email);
    }
    
    /**
     * Get user by username
     */
    public static function get_user_by_username($username) {
        return get_user_by('login', $username);
    }
    
    /**
     * Check if user can withdraw amount
     */
    public static function can_withdraw($user_id, $amount) {
        $balance = self::get_balance($user_id);
        return $balance->wallet_balance >= $amount;
    }
    
    /**
     * Check if user can transfer amount
     */
    public static function can_transfer($user_id, $amount) {
        $balance = self::get_balance($user_id);
        return $balance->wallet_balance >= $amount;
    }
    
    /**
     * Get user avatar URL
     */
    public static function get_avatar_url($user_id, $size = 96) {
        return get_avatar_url($user_id, array('size' => $size));
    }
    
    /**
     * Get user display name
     */
    public static function get_display_name($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            return $user->display_name ? $user->display_name : $user->user_login;
        }
        return '';
    }
}
