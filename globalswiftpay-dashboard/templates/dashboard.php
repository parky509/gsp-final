<?php
/**
 * Dashboard template for GlobalSwiftPay
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$balance = GSP_User::get_balance($user_id);
$transactions = GSP_Transactions::get_user_transactions($user_id, 10);
$settings = GSP_Database::get_all_settings();

$avatar_url = get_avatar_url($user_id, array('size' => 100));
$display_name = $current_user->display_name ?: $current_user->user_login;
?>

<div class="gsp-dashboard">
    <!-- Header Section -->
    <div class="gsp-header">
        <div class="gsp-header-content">
            <div class="gsp-user-info">
                <div class="gsp-avatar">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
                </div>
                <div class="gsp-user-details">
                    <h2><?php esc_html_e('Welcome back,', 'globalswiftpay-dashboard'); ?></h2>
                    <h1><?php echo esc_html($display_name); ?></h1>
                </div>
            </div>
            <div class="gsp-header-actions">
                <a href="<?php echo esc_url(wp_logout_url('https://globalswiftpay2.com')); ?>" class="gsp-btn gsp-btn-logout">
                    <svg class="gsp-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M16.56 5.44l-1.45 1.45A5.969 5.969 0 0 1 18 12a6 6 0 0 1-6 6a6 6 0 0 1-6-6c0-2.17 1.16-4.06 2.88-5.12L7.44 5.44A7.961 7.961 0 0 0 4 12a8 8 0 0 0 8 8a8 8 0 0 0 8-8c0-2.72-1.36-5.12-3.44-6.56M13 3h-2v10h2"/></svg>
                    <?php esc_html_e('Logout', 'globalswiftpay-dashboard'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Balance Cards Section -->
    <div class="gsp-balance-section">
        <div class="gsp-glass-card gsp-balance-card gsp-wallet-balance">
            <div class="gsp-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1h-9a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2m0-10h10v8H12m4 1a1.5 1.5 0 0 1-1.5-1.5A1.5 1.5 0 0 1 16 14a1.5 1.5 0 0 1 1.5 1.5A1.5 1.5 0 0 1 16 17"/></svg>
            </div>
            <div class="gsp-card-content">
                <h3><?php esc_html_e('Wallet Balance', 'globalswiftpay-dashboard'); ?></h3>
                <p class="gsp-amount" id="gsp-wallet-balance">$<?php echo esc_html(number_format($balance->wallet_balance, 2)); ?></p>
            </div>
        </div>
        
        <div class="gsp-glass-card gsp-balance-card gsp-savings-balance" id="savings-card">
            <div class="gsp-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M11.5 1L2 6v2h19V6m-5 4v6h4v-6m-16 6v-6h4v6m2-6v6h4v-6M2 20v2h19v-2"/></svg>
            </div>
            <div class="gsp-card-content">
                <h3><?php esc_html_e('Savings', 'globalswiftpay-dashboard'); ?></h3>
                <p class="gsp-amount" id="gsp-savings-balance">$<?php echo esc_html(number_format($balance->savings_balance, 2)); ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="gsp-actions-section">
        <h2 class="gsp-section-title"><?php esc_html_e('Quick Actions', 'globalswiftpay-dashboard'); ?></h2>
        <div class="gsp-actions-grid">
            <button class="gsp-glass-btn gsp-action-btn" data-modal="deposit-modal">
                <svg class="gsp-btn-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M3 6h18v12H3zm5 3a3 3 0 0 0 3 3a3 3 0 0 0 3-3h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1zm4-6h2v3h3l-4 4l-4-4h3z"/></svg>
                <span class="gsp-btn-text"><?php esc_html_e('Deposit', 'globalswiftpay-dashboard'); ?></span>
            </button>
            
            <button class="gsp-glass-btn gsp-action-btn" data-modal="add-balance-modal">
                <svg class="gsp-btn-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M17 13h-4v4h-2v-4H7v-2h4V7h2v4h4m-5-9A10 10 0 0 0 2 12a10 10 0 0 0 10 10a10 10 0 0 0 10-10A10 10 0 0 0 12 2"/></svg>
                <span class="gsp-btn-text"><?php esc_html_e('Add Balance', 'globalswiftpay-dashboard'); ?></span>
            </button>
            
            <button class="gsp-glass-btn gsp-action-btn" data-modal="withdraw-modal">
                <svg class="gsp-btn-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M3 6h18v12H3zm5 3a3 3 0 0 0 3 3a3 3 0 0 0 3-3h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1zm7 11l4-4h-3v-3h-2v3H9z"/></svg>
                <span class="gsp-btn-text"><?php esc_html_e('Withdraw', 'globalswiftpay-dashboard'); ?></span>
            </button>
            
            <button class="gsp-glass-btn gsp-action-btn" data-modal="transfer-modal">
                <svg class="gsp-btn-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M21 9l-4-4v3h-7v2h7v3M7 11l-4 4l4 4v-3h7v-2H7z"/></svg>
                <span class="gsp-btn-text"><?php esc_html_e('Transfer', 'globalswiftpay-dashboard'); ?></span>
            </button>
            
            <button class="gsp-glass-btn gsp-action-btn" data-modal="convert-btc-modal">
                <svg class="gsp-btn-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M14.24 10.56C13.93 11.8 12 12.06 11.4 11.9l.34-1.42c.61.14 1.52.28 1.5-.51c-.01-.77-.88-.62-1.18-.67l.36-1.43c.23.05.52.08.85.12c.63.07 1.12.4 1.03 1.07c.07 0 .13.01.2.02c.93.11 1.52.68 1.37 1.57c-.15.89-.83 1.44-1.63 1.44m.33-3.06c.14-.62-.39-.87-1.08-1.02l.22-.88l-.54-.13l-.21.85c-.14-.03-.29-.07-.44-.1l.21-.86l-.54-.13l-.22.88c-.12-.03-.23-.06-.34-.08l.01-.01l-.75-.18l-.14.57s.4.1.4.1c.22.05.26.2.25.31l-.26 1.02l.06.02l-.06-.01l-.36 1.43c-.02.09-.09.2-.24.17c.01.01-.4-.1-.4-.1l-.27.61l.7.17c.13.03.26.07.39.1l-.22.9l.54.13l.22-.88c.15.04.3.08.45.11l-.22.87l.54.14l.22-.9c.9.17 1.58.1 1.87-.72c.23-.66-.01-1.04-.49-1.29c.35-.08.61-.31.68-.78M12 2a10 10 0 0 1 10 10a10 10 0 0 1-10 10A10 10 0 0 1 2 12A10 10 0 0 1 12 2"/></svg>
                <span class="gsp-btn-text"><?php esc_html_e('Convert to BTC', 'globalswiftpay-dashboard'); ?></span>
            </button>
            
            <button class="gsp-glass-btn gsp-action-btn" data-modal="convert-usdt-modal">
                <svg class="gsp-btn-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2m0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3s-3-1.34-3-3s1.34-3 3-3m0 14.2a7.2 7.2 0 0 1-6-3.22c.03-1.99 4-3.08 6-3.08c1.99 0 5.97 1.09 6 3.08a7.2 7.2 0 0 1-6 3.22"/></svg>
                <span class="gsp-btn-text"><?php esc_html_e('Convert to USDT', 'globalswiftpay-dashboard'); ?></span>
            </button>
            
            <button class="gsp-glass-btn gsp-action-btn" data-modal="convert-bank-modal">
                <svg class="gsp-btn-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><path fill="currentColor" d="M15 14v-3h3V9l4 3.5l-4 3.5v-2zm-1-6.3V9H2V7.7L8 4zM7 10h2v5H7zm-4 0h2v5H3zm10 0v2.5l-2 1.8V10zm-3.5 6l.5.5v1H2v-2h7.5zM14 16h8v2h-8z"/></svg>
                <span class="gsp-btn-text"><?php esc_html_e('Convert to Bank', 'globalswiftpay-dashboard'); ?></span>
            </button>
        </div>
    </div>

    <!-- Transaction History Section -->
    <div class="gsp-transactions-section">
        <h2 class="gsp-section-title"><?php esc_html_e('Transaction History', 'globalswiftpay-dashboard'); ?></h2>
        <div class="gsp-glass-card gsp-transactions-card">
            <div class="gsp-transactions-table-wrapper">
                <table class="gsp-transactions-table" id="gsp-transactions-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Type', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Amount', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Status', 'globalswiftpay-dashboard'); ?></th>
                            <th><?php esc_html_e('Date', 'globalswiftpay-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="4" class="gsp-no-transactions"><?php esc_html_e('No transactions yet.', 'globalswiftpay-dashboard'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <span class="gsp-transaction-type gsp-type-<?php echo esc_attr(str_replace('_', '-', $transaction->type)); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $transaction->type))); ?>
                                        </span>
                                    </td>
                                    <td class="gsp-transaction-amount">$<?php echo esc_html(number_format($transaction->amount, 2)); ?></td>
                                    <td>
                                        <span class="gsp-status gsp-status-<?php echo esc_attr($transaction->status); ?>">
                                            <?php echo esc_html(ucfirst($transaction->status)); ?>
                                        </span>
                                    </td>
                                    <td class="gsp-transaction-date"><?php echo esc_html(date('M j, Y g:i A', strtotime($transaction->created_at))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Deposit -->
<div id="deposit-modal" class="gsp-modal">
    <div class="gsp-modal-overlay"></div>
    <div class="gsp-modal-container gsp-glass-card">
        <button class="gsp-modal-close">&times;</button>
        <h2 class="gsp-modal-title"><?php esc_html_e('Make a Deposit', 'globalswiftpay-dashboard'); ?></h2>
        <form id="gsp-deposit-form" class="gsp-form">
            <div class="gsp-form-group">
                <label for="deposit-name"><?php esc_html_e('Full Name', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="deposit-name" name="name" class="gsp-input" required>
            </div>
            <div class="gsp-form-group">
                <label for="deposit-email"><?php esc_html_e('Email Address', 'globalswiftpay-dashboard'); ?></label>
                <input type="email" id="deposit-email" name="email" class="gsp-input" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="deposit-amount"><?php esc_html_e('Deposit Amount ($)', 'globalswiftpay-dashboard'); ?></label>
                <input type="number" id="deposit-amount" name="amount" class="gsp-input" step="0.01" min="1" required>
            </div>
            <button type="submit" class="gsp-btn gsp-btn-primary gsp-btn-full">
                <?php esc_html_e('Submit Deposit', 'globalswiftpay-dashboard'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Modal: Add Balance -->
<div id="add-balance-modal" class="gsp-modal">
    <div class="gsp-modal-overlay"></div>
    <div class="gsp-modal-container gsp-glass-card">
        <button class="gsp-modal-close">&times;</button>
        <h2 class="gsp-modal-title"><?php esc_html_e('Add Balance', 'globalswiftpay-dashboard'); ?></h2>
        
        <div class="gsp-payment-details">
            <h3><?php esc_html_e('Transfer to this account:', 'globalswiftpay-dashboard'); ?></h3>
            <div class="gsp-detail-row">
                <span class="gsp-detail-label"><?php esc_html_e('Bank Name:', 'globalswiftpay-dashboard'); ?></span>
                <span class="gsp-detail-value"><?php echo esc_html($settings['bank_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="gsp-detail-row">
                <span class="gsp-detail-label"><?php esc_html_e('Account Name:', 'globalswiftpay-dashboard'); ?></span>
                <span class="gsp-detail-value"><?php echo esc_html($settings['account_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="gsp-detail-row">
                <span class="gsp-detail-label"><?php esc_html_e('Account Number:', 'globalswiftpay-dashboard'); ?></span>
                <span class="gsp-detail-value gsp-copy-text" data-copy="<?php echo esc_attr($settings['account_number'] ?? ''); ?>"><?php echo esc_html($settings['account_number'] ?? 'N/A'); ?> <svg class="gsp-copy-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="currentColor" d="M19 21H8V7h11m0-2H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m-3-4H4a2 2 0 0 0-2 2v14h2V3h12z"/></svg></span>
            </div>
        </div>
        
        <form id="gsp-add-balance-form" class="gsp-form" enctype="multipart/form-data">
            <div class="gsp-form-group">
                <label for="add-sender-name"><?php esc_html_e('Sender Name', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="add-sender-name" name="sender_name" class="gsp-input" required>
            </div>
            <div class="gsp-form-group">
                <label for="add-email"><?php esc_html_e('Email Address', 'globalswiftpay-dashboard'); ?></label>
                <input type="email" id="add-email" name="email" class="gsp-input" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="add-amount"><?php esc_html_e('Amount Transferred ($)', 'globalswiftpay-dashboard'); ?></label>
                <input type="number" id="add-amount" name="amount" class="gsp-input" step="0.01" min="1" required>
            </div>
            <div class="gsp-form-group">
                <label for="add-receipt"><?php esc_html_e('Upload Receipt (PDF/PNG/JPEG)', 'globalswiftpay-dashboard'); ?></label>
                <div class="gsp-file-upload">
                    <input type="file" id="add-receipt" name="receipt" class="gsp-file-input" accept=".pdf,.png,.jpg,.jpeg">
                    <label for="add-receipt" class="gsp-file-label">
                        <svg class="gsp-file-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6z"/></svg>
                        <span class="gsp-file-text"><?php esc_html_e('Choose file...', 'globalswiftpay-dashboard'); ?></span>
                    </label>
                </div>
            </div>
            <button type="submit" class="gsp-btn gsp-btn-primary gsp-btn-full">
                <?php esc_html_e('Submit for Approval', 'globalswiftpay-dashboard'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Modal: Withdraw -->
<div id="withdraw-modal" class="gsp-modal">
    <div class="gsp-modal-overlay"></div>
    <div class="gsp-modal-container gsp-glass-card">
        <button class="gsp-modal-close">&times;</button>
        <h2 class="gsp-modal-title"><?php esc_html_e('Withdraw Funds', 'globalswiftpay-dashboard'); ?></h2>
        <p class="gsp-modal-subtitle"><?php esc_html_e('Available balance:', 'globalswiftpay-dashboard'); ?> <strong>$<?php echo esc_html(number_format($balance->wallet_balance, 2)); ?></strong></p>
        
        <form id="gsp-withdraw-form" class="gsp-form">
            <div class="gsp-form-group">
                <label for="withdraw-amount"><?php esc_html_e('Amount to Withdraw ($)', 'globalswiftpay-dashboard'); ?></label>
                <input type="number" id="withdraw-amount" name="amount" class="gsp-input" step="0.01" min="1" max="<?php echo esc_attr($balance->wallet_balance); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="withdraw-method"><?php esc_html_e('Withdrawal Method', 'globalswiftpay-dashboard'); ?></label>
                <select id="withdraw-method" name="method" class="gsp-input gsp-select" required>
                    <option value=""><?php esc_html_e('Select method...', 'globalswiftpay-dashboard'); ?></option>
                    <option value="bank"><?php esc_html_e('Bank Transfer', 'globalswiftpay-dashboard'); ?></option>
                    <option value="btc"><?php esc_html_e('Bitcoin (BTC)', 'globalswiftpay-dashboard'); ?></option>
                    <option value="usdt"><?php esc_html_e('Tether (USDT)', 'globalswiftpay-dashboard'); ?></option>
                </select>
            </div>
            
            <div id="withdraw-bank-details" class="gsp-conditional-fields" style="display: none;">
                <div class="gsp-form-group">
                    <label for="withdraw-bank-name"><?php esc_html_e('Bank Name', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="withdraw-bank-name" name="details[bank_name]" class="gsp-input">
                </div>
                <div class="gsp-form-group">
                    <label for="withdraw-account-name"><?php esc_html_e('Account Name', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="withdraw-account-name" name="details[account_name]" class="gsp-input">
                </div>
                <div class="gsp-form-group">
                    <label for="withdraw-account-number"><?php esc_html_e('Account Number', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="withdraw-account-number" name="details[account_number]" class="gsp-input">
                </div>
            </div>
            
            <div id="withdraw-crypto-details" class="gsp-conditional-fields" style="display: none;">
                <div class="gsp-form-group">
                    <label for="withdraw-wallet-address"><?php esc_html_e('Wallet Address', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="withdraw-wallet-address" name="details[wallet_address]" class="gsp-input">
                </div>
            </div>
            
            <button type="submit" class="gsp-btn gsp-btn-primary gsp-btn-full">
                <?php esc_html_e('Request Withdrawal', 'globalswiftpay-dashboard'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Modal: Transfer -->
<div id="transfer-modal" class="gsp-modal">
    <div class="gsp-modal-overlay"></div>
    <div class="gsp-modal-container gsp-glass-card">
        <button class="gsp-modal-close">&times;</button>
        <h2 class="gsp-modal-title"><?php esc_html_e('Transfer Funds', 'globalswiftpay-dashboard'); ?></h2>
        <p class="gsp-modal-subtitle"><?php esc_html_e('Available balance:', 'globalswiftpay-dashboard'); ?> <strong>$<?php echo esc_html(number_format($balance->wallet_balance, 2)); ?></strong></p>
        
        <form id="gsp-transfer-form" class="gsp-form">
            <div class="gsp-form-group">
                <label for="transfer-email"><?php esc_html_e('Recipient Email', 'globalswiftpay-dashboard'); ?></label>
                <input type="email" id="transfer-email" name="recipient_email" class="gsp-input" required>
            </div>
            <div class="gsp-form-group">
                <label for="transfer-amount"><?php esc_html_e('Amount to Transfer ($)', 'globalswiftpay-dashboard'); ?></label>
                <input type="number" id="transfer-amount" name="amount" class="gsp-input" step="0.01" min="1" max="<?php echo esc_attr($balance->wallet_balance); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="transfer-token"><?php esc_html_e('Token Code', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="transfer-token" name="token_code" class="gsp-input" placeholder="<?php esc_attr_e('Enter your security token', 'globalswiftpay-dashboard'); ?>" required>
            </div>
            <button type="submit" class="gsp-btn gsp-btn-primary gsp-btn-full">
                <?php esc_html_e('Request Transfer', 'globalswiftpay-dashboard'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Modal: Convert to BTC -->
<div id="convert-btc-modal" class="gsp-modal">
    <div class="gsp-modal-overlay"></div>
    <div class="gsp-modal-container gsp-glass-card">
        <button class="gsp-modal-close">&times;</button>
        <h2 class="gsp-modal-title"><?php esc_html_e('Convert to Bitcoin (BTC)', 'globalswiftpay-dashboard'); ?></h2>
        
        <form id="gsp-convert-btc-form" class="gsp-form">
            <input type="hidden" name="conversion_type" value="btc">
            <div class="gsp-form-group">
                <label for="btc-email"><?php esc_html_e('Email Address', 'globalswiftpay-dashboard'); ?></label>
                <input type="email" id="btc-email" name="email" class="gsp-input" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="btc-amount"><?php esc_html_e('Amount to Convert ($)', 'globalswiftpay-dashboard'); ?></label>
                <input type="number" id="btc-amount" name="amount" class="gsp-input" step="0.01" min="1" max="<?php echo esc_attr($balance->wallet_balance); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="btc-address"><?php esc_html_e('Bitcoin Address', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="btc-address" name="btc_address" class="gsp-input" required>
            </div>
            <div class="gsp-form-group">
                <label for="btc-security"><?php esc_html_e('Security Phrase', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="btc-security" name="security_phrase" class="gsp-input" required>
            </div>
            <button type="submit" class="gsp-btn gsp-btn-primary gsp-btn-full">
                <?php esc_html_e('Convert to BTC', 'globalswiftpay-dashboard'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Modal: Convert to USDT -->
<div id="convert-usdt-modal" class="gsp-modal">
    <div class="gsp-modal-overlay"></div>
    <div class="gsp-modal-container gsp-glass-card">
        <button class="gsp-modal-close">&times;</button>
        <h2 class="gsp-modal-title"><?php esc_html_e('Convert to Tether (USDT)', 'globalswiftpay-dashboard'); ?></h2>
        
        <form id="gsp-convert-usdt-form" class="gsp-form">
            <input type="hidden" name="conversion_type" value="usdt">
            <div class="gsp-form-group">
                <label for="usdt-email"><?php esc_html_e('Email Address', 'globalswiftpay-dashboard'); ?></label>
                <input type="email" id="usdt-email" name="email" class="gsp-input" value="<?php echo esc_attr($current_user->user_email); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="usdt-amount"><?php esc_html_e('Amount to Convert ($)', 'globalswiftpay-dashboard'); ?></label>
                <input type="number" id="usdt-amount" name="amount" class="gsp-input" step="0.01" min="1" max="<?php echo esc_attr($balance->wallet_balance); ?>" required>
            </div>
            <div class="gsp-form-group">
                <label for="usdt-address"><?php esc_html_e('Tether (USDT) Address', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="usdt-address" name="usdt_address" class="gsp-input" required>
            </div>
            <div class="gsp-form-group">
                <label for="usdt-security"><?php esc_html_e('Security Phrase', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="usdt-security" name="security_phrase" class="gsp-input" required>
            </div>
            <button type="submit" class="gsp-btn gsp-btn-primary gsp-btn-full">
                <?php esc_html_e('Convert to USDT', 'globalswiftpay-dashboard'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Modal: Convert to Bank -->
<div id="convert-bank-modal" class="gsp-modal">
    <div class="gsp-modal-overlay"></div>
    <div class="gsp-modal-container gsp-glass-card gsp-modal-large">
        <button class="gsp-modal-close">&times;</button>
        <h2 class="gsp-modal-title"><?php esc_html_e('Convert to Bank Transfer', 'globalswiftpay-dashboard'); ?></h2>
        
        <form id="gsp-convert-bank-form" class="gsp-form">
            <input type="hidden" name="conversion_type" value="bank">
            <div class="gsp-form-row">
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-email"><?php esc_html_e('Email Address', 'globalswiftpay-dashboard'); ?></label>
                    <input type="email" id="bank-email" name="email" class="gsp-input" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                </div>
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-amount"><?php esc_html_e('Amount in Dollars ($)', 'globalswiftpay-dashboard'); ?></label>
                    <input type="number" id="bank-amount" name="amount" class="gsp-input" step="0.01" min="1" max="<?php echo esc_attr($balance->wallet_balance); ?>" required>
                </div>
            </div>
            <div class="gsp-form-row">
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-name"><?php esc_html_e('Bank Name', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="bank-name" name="bank_name" class="gsp-input" required>
                </div>
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-account-name"><?php esc_html_e('Account Name', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="bank-account-name" name="account_name" class="gsp-input" required>
                </div>
            </div>
            <div class="gsp-form-row">
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-account-no"><?php esc_html_e('Bank Account No.', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="bank-account-no" name="account_number" class="gsp-input" required>
                </div>
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-swift"><?php esc_html_e('SWIFT/IFSC/Routing No./Sort Code/IBAN', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="bank-swift" name="swift_code" class="gsp-input" required>
                </div>
            </div>
            <div class="gsp-form-row">
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-address"><?php esc_html_e('Bank Address', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="bank-address" name="bank_address" class="gsp-input" required>
                </div>
                <div class="gsp-form-group gsp-form-half">
                    <label for="bank-country"><?php esc_html_e('Country', 'globalswiftpay-dashboard'); ?></label>
                    <input type="text" id="bank-country" name="country" class="gsp-input" required>
                </div>
            </div>
            <div class="gsp-form-group">
                <label for="bank-security"><?php esc_html_e('Security Phrase', 'globalswiftpay-dashboard'); ?></label>
                <input type="text" id="bank-security" name="security_phrase" class="gsp-input" required>
            </div>
            <button type="submit" class="gsp-btn gsp-btn-primary gsp-btn-full">
                <?php esc_html_e('Convert to Bank Transfer', 'globalswiftpay-dashboard'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="gsp-toast" class="gsp-toast"></div>
