/**
 * GlobalSwiftPay Dashboard - Admin JavaScript
 */

(function($) {
    'use strict';

    // Admin Actions Handler
    const AdminActions = {
        errorMessage: 'An error occurred. Please try again.',
        init: function() {
            // Approve/Decline buttons
            $(document).on('click', '.gsp-admin-btn[data-action]', function() {
                const $btn = $(this);
                const action = $btn.data('action');
                const type = $btn.data('type');
                const id = $btn.data('id');
                
                const confirmMsg = action === 'approve' 
                    ? 'Are you sure you want to approve this request?' 
                    : 'Are you sure you want to decline this request?';
                
                if (confirm(confirmMsg)) {
                    AdminActions.updateStatus(type, id, action === 'approve' ? 'approved' : 'declined', $btn);
                }
            });
            
            // Edit balance button
            $(document).on('click', '.gsp-btn-edit-balance', function() {
                const userId = $(this).data('user-id');
                const currentBalance = $(this).data('balance');
                
                $('#edit-balance-user-id').val(userId);
                $('#edit-balance-amount').val(currentBalance);
                $('#gsp-edit-balance-modal').addClass('active');
            });

            // Add user button
            $(document).on('click', '#gsp-add-user', function() {
                $('#gsp-create-user-form')[0].reset();
                $('#gsp-create-user-modal').addClass('active');
            });

            // Edit user button
            $(document).on('click', '.gsp-btn-edit-user', function() {
                const $row = $(this).closest('tr');
                $('#edit-user-id').val($row.data('user-id'));
                $('#edit-username').val($row.data('username'));
                $('#edit-email').val($row.data('email'));
                $('#edit-display-name').val($row.data('display-name'));
                $('#edit-password').val('');
                $('#gsp-edit-user-modal').addClass('active');
            });

            // Delete user button
            $(document).on('click', '.gsp-btn-delete-user', function() {
                const userId = $(this).data('user-id');
                if (!confirm('Are you sure you want to delete this user?')) {
                    return;
                }
                AdminActions.deleteUser(userId, $(this));
            });
            
            // Edit balance form
            $('#gsp-edit-balance-form').on('submit', function(e) {
                e.preventDefault();
                AdminActions.updateUserBalance($(this));
            });

            // Create user form
            $('#gsp-create-user-form').on('submit', function(e) {
                e.preventDefault();
                AdminActions.createUser($(this));
            });

            // Edit user form
            $('#gsp-edit-user-form').on('submit', function(e) {
                e.preventDefault();
                AdminActions.updateUser($(this));
            });
            
            // Settings form
            $('#gsp-settings-form').on('submit', function(e) {
                e.preventDefault();
                AdminActions.saveSettings($(this));
            });

            // Wallet migration form
            $('#gsp-wallet-migration-form').on('submit', function(e) {
                e.preventDefault();
                AdminActions.runWalletMigration($(this));
            });

            $('#gsp-detect-wallet-sources').on('click', function() {
                AdminActions.detectWalletSources($(this));
            });

            $('#gsp-migrate-all-sources').on('click', function() {
                AdminActions.runAllWalletMigrations($(this));
            });
            
            // Modal close
            $(document).on('click', '.gsp-modal-close', function() {
                $(this).closest('.gsp-modal').removeClass('active');
            });
            
            // View details button
            $(document).on('click', '.gsp-view-details-btn', function() {
                const details = $(this).data('details');
                AdminActions.showDetailsModal(details);
            });
        },
        
        updateStatus: function(type, id, status, $btn) {
            const $row = $btn.closest('tr');
            $btn.prop('disabled', true).text('Processing...');
            
            const actionMap = {
                'deposit': 'gsp_admin_update_deposit',
                'withdrawal': 'gsp_admin_update_withdrawal',
                'transfer': 'gsp_admin_update_transfer',
                'conversion': 'gsp_admin_update_conversion'
            };
            
            const idMap = {
                'deposit': 'deposit_id',
                'withdrawal': 'withdrawal_id',
                'transfer': 'transfer_id',
                'conversion': 'conversion_id'
            };
            
            const data = {
                action: actionMap[type],
                nonce: gsp_admin_ajax.nonce,
                status: status,
                notes: ''
            };
            data[idMap[type]] = id;
            
            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // Update the row
                        const statusClass = status === 'approved' ? 'gsp-status-approved' : 'gsp-status-declined';
                        const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                        
                        $row.find('.gsp-status').removeClass('gsp-status-pending gsp-status-approved gsp-status-declined')
                            .addClass(statusClass)
                            .text(statusText);
                        
                        $row.find('.gsp-admin-actions').html('<span class="gsp-action-completed">' + statusText + '</span>');
                        
                        AdminActions.showNotification(response.data.message, 'success');
                    } else {
                        $btn.prop('disabled', false).text(status === 'approved' ? 'Approve' : 'Decline');
                        AdminActions.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(status === 'approved' ? 'Approve' : 'Decline');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },
        
        updateUserBalance: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');
            
            const userId = $('#edit-balance-user-id').val();
            const balance = $('#edit-balance-amount').val();
            
            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_update_user_balance',
                    nonce: gsp_admin_ajax.nonce,
                    user_id: userId,
                    balance: balance
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Save Balance');
                    
                    if (response.success) {
                        // Update the table
                        const $row = $('tr[data-user-id="' + userId + '"]');
                        $row.find('.gsp-user-wallet-balance').text('$' + parseFloat(balance).toFixed(2));
                        $row.find('.gsp-btn-edit-balance').data('balance', balance);
                        
                        $('#gsp-edit-balance-modal').removeClass('active');
                        AdminActions.showNotification(response.data.message, 'success');
                    } else {
                        AdminActions.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Save Balance');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },

        createUser: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Creating...');

            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_create_user',
                    nonce: gsp_admin_ajax.nonce,
                    username: $('#create-username').val(),
                    email: $('#create-email').val(),
                    display_name: $('#create-display-name').val(),
                    password: $('#create-password').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Create User');
                    if (response.success) {
                        AdminActions.showNotification(response.data.message, 'success');
                        location.reload();
                    } else {
                        AdminActions.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Create User');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },

        updateUser: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_update_user',
                    nonce: gsp_admin_ajax.nonce,
                    user_id: $('#edit-user-id').val(),
                    email: $('#edit-email').val(),
                    display_name: $('#edit-display-name').val(),
                    password: $('#edit-password').val()
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Save User');
                    if (response.success) {
                        AdminActions.showNotification(response.data.message, 'success');
                        location.reload();
                    } else {
                        AdminActions.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Save User');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },

        deleteUser: function(userId, $btn) {
            $btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_delete_user',
                    nonce: gsp_admin_ajax.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        AdminActions.showNotification(response.data.message, 'success');
                        $('tr[data-user-id="' + userId + '"]').remove();
                    } else {
                        $btn.prop('disabled', false).text('Delete');
                        AdminActions.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Delete');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },
        
        saveSettings: function($form) {
            const $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Saving...');
            
            const settings = {};
            $form.find('input[name^="settings"]').each(function() {
                // Use regex with global flag to replace all occurrences
                const name = $(this).attr('name').replace(/settings\[/g, '').replace(/\]/g, '');
                settings[name] = $(this).val();
            });
            
            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_update_settings',
                    nonce: gsp_admin_ajax.nonce,
                    settings: settings
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Save Settings');
                    
                    if (response.success) {
                        AdminActions.showNotification(response.data.message, 'success');
                    } else {
                        AdminActions.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Save Settings');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },

        detectWalletSources: function($button) {
            $button.prop('disabled', true).text('Detecting...');

            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_detect_wallet_sources',
                    nonce: gsp_admin_ajax.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Detect Sources');
                    if (response.success) {
                        const $select = $('#gsp-wallet-source');
                        $select.empty();
                        $select.append($('<option>').val('').text('Select a source'));
                        if (response.data.sources && response.data.sources.length) {
                            response.data.sources.forEach(function(source) {
                                $select.append($('<option>').val(source.id).text(source.label));
                            });
                            AdminActions.showNotification('Sources detected successfully.', 'success');
                        } else {
                            AdminActions.showNotification('No wallet sources found.', 'error');
                        }
                    } else {
                        AdminActions.showNotification(response.data.message || 'Failed to detect sources.', 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Detect Sources');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },

        runWalletMigration: function($form) {
            const $btn = $form.find('button[type="submit"]');
            const sourceId = $('#gsp-wallet-source').val();
            if (!sourceId) {
                AdminActions.showNotification('Select a wallet source before migrating.', 'error');
                return;
            }

            $btn.prop('disabled', true).text('Migrating...');

            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_migrate_wallet_balances',
                    nonce: gsp_admin_ajax.nonce,
                    source_id: sourceId
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Run Migration');
                    if (response.success) {
                        AdminActions.showNotification(response.data.message, 'success');
                    } else {
                        AdminActions.showNotification(response.data.message || 'Migration failed.', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Run Migration');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },

        runAllWalletMigrations: function($button) {
            $button.prop('disabled', true).text('Migrating...');

            $.ajax({
                url: gsp_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gsp_admin_migrate_all_wallet_sources',
                    nonce: gsp_admin_ajax.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Migrate All Sources');
                    if (response.success) {
                        AdminActions.showNotification(response.data.message, 'success');
                    } else {
                        AdminActions.showNotification(response.data.message || 'Migration failed.', 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Migrate All Sources');
                    AdminActions.showNotification(AdminActions.errorMessage, 'error');
                }
            });
        },
        
        showDetailsModal: function(details) {
            let html = '<div class="gsp-details-modal-content">';
            
            if (typeof details === 'object') {
                for (const key in details) {
                    if (details[key] && details[key] !== null) {
                        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        let value = details[key];
                        
                        if (typeof value === 'object') {
                            value = '<ul>';
                            for (const subKey in details[key]) {
                                const subLabel = subKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                value += '<li><strong>' + subLabel + ':</strong> ' + details[key][subKey] + '</li>';
                            }
                            value += '</ul>';
                        }
                        
                        html += '<p><strong>' + label + ':</strong> ' + value + '</p>';
                    }
                }
            }
            
            html += '</div>';
            
            // Create and show modal
            const modalHtml = `
                <div id="gsp-details-modal" class="gsp-modal active" style="display: flex;">
                    <div class="gsp-modal-content" style="max-width: 500px;">
                        <span class="gsp-modal-close">&times;</span>
                        <h2>Request Details</h2>
                        ${html}
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#gsp-details-modal').remove();
            
            // Add new modal
            $('body').append(modalHtml);
        },
        
        showNotification: function(message, type) {
            // Create notification element if it doesn't exist
            let $notification = $('#gsp-admin-notification');
            if ($notification.length === 0) {
                $notification = $('<div id="gsp-admin-notification" style="position: fixed; top: 50px; right: 20px; padding: 15px 25px; border-radius: 8px; z-index: 100001; font-weight: 500; box-shadow: 0 4px 20px rgba(0,0,0,0.2); transition: all 0.3s ease;"></div>');
                $('body').append($notification);
            }
            
            // Set styles based on type
            if (type === 'success') {
                $notification.css({
                    'background': 'linear-gradient(135deg, #00c853, #69f0ae)',
                    'color': '#ffffff'
                });
            } else {
                $notification.css({
                    'background': 'linear-gradient(135deg, #ff5252, #ff8a80)',
                    'color': '#ffffff'
                });
            }
            
            $notification.text(message).fadeIn();
            
            setTimeout(function() {
                $notification.fadeOut();
            }, 4000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AdminActions.init();
    });

})(jQuery);
