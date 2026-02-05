<?php
/**
 * REST API for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_API class
 */
class SPC_API {

    /**
     * Instance
     *
     * @var SPC_API
     */
    private static $instance = null;

    /**
     * Namespace
     *
     * @var string
     */
    private $namespace = 'sms-payment/v1';

    /**
     * Get instance
     *
     * @return SPC_API
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
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Device endpoints
        register_rest_route($this->namespace, '/notify', array(
            'methods' => 'POST',
            'callback' => array($this, 'notify'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'status'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/register-device', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_device'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/orders/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => array($this, 'approve_order'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/orders/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_order'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/device-settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_device_settings'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/device-settings', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_device_settings'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/dashboard-stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_stats'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/register-fcm-token', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_fcm_token'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/pusher/auth', array(
            'methods' => 'POST',
            'callback' => array($this, 'pusher_auth'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/sync', array(
            'methods' => 'GET',
            'callback' => array($this, 'sync'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        register_rest_route($this->namespace, '/sync-version', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sync_version'),
            'permission_callback' => array($this, 'check_device_permission'),
        ));

        // Admin endpoints (requires authentication)
        register_rest_route($this->namespace, '/generate-amount', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_amount'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/notifications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notifications'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
    }

    /**
     * Check device permission
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_device_permission($request) {
        $api_key = $request->get_header('X-Api-Key');
        $device_id = $request->get_header('X-Device-Id');

        if (empty($api_key) || empty($device_id)) {
            return new WP_Error('unauthorized', __('Missing API key or Device ID', 'sms-payment-checker'), array('status' => 401));
        }

        $device = SPC_Device::instance()->get_by_api_key($api_key);
        if (!$device || $device->device_id !== $device_id) {
            return new WP_Error('unauthorized', __('Invalid credentials', 'sms-payment-checker'), array('status' => 401));
        }

        if ($device->status !== 'active') {
            return new WP_Error('forbidden', __('Device is not active', 'sms-payment-checker'), array('status' => 403));
        }

        // Store device in request for later use
        $request->set_param('_device', $device);

        // Update last active
        SPC_Device::instance()->update_last_active($device->id);

        return true;
    }

    /**
     * Check admin permission
     *
     * @param WP_REST_Request $request Request object.
     * @return bool
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Receive SMS notification
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function notify($request) {
        $device = $request->get_param('_device');
        $params = $request->get_json_params();

        // Check for encrypted payload
        if (!empty($params['encrypted'])) {
            $decrypted = SPC_Encryption::instance()->decrypt(
                $params['encrypted'],
                $device->secret_key
            );

            if (!$decrypted) {
                return new WP_Error('decryption_failed', __('Failed to decrypt payload', 'sms-payment-checker'), array('status' => 400));
            }

            $params = array_merge($params, $decrypted);
        }

        // Verify signature
        if (!empty($params['signature'])) {
            $payload_to_verify = $params;
            unset($payload_to_verify['signature']);
            unset($payload_to_verify['encrypted']);

            if (!SPC_Encryption::instance()->verify_signature($payload_to_verify, $params['signature'], $device->secret_key)) {
                return new WP_Error('invalid_signature', __('Invalid signature', 'sms-payment-checker'), array('status' => 400));
            }
        }

        // Process notification
        $result = SPC_Notification::instance()->process($params, $device);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array(
            'success' => true,
            'notification_id' => $result->id,
            'status' => $result->status,
            'matched_order_id' => $result->matched_order_id,
        ));
    }

    /**
     * Get device status
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function status($request) {
        $device = $request->get_param('_device');

        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';
        $pending_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE device_id = %s AND status IN ('pending', 'matched')",
            $device->device_id
        ));

        return rest_ensure_response(array(
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'status' => $device->status,
            'approval_mode' => $device->approval_mode,
            'pending_count' => (int) $pending_count,
            'last_active_at' => $device->last_active_at,
            'server_time' => current_time('c'),
        ));
    }

    /**
     * Register device
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function register_device($request) {
        $device = $request->get_param('_device');
        $params = $request->get_json_params();

        $update_data = array();

        if (!empty($params['device_name'])) {
            $update_data['device_name'] = sanitize_text_field($params['device_name']);
        }

        if (!empty($params['device_info'])) {
            $update_data['device_info'] = wp_json_encode($params['device_info']);
        }

        if (!empty($params['fcm_token'])) {
            $update_data['fcm_token'] = sanitize_text_field($params['fcm_token']);
        }

        if (!empty($update_data)) {
            SPC_Device::instance()->update($device->id, $update_data);
        }

        $device = SPC_Device::instance()->get($device->id);

        return rest_ensure_response(array(
            'success' => true,
            'device' => array(
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'status' => $device->status,
                'approval_mode' => $device->approval_mode,
            ),
        ));
    }

    /**
     * Get orders
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_orders($request) {
        $args = array(
            'status' => $request->get_param('status') ?: '',
            'date_from' => $request->get_param('date_from') ?: '',
            'date_to' => $request->get_param('date_to') ?: '',
            'limit' => $request->get_param('limit') ?: 20,
            'page' => $request->get_param('page') ?: 1,
        );

        $orders = SPC_Matching::instance()->get_orders($args);

        return rest_ensure_response(array(
            'success' => true,
            'orders' => $orders,
        ));
    }

    /**
     * Approve order
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function approve_order($request) {
        $order_id = (int) $request->get_param('id');

        $result = SPC_Matching::instance()->approve_order($order_id);

        if (!$result) {
            return new WP_Error('approval_failed', __('Failed to approve order', 'sms-payment-checker'), array('status' => 400));
        }

        // Broadcast event
        if (get_option('spc_pusher_app_key')) {
            $order = wc_get_order($order_id);
            SPC_Pusher::instance()->broadcast_order_status_changed($order, 'confirmed');
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Order approved successfully', 'sms-payment-checker'),
        ));
    }

    /**
     * Reject order
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function reject_order($request) {
        $order_id = (int) $request->get_param('id');
        $params = $request->get_json_params();
        $reason = isset($params['reason']) ? sanitize_text_field($params['reason']) : '';

        $result = SPC_Matching::instance()->reject_order($order_id, $reason);

        if (!$result) {
            return new WP_Error('rejection_failed', __('Failed to reject order', 'sms-payment-checker'), array('status' => 400));
        }

        // Broadcast event
        if (get_option('spc_pusher_app_key')) {
            $order = wc_get_order($order_id);
            SPC_Pusher::instance()->broadcast_order_status_changed($order, 'rejected');
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Order rejected successfully', 'sms-payment-checker'),
        ));
    }

    /**
     * Get device settings
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_device_settings($request) {
        $device = $request->get_param('_device');

        return rest_ensure_response(array(
            'success' => true,
            'settings' => array(
                'approval_mode' => $device->approval_mode ?: get_option('spc_default_approval_mode', 'auto'),
                'notification_enabled' => (bool) get_option('spc_fcm_enabled', true),
                'sync_interval' => (int) get_option('spc_sync_interval', 30),
                'supported_banks' => $this->get_supported_banks(),
            ),
        ));
    }

    /**
     * Update device settings
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function update_device_settings($request) {
        $device = $request->get_param('_device');
        $params = $request->get_json_params();

        $update_data = array();

        if (isset($params['approval_mode']) && in_array($params['approval_mode'], array('auto', 'manual', 'smart'))) {
            $update_data['approval_mode'] = $params['approval_mode'];
        }

        if (!empty($update_data)) {
            SPC_Device::instance()->update($device->id, $update_data);
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Settings updated successfully', 'sms-payment-checker'),
        ));
    }

    /**
     * Get dashboard statistics
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_dashboard_stats($request) {
        $days = $request->get_param('days') ?: 7;
        $stats = SPC_Matching::instance()->get_stats($days);

        return rest_ensure_response(array(
            'success' => true,
            'stats' => $stats,
        ));
    }

    /**
     * Register FCM token
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function register_fcm_token($request) {
        $device = $request->get_param('_device');
        $params = $request->get_json_params();

        if (empty($params['fcm_token'])) {
            return new WP_Error('missing_token', __('FCM token is required', 'sms-payment-checker'), array('status' => 400));
        }

        SPC_Device::instance()->update($device->id, array(
            'fcm_token' => sanitize_text_field($params['fcm_token']),
        ));

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('FCM token registered successfully', 'sms-payment-checker'),
        ));
    }

    /**
     * Pusher auth endpoint
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function pusher_auth($request) {
        $device = $request->get_param('_device');
        $params = $request->get_json_params();

        if (empty($params['socket_id']) || empty($params['channel_name'])) {
            return new WP_Error('missing_params', __('socket_id and channel_name are required', 'sms-payment-checker'), array('status' => 400));
        }

        $auth = SPC_Pusher::instance()->auth($params['socket_id'], $params['channel_name'], $device);

        if (!$auth) {
            return new WP_Error('auth_failed', __('Pusher authentication failed', 'sms-payment-checker'), array('status' => 403));
        }

        return rest_ensure_response($auth);
    }

    /**
     * Sync endpoint - get changes since last sync
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function sync($request) {
        global $wpdb;

        $since_version = (int) $request->get_param('since_version');
        $current_version = (int) get_option('spc_sync_version', 1);

        // Get notifications changed since version
        $notifications_table = $wpdb->prefix . 'spc_notifications';
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $notifications_table WHERE sync_version > %d ORDER BY sync_version ASC LIMIT 100",
            $since_version
        ));

        // Get orders with SMS verification changed since version
        $orders = array();
        $wc_orders = wc_get_orders(array(
            'limit' => 100,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_spc_sync_version',
                    'value' => $since_version,
                    'compare' => '>',
                    'type' => 'NUMERIC',
                ),
            ),
        ));

        foreach ($wc_orders as $order) {
            $orders[] = SPC_Matching::instance()->transform_order($order);
        }

        return rest_ensure_response(array(
            'success' => true,
            'current_version' => $current_version,
            'notifications' => $notifications,
            'orders' => $orders,
            'has_more' => count($notifications) >= 100 || count($orders) >= 100,
        ));
    }

    /**
     * Get current sync version
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_sync_version($request) {
        return rest_ensure_response(array(
            'success' => true,
            'version' => (int) get_option('spc_sync_version', 1),
            'server_time' => current_time('c'),
        ));
    }

    /**
     * Generate unique payment amount
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_amount($request) {
        $params = $request->get_json_params();

        if (empty($params['amount'])) {
            return new WP_Error('missing_amount', __('Amount is required', 'sms-payment-checker'), array('status' => 400));
        }

        $order_id = isset($params['order_id']) ? (int) $params['order_id'] : null;
        $result = SPC_Matching::instance()->generate_unique_amount((float) $params['amount'], $order_id);

        if (!$result) {
            return new WP_Error('generation_failed', __('Failed to generate unique amount. All slots may be in use.', 'sms-payment-checker'), array('status' => 400));
        }

        // Update order with unique amount
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_spc_unique_amount_id', $result->id);
                $order->update_meta_data('_spc_unique_amount', $result->unique_amount);
                $order->update_meta_data('_spc_verification_status', 'pending');
                $order->save();

                // Broadcast new order event
                if (get_option('spc_pusher_app_key')) {
                    SPC_Pusher::instance()->broadcast_new_order($order);
                }

                // Send FCM notification
                if (get_option('spc_fcm_on_new_order')) {
                    SPC_FCM::instance()->notify_new_order($order);
                }
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'unique_amount' => $result->unique_amount,
            'expires_at' => $result->expires_at,
        ));
    }

    /**
     * Get notifications history
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_notifications($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';

        $page = (int) ($request->get_param('page') ?: 1);
        $limit = (int) ($request->get_param('limit') ?: 20);
        $offset = ($page - 1) * $limit;

        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        return rest_ensure_response(array(
            'success' => true,
            'notifications' => $notifications,
            'pagination' => array(
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => ceil($total / $limit),
            ),
        ));
    }

    /**
     * Get supported banks
     *
     * @return array
     */
    private function get_supported_banks() {
        return array(
            'KBANK' => 'ธนาคารกสิกรไทย',
            'SCB' => 'ธนาคารไทยพาณิชย์',
            'KTB' => 'ธนาคารกรุงไทย',
            'BBL' => 'ธนาคารกรุงเทพ',
            'BAY' => 'ธนาคารกรุงศรีอยุธยา',
            'TTB' => 'ธนาคารทหารไทยธนชาต',
            'GSB' => 'ธนาคารออมสิน',
            'BAAC' => 'ธนาคาร ธ.ก.ส.',
            'CIMB' => 'ธนาคารซีไอเอ็มบี',
            'UOB' => 'ธนาคารยูโอบี',
            'LH' => 'ธนาคารแลนด์แอนด์เฮ้าส์',
            'KK' => 'ธนาคารเกียรตินาคินภัทร',
            'TISCO' => 'ธนาคารทิสโก้',
            'PROMPTPAY' => 'พร้อมเพย์',
            'TRUEWALLET' => 'ทรูมันนี่วอลเล็ท',
        );
    }
}
