<?php
/**
 * User handler for GlobalSwiftPay Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSP_User {
    
    /**
     * Get user balance
     */
    public static function get_balance($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gsp_user_balances';
        
        $balance = $wpdb->get_row($wpdb->prepare(
            "SELECT wallet_balance, savings_balance FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        if (!$balance) {
            // Create initial balance record
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'wallet_balance' => 0,
                'savings_balance' => 0
            ));
            
            return (object) array(
                'wallet_balance' => 0,
                'savings_balance' => 0
            );
        }
        
        return $balance;
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
