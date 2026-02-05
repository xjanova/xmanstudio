/**
 * SMS Payment Checker Admin JavaScript
 */
(function($) {
    'use strict';

    // Device Management
    var SPCAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Add device button
            $('#spc-add-device').on('click', this.showAddDeviceModal);

            // Add device form
            $('#spc-add-device-form').on('submit', this.handleAddDevice);

            // Show QR code
            $(document).on('click', '.spc-show-qr', this.handleShowQR);

            // Regenerate QR
            $(document).on('click', '.spc-regenerate-qr', this.handleRegenerateQR);

            // Delete device
            $(document).on('click', '.spc-delete-device', this.handleDeleteDevice);

            // Close modals
            $(document).on('click', '.spc-modal-close', this.closeModal);
            $(document).on('click', '.spc-modal', function(e) {
                if (e.target === this) {
                    SPCAdmin.closeModal();
                }
            });

            // ESC key to close modal
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    SPCAdmin.closeModal();
                }
            });
        },

        showAddDeviceModal: function(e) {
            e.preventDefault();
            $('#spc-device-modal').show();
            $('#device_name').focus();
        },

        handleAddDevice: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();

            $button.text('Creating...').prop('disabled', true);

            $.ajax({
                url: spcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'spc_generate_device',
                    nonce: spcAdmin.nonce,
                    device_name: $('#device_name').val(),
                    approval_mode: $('#approval_mode').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Close add modal and show QR
                        $('#spc-device-modal').hide();
                        $form[0].reset();

                        // Show QR code
                        SPCAdmin.showQRCode(response.data.qr_data);

                        // Reload page to show new device
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        alert(response.data.message || spcAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(spcAdmin.strings.error);
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        handleShowQR: function(e) {
            e.preventDefault();

            var deviceId = $(this).data('device-id');
            var $row = $(this).closest('tr');
            var deviceName = $row.find('td:first strong').text();

            // Get device data via AJAX
            $.ajax({
                url: spcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'spc_get_device_qr',
                    nonce: spcAdmin.nonce,
                    device_id: deviceId
                },
                success: function(response) {
                    if (response.success) {
                        SPCAdmin.showQRCode(response.data.qr_data);
                    } else {
                        // Try regenerating
                        SPCAdmin.handleRegenerateQR.call($row.find('.spc-regenerate-qr')[0], e);
                    }
                },
                error: function() {
                    alert(spcAdmin.strings.error);
                }
            });
        },

        handleRegenerateQR: function(e) {
            e.preventDefault();

            if (!confirm(spcAdmin.strings.confirmRegenerate)) {
                return;
            }

            var $button = $(this);
            var deviceId = $button.data('device-id');
            var originalText = $button.text();

            $button.text('...').prop('disabled', true);

            $.ajax({
                url: spcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'spc_regenerate_qr',
                    nonce: spcAdmin.nonce,
                    device_id: deviceId
                },
                success: function(response) {
                    if (response.success) {
                        SPCAdmin.showQRCode(response.data.qr_data);
                    } else {
                        alert(response.data.message || spcAdmin.strings.error);
                    }
                },
                error: function() {
                    alert(spcAdmin.strings.error);
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        handleDeleteDevice: function(e) {
            e.preventDefault();

            if (!confirm(spcAdmin.strings.confirmDelete)) {
                return;
            }

            var $button = $(this);
            var $row = $button.closest('tr');
            var deviceId = $button.data('device-id');

            $row.addClass('spc-loading');

            $.ajax({
                url: spcAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'spc_delete_device',
                    nonce: spcAdmin.nonce,
                    device_id: deviceId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || spcAdmin.strings.error);
                        $row.removeClass('spc-loading');
                    }
                },
                error: function() {
                    alert(spcAdmin.strings.error);
                    $row.removeClass('spc-loading');
                }
            });
        },

        showQRCode: function(qrData) {
            var $modal = $('#spc-qr-modal');
            var $container = $('#spc-qr-container');

            $container.empty();

            // Create canvas for QR code
            var canvas = document.createElement('canvas');
            $container.append(canvas);

            // Generate QR code
            if (typeof QRCode !== 'undefined') {
                QRCode.toCanvas(canvas, qrData, {
                    width: 300,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#ffffff'
                    }
                }, function(error) {
                    if (error) {
                        console.error(error);
                        $container.html('<p style="color:red;">Failed to generate QR code</p>');
                    }
                });
            } else {
                $container.html('<p style="color:red;">QR Code library not loaded</p>');
            }

            $modal.show();
        },

        closeModal: function() {
            $('.spc-modal').hide();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SPCAdmin.init();
    });

})(jQuery);
