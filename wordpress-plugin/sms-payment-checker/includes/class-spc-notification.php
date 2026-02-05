<?php
/**
 * SMS Notification handling for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_Notification class
 */
class SPC_Notification {

    /**
     * Instance
     *
     * @var SPC_Notification
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SPC_Notification
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
        // Schedule cleanup
        if (!wp_next_scheduled('spc_cleanup_expired')) {
            wp_schedule_event(time(), 'hourly', 'spc_cleanup_expired');
        }
        add_action('spc_cleanup_expired', array($this, 'cleanup_expired'));
    }

    /**
     * Process incoming SMS notification
     *
     * @param array  $payload   Decrypted payload.
     * @param object $device    Device object.
     * @param string $ip_address IP address.
     * @return array Result.
     */
    public function process($payload, $device, $ip_address) {
        global $wpdb;

        // Check for duplicate nonce
        $nonce_table = $wpdb->prefix . 'spc_nonces';
        $existing_nonce = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $nonce_table WHERE nonce = %s",
            $payload['nonce']
        ));

        if ($existing_nonce) {
            return array(
                'success' => false,
                'message' => 'Duplicate request (nonce already used)',
            );
        }

        // Record nonce
        $wpdb->insert($nonce_table, array(
            'nonce' => $payload['nonce'],
            'device_id' => $device->device_id,
            'used_at' => current_time('mysql'),
        ));

        // Create notification record
        $notification_table = $wpdb->prefix . 'spc_notifications';
        $sms_timestamp = date('Y-m-d H:i:s', $payload['sms_timestamp'] / 1000);

        $wpdb->insert($notification_table, array(
            'device_id' => $device->device_id,
            'bank' => $payload['bank'],
            'type' => $payload['type'],
            'amount' => $payload['amount'],
            'account_number' => isset($payload['account_number']) ? $payload['account_number'] : '',
            'sender_or_receiver' => isset($payload['sender_or_receiver']) ? $payload['sender_or_receiver'] : '',
            'reference_number' => isset($payload['reference_number']) ? $payload['reference_number'] : '',
            'sms_timestamp' => $sms_timestamp,
            'nonce' => $payload['nonce'],
            'status' => 'pending',
            'raw_payload' => wp_json_encode($payload),
            'ip_address' => $ip_address,
        ));

        $notification_id = $wpdb->insert_id;
        $notification = $this->get($notification_id);

        // Update device activity
        SPC_Device::instance()->update_activity($device->id, $ip_address);

        // Attempt auto-match for credit transactions
        $matched = false;
        if ($payload['type'] === 'credit') {
            $matched = SPC_Matching::instance()->attempt_match($notification, $device);
        }

        return array(
            'success' => true,
            'message' => $matched ? 'Payment matched and confirmed' : 'Notification recorded',
            'data' => array(
                'notification_id' => $notification_id,
                'status' => $matched ? 'matched' : 'pending',
                'matched' => $matched,
                'matched_transaction_id' => $notification->matched_order_id,
            ),
        );
    }

    /**
     * Get notification by ID
     *
     * @param int $id Notification ID.
     * @return object|null
     */
    public function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Get notifications
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';

        $defaults = array(
            'status' => '',
            'bank' => '',
            'type' => '',
            'device_id' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $where = '1=1';
        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(' AND status = %s', $args['status']);
        }
        if (!empty($args['bank'])) {
            $where .= $wpdb->prepare(' AND bank = %s', $args['bank']);
        }
        if (!empty($args['type'])) {
            $where .= $wpdb->prepare(' AND type = %s', $args['type']);
        }
        if (!empty($args['device_id'])) {
            $where .= $wpdb->prepare(' AND device_id = %s', $args['device_id']);
        }

        $sql = "SELECT * FROM $table WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, $args['limit'], $args['offset']));
    }

    /**
     * Update notification status
     *
     * @param int    $id       Notification ID.
     * @param string $status   New status.
     * @param int    $order_id Matched order ID.
     * @return bool
     */
    public function update_status($id, $status, $order_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';

        $data = array('status' => $status);
        if ($order_id) {
            $data['matched_order_id'] = $order_id;
        }

        return $wpdb->update($table, $data, array('id' => $id)) !== false;
    }

    /**
     * Cleanup expired nonces and notifications
     */
    public function cleanup_expired() {
        global $wpdb;

        $nonce_expiry_hours = (int) get_option('spc_nonce_expiry_hours', 24);

        // Delete old nonces
        $nonce_table = $wpdb->prefix . 'spc_nonces';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $nonce_table WHERE used_at < DATE_SUB(NOW(), INTERVAL %d HOUR)",
            $nonce_expiry_hours
        ));

        // Expire old pending notifications (older than 7 days)
        $notification_table = $wpdb->prefix . 'spc_notifications';
        $wpdb->query(
            "UPDATE $notification_table SET status = 'expired' WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        // Expire old unique amounts
        $amounts_table = $wpdb->prefix . 'spc_unique_amounts';
        $wpdb->query(
            "UPDATE $amounts_table SET status = 'expired' WHERE status = 'reserved' AND expires_at <= NOW()"
        );
    }

    /**
     * Count notifications
     *
     * @param string $status Optional status filter.
     * @return int
     */
    public function count($status = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';

        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE status = %s",
                $status
            ));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Get pending count for device
     *
     * @param string $device_id Device ID.
     * @return int
     */
    public function get_pending_count($device_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE device_id = %s AND status = 'pending'",
            $device_id
        ));
    }
}
