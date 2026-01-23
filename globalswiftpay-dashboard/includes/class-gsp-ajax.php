<?php
/**
 * AJAX handler for GlobalSwiftPay Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSP_Ajax {
    
    public function __construct() {
        // User AJAX actions
        add_action('wp_ajax_gsp_submit_deposit', array($this, 'submit_deposit'));
        add_action('wp_ajax_gsp_submit_add_balance', array($this, 'submit_add_balance'));
        add_action('wp_ajax_gsp_submit_withdrawal', array($this, 'submit_withdrawal'));
        add_action('wp_ajax_gsp_submit_transfer', array($this, 'submit_transfer'));
        add_action('wp_ajax_gsp_submit_conversion', array($this, 'submit_conversion'));
        add_action('wp_ajax_gsp_get_transactions', array($this, 'get_transactions'));
        add_action('wp_ajax_gsp_get_balance', array($this, 'get_balance'));
        
        // Admin AJAX actions
        add_action('wp_ajax_gsp_admin_update_deposit', array($this, 'admin_update_deposit'));
        add_action('wp_ajax_gsp_admin_update_withdrawal', array($this, 'admin_update_withdrawal'));
        add_action('wp_ajax_gsp_admin_update_transfer', array($this, 'admin_update_transfer'));
        add_action('wp_ajax_gsp_admin_update_conversion', array($this, 'admin_update_conversion'));
        add_action('wp_ajax_gsp_admin_update_settings', array($this, 'admin_update_settings'));
        add_action('wp_ajax_gsp_admin_update_user_balance', array($this, 'admin_update_user_balance'));
        add_action('wp_ajax_gsp_admin_detect_wallet_sources', array($this, 'admin_detect_wallet_sources'));
        add_action('wp_ajax_gsp_admin_migrate_wallet_balances', array($this, 'admin_migrate_wallet_balances'));
        add_action('wp_ajax_gsp_admin_migrate_all_wallet_sources', array($this, 'admin_migrate_all_wallet_sources'));
    }
    
    /**
     * Verify nonce
     */
    private function verify_nonce($nonce_field = 'nonce') {
        if (!isset($_POST[$nonce_field]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_field])), 'gsp_nonce')) {
            wp_send_json_error(array('message' => 'Security verification failed.'));
        }
    }
    
    /**
     * Verify admin nonce
     */
    private function verify_admin_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'gsp_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security verification failed.'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
    }
    
    /**
     * Submit deposit request
     */
    public function submit_deposit() {
        $this->verify_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to continue.'));
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        if (empty($name) || empty($email) || $amount <= 0) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        $deposit_id = GSP_Transactions::create_deposit($user_id, array(
            'name' => $name,
            'email' => $email,
            'amount' => $amount
        ));
        
        if ($deposit_id) {
            // Notify admin
            GSP_Email::notify_admin('deposit', array(
                'user' => $name,
                'email' => $email,
                'amount' => '$' . number_format($amount, 2)
            ));
            
            wp_send_json_success(array('message' => 'Deposit request submitted successfully. Awaiting approval.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit deposit request. Please try again.'));
        }
    }
    
    /**
     * Submit add balance (with receipt upload)
     */
    public function submit_add_balance() {
        $this->verify_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to continue.'));
        }
        
        $sender_name = isset($_POST['sender_name']) ? sanitize_text_field(wp_unslash($_POST['sender_name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        
        if (empty($sender_name) || empty($email) || $amount <= 0) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        // Handle file upload with enhanced security
        $receipt_path = '';
        if (!empty($_FILES['receipt']) && isset($_FILES['receipt']['tmp_name']) && $_FILES['receipt']['tmp_name'] !== '') {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            
            // Maximum file size: 5MB
            $max_file_size = 5 * 1024 * 1024;
            if (isset($_FILES['receipt']['size']) && $_FILES['receipt']['size'] > $max_file_size) {
                wp_send_json_error(array('message' => 'File size exceeds maximum limit of 5MB.'));
            }
            
            // Validate file extension using wp_check_filetype
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');
            $file_name = isset($_FILES['receipt']['name']) ? sanitize_file_name($_FILES['receipt']['name']) : '';
            $file_info = wp_check_filetype($file_name, array(
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'pdf' => 'application/pdf'
            ));
            
            if (!$file_info['ext'] || !in_array(strtolower($file_info['ext']), $allowed_extensions, true)) {
                wp_send_json_error(array('message' => 'Invalid file type. Only PDF, PNG, and JPEG are allowed.'));
            }
            
            $upload = wp_handle_upload($_FILES['receipt'], array(
                'test_form' => false,
                'mimes' => array(
                    'jpg|jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'pdf' => 'application/pdf'
                )
            ));
            
            if (isset($upload['error'])) {
                wp_send_json_error(array('message' => $upload['error']));
            }
            
            $receipt_path = $upload['url'];
        }
        
        $deposit_id = GSP_Transactions::create_deposit($user_id, array(
            'name' => $sender_name,
            'email' => $email,
            'amount' => $amount,
            'receipt_path' => $receipt_path,
            'sender_name' => $sender_name
        ));
        
        if ($deposit_id) {
            // Notify admin
            GSP_Email::notify_admin('deposit', array(
                'sender' => $sender_name,
                'email' => $email,
                'amount' => '$' . number_format($amount, 2),
                'receipt' => $receipt_path ? 'Uploaded' : 'Not provided'
            ));
            
            wp_send_json_success(array('message' => 'Balance request submitted successfully. Awaiting admin approval.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit request. Please try again.'));
        }
    }
    
    /**
     * Submit withdrawal request
     */
    public function submit_withdrawal() {
        $this->verify_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to continue.'));
        }
        
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $method = isset($_POST['method']) ? sanitize_text_field(wp_unslash($_POST['method'])) : '';
        $details = isset($_POST['details']) ? array_map('sanitize_text_field', wp_unslash($_POST['details'])) : array();
        
        if ($amount <= 0 || empty($method)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        // Check if user has enough balance
        if (!GSP_User::can_withdraw($user_id, $amount)) {
            wp_send_json_error(array('message' => 'Insufficient balance for this withdrawal.'));
        }
        
        $withdrawal_id = GSP_Transactions::create_withdrawal($user_id, $amount, $method, $details);
        
        if ($withdrawal_id) {
            $user = get_userdata($user_id);
            
            // Notify admin
            GSP_Email::notify_admin('withdrawal', array(
                'user' => $user->display_name,
                'email' => $user->user_email,
                'amount' => '$' . number_format($amount, 2),
                'method' => $method
            ));
            
            wp_send_json_success(array('message' => 'Withdrawal request submitted successfully. Awaiting admin approval.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit withdrawal request. Please try again.'));
        }
    }
    
    /**
     * Submit transfer request
     */
    public function submit_transfer() {
        $this->verify_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to continue.'));
        }
        
        $recipient_email = isset($_POST['recipient_email']) ? sanitize_email(wp_unslash($_POST['recipient_email'])) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $token_code = isset($_POST['token_code']) ? sanitize_text_field(wp_unslash($_POST['token_code'])) : '';
        
        if (empty($recipient_email) || $amount <= 0 || empty($token_code)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        // Find recipient user
        $recipient = get_user_by('email', $recipient_email);
        if (!$recipient) {
            wp_send_json_error(array('message' => 'Recipient user not found.'));
        }
        
        if ($recipient->ID === $user_id) {
            wp_send_json_error(array('message' => 'You cannot transfer to yourself.'));
        }
        
        // Check if user has enough balance
        if (!GSP_User::can_transfer($user_id, $amount)) {
            wp_send_json_error(array('message' => 'Insufficient balance for this transfer.'));
        }
        
        $transfer_id = GSP_Transactions::create_transfer($user_id, $recipient->ID, $amount, $token_code);
        
        if ($transfer_id) {
            $user = get_userdata($user_id);
            
            // Notify admin
            GSP_Email::notify_admin('transfer', array(
                'from' => $user->display_name . ' (' . $user->user_email . ')',
                'to' => $recipient->display_name . ' (' . $recipient_email . ')',
                'amount' => '$' . number_format($amount, 2),
                'token_code' => $token_code
            ));
            
            wp_send_json_success(array('message' => 'Transfer request submitted successfully. Awaiting admin approval.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit transfer request. Please try again.'));
        }
    }
    
    /**
     * Submit conversion request
     */
    public function submit_conversion() {
        $this->verify_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to continue.'));
        }
        
        $conversion_type = isset($_POST['conversion_type']) ? sanitize_text_field(wp_unslash($_POST['conversion_type'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $security_phrase = isset($_POST['security_phrase']) ? sanitize_text_field(wp_unslash($_POST['security_phrase'])) : '';
        
        if (empty($conversion_type) || empty($email) || $amount <= 0 || empty($security_phrase)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }
        
        // Check if user has enough balance
        if (!GSP_User::can_withdraw($user_id, $amount)) {
            wp_send_json_error(array('message' => 'Insufficient balance for this conversion.'));
        }
        
        $data = array(
            'conversion_type' => $conversion_type,
            'email' => $email,
            'amount' => $amount,
            'security_phrase' => $security_phrase
        );
        
        // Handle different conversion types
        if ($conversion_type === 'btc') {
            $btc_address = isset($_POST['btc_address']) ? sanitize_text_field(wp_unslash($_POST['btc_address'])) : '';
            if (empty($btc_address)) {
                wp_send_json_error(array('message' => 'Bitcoin address is required.'));
            }
            $data['destination_address'] = $btc_address;
        } elseif ($conversion_type === 'usdt') {
            $usdt_address = isset($_POST['usdt_address']) ? sanitize_text_field(wp_unslash($_POST['usdt_address'])) : '';
            if (empty($usdt_address)) {
                wp_send_json_error(array('message' => 'USDT address is required.'));
            }
            $data['destination_address'] = $usdt_address;
        } elseif ($conversion_type === 'bank') {
            $bank_details = array(
                'bank_name' => isset($_POST['bank_name']) ? sanitize_text_field(wp_unslash($_POST['bank_name'])) : '',
                'account_name' => isset($_POST['account_name']) ? sanitize_text_field(wp_unslash($_POST['account_name'])) : '',
                'account_number' => isset($_POST['account_number']) ? sanitize_text_field(wp_unslash($_POST['account_number'])) : '',
                'swift_code' => isset($_POST['swift_code']) ? sanitize_text_field(wp_unslash($_POST['swift_code'])) : '',
                'bank_address' => isset($_POST['bank_address']) ? sanitize_text_field(wp_unslash($_POST['bank_address'])) : '',
                'country' => isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : ''
            );
            
            foreach ($bank_details as $key => $value) {
                if (empty($value)) {
                    wp_send_json_error(array('message' => 'All bank details are required.'));
                }
            }
            
            $data['bank_details'] = $bank_details;
        }
        
        $conversion_id = GSP_Transactions::create_conversion($user_id, $data);
        
        if ($conversion_id) {
            $user = get_userdata($user_id);
            
            // Notify admin
            GSP_Email::notify_admin('conversion', array(
                'user' => $user->display_name,
                'email' => $email,
                'amount' => '$' . number_format($amount, 2),
                'type' => strtoupper($conversion_type)
            ));
            
            wp_send_json_success(array('message' => 'Conversion request submitted successfully. Awaiting admin approval.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit conversion request. Please try again.'));
        }
    }
    
    /**
     * Get user transactions
     */
    public function get_transactions() {
        $this->verify_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to continue.'));
        }

        nocache_headers();
        
        $transactions = GSP_Transactions::get_user_transactions($user_id);
        
        wp_send_json_success(array('transactions' => $transactions));
    }
    
    /**
     * Get user balance
     */
    public function get_balance() {
        $this->verify_nonce();
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Please log in to continue.'));
        }

        nocache_headers();
        
        $balance = GSP_User::get_balance($user_id);
        
        wp_send_json_success(array(
            'wallet_balance' => number_format($balance->wallet_balance, 2),
            'savings_balance' => number_format($balance->savings_balance, 2)
        ));
    }
    
    /**
     * Admin: Update deposit status
     */
    public function admin_update_deposit() {
        $this->verify_admin_nonce();
        
        $deposit_id = isset($_POST['deposit_id']) ? intval($_POST['deposit_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
        
        if (!$deposit_id || !in_array($status, array('approved', 'declined'), true)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }
        
        $result = GSP_Transactions::update_deposit_status($deposit_id, $status, $notes);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Deposit status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update deposit status.'));
        }
    }
    
    /**
     * Admin: Update withdrawal status
     */
    public function admin_update_withdrawal() {
        $this->verify_admin_nonce();
        
        $withdrawal_id = isset($_POST['withdrawal_id']) ? intval($_POST['withdrawal_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
        
        if (!$withdrawal_id || !in_array($status, array('approved', 'declined'), true)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }
        
        $result = GSP_Transactions::update_withdrawal_status($withdrawal_id, $status, $notes);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Withdrawal status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update withdrawal status.'));
        }
    }
    
    /**
     * Admin: Update transfer status
     */
    public function admin_update_transfer() {
        $this->verify_admin_nonce();
        
        $transfer_id = isset($_POST['transfer_id']) ? intval($_POST['transfer_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
        
        if (!$transfer_id || !in_array($status, array('approved', 'declined'), true)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }
        
        $result = GSP_Transactions::update_transfer_status($transfer_id, $status, $notes);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Transfer status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update transfer status.'));
        }
    }
    
    /**
     * Admin: Update conversion status
     */
    public function admin_update_conversion() {
        $this->verify_admin_nonce();
        
        $conversion_id = isset($_POST['conversion_id']) ? intval($_POST['conversion_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
        
        if (!$conversion_id || !in_array($status, array('approved', 'declined'), true)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }
        
        $result = GSP_Transactions::update_conversion_status($conversion_id, $status, $notes);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Conversion status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update conversion status.'));
        }
    }
    
    /**
     * Admin: Update settings
     */
    public function admin_update_settings() {
        $this->verify_admin_nonce();
        
        $settings = isset($_POST['settings']) ? array_map('sanitize_text_field', wp_unslash($_POST['settings'])) : array();
        
        foreach ($settings as $key => $value) {
            GSP_Database::set_setting($key, $value);
        }
        
        wp_send_json_success(array('message' => 'Settings updated successfully.'));
    }
    
    /**
     * Admin: Update user balance
     */
    public function admin_update_user_balance() {
        $this->verify_admin_nonce();
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $balance = isset($_POST['balance']) ? floatval($_POST['balance']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Invalid user.'));
        }
        
        GSP_User::set_wallet_balance($user_id, $balance);
        
        wp_send_json_success(array('message' => 'User balance updated successfully.'));
    }

    /**
     * Admin: Detect wallet sources from other plugins
     */
    public function admin_detect_wallet_sources() {
        $this->verify_admin_nonce();

        $sources = GSP_User::detect_wallet_sources();

        wp_send_json_success(array('sources' => $sources));
    }

    /**
     * Admin: Migrate wallet balances from selected source
     */
    public function admin_migrate_wallet_balances() {
        $this->verify_admin_nonce();

        $source_id = isset($_POST['source_id']) ? sanitize_text_field(wp_unslash($_POST['source_id'])) : '';
        if (empty($source_id)) {
            wp_send_json_error(array('message' => 'Select a source to migrate.'));
        }

        $result = GSP_User::migrate_wallet_balances($source_id);

        wp_send_json_success(array(
            'message' => sprintf(
                'Wallet migration completed. Updated %1$d of %2$d records.',
                (int) $result['updated'],
                (int) $result['total']
            )
        ));
    }

    /**
     * Admin: Migrate balances from all detected sources
     */
    public function admin_migrate_all_wallet_sources() {
        $this->verify_admin_nonce();

        $sources = GSP_User::detect_wallet_sources();
        if (empty($sources)) {
            wp_send_json_error(array('message' => 'No wallet sources detected.'));
        }

        $total_updated = 0;
        $total_records = 0;
        $failed_sources = array();
        foreach ($sources as $source) {
            try {
                $result = GSP_User::migrate_wallet_balances($source['id']);
                $total_updated += (int) $result['updated'];
                $total_records += (int) $result['total'];
            } catch (Exception $e) {
                error_log('Wallet migration failed for a source: ' . $e->getMessage());
                $failed_sources[] = isset($source['label']) ? $source['label'] : $source['id'];
            }
        }

        if (!empty($failed_sources)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    'Migration completed with errors. Updated %1$d of %2$d records. Failed sources: %3$s',
                    $total_updated,
                    $total_records,
                    implode(', ', $failed_sources)
                )
            ));
        }

        wp_send_json_success(array(
            'message' => sprintf(
                'Wallet migration completed. Updated %1$d of %2$d records.',
                $total_updated,
                $total_records
            )
        ));
    }
}
