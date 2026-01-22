<?php
/**
 * Email handler for GlobalSwiftPay Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSP_Email {
    
    /**
     * Get email headers
     */
    private static function get_headers() {
        return array(
            'Content-Type: text/html; charset=UTF-8',
            'From: GlobalSwiftPay <support@globalswiftpay2.com>'
        );
    }
    
    /**
     * Get email template wrapper
     */
    private static function get_template($content, $title = '') {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($title); ?></title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f4f7fa;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 15px;
                    box-shadow: 0 10px 40px rgba(0, 150, 255, 0.1);
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #0096ff 0%, #00d4ff 100%);
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    color: #ffffff;
                    margin: 0;
                    font-size: 24px;
                    font-weight: 600;
                }
                .content {
                    padding: 40px 30px;
                }
                .status-badge {
                    display: inline-block;
                    padding: 8px 20px;
                    border-radius: 25px;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 12px;
                }
                .status-approved {
                    background: linear-gradient(135deg, #00c853 0%, #69f0ae 100%);
                    color: #ffffff;
                }
                .status-declined {
                    background: linear-gradient(135deg, #ff5252 0%, #ff8a80 100%);
                    color: #ffffff;
                }
                .amount {
                    font-size: 32px;
                    font-weight: 700;
                    color: #0096ff;
                    margin: 20px 0;
                }
                .footer {
                    background: #f8fafc;
                    padding: 20px 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #0096ff 0%, #00d4ff 100%);
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 25px;
                    font-weight: 600;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>GlobalSwiftPay</h1>
                </div>
                <div class="content">
                    <?php echo $content; ?>
                </div>
                <div class="footer">
                    <p>This is an automated message from GlobalSwiftPay.</p>
                    <p>&copy; <?php echo date('Y'); ?> GlobalSwiftPay. All rights reserved.</p>
                    <p><a href="https://globalswiftpay2.com">globalswiftpay2.com</a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send deposit status email
     */
    public static function send_deposit_status($to, $status, $amount) {
        $subject = $status === 'approved' 
            ? 'Deposit Approved - GlobalSwiftPay' 
            : 'Deposit Request Update - GlobalSwiftPay';
        
        $status_class = $status === 'approved' ? 'status-approved' : 'status-declined';
        $status_text = $status === 'approved' ? 'Approved' : 'Declined';
        
        $message = $status === 'approved'
            ? 'Great news! Your deposit request has been approved and your wallet has been credited.'
            : 'Unfortunately, your deposit request could not be processed at this time. Please contact support for more information.';
        
        ob_start();
        ?>
        <h2 style="color: #333; margin-bottom: 10px;">Deposit Request Update</h2>
        <p><?php echo esc_html($message); ?></p>
        <div class="amount">$<?php echo number_format($amount, 2); ?></div>
        <p>Status: <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></p>
        <a href="https://globalswiftpay2.com/gsp-dashboard" class="btn">View Dashboard</a>
        <?php
        $content = ob_get_clean();
        
        $html = self::get_template($content, $subject);
        
        wp_mail($to, $subject, $html, self::get_headers());
    }
    
    /**
     * Send withdrawal status email
     */
    public static function send_withdrawal_status($to, $status, $amount) {
        $subject = $status === 'approved' 
            ? 'Withdrawal Approved - GlobalSwiftPay' 
            : 'Withdrawal Request Update - GlobalSwiftPay';
        
        $status_class = $status === 'approved' ? 'status-approved' : 'status-declined';
        $status_text = $status === 'approved' ? 'Approved' : 'Declined';
        
        $message = $status === 'approved'
            ? 'Your withdrawal request has been approved and is being processed. Funds will be transferred shortly.'
            : 'Unfortunately, your withdrawal request could not be processed at this time. Please contact support for more information.';
        
        ob_start();
        ?>
        <h2 style="color: #333; margin-bottom: 10px;">Withdrawal Request Update</h2>
        <p><?php echo esc_html($message); ?></p>
        <div class="amount">$<?php echo number_format($amount, 2); ?></div>
        <p>Status: <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></p>
        <a href="https://globalswiftpay2.com/gsp-dashboard" class="btn">View Dashboard</a>
        <?php
        $content = ob_get_clean();
        
        $html = self::get_template($content, $subject);
        
        wp_mail($to, $subject, $html, self::get_headers());
    }
    
    /**
     * Send transfer status email
     */
    public static function send_transfer_status($to, $status, $amount, $type = 'sender') {
        $subject = $status === 'approved' 
            ? 'Transfer Completed - GlobalSwiftPay' 
            : 'Transfer Request Update - GlobalSwiftPay';
        
        $status_class = $status === 'approved' ? 'status-approved' : 'status-declined';
        $status_text = $status === 'approved' ? 'Approved' : 'Declined';
        
        if ($type === 'sender') {
            $message = $status === 'approved'
                ? 'Your transfer has been completed successfully. The amount has been deducted from your wallet.'
                : 'Unfortunately, your transfer request could not be processed at this time. Please contact support for more information.';
        } else {
            $message = 'You have received a transfer! The amount has been credited to your wallet.';
        }
        
        ob_start();
        ?>
        <h2 style="color: #333; margin-bottom: 10px;">Transfer <?php echo $type === 'receiver' ? 'Received' : 'Update'; ?></h2>
        <p><?php echo esc_html($message); ?></p>
        <div class="amount">$<?php echo number_format($amount, 2); ?></div>
        <p>Status: <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></p>
        <a href="https://globalswiftpay2.com/gsp-dashboard" class="btn">View Dashboard</a>
        <?php
        $content = ob_get_clean();
        
        $html = self::get_template($content, $subject);
        
        wp_mail($to, $subject, $html, self::get_headers());
    }
    
    /**
     * Send conversion status email
     */
    public static function send_conversion_status($to, $status, $amount, $conversion_type) {
        $type_label = ucfirst($conversion_type);
        if ($conversion_type === 'btc') {
            $type_label = 'Bitcoin (BTC)';
        } elseif ($conversion_type === 'usdt') {
            $type_label = 'Tether (USDT)';
        } elseif ($conversion_type === 'bank') {
            $type_label = 'Bank Transfer';
        }
        
        $subject = $status === 'approved' 
            ? "Conversion to $type_label Approved - GlobalSwiftPay" 
            : "Conversion Request Update - GlobalSwiftPay";
        
        $status_class = $status === 'approved' ? 'status-approved' : 'status-declined';
        $status_text = $status === 'approved' ? 'Approved' : 'Declined';
        
        $message = $status === 'approved'
            ? "Your conversion to $type_label has been approved and is being processed."
            : 'Unfortunately, your conversion request could not be processed at this time. Please contact support for more information.';
        
        ob_start();
        ?>
        <h2 style="color: #333; margin-bottom: 10px;">Conversion Request Update</h2>
        <p><?php echo esc_html($message); ?></p>
        <div class="amount">$<?php echo number_format($amount, 2); ?></div>
        <p>Conversion Type: <strong><?php echo esc_html($type_label); ?></strong></p>
        <p>Status: <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></p>
        <a href="https://globalswiftpay2.com/gsp-dashboard" class="btn">View Dashboard</a>
        <?php
        $content = ob_get_clean();
        
        $html = self::get_template($content, $subject);
        
        wp_mail($to, $subject, $html, self::get_headers());
    }
    
    /**
     * Send new request notification to admin
     */
    public static function notify_admin($type, $data) {
        $admin_email = get_option('admin_email');
        $subject = "New $type Request - GlobalSwiftPay Dashboard";
        
        ob_start();
        ?>
        <h2 style="color: #333; margin-bottom: 10px;">New <?php echo esc_html(ucfirst($type)); ?> Request</h2>
        <p>A new <?php echo esc_html($type); ?> request has been submitted and requires your attention.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <?php foreach ($data as $key => $value): ?>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: 600;"><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo esc_html($value); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <a href="<?php echo admin_url('admin.php?page=globalswiftpay-' . $type . 's'); ?>" class="btn">Review Request</a>
        <?php
        $content = ob_get_clean();
        
        $html = self::get_template($content, $subject);
        
        wp_mail($admin_email, $subject, $html, self::get_headers());
    }
}
