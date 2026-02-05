<?php
/**
 * WooCommerce Payment Gateway for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_WC_Gateway class
 */
class SPC_WC_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'spc_bank_transfer';
        $this->icon = '';
        $this->has_fields = true;
        $this->method_title = __('Bank Transfer (SMS Verified)', 'sms-payment-checker');
        $this->method_description = __('Accept bank transfers with automatic SMS verification.', 'sms-payment-checker');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define properties
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->instructions = $this->get_option('instructions');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'sms-payment-checker'),
                'type' => 'checkbox',
                'label' => __('Enable Bank Transfer (SMS Verified)', 'sms-payment-checker'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Title', 'sms-payment-checker'),
                'type' => 'text',
                'description' => __('Payment method title that the customer will see at checkout.', 'sms-payment-checker'),
                'default' => __('Bank Transfer', 'sms-payment-checker'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'sms-payment-checker'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see at checkout.', 'sms-payment-checker'),
                'default' => __('Transfer to our bank account. Your order will be confirmed automatically after payment verification.', 'sms-payment-checker'),
            ),
            'instructions' => array(
                'title' => __('Instructions', 'sms-payment-checker'),
                'type' => 'textarea',
                'description' => __('Instructions shown on the thank you page and in emails. Use {amount} for unique amount, {bank_name} for bank name, {account_number} for account number.', 'sms-payment-checker'),
                'default' => __("Please transfer exactly {amount} to:\n\nBank: {bank_name}\nAccount: {account_number}\nName: {account_name}\n\nYour order will be confirmed automatically after payment verification.", 'sms-payment-checker'),
            ),
            'bank_name' => array(
                'title' => __('Bank Name', 'sms-payment-checker'),
                'type' => 'text',
                'description' => __('Your bank name.', 'sms-payment-checker'),
                'default' => '',
            ),
            'account_number' => array(
                'title' => __('Account Number', 'sms-payment-checker'),
                'type' => 'text',
                'description' => __('Your bank account number.', 'sms-payment-checker'),
                'default' => '',
            ),
            'account_name' => array(
                'title' => __('Account Name', 'sms-payment-checker'),
                'type' => 'text',
                'description' => __('The name on your bank account.', 'sms-payment-checker'),
                'default' => '',
            ),
        );
    }

    /**
     * Process the payment
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Generate unique payment amount
        $base_amount = $order->get_total();
        $unique_amount = SPC_Matching::instance()->generate_unique_amount($base_amount, $order_id);

        if (!$unique_amount) {
            wc_add_notice(__('Unable to generate payment amount. Please try again later.', 'sms-payment-checker'), 'error');
            return array(
                'result' => 'fail',
            );
        }

        // Update order
        $order->update_meta_data('_spc_unique_amount_id', $unique_amount->id);
        $order->update_meta_data('_spc_unique_amount', $unique_amount->unique_amount);
        $order->update_meta_data('_spc_verification_status', 'pending');
        $order->update_meta_data('_spc_amount_expires_at', $unique_amount->expires_at);
        $order->update_status('on-hold', __('Awaiting bank transfer payment.', 'sms-payment-checker'));
        $order->save();

        // Reduce stock
        wc_reduce_stock_levels($order_id);

        // Empty cart
        WC()->cart->empty_cart();

        // Broadcast new order event
        if (get_option('spc_pusher_app_key')) {
            SPC_Pusher::instance()->broadcast_new_order($order);
        }

        // Send FCM notification
        if (get_option('spc_fcm_on_new_order')) {
            SPC_FCM::instance()->notify_new_order($order);
        }

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    /**
     * Output for the thank you page
     *
     * @param int $order_id Order ID.
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $unique_amount = $order->get_meta('_spc_unique_amount');
        $expires_at = $order->get_meta('_spc_amount_expires_at');
        $verification_status = $order->get_meta('_spc_verification_status');

        if ($verification_status === 'confirmed') {
            echo '<div class="spc-payment-confirmed">';
            echo '<h3>' . esc_html__('Payment Confirmed!', 'sms-payment-checker') . '</h3>';
            echo '<p>' . esc_html__('Your payment has been verified successfully.', 'sms-payment-checker') . '</p>';
            echo '</div>';
            return;
        }

        $instructions = $this->instructions;
        $instructions = str_replace('{amount}', wc_price($unique_amount), $instructions);
        $instructions = str_replace('{bank_name}', $this->get_option('bank_name'), $instructions);
        $instructions = str_replace('{account_number}', $this->get_option('account_number'), $instructions);
        $instructions = str_replace('{account_name}', $this->get_option('account_name'), $instructions);

        echo '<div class="spc-payment-instructions">';
        echo '<h3>' . esc_html__('Payment Instructions', 'sms-payment-checker') . '</h3>';
        echo '<div class="spc-amount-highlight">';
        echo '<span class="spc-label">' . esc_html__('Amount to Transfer:', 'sms-payment-checker') . '</span>';
        echo '<span class="spc-amount">' . wc_price($unique_amount) . '</span>';
        echo '</div>';
        echo '<div class="spc-important-notice">';
        echo '<strong>' . esc_html__('Important:', 'sms-payment-checker') . '</strong> ';
        echo esc_html__('Please transfer the exact amount shown above for automatic verification.', 'sms-payment-checker');
        echo '</div>';
        echo '<div class="spc-instructions-text">' . wp_kses_post(nl2br($instructions)) . '</div>';

        if ($expires_at) {
            $expires_timestamp = strtotime($expires_at);
            $time_left = $expires_timestamp - current_time('timestamp');
            if ($time_left > 0) {
                $minutes = ceil($time_left / 60);
                echo '<div class="spc-expires">';
                echo sprintf(
                    esc_html__('This payment amount is valid for %d minutes.', 'sms-payment-checker'),
                    $minutes
                );
                echo '</div>';
            }
        }

        echo '</div>';

        // Add inline styles
        $this->output_thankyou_styles();
    }

    /**
     * Add content to the WC emails
     *
     * @param WC_Order $order Order object.
     * @param bool     $sent_to_admin Sent to admin.
     * @param bool     $plain_text Plain text.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($this->id !== $order->get_payment_method()) {
            return;
        }

        if ($sent_to_admin) {
            return;
        }

        $unique_amount = $order->get_meta('_spc_unique_amount');
        if (!$unique_amount) {
            return;
        }

        $instructions = $this->instructions;
        $instructions = str_replace('{amount}', wc_price($unique_amount), $instructions);
        $instructions = str_replace('{bank_name}', $this->get_option('bank_name'), $instructions);
        $instructions = str_replace('{account_number}', $this->get_option('account_number'), $instructions);
        $instructions = str_replace('{account_name}', $this->get_option('account_name'), $instructions);

        if ($plain_text) {
            echo "\n" . __('Payment Instructions', 'sms-payment-checker') . "\n\n";
            echo __('Amount to Transfer:', 'sms-payment-checker') . ' ' . strip_tags(wc_price($unique_amount)) . "\n\n";
            echo wp_strip_all_tags($instructions) . "\n\n";
        } else {
            echo '<h2>' . esc_html__('Payment Instructions', 'sms-payment-checker') . '</h2>';
            echo '<p><strong>' . esc_html__('Amount to Transfer:', 'sms-payment-checker') . '</strong> ' . wc_price($unique_amount) . '</p>';
            echo '<p>' . wp_kses_post(nl2br($instructions)) . '</p>';
        }
    }

    /**
     * Output thank you page styles
     */
    private function output_thankyou_styles() {
        ?>
        <style>
            .spc-payment-instructions {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            .spc-payment-instructions h3 {
                margin-top: 0;
                color: #333;
            }
            .spc-amount-highlight {
                background: #007bff;
                color: #fff;
                padding: 15px 20px;
                border-radius: 8px;
                margin: 15px 0;
                text-align: center;
            }
            .spc-amount-highlight .spc-label {
                display: block;
                font-size: 14px;
                opacity: 0.9;
            }
            .spc-amount-highlight .spc-amount {
                display: block;
                font-size: 28px;
                font-weight: bold;
                margin-top: 5px;
            }
            .spc-important-notice {
                background: #fff3cd;
                border: 1px solid #ffc107;
                color: #856404;
                padding: 10px 15px;
                border-radius: 4px;
                margin: 15px 0;
            }
            .spc-instructions-text {
                padding: 15px;
                background: #fff;
                border-radius: 4px;
                margin: 15px 0;
            }
            .spc-expires {
                text-align: center;
                color: #dc3545;
                font-size: 14px;
                margin-top: 15px;
            }
            .spc-payment-confirmed {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                text-align: center;
            }
            .spc-payment-confirmed h3 {
                margin-top: 0;
                color: #155724;
            }
        </style>
        <?php
    }
}
