<?php
/**
 * Admin handler for GlobalSwiftPay Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSP_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('GlobalSwiftPay', 'globalswiftpay-dashboard'),
            __('GlobalSwiftPay', 'globalswiftpay-dashboard'),
            'manage_options',
            'globalswiftpay',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-area',
            30
        );
        
        // Deposits submenu
        add_submenu_page(
            'globalswiftpay',
            __('Deposits', 'globalswiftpay-dashboard'),
            __('Deposits', 'globalswiftpay-dashboard'),
            'manage_options',
            'globalswiftpay-deposits',
            array($this, 'render_deposits_page')
        );
        
        // Withdrawals submenu
        add_submenu_page(
            'globalswiftpay',
            __('Withdrawals', 'globalswiftpay-dashboard'),
            __('Withdrawals', 'globalswiftpay-dashboard'),
            'manage_options',
            'globalswiftpay-withdrawals',
            array($this, 'render_withdrawals_page')
        );
        
        // Transfers submenu
        add_submenu_page(
            'globalswiftpay',
            __('Transfers', 'globalswiftpay-dashboard'),
            __('Transfers', 'globalswiftpay-dashboard'),
            'manage_options',
            'globalswiftpay-transfers',
            array($this, 'render_transfers_page')
        );
        
        // Conversions submenu
        add_submenu_page(
            'globalswiftpay',
            __('Conversions', 'globalswiftpay-dashboard'),
            __('Conversions', 'globalswiftpay-dashboard'),
            'manage_options',
            'globalswiftpay-conversions',
            array($this, 'render_conversions_page')
        );
        
        // Users submenu
        add_submenu_page(
            'globalswiftpay',
            __('Users', 'globalswiftpay-dashboard'),
            __('Users', 'globalswiftpay-dashboard'),
            'manage_options',
            'globalswiftpay-users',
            array($this, 'render_users_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'globalswiftpay',
            __('Settings', 'globalswiftpay-dashboard'),
            __('Settings', 'globalswiftpay-dashboard'),
            'manage_options',
            'globalswiftpay-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render admin dashboard page
     */
    public function render_dashboard_page() {
        $pending_deposits = count(GSP_Transactions::get_all_deposits('pending'));
        $pending_withdrawals = count(GSP_Transactions::get_all_withdrawals('pending'));
        $pending_transfers = count(GSP_Transactions::get_all_transfers('pending'));
        $pending_conversions = count(GSP_Transactions::get_all_conversions('pending'));
        
        ?>
        <div class="wrap gsp-admin-wrap">
            <h1><?php esc_html_e('GlobalSwiftPay Dashboard', 'globalswiftpay-dashboard'); ?></h1>
            
            <div class="gsp-admin-dashboard">
                <div class="gsp-admin-cards">
                    <div class="gsp-admin-card">
                        <div class="gsp-admin-card-icon deposits">
                            <span class="dashicons dashicons-download"></span>
                        </div>
                        <div class="gsp-admin-card-content">
                            <h3><?php echo esc_html($pending_deposits); ?></h3>
                            <p><?php esc_html_e('Pending Deposits', 'globalswiftpay-dashboard'); ?></p>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=globalswiftpay-deposits')); ?>" class="gsp-admin-card-link"><?php esc_html_e('View All', 'globalswiftpay-dashboard'); ?></a>
                    </div>
                    
                    <div class="gsp-admin-card">
                        <div class="gsp-admin-card-icon withdrawals">
                            <span class="dashicons dashicons-upload"></span>
                        </div>
                        <div class="gsp-admin-card-content">
                            <h3><?php echo esc_html($pending_withdrawals); ?></h3>
                            <p><?php esc_html_e('Pending Withdrawals', 'globalswiftpay-dashboard'); ?></p>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=globalswiftpay-withdrawals')); ?>" class="gsp-admin-card-link"><?php esc_html_e('View All', 'globalswiftpay-dashboard'); ?></a>
                    </div>
                    
                    <div class="gsp-admin-card">
                        <div class="gsp-admin-card-icon transfers">
                            <span class="dashicons dashicons-randomize"></span>
                        </div>
                        <div class="gsp-admin-card-content">
                            <h3><?php echo esc_html($pending_transfers); ?></h3>
                            <p><?php esc_html_e('Pending Transfers', 'globalswiftpay-dashboard'); ?></p>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=globalswiftpay-transfers')); ?>" class="gsp-admin-card-link"><?php esc_html_e('View All', 'globalswiftpay-dashboard'); ?></a>
                    </div>
                    
                    <div class="gsp-admin-card">
                        <div class="gsp-admin-card-icon conversions">
                            <span class="dashicons dashicons-update"></span>
                        </div>
                        <div class="gsp-admin-card-content">
                            <h3><?php echo esc_html($pending_conversions); ?></h3>
                            <p><?php esc_html_e('Pending Conversions', 'globalswiftpay-dashboard'); ?></p>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=globalswiftpay-conversions')); ?>" class="gsp-admin-card-link"><?php esc_html_e('View All', 'globalswiftpay-dashboard'); ?></a>
                    </div>
                </div>
                
                <div class="gsp-admin-quick-actions">
                    <h2><?php esc_html_e('Quick Actions', 'globalswiftpay-dashboard'); ?></h2>
                    <div class="gsp-admin-actions-grid">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=globalswiftpay-settings')); ?>" class="gsp-admin-action-btn">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php esc_html_e('Update Settings', 'globalswiftpay-dashboard'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=globalswiftpay-users')); ?>" class="gsp-admin-action-btn">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php esc_html_e('Manage Users', 'globalswiftpay-dashboard'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render deposits page
     */
    public function render_deposits_page() {
        $deposits = GSP_Transactions::get_all_deposits();
        ?>
        <div class="wrap gsp-admin-wrap">
            <h1><?php esc_html_e('Deposit Requests', 'globalswiftpay-dashboard'); ?></h1>
            
            <div class="gsp-admin-table-container">
                <table class="gsp-admin-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('User', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Name', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Email', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Amount', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Receipt', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Status', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Date', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Actions', 'globalswiftpay-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deposits)): ?>
                            <tr>
                                <td colspan="9" class="gsp-admin-no-data"><?php esc_html_e('No deposit requests found.', 'globalswiftpay-dashboard'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deposits as $deposit): ?>
                                <tr data-id="<?php echo esc_attr($deposit->id); ?>">
                                    <td><?php echo esc_html($deposit->id); ?></td>
                                    <td><?php echo esc_html($deposit->display_name ?: $deposit->user_login); ?></td>
                                    <td><?php echo esc_html($deposit->name); ?></td>
                                    <td><?php echo esc_html($deposit->email); ?></td>
                                    <td>$<?php echo esc_html(number_format($deposit->amount, 2)); ?></td>
                                    <td>
                                        <?php if ($deposit->receipt_path): ?>
                                            <a href="<?php echo esc_url($deposit->receipt_path); ?>" target="_blank" class="gsp-view-receipt"><?php esc_html_e('View', 'globalswiftpay-dashboard'); ?></a>
                                        <?php else: ?>
                                            <span class="gsp-no-receipt"><?php esc_html_e('None', 'globalswiftpay-dashboard'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="gsp-status gsp-status-<?php echo esc_attr($deposit->status); ?>"><?php echo esc_html(ucfirst($deposit->status)); ?></span></td>
                                    <td><?php echo esc_html(date('M j, Y g:i A', strtotime($deposit->created_at))); ?></td>
                                    <td class="gsp-admin-actions">
                                        <?php if ($deposit->status === 'pending'): ?>
                                            <button class="gsp-admin-btn gsp-btn-approve" data-action="approve" data-type="deposit" data-id="<?php echo esc_attr($deposit->id); ?>"><?php esc_html_e('Approve', 'globalswiftpay-dashboard'); ?></button>
                                            <button class="gsp-admin-btn gsp-btn-decline" data-action="decline" data-type="deposit" data-id="<?php echo esc_attr($deposit->id); ?>"><?php esc_html_e('Decline', 'globalswiftpay-dashboard'); ?></button>
                                        <?php else: ?>
                                            <span class="gsp-action-completed"><?php echo esc_html(ucfirst($deposit->status)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render withdrawals page
     */
    public function render_withdrawals_page() {
        $withdrawals = GSP_Transactions::get_all_withdrawals();
        ?>
        <div class="wrap gsp-admin-wrap">
            <h1><?php esc_html_e('Withdrawal Requests', 'globalswiftpay-dashboard'); ?></h1>
            
            <div class="gsp-admin-table-container">
                <table class="gsp-admin-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('User', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Email', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Amount', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Method', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Details', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Status', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Date', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Actions', 'globalswiftpay-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($withdrawals)): ?>
                            <tr>
                                <td colspan="9" class="gsp-admin-no-data"><?php esc_html_e('No withdrawal requests found.', 'globalswiftpay-dashboard'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($withdrawals as $withdrawal): ?>
                                <?php $details = maybe_unserialize($withdrawal->details); ?>
                                <tr data-id="<?php echo esc_attr($withdrawal->id); ?>">
                                    <td><?php echo esc_html($withdrawal->id); ?></td>
                                    <td><?php echo esc_html($withdrawal->display_name ?: $withdrawal->user_login); ?></td>
                                    <td><?php echo esc_html($withdrawal->user_email); ?></td>
                                    <td>$<?php echo esc_html(number_format($withdrawal->amount, 2)); ?></td>
                                    <td><?php echo esc_html(ucfirst($withdrawal->withdrawal_method)); ?></td>
                                    <td>
                                        <button class="gsp-view-details-btn" data-details="<?php echo esc_attr(wp_json_encode($details)); ?>"><?php esc_html_e('View', 'globalswiftpay-dashboard'); ?></button>
                                    </td>
                                    <td><span class="gsp-status gsp-status-<?php echo esc_attr($withdrawal->status); ?>"><?php echo esc_html(ucfirst($withdrawal->status)); ?></span></td>
                                    <td><?php echo esc_html(date('M j, Y g:i A', strtotime($withdrawal->created_at))); ?></td>
                                    <td class="gsp-admin-actions">
                                        <?php if ($withdrawal->status === 'pending'): ?>
                                            <button class="gsp-admin-btn gsp-btn-approve" data-action="approve" data-type="withdrawal" data-id="<?php echo esc_attr($withdrawal->id); ?>"><?php esc_html_e('Approve', 'globalswiftpay-dashboard'); ?></button>
                                            <button class="gsp-admin-btn gsp-btn-decline" data-action="decline" data-type="withdrawal" data-id="<?php echo esc_attr($withdrawal->id); ?>"><?php esc_html_e('Decline', 'globalswiftpay-dashboard'); ?></button>
                                        <?php else: ?>
                                            <span class="gsp-action-completed"><?php echo esc_html(ucfirst($withdrawal->status)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render transfers page
     */
    public function render_transfers_page() {
        $transfers = GSP_Transactions::get_all_transfers();
        ?>
        <div class="wrap gsp-admin-wrap">
            <h1><?php esc_html_e('Transfer Requests', 'globalswiftpay-dashboard'); ?></h1>
            
            <div class="gsp-admin-table-container">
                <table class="gsp-admin-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('From', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('To', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Amount', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Token Code', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Status', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Date', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Actions', 'globalswiftpay-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transfers)): ?>
                            <tr>
                                <td colspan="8" class="gsp-admin-no-data"><?php esc_html_e('No transfer requests found.', 'globalswiftpay-dashboard'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transfers as $transfer): ?>
                                <tr data-id="<?php echo esc_attr($transfer->id); ?>">
                                    <td><?php echo esc_html($transfer->id); ?></td>
                                    <td><?php echo esc_html($transfer->from_name); ?><br><small><?php echo esc_html($transfer->from_email); ?></small></td>
                                    <td><?php echo esc_html($transfer->to_name); ?><br><small><?php echo esc_html($transfer->to_email); ?></small></td>
                                    <td>$<?php echo esc_html(number_format($transfer->amount, 2)); ?></td>
                                    <td><code><?php echo esc_html($transfer->token_code); ?></code></td>
                                    <td><span class="gsp-status gsp-status-<?php echo esc_attr($transfer->status); ?>"><?php echo esc_html(ucfirst($transfer->status)); ?></span></td>
                                    <td><?php echo esc_html(date('M j, Y g:i A', strtotime($transfer->created_at))); ?></td>
                                    <td class="gsp-admin-actions">
                                        <?php if ($transfer->status === 'pending'): ?>
                                            <button class="gsp-admin-btn gsp-btn-approve" data-action="approve" data-type="transfer" data-id="<?php echo esc_attr($transfer->id); ?>"><?php esc_html_e('Approve', 'globalswiftpay-dashboard'); ?></button>
                                            <button class="gsp-admin-btn gsp-btn-decline" data-action="decline" data-type="transfer" data-id="<?php echo esc_attr($transfer->id); ?>"><?php esc_html_e('Decline', 'globalswiftpay-dashboard'); ?></button>
                                        <?php else: ?>
                                            <span class="gsp-action-completed"><?php echo esc_html(ucfirst($transfer->status)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render conversions page
     */
    public function render_conversions_page() {
        $conversions = GSP_Transactions::get_all_conversions();
        ?>
        <div class="wrap gsp-admin-wrap">
            <h1><?php esc_html_e('Conversion Requests', 'globalswiftpay-dashboard'); ?></h1>
            
            <div class="gsp-admin-table-container">
                <table class="gsp-admin-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('User', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Email', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Type', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Amount', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Details', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Status', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Date', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Actions', 'globalswiftpay-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($conversions)): ?>
                            <tr>
                                <td colspan="9" class="gsp-admin-no-data"><?php esc_html_e('No conversion requests found.', 'globalswiftpay-dashboard'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($conversions as $conversion): ?>
                                <?php 
                                $details = array(
                                    'destination_address' => $conversion->destination_address,
                                    'bank_details' => maybe_unserialize($conversion->bank_details),
                                    'security_phrase' => $conversion->security_phrase
                                );
                                ?>
                                <tr data-id="<?php echo esc_attr($conversion->id); ?>">
                                    <td><?php echo esc_html($conversion->id); ?></td>
                                    <td><?php echo esc_html($conversion->display_name ?: $conversion->user_login); ?></td>
                                    <td><?php echo esc_html($conversion->email); ?></td>
                                    <td><span class="gsp-conversion-type gsp-type-<?php echo esc_attr($conversion->conversion_type); ?>"><?php echo esc_html(strtoupper($conversion->conversion_type)); ?></span></td>
                                    <td>$<?php echo esc_html(number_format($conversion->amount, 2)); ?></td>
                                    <td>
                                        <button class="gsp-view-details-btn" data-details="<?php echo esc_attr(wp_json_encode($details)); ?>"><?php esc_html_e('View', 'globalswiftpay-dashboard'); ?></button>
                                    </td>
                                    <td><span class="gsp-status gsp-status-<?php echo esc_attr($conversion->status); ?>"><?php echo esc_html(ucfirst($conversion->status)); ?></span></td>
                                    <td><?php echo esc_html(date('M j, Y g:i A', strtotime($conversion->created_at))); ?></td>
                                    <td class="gsp-admin-actions">
                                        <?php if ($conversion->status === 'pending'): ?>
                                            <button class="gsp-admin-btn gsp-btn-approve" data-action="approve" data-type="conversion" data-id="<?php echo esc_attr($conversion->id); ?>"><?php esc_html_e('Approve', 'globalswiftpay-dashboard'); ?></button>
                                            <button class="gsp-admin-btn gsp-btn-decline" data-action="decline" data-type="conversion" data-id="<?php echo esc_attr($conversion->id); ?>"><?php esc_html_e('Decline', 'globalswiftpay-dashboard'); ?></button>
                                        <?php else: ?>
                                            <span class="gsp-action-completed"><?php echo esc_html(ucfirst($conversion->status)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render users page
     */
    public function render_users_page() {
        global $wpdb;
        $users = get_users();
        ?>
        <div class="wrap gsp-admin-wrap">
            <h1><?php esc_html_e('User Balances', 'globalswiftpay-dashboard'); ?></h1>
            
            <div class="gsp-admin-table-container">
                <table class="gsp-admin-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Username', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Email', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Display Name', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Wallet Balance', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Savings Balance', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Actions', 'globalswiftpay-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $balance = GSP_User::get_balance($user->ID); ?>
                            <tr data-user-id="<?php echo esc_attr($user->ID); ?>">
                                <td><?php echo esc_html($user->ID); ?></td>
                                <td><?php echo esc_html($user->user_login); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td class="gsp-user-wallet-balance">$<?php echo esc_html(number_format($balance->wallet_balance, 2)); ?></td>
                                <td>$<?php echo esc_html(number_format($balance->savings_balance, 2)); ?></td>
                                <td>
                                    <button class="gsp-admin-btn gsp-btn-edit-balance" data-user-id="<?php echo esc_attr($user->ID); ?>" data-balance="<?php echo esc_attr($balance->wallet_balance); ?>"><?php esc_html_e('Edit Balance', 'globalswiftpay-dashboard'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Edit Balance Modal -->
        <div id="gsp-edit-balance-modal" class="gsp-modal" style="display: none;">
            <div class="gsp-modal-content">
                <span class="gsp-modal-close">&times;</span>
                <h2><?php esc_html_e('Edit User Balance', 'globalswiftpay-dashboard'); ?></h2>
                <form id="gsp-edit-balance-form">
                    <input type="hidden" id="edit-balance-user-id" name="user_id" value="">
                    <div class="gsp-form-group">
                        <label for="edit-balance-amount"><?php esc_html_e('New Balance ($)', 'globalswiftpay-dashboard'); ?></label>
                        <input type="number" id="edit-balance-amount" name="balance" step="0.01" min="0" required>
                    </div>
                    <button type="submit" class="gsp-admin-btn gsp-btn-save"><?php esc_html_e('Save Balance', 'globalswiftpay-dashboard'); ?></button>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings = GSP_Database::get_all_settings();
        ?>
        <div class="wrap gsp-admin-wrap">
            <h1><?php esc_html_e('GlobalSwiftPay Settings', 'globalswiftpay-dashboard'); ?></h1>
            
            <div class="gsp-admin-settings">
                <form id="gsp-settings-form" class="gsp-settings-form">
                    <div class="gsp-settings-section">
                        <h2><?php esc_html_e('Bank Account Details', 'globalswiftpay-dashboard'); ?></h2>
                        <p class="gsp-settings-description"><?php esc_html_e('This account information will be shown to users when they want to add balance.', 'globalswiftpay-dashboard'); ?></p>
                        
                        <div class="gsp-form-group">
                            <label for="account_number"><?php esc_html_e('Account Number', 'globalswiftpay-dashboard'); ?></label>
                            <input type="text" id="account_number" name="settings[account_number]" value="<?php echo esc_attr($settings['account_number'] ?? ''); ?>" class="gsp-input">
                        </div>
                        
                        <div class="gsp-form-group">
                            <label for="account_name"><?php esc_html_e('Account Name', 'globalswiftpay-dashboard'); ?></label>
                            <input type="text" id="account_name" name="settings[account_name]" value="<?php echo esc_attr($settings['account_name'] ?? ''); ?>" class="gsp-input">
                        </div>
                        
                        <div class="gsp-form-group">
                            <label for="bank_name"><?php esc_html_e('Bank Name', 'globalswiftpay-dashboard'); ?></label>
                            <input type="text" id="bank_name" name="settings[bank_name]" value="<?php echo esc_attr($settings['bank_name'] ?? ''); ?>" class="gsp-input">
                        </div>
                    </div>
                    
                    <div class="gsp-settings-section">
                        <h2><?php esc_html_e('Cryptocurrency Addresses', 'globalswiftpay-dashboard'); ?></h2>
                        <p class="gsp-settings-description"><?php esc_html_e('These addresses will be displayed for cryptocurrency deposits.', 'globalswiftpay-dashboard'); ?></p>
                        
                        <div class="gsp-form-group">
                            <label for="btc_address"><?php esc_html_e('Bitcoin (BTC) Address', 'globalswiftpay-dashboard'); ?></label>
                            <input type="text" id="btc_address" name="settings[btc_address]" value="<?php echo esc_attr($settings['btc_address'] ?? ''); ?>" class="gsp-input">
                        </div>
                        
                        <div class="gsp-form-group">
                            <label for="usdt_address"><?php esc_html_e('Tether (USDT) Address', 'globalswiftpay-dashboard'); ?></label>
                            <input type="text" id="usdt_address" name="settings[usdt_address]" value="<?php echo esc_attr($settings['usdt_address'] ?? ''); ?>" class="gsp-input">
                        </div>
                    </div>
                    
                    <div class="gsp-settings-section">
                        <h2><?php esc_html_e('Contact Information', 'globalswiftpay-dashboard'); ?></h2>
                        
                        <div class="gsp-form-group">
                            <label for="company_email"><?php esc_html_e('Company Email', 'globalswiftpay-dashboard'); ?></label>
                            <input type="email" id="company_email" name="settings[company_email]" value="<?php echo esc_attr($settings['company_email'] ?? ''); ?>" class="gsp-input">
                        </div>
                    </div>
                    
                    <div class="gsp-settings-section">
                        <h2><?php esc_html_e('Site Settings', 'globalswiftpay-dashboard'); ?></h2>
                        
                        <div class="gsp-form-group">
                            <label for="logout_redirect_url"><?php esc_html_e('Logout Redirect URL', 'globalswiftpay-dashboard'); ?></label>
                            <input type="url" id="logout_redirect_url" name="settings[logout_redirect_url]" value="<?php echo esc_attr($settings['logout_redirect_url'] ?? 'https://globalswiftpay2.com'); ?>" class="gsp-input" placeholder="https://globalswiftpay2.com">
                            <p class="gsp-settings-description"><?php esc_html_e('URL to redirect users after logout. Leave empty for site home page.', 'globalswiftpay-dashboard'); ?></p>
                        </div>
                    </div>
                    
                    <button type="submit" class="gsp-admin-btn gsp-btn-save"><?php esc_html_e('Save Settings', 'globalswiftpay-dashboard'); ?></button>
                </form>
            </div>
        </div>
        <?php
    }
}
