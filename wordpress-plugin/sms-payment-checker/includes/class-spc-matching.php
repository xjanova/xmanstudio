<?php
/**
 * Order matching logic for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_Matching class
 */
class SPC_Matching {

    /**
     * Instance
     *
     * @var SPC_Matching
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SPC_Matching
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Nothing to initialize
    }

    /**
     * Generate a unique payment amount
     *
     * @param float $base_amount   Base amount.
     * @param int   $order_id      Order ID.
     * @param int   $expiry_minutes Expiry time in minutes.
     * @return object|false
     */
    public function generate_unique_amount($base_amount, $order_id = null, $expiry_minutes = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_unique_amounts';

        if (is_null($expiry_minutes)) {
            $expiry_minutes = (int) get_option('spc_amount_expiry', 30);
        }

        $max_pending = (int) get_option('spc_max_pending_per_amount', 99);
        $base_amount = floor($base_amount);

        // Find available decimal suffix
        $used_suffixes = $wpdb->get_col($wpdb->prepare(
            "SELECT decimal_suffix FROM $table WHERE base_amount = %f AND status = 'reserved' AND expires_at > NOW()",
            $base_amount
        ));

        $available_suffix = null;
        for ($i = 1; $i <= $max_pending; $i++) {
            if (!in_array($i, $used_suffixes)) {
                $available_suffix = $i;
                break;
            }
        }

        if (is_null($available_suffix)) {
            return false;
        }

        $unique_amount = $base_amount + ($available_suffix / 100);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));

        $result = $wpdb->insert($table, array(
            'order_id' => $order_id,
            'base_amount' => $base_amount,
            'unique_amount' => $unique_amount,
            'decimal_suffix' => $available_suffix,
            'status' => 'reserved',
            'expires_at' => $expires_at,
        ));

        if ($result) {
            $id = $wpdb->insert_id;
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        }

        return false;
    }

    /**
     * Attempt to match a notification to an order
     *
     * @param object $notification Notification object.
     * @param object $device       Device object.
     * @return bool Whether matched.
     */
    public function attempt_match($notification, $device) {
        global $wpdb;

        // Only match credit transactions
        if ($notification->type !== 'credit') {
            return false;
        }

        $amount = (float) $notification->amount;

        // Find matching unique amount
        $amounts_table = $wpdb->prefix . 'spc_unique_amounts';
        $unique_amount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $amounts_table WHERE unique_amount = %f AND status = 'reserved' AND expires_at > NOW() ORDER BY created_at ASC LIMIT 1",
            $amount
        ));

        if (!$unique_amount || !$unique_amount->order_id) {
            return false;
        }

        // Get the WooCommerce order
        $order = wc_get_order($unique_amount->order_id);
        if (!$order) {
            return false;
        }

        // Check if order is pending payment
        if (!in_array($order->get_status(), array('pending', 'on-hold'))) {
            return false;
        }

        // Get approval mode
        $approval_mode = $device->approval_mode ?: get_option('spc_default_approval_mode', 'auto');

        // Match found!
        $wpdb->update(
            $amounts_table,
            array('status' => 'used'),
            array('id' => $unique_amount->id)
        );

        // Update notification
        $notification_table = $wpdb->prefix . 'spc_notifications';
        $new_status = ($approval_mode === 'auto') ? 'confirmed' : 'matched';
        $wpdb->update(
            $notification_table,
            array(
                'status' => $new_status,
                'matched_order_id' => $order->get_id(),
            ),
            array('id' => $notification->id)
        );

        // Update order meta
        $order->update_meta_data('_spc_verification_status', $new_status);
        $order->update_meta_data('_spc_notification_id', $notification->id);
        $order->update_meta_data('_spc_verified_at', current_time('mysql'));
        $order->update_meta_data('_spc_verified_bank', $notification->bank);
        $order->update_meta_data('_spc_verified_amount', $notification->amount);

        // Auto-complete order if auto mode
        if ($approval_mode === 'auto') {
            $order->update_meta_data('_spc_verification_status', 'confirmed');
            $order->payment_complete();
            $order->add_order_note(sprintf(
                __('Payment verified via SMS Checker. Bank: %s, Amount: %s', 'sms-payment-checker'),
                $notification->bank,
                wc_price($notification->amount)
            ));
        } else {
            $order->add_order_note(sprintf(
                __('Payment matched via SMS Checker (awaiting approval). Bank: %s, Amount: %s', 'sms-payment-checker'),
                $notification->bank,
                wc_price($notification->amount)
            ));
        }

        $order->save();

        // Broadcast payment matched via Pusher
        if (get_option('spc_pusher_app_key')) {
            SPC_Pusher::instance()->broadcast_payment_matched($order, $notification);
        }

        // Send FCM notification
        if (get_option('spc_fcm_on_match')) {
            SPC_FCM::instance()->notify_payment_matched($order, $notification);
        }

        return true;
    }

    /**
     * Get orders for approval
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_orders($args = array()) {
        $defaults = array(
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'limit' => 20,
            'page' => 1,
        );

        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'limit' => $args['limit'],
            'page' => $args['page'],
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_spc_unique_amount_id',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        // Filter by verification status
        if (!empty($args['status'])) {
            $query_args['meta_query'][] = array(
                'key' => '_spc_verification_status',
                'value' => $args['status'],
            );
        }

        // Date filters
        if (!empty($args['date_from'])) {
            $query_args['date_created'] = '>=' . $args['date_from'];
        }
        if (!empty($args['date_to'])) {
            if (isset($query_args['date_created'])) {
                $query_args['date_created'] = array(
                    '>=' . $args['date_from'],
                    '<=' . $args['date_to'],
                );
            } else {
                $query_args['date_created'] = '<=' . $args['date_to'];
            }
        }

        $orders = wc_get_orders($query_args);

        return array_map(function($order) {
            return $this->transform_order($order);
        }, $orders);
    }

    /**
     * Transform WooCommerce order for API response
     *
     * @param WC_Order $order Order object.
     * @return array
     */
    public function transform_order($order) {
        $notification_id = $order->get_meta('_spc_notification_id');
        $notification = null;

        if ($notification_id) {
            $notification = SPC_Notification::instance()->get($notification_id);
        }

        return array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer_email' => $order->get_billing_email(),
            'total' => (float) $order->get_total(),
            'unique_amount' => (float) $order->get_meta('_spc_unique_amount'),
            'payment_status' => $order->get_status(),
            'sms_verification_status' => $order->get_meta('_spc_verification_status') ?: 'pending',
            'sms_verified_at' => $order->get_meta('_spc_verified_at'),
            'notification' => $notification ? array(
                'id' => $notification->id,
                'bank' => $notification->bank,
                'amount' => (float) $notification->amount,
                'sms_timestamp' => $notification->sms_timestamp,
            ) : null,
            'created_at' => $order->get_date_created()->format('c'),
        );
    }

    /**
     * Approve an order
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public function approve_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $status = $order->get_meta('_spc_verification_status');
        if (!in_array($status, array('pending', 'matched'))) {
            return false;
        }

        $order->update_meta_data('_spc_verification_status', 'confirmed');
        $order->payment_complete();
        $order->add_order_note(__('Payment approved via SMS Checker', 'sms-payment-checker'));
        $order->save();

        // Update notification
        $notification_id = $order->get_meta('_spc_notification_id');
        if ($notification_id) {
            SPC_Notification::instance()->update_status($notification_id, 'confirmed');
        }

        return true;
    }

    /**
     * Reject an order
     *
     * @param int    $order_id Order ID.
     * @param string $reason   Rejection reason.
     * @return bool
     */
    public function reject_order($order_id, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $order->update_meta_data('_spc_verification_status', 'rejected');
        $order->update_status('failed', sprintf(
            __('Payment rejected via SMS Checker. Reason: %s', 'sms-payment-checker'),
            $reason ?: __('No reason provided', 'sms-payment-checker')
        ));
        $order->save();

        // Update notification
        $notification_id = $order->get_meta('_spc_notification_id');
        if ($notification_id) {
            SPC_Notification::instance()->update_status($notification_id, 'rejected');
        }

        // Cancel unique amount
        $unique_amount_id = $order->get_meta('_spc_unique_amount_id');
        if ($unique_amount_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'spc_unique_amounts';
            $wpdb->update($table, array('status' => 'cancelled'), array('id' => $unique_amount_id));
        }

        return true;
    }

    /**
     * Get dashboard statistics
     *
     * @param int $days Number of days.
     * @return array
     */
    public function get_stats($days = 7) {
        global $wpdb;

        $start_date = date('Y-m-d', strtotime("-{$days} days"));

        $stats = array(
            'total_orders' => 0,
            'auto_approved' => 0,
            'pending_review' => 0,
            'rejected' => 0,
            'total_amount' => 0,
        );

        // Get orders with SMS verification
        $orders = wc_get_orders(array(
            'limit' => -1,
            'date_created' => '>=' . $start_date,
            'meta_query' => array(
                array(
                    'key' => '_spc_unique_amount_id',
                    'compare' => 'EXISTS',
                ),
            ),
        ));

        foreach ($orders as $order) {
            $stats['total_orders']++;
            $status = $order->get_meta('_spc_verification_status');

            if ($status === 'confirmed') {
                $stats['auto_approved']++;
                $stats['total_amount'] += $order->get_total();
            } elseif (in_array($status, array('pending', 'matched'))) {
                $stats['pending_review']++;
            } elseif ($status === 'rejected') {
                $stats['rejected']++;
            }
        }

        return $stats;
    }
}
