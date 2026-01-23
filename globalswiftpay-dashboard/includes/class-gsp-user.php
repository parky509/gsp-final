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
    private const TABLE_NAME_REGEX = '/^[A-Za-z0-9_]+$/';
    private const TABLE_EXCLUDE_REGEX = '/^(wp_|wpqj_)(posts|postmeta|terms|term_taxonomy|term_relationships|users|usermeta|options|comments|commentmeta|links|site|sitemeta|actionscheduler_.*|woocommerce_.*|wc_.*)$/';
    private static $wallet_id_tables = null;
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
    private const WALLET_CREDIT_TYPES = array('credit', 'deposit', 'add', 'added', 'topup');
    private const WALLET_DEBIT_TYPES = array('debit', 'withdraw', 'deduct', 'spent');
    
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
            $prefix . 'woo_wallet_balance',
            $prefix . 'wps_wsfw_wallet_transaction'
        );
        $wallet_tables = $wpdb->get_col($wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $wpdb->esc_like($prefix) . '%wallet%'
        ));
        /**
         * Filter whether to enable full database table scanning for wallet balances.
         *
         * @param bool $full_scan_enabled Enable full scan of all tables.
         */
        $full_scan_enabled = apply_filters('gsp_wallet_migration_full_scan', true);
        if ($full_scan_enabled) {
            $all_tables = $wpdb->get_col('SHOW TABLES');
            $table_candidates = array_unique(array_merge($table_candidates, $wallet_tables, $all_tables));
        } else {
            $table_candidates = array_unique(array_merge($table_candidates, $wallet_tables));
        }
        $user_columns = array('user_id', 'customer_id', 'userid');
        $balance_columns = array('wallet_balance', 'balance', 'total_balance', 'amount');
        $table_candidates = array_values(array_filter($table_candidates, function($table) {
            return self::is_allowed_wallet_table($table);
        }));

        foreach ($table_candidates as $table) {
            $table_name = $table;
            $table_exists = $wpdb->get_var($wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($table_name)
            ));
            if (!$table_exists) {
                continue;
            }

            $columns = $wpdb->get_col(sprintf('SHOW COLUMNS FROM `%s`', esc_sql($table_name)));
            $columns_map = array();
            foreach ($columns as $column) {
                $columns_map[strtolower($column)] = $column;
            }
            $has_transaction_type = isset($columns_map['transaction_type']) || isset($columns_map['transaction_type_1']);

            $user_column = '';
            foreach ($user_columns as $candidate) {
                if (isset($columns_map[$candidate])) {
                    $user_column = $columns_map[$candidate];
                    break;
                }
            }
            if ($user_column === '') {
                continue;
            }

            if (!$has_transaction_type) {
                foreach ($balance_columns as $column) {
                    if (isset($columns_map[$column])) {
                        $sources[] = array(
                            'id' => sprintf('table|%s|%s|%s', $table_name, $user_column, $columns_map[$column]),
                            'type' => 'table',
                            'label' => sprintf(__('Table %1$s (%2$s)', 'globalswiftpay-dashboard'), $table_name, $column),
                            'table' => $table_name,
                            'user_column' => $user_column,
                            'balance_column' => $columns_map[$column]
                        );
                        break;
                    }
                }
            }

            if (isset($columns_map['amount'])) {
                $transaction_column = '';
                if (isset($columns_map['transaction_type'])) {
                    $transaction_column = $columns_map['transaction_type'];
                } elseif (isset($columns_map['transaction_type_1'])) {
                    $transaction_column = $columns_map['transaction_type_1'];
                }
                $sources[] = array(
                    'id' => sprintf('transactions|%s|%s|amount', $table_name, $user_column),
                    'type' => 'transactions',
                    'label' => sprintf(__('Table %1$s (transaction sum)', 'globalswiftpay-dashboard'), $table_name),
                    'table' => $table_name,
                    'user_column' => $user_column,
                    'balance_column' => $columns_map['amount'],
                    'transaction_type_column' => $transaction_column
                );
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
        } elseif ($selected['type'] === 'table' || $selected['type'] === 'transactions') {
            $table = $selected['table'];
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                return array('updated' => 0, 'total' => 0);
            }
            $columns = $wpdb->get_col(sprintf('SHOW COLUMNS FROM `%s`', esc_sql($table)));
            $columns_map = array();
            foreach ($columns as $column) {
                $columns_map[strtolower($column)] = $column;
            }
            $user_column = preg_replace('/[^A-Za-z0-9_]/', '', $selected['user_column']);
            $balance_column = preg_replace('/[^A-Za-z0-9_]/', '', $selected['balance_column']);
            $user_column_key = strtolower($user_column);
            $balance_column_key = strtolower($balance_column);
            if (isset($columns_map[$user_column_key])) {
                $user_column = $columns_map[$user_column_key];
            }
            if (isset($columns_map[$balance_column_key])) {
                $balance_column = $columns_map[$balance_column_key];
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $user_column) || !preg_match('/^[A-Za-z0-9_]+$/', $balance_column)) {
                return array('updated' => 0, 'total' => 0);
            }
            if (!in_array($user_column, $columns, true) || !in_array($balance_column, $columns, true)) {
                return array('updated' => 0, 'total' => 0);
            }
            $table_safe = esc_sql($table);
            if ($selected['type'] === 'transactions') {
                $transaction_column = '';
                if (!empty($selected['transaction_type_column'])) {
                    $transaction_column = preg_replace('/[^A-Za-z0-9_]/', '', $selected['transaction_type_column']);
                }

                if ($transaction_column && !in_array($transaction_column, $columns, true)) {
                    $transaction_column = '';
                }

                if ($transaction_column) {
                    if (!preg_match('/^[A-Za-z0-9_]+$/', $transaction_column)) {
                        $transaction_column = '';
                    }
                    $credit_types = array_map('sanitize_text_field', self::WALLET_CREDIT_TYPES);
                    $debit_types = array_map('sanitize_text_field', self::WALLET_DEBIT_TYPES);
                    $credit_placeholders = implode(',', array_fill(0, count($credit_types), '%s'));
                    $debit_placeholders = implode(',', array_fill(0, count($debit_types), '%s'));
                    $query = sprintf(
                        'SELECT `%1$s` AS user_id, SUM(CASE WHEN `%2$s` IN (%3$s) THEN `%4$s` WHEN `%2$s` IN (%5$s) THEN -`%4$s` ELSE `%4$s` END) AS balance FROM `%6$s` GROUP BY `%1$s`',
                        $user_column,
                        $transaction_column,
                        $credit_placeholders,
                        $balance_column,
                        $debit_placeholders,
                        $table_safe
                    );
                    $rows = $wpdb->get_results($wpdb->prepare($query, array_merge($credit_types, $debit_types)));
                } else {
                    $query = sprintf(
                        'SELECT `%1$s` AS user_id, SUM(`%2$s`) AS balance FROM `%3$s` GROUP BY `%1$s`',
                        $user_column,
                        $balance_column,
                        $table_safe
                    );
                    $rows = $wpdb->get_results($query);
                }
            } else {
                $rows = $wpdb->get_results($wpdb->prepare(
                    'SELECT `%1$s` AS user_id, `%2$s` AS balance FROM `%3$s`',
                    $user_column,
                    $balance_column,
                    $table_safe
                ));
            }
        }

        $total = 0;
        $updated = 0;
        foreach ($rows as $row) {
            $user_id = (int) $row->user_id;
            $balance = null;
            if ($selected['type'] === 'meta') {
                $balance = self::resolve_wallet_balance_from_meta($selected['meta_key'], $row->balance, $user_id);
            } else {
                $balance = is_numeric($row->balance) ? (float) $row->balance : null;
            }
            if ($user_id <= 0) {
                continue;
            }

            if ($balance === null) {
                continue;
            }

            $total++;
            if (self::set_wallet_balance($user_id, $balance)) {
                $updated++;
            }
        }

        return array('updated' => $updated, 'total' => $total);
    }

    private static function resolve_wallet_balance_from_meta($meta_key, $meta_value, $user_id) {
        $raw_value = maybe_unserialize($meta_value);
        $numeric_value = is_numeric($raw_value) ? (int) $raw_value : null;
        $meta_key = strtolower((string) $meta_key);
        $is_wallet_id_key = (strpos($meta_key, 'wallet_id') !== false || $meta_key === 'wps_wallet');

        if ($numeric_value !== null && $is_wallet_id_key) {
            $balance = self::resolve_wallet_balance_from_id($numeric_value, $user_id);
            if ($balance !== null) {
                return $balance;
            }
        }

        $balance = self::normalize_wallet_balance_value($raw_value);
        if ($balance !== null) {
            return $balance;
        }

        if ($numeric_value !== null) {
            $balance = self::resolve_wallet_balance_from_id($numeric_value, $user_id);
            if ($balance !== null) {
                return $balance;
            }
        }

        return null;
    }

    private static function normalize_wallet_balance_value($value) {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value)) {
            $keys = array('wallet_balance', 'balance', 'amount', 'wallet');
            foreach ($keys as $key) {
                if (isset($value[$key]) && is_numeric($value[$key])) {
                    return (float) $value[$key];
                }
            }
        }

        return null;
    }

    private static function resolve_wallet_balance_from_id($wallet_id, $user_id = null) {
        global $wpdb;

        $tables = self::get_wallet_id_tables();
        foreach ($tables as $table_info) {
            $table_name = esc_sql($table_info['table']);
            $id_column = preg_replace('/[^A-Za-z0-9_]/', '', $table_info['id_column']);
            $balance_column = preg_replace('/[^A-Za-z0-9_]/', '', $table_info['balance_column']);
            $user_column = isset($table_info['user_column'])
                ? preg_replace('/[^A-Za-z0-9_]/', '', $table_info['user_column'])
                : '';
            if ($user_id && $user_column !== '') {
                $balance = $wpdb->get_var($wpdb->prepare(
                    "SELECT `{$balance_column}` FROM `{$table_name}` WHERE `{$id_column}` = %d AND `{$user_column}` = %d",
                    $wallet_id,
                    $user_id
                ));
            } else {
                $balance = $wpdb->get_var($wpdb->prepare(
                    "SELECT `{$balance_column}` FROM `{$table_name}` WHERE `{$id_column}` = %d",
                    $wallet_id
                ));
            }
            if ($balance !== null && is_numeric($balance)) {
                return (float) $balance;
            }
        }

        return null;
    }

    private static function get_wallet_id_tables() {
        if (self::$wallet_id_tables !== null) {
            return self::$wallet_id_tables;
        }

        global $wpdb;
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
        self::$wallet_id_tables = array();

        $id_columns = array('id', 'wallet_id');
        $user_columns = array('user_id', 'customer_id', 'userid');
        $balance_columns = array('wallet_balance', 'balance', 'total_balance', 'amount');

        foreach ($table_candidates as $table) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                continue;
            }
            $columns = $wpdb->get_col(sprintf('SHOW COLUMNS FROM `%s`', esc_sql($table)));
            $id_column = '';
            $balance_column = '';
            $user_column = '';

            foreach ($id_columns as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    $id_column = $candidate;
                    break;
                }
            }
            if (!$id_column) {
                continue;
            }

            foreach ($user_columns as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    $user_column = $candidate;
                    break;
                }
            }

            foreach ($balance_columns as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    $balance_column = $candidate;
                    break;
                }
            }
            if (!$balance_column) {
                continue;
            }

            self::$wallet_id_tables[] = array(
                'table' => $table,
                'id_column' => $id_column,
                'balance_column' => $balance_column,
                'user_column' => $user_column
            );
        }

        return self::$wallet_id_tables;
    }

    private static function is_allowed_wallet_table($table) {
        if (!preg_match(self::TABLE_NAME_REGEX, $table)) {
            return false;
        }

        return !preg_match(self::TABLE_EXCLUDE_REGEX, $table);
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
