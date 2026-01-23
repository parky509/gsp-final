<?php
/**
 * Database handler for GlobalSwiftPay Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSP_Database {
    
    /**
     * Create all necessary database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Transactions table
        $table_transactions = $wpdb->prefix . 'gsp_transactions';
        $sql_transactions = "CREATE TABLE $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            details longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";
        
        // User balances table
        $table_balances = $wpdb->prefix . 'gsp_user_balances';
        $sql_balances = "CREATE TABLE $table_balances (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL UNIQUE,
            wallet_balance decimal(20,8) DEFAULT 0,
            savings_balance decimal(20,8) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Deposit requests table
        $table_deposits = $wpdb->prefix . 'gsp_deposits';
        $sql_deposits = "CREATE TABLE $table_deposits (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            amount decimal(20,8) NOT NULL,
            receipt_path varchar(500),
            sender_name varchar(255),
            status varchar(20) DEFAULT 'pending',
            admin_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Withdrawal requests table
        $table_withdrawals = $wpdb->prefix . 'gsp_withdrawals';
        $sql_withdrawals = "CREATE TABLE $table_withdrawals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(20,8) NOT NULL,
            withdrawal_method varchar(50) NOT NULL,
            details longtext,
            status varchar(20) DEFAULT 'pending',
            admin_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Transfer requests table
        $table_transfers = $wpdb->prefix . 'gsp_transfers';
        $sql_transfers = "CREATE TABLE $table_transfers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            from_user_id bigint(20) NOT NULL,
            to_user_id bigint(20) NOT NULL,
            amount decimal(20,8) NOT NULL,
            token_code varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            admin_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY from_user_id (from_user_id),
            KEY to_user_id (to_user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Conversion requests table
        $table_conversions = $wpdb->prefix . 'gsp_conversions';
        $sql_conversions = "CREATE TABLE $table_conversions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            email varchar(255) NOT NULL,
            conversion_type varchar(50) NOT NULL,
            amount decimal(20,8) NOT NULL,
            destination_address text,
            bank_details longtext,
            security_phrase varchar(255),
            status varchar(20) DEFAULT 'pending',
            admin_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY conversion_type (conversion_type),
            KEY status (status)
        ) $charset_collate;";
        
        // Settings table
        $table_settings = $wpdb->prefix . 'gsp_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL UNIQUE,
            setting_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_transactions);
        dbDelta($sql_balances);
        dbDelta($sql_deposits);
        dbDelta($sql_withdrawals);
        dbDelta($sql_transfers);
        dbDelta($sql_conversions);
        dbDelta($sql_settings);
        
        // Insert default settings
        self::insert_default_settings();
    }
    
    /**
     * Insert default settings
     * Note: Financial details are left empty for admin to configure securely
     */
    private static function insert_default_settings() {
        $defaults = array(
            'account_number' => '',
            'account_name' => '',
            'bank_name' => '',
            'btc_address' => '',
            'usdt_address' => '',
            'company_email' => ''
        );
        
        foreach ($defaults as $key => $value) {
            // Only insert if setting doesn't already exist
            if (self::get_setting($key) === null) {
                self::set_setting($key, $value);
            }
        }
    }
    
    /**
     * Get a setting value
     */
    public static function get_setting($key) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsp_settings';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_key = %s",
            $key
        ));
        
        return $value;
    }
    
    /**
     * Set a setting value
     */
    public static function set_setting($key, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsp_settings';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE setting_key = %s",
            $key
        ));
        
        if ($existing) {
            $wpdb->update(
                $table,
                array('setting_value' => $value),
                array('setting_key' => $key)
            );
        } else {
            $wpdb->insert(
                $table,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value
                )
            );
        }
    }
    
    /**
     * Get all settings
     */
    public static function get_all_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'gsp_settings';
        
        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM $table", ARRAY_A);
        
        $settings = array();
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
}
