<?php
/**
 * Device management for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_Device class
 */
class SPC_Device {

    /**
     * Instance
     *
     * @var SPC_Device
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SPC_Device
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
     * Get device by API key
     *
     * @param string $api_key API key.
     * @return object|null Device object or null.
     */
    public function get_by_api_key($api_key) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE api_key = %s AND status = 'active'",
            $api_key
        ));
    }

    /**
     * Get device by device ID
     *
     * @param string $device_id Device ID.
     * @return object|null Device object or null.
     */
    public function get_by_device_id($device_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE device_id = %s",
            $device_id
        ));
    }

    /**
     * Get device by ID
     *
     * @param int $id Device ID.
     * @return object|null Device object or null.
     */
    public function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * Get all devices
     *
     * @param array $args Query arguments.
     * @return array Devices.
     */
    public function get_all($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        $defaults = array(
            'status' => '',
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

        $sql = "SELECT * FROM $table WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";

        return $wpdb->get_results($wpdb->prepare($sql, $args['limit'], $args['offset']));
    }

    /**
     * Create a new device
     *
     * @param array $data Device data.
     * @return object|false Device object or false.
     */
    public function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        $device_id = isset($data['device_id']) ? $data['device_id'] : SPC_Encryption::generate_device_id();
        $api_key = isset($data['api_key']) ? $data['api_key'] : SPC_Encryption::generate_api_key();
        $secret_key = isset($data['secret_key']) ? $data['secret_key'] : SPC_Encryption::generate_secret_key();

        $result = $wpdb->insert($table, array(
            'device_id' => $device_id,
            'device_name' => isset($data['device_name']) ? $data['device_name'] : '',
            'api_key' => $api_key,
            'secret_key' => $secret_key,
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'approval_mode' => isset($data['approval_mode']) ? $data['approval_mode'] : get_option('spc_default_approval_mode', 'auto'),
        ));

        if ($result) {
            return $this->get($wpdb->insert_id);
        }

        return false;
    }

    /**
     * Update device
     *
     * @param int   $id   Device ID.
     * @param array $data Device data.
     * @return bool
     */
    public function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        $allowed_fields = array(
            'device_name', 'status', 'approval_mode', 'platform',
            'app_version', 'ip_address', 'last_active_at', 'fcm_token'
        );

        $update_data = array();
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false;
        }

        return $wpdb->update($table, $update_data, array('id' => $id)) !== false;
    }

    /**
     * Delete device
     *
     * @param int $id Device ID.
     * @return bool
     */
    public function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        return $wpdb->delete($table, array('id' => $id)) !== false;
    }

    /**
     * Update last active timestamp
     *
     * @param int    $id         Device ID.
     * @param string $ip_address IP address.
     * @return bool
     */
    public function update_activity($id, $ip_address = '') {
        return $this->update($id, array(
            'last_active_at' => current_time('mysql'),
            'ip_address' => $ip_address,
        ));
    }

    /**
     * Register FCM token
     *
     * @param int    $id        Device ID.
     * @param string $fcm_token FCM token.
     * @return bool
     */
    public function register_fcm_token($id, $fcm_token) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        // Remove token from other devices
        $wpdb->update(
            $table,
            array('fcm_token' => null),
            array('fcm_token' => $fcm_token)
        );

        // Set token for this device
        return $this->update($id, array('fcm_token' => $fcm_token));
    }

    /**
     * Get devices with FCM tokens
     *
     * @return array
     */
    public function get_with_fcm_tokens() {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        return $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'active' AND fcm_token IS NOT NULL AND fcm_token != ''"
        );
    }

    /**
     * Get QR code data for device
     *
     * @param object $device Device object.
     * @return array
     */
    public function get_qr_data($device) {
        return array(
            'type' => 'smschecker_config',
            'version' => 1,
            'url' => home_url(),
            'apiKey' => $device->api_key,
            'secretKey' => $device->secret_key,
            'deviceName' => $device->device_name,
            'deviceId' => $device->device_id,
        );
    }

    /**
     * Count devices
     *
     * @param string $status Optional status filter.
     * @return int
     */
    public function count($status = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE status = %s",
                $status
            ));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Get active devices
     *
     * @return array
     */
    public function get_active_devices() {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        return $wpdb->get_results("SELECT * FROM $table WHERE status = 'active'");
    }

    /**
     * Regenerate device credentials
     *
     * @param int $id Device ID.
     * @return object|false
     */
    public function regenerate_credentials($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        $device = $this->get($id);
        if (!$device) {
            return false;
        }

        $new_api_key = $this->generate_api_key();
        $new_secret_key = $this->generate_secret_key();

        $result = $wpdb->update(
            $table,
            array(
                'api_key' => $new_api_key,
                'secret_key' => $new_secret_key,
            ),
            array('id' => $id)
        );

        if ($result === false) {
            return false;
        }

        return $this->get($id);
    }

    /**
     * Update last active timestamp
     *
     * @param int $id Device ID.
     * @return bool
     */
    public function update_last_active($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_devices';

        return $wpdb->update(
            $table,
            array('last_active_at' => current_time('mysql')),
            array('id' => $id)
        ) !== false;
    }
}
