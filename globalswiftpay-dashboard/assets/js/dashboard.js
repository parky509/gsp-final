/**
 * GlobalSwiftPay Dashboard - Main JavaScript
 */

(function($) {
    'use strict';

    // Toast notification system
    const Toast = {
        show: function(message, type = 'success') {
            const toast = $('#gsp-toast');
            toast.removeClass('success error').addClass(type).text(message).addClass('show');
            
            setTimeout(function() {
                toast.removeClass('show');
            }, 4000);
        }
    };

    // Modal handler
    const Modal = {
        open: function(modalId) {
            $('#' + modalId).addClass('active');
            $('body').css('overflow', 'hidden');
        },
        
        close: function(modal) {
            $(modal).removeClass('active');
            $('body').css('overflow', '');
            // Reset forms
            $(modal).find('form')[0]?.reset();
        },
        
        init: function() {
            // Open modal on button click
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                const modalId = $(this).data('modal');
                Modal.open(modalId);
            });
            
            // Close modal on X click
            $(document).on('click', '.gsp-modal-close', function() {
                Modal.close($(this).closest('.gsp-modal'));
            });
            
            // Close modal on overlay click
            $(document).on('click', '.gsp-modal-overlay', function() {
                Modal.close($(this).closest('.gsp-modal'));
            });
            
            // Close modal on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.gsp-modal.active').each(function() {
                        Modal.close(this);
                    });
                }
            });
        }
    };

    // Form handlers
    const Forms = {
        balanceRequest: null,
        transactionsRequest: null,
        init: function() {
            // Deposit form
            $('#gsp-deposit-form').on('submit', function(e) {
                e.preventDefault();
                Forms.submitDeposit($(this));
            });
            
            // Add balance form
            $('#gsp-add-balance-form').on('submit', function(e) {
                e.preventDefault();
                Forms.submitAddBalance($(this));
            });
            
            // Withdraw form
            $('#gsp-withdraw-form').on('submit', function(e) {
                e.preventDefault();
                Forms.submitWithdraw($(this));
            });
            
            // Transfer form
            $('#gsp-transfer-form').on('submit', function(e) {
                e.preventDefault();
                Forms.submitTransfer($(this));
            });
            
            // Convert BTC form
            $('#gsp-convert-btc-form').on('submit', function(e) {
                e.preventDefault();
                Forms.submitConversion($(this), 'btc');
            });
            
            // Convert USDT form
            $('#gsp-convert-usdt-form').on('submit', function(e) {
                e.preventDefault();
                Forms.submitConversion($(this), 'usdt');
            });
            
            // Convert Bank form
            $('#gsp-convert-bank-form').on('submit', function(e) {
                e.preventDefault();
                Forms.submitConversion($(this), 'bank');
            });
            
            // Withdrawal method change
            $('#withdraw-method').on('change', function() {
                const method = $(this).val();
                $('#withdraw-bank-details, #withdraw-crypto-details').hide();
                
                if (method === 'bank') {
                    $('#withdraw-bank-details').show();
                } else if (method === 'btc' || method === 'usdt') {
                    $('#withdraw-crypto-details').show();
                }
            });
            
            // File input display
            $('.gsp-file-input').on('change', function() {
                const fileName = this.files[0]?.name || 'Choose file...';
                $(this).siblings('.gsp-file-label').find('.gsp-file-text').text(fileName);
            });
        },
        
        submitDeposit: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.addClass('loading');
            
            $.ajax({
                url: gsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_submit_deposit',
                    nonce: gsp_ajax.nonce,
                    name: $form.find('[name="name"]').val(),
                    email: $form.find('[name="email"]').val(),
                    amount: $form.find('[name="amount"]').val()
                },
                success: function(response) {
                    $btn.removeClass('loading');
                    if (response.success) {
                        Toast.show(response.data.message, 'success');
                        Modal.close($form.closest('.gsp-modal'));
                        Forms.refreshTransactions();
                    } else {
                        Toast.show(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.removeClass('loading');
                    Toast.show('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        submitAddBalance: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.addClass('loading');
            
            const formData = new FormData($form[0]);
            formData.append('action', 'gsp_submit_add_balance');
            formData.append('nonce', gsp_ajax.nonce);
            
            $.ajax({
                url: gsp_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $btn.removeClass('loading');
                    if (response.success) {
                        Toast.show(response.data.message, 'success');
                        Modal.close($form.closest('.gsp-modal'));
                        Forms.refreshTransactions();
                    } else {
                        Toast.show(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.removeClass('loading');
                    Toast.show('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        submitWithdraw: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.addClass('loading');
            
            const formData = {
                action: 'gsp_submit_withdrawal',
                nonce: gsp_ajax.nonce,
                amount: $form.find('[name="amount"]').val(),
                method: $form.find('[name="method"]').val(),
                details: {}
            };
            
            // Get conditional details - use regex with global flag to replace all occurrences
            $form.find('[name^="details"]').each(function() {
                const name = $(this).attr('name').replace(/details\[/g, '').replace(/\]/g, '');
                formData.details[name] = $(this).val();
            });
            
            $.ajax({
                url: gsp_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $btn.removeClass('loading');
                    if (response.success) {
                        Toast.show(response.data.message, 'success');
                        Modal.close($form.closest('.gsp-modal'));
                        Forms.refreshTransactions();
                        Forms.refreshBalance();
                    } else {
                        Toast.show(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.removeClass('loading');
                    Toast.show('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        submitTransfer: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.addClass('loading');
            
            $.ajax({
                url: gsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_submit_transfer',
                    nonce: gsp_ajax.nonce,
                    recipient_email: $form.find('[name="recipient_email"]').val(),
                    amount: $form.find('[name="amount"]').val(),
                    token_code: $form.find('[name="token_code"]').val()
                },
                success: function(response) {
                    $btn.removeClass('loading');
                    if (response.success) {
                        Toast.show(response.data.message, 'success');
                        Modal.close($form.closest('.gsp-modal'));
                        Forms.refreshTransactions();
                        Forms.refreshBalance();
                    } else {
                        Toast.show(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.removeClass('loading');
                    Toast.show('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        submitConversion: function($form, type) {
            const $btn = $form.find('button[type="submit"]');
            $btn.addClass('loading');
            
            const data = {
                action: 'gsp_submit_conversion',
                nonce: gsp_ajax.nonce,
                conversion_type: type,
                email: $form.find('[name="email"]').val(),
                amount: $form.find('[name="amount"]').val(),
                security_phrase: $form.find('[name="security_phrase"]').val()
            };
            
            // Add type-specific fields
            if (type === 'btc') {
                data.btc_address = $form.find('[name="btc_address"]').val();
            } else if (type === 'usdt') {
                data.usdt_address = $form.find('[name="usdt_address"]').val();
            } else if (type === 'bank') {
                data.bank_name = $form.find('[name="bank_name"]').val();
                data.account_name = $form.find('[name="account_name"]').val();
                data.account_number = $form.find('[name="account_number"]').val();
                data.swift_code = $form.find('[name="swift_code"]').val();
                data.bank_address = $form.find('[name="bank_address"]').val();
                data.country = $form.find('[name="country"]').val();
            }
            
            $.ajax({
                url: gsp_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    $btn.removeClass('loading');
                    if (response.success) {
                        Toast.show(response.data.message, 'success');
                        Modal.close($form.closest('.gsp-modal'));
                        Forms.refreshTransactions();
                        Forms.refreshBalance();
                    } else {
                        Toast.show(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.removeClass('loading');
                    Toast.show('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        refreshTransactions: function() {
            if (Forms.transactionsRequest) {
                return;
            }

            Forms.transactionsRequest = $.ajax({
                url: gsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_get_transactions',
                    nonce: gsp_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.transactions) {
                        Forms.updateTransactionsTable(response.data.transactions);
                    }
                },
                complete: function() {
                    Forms.transactionsRequest = null;
                }
            });
        },
        
        refreshBalance: function() {
            if (Forms.balanceRequest) {
                return;
            }

            Forms.balanceRequest = $.ajax({
                url: gsp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_get_balance',
                    nonce: gsp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#gsp-wallet-balance').text('$' + response.data.wallet_balance);
                        $('#gsp-savings-balance').text('$' + response.data.savings_balance);
                    }
                },
                complete: function() {
                    Forms.balanceRequest = null;
                }
            });
        },
        
        updateTransactionsTable: function(transactions) {
            const $tbody = $('#gsp-transactions-table tbody');
            $tbody.empty();
            
            if (transactions.length === 0) {
                $tbody.append('<tr><td colspan="5" class="gsp-no-transactions">No transactions yet.</td></tr>');
                return;
            }
            
            transactions.forEach(function(tx) {
                const typeClass = 'gsp-type-' + tx.type.replace(/_/g, '-');
                const statusClass = 'gsp-status-' + tx.status;
                const date = new Date(tx.created_at);
                const formattedDate = date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
                
                const senderName = tx.sender_name || '';
                const row = `
                    <tr>
                        <td>
                            <span class="gsp-transaction-type ${typeClass}">
                                ${tx.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </span>
                        </td>
                        <td class="gsp-transaction-sender">${senderName}</td>
                        <td class="gsp-transaction-amount">$${parseFloat(tx.amount).toFixed(2)}</td>
                        <td>
                            <span class="gsp-status ${statusClass}">
                                ${tx.status.charAt(0).toUpperCase() + tx.status.slice(1)}
                            </span>
                        </td>
                        <td class="gsp-transaction-date">${formattedDate}</td>
                    </tr>
                `;
                
                $tbody.append(row);
            });
        }
    };

    // Copy to clipboard
    const Clipboard = {
        init: function() {
            $(document).on('click', '.gsp-copy-text', function() {
                const text = $(this).data('copy');
                if (text) {
                    navigator.clipboard.writeText(text).then(function() {
                        Toast.show('Copied to clipboard!', 'success');
                    }).catch(function() {
                        Toast.show('Failed to copy', 'error');
                    });
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        Modal.init();
        Forms.init();
        Clipboard.init();
        
        Forms.refreshBalance();
        Forms.refreshTransactions();

        // Auto-refresh balance and transactions every 5 seconds
        const refreshInterval = setInterval(function() {
            Forms.refreshBalance();
            Forms.refreshTransactions();
        }, 5000);

        $(window).on('beforeunload', function() {
            clearInterval(refreshInterval);
        });
    });

})(jQuery);
