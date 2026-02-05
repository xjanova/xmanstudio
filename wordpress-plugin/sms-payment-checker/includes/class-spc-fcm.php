<?php
/**
 * FCM Push Notifications for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_FCM class
 */
class SPC_FCM {

    /**
     * Instance
     *
     * @var SPC_FCM
     */
    private static $instance = null;

    /**
     * FCM API URL
     *
     * @var string
     */
    private $api_url = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    /**
     * Access token cache
     *
     * @var string
     */
    private $access_token = null;

    /**
     * Token expiry time
     *
     * @var int
     */
    private $token_expiry = 0;

    /**
     * Get instance
     *
     * @return SPC_FCM
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
     * Send notification to device
     *
     * @param string $fcm_token FCM token.
     * @param array  $notification Notification data.
     * @param array  $data Additional data.
     * @return bool
     */
    public function send($fcm_token, $notification = array(), $data = array()) {
        $project_id = get_option('spc_firebase_project_id');
        if (empty($project_id)) {
            $this->log('Firebase project ID not configured');
            return false;
        }

        $access_token = $this->get_access_token();
        if (!$access_token) {
            $this->log('Failed to get Firebase access token');
            return false;
        }

        $url = sprintf($this->api_url, $project_id);

        $message = array(
            'token' => $fcm_token,
        );

        if (!empty($notification)) {
            $message['notification'] = $notification;
        }

        if (!empty($data)) {
            // Convert all values to strings
            $message['data'] = array_map('strval', $data);
        }

        // Android specific config
        $message['android'] = array(
            'priority' => 'high',
        );

        if (!empty($notification)) {
            $message['android']['notification'] = array(
                'channel_id' => 'sms_checker_channel',
                'sound' => 'default',
            );
        }

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array('message' => $message)),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            $this->log('FCM request failed: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $this->log('FCM request failed with code ' . $code . ': ' . $body);
            return false;
        }

        return true;
    }

    /**
     * Notify new order
     *
     * @param WC_Order $order Order object.
     * @return int Number of devices notified.
     */
    public function notify_new_order($order) {
        $devices = SPC_Device::instance()->get_active_devices();
        $count = 0;

        $notification = array(
            'title' => __('New Order', 'sms-payment-checker'),
            'body' => sprintf(
                __('Order #%s - %s waiting for payment', 'sms-payment-checker'),
                $order->get_order_number(),
                wc_price($order->get_meta('_spc_unique_amount') ?: $order->get_total())
            ),
        );

        $data = array(
            'type' => 'new_order',
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'amount' => $order->get_meta('_spc_unique_amount') ?: $order->get_total(),
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        );

        foreach ($devices as $device) {
            if (!empty($device->fcm_token)) {
                if ($this->send($device->fcm_token, $notification, $data)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Notify payment matched
     *
     * @param WC_Order $order Order object.
     * @param object   $sms_notification SMS notification object.
     * @return int Number of devices notified.
     */
    public function notify_payment_matched($order, $sms_notification) {
        $devices = SPC_Device::instance()->get_active_devices();
        $count = 0;

        $notification = array(
            'title' => __('Payment Matched!', 'sms-payment-checker'),
            'body' => sprintf(
                __('Order #%s matched with %s from %s', 'sms-payment-checker'),
                $order->get_order_number(),
                wc_price($sms_notification->amount),
                $sms_notification->bank
            ),
        );

        $data = array(
            'type' => 'payment_matched',
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'amount' => $sms_notification->amount,
            'bank' => $sms_notification->bank,
            'notification_id' => $sms_notification->id,
        );

        foreach ($devices as $device) {
            if (!empty($device->fcm_token)) {
                if ($this->send($device->fcm_token, $notification, $data)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Notify order status update
     *
     * @param WC_Order $order Order object.
     * @param string   $status New status.
     * @return int Number of devices notified.
     */
    public function notify_order_update($order, $status) {
        $devices = SPC_Device::instance()->get_active_devices();
        $count = 0;

        $status_labels = array(
            'confirmed' => __('Confirmed', 'sms-payment-checker'),
            'rejected' => __('Rejected', 'sms-payment-checker'),
            'pending' => __('Pending', 'sms-payment-checker'),
        );

        $notification = array(
            'title' => __('Order Updated', 'sms-payment-checker'),
            'body' => sprintf(
                __('Order #%s status changed to %s', 'sms-payment-checker'),
                $order->get_order_number(),
                isset($status_labels[$status]) ? $status_labels[$status] : $status
            ),
        );

        $data = array(
            'type' => 'order_update',
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $status,
        );

        foreach ($devices as $device) {
            if (!empty($device->fcm_token)) {
                if ($this->send($device->fcm_token, $notification, $data)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Trigger sync on all devices (silent push)
     *
     * @return int Number of devices notified.
     */
    public function trigger_sync() {
        $devices = SPC_Device::instance()->get_active_devices();
        $count = 0;

        $data = array(
            'type' => 'sync',
            'timestamp' => current_time('timestamp'),
        );

        foreach ($devices as $device) {
            if (!empty($device->fcm_token)) {
                if ($this->send($device->fcm_token, array(), $data)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get Firebase access token using service account
     *
     * @return string|false
     */
    private function get_access_token() {
        // Check cache
        if ($this->access_token && time() < $this->token_expiry - 60) {
            return $this->access_token;
        }

        // Check transient
        $cached_token = get_transient('spc_fcm_access_token');
        if ($cached_token) {
            $this->access_token = $cached_token;
            return $this->access_token;
        }

        // Get service account credentials
        $credentials_path = get_option('spc_firebase_credentials_path');
        if (empty($credentials_path) || !file_exists($credentials_path)) {
            // Try using stored credentials
            $credentials = get_option('spc_firebase_credentials');
            if (empty($credentials)) {
                $this->log('Firebase credentials not configured');
                return false;
            }
            $credentials = json_decode($credentials, true);
        } else {
            $credentials = json_decode(file_get_contents($credentials_path), true);
        }

        if (empty($credentials)) {
            $this->log('Invalid Firebase credentials');
            return false;
        }

        // Generate JWT
        $jwt = $this->create_jwt($credentials);
        if (!$jwt) {
            return false;
        }

        // Exchange JWT for access token
        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            $this->log('Token exchange failed: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            $this->log('Token exchange failed: ' . wp_json_encode($body));
            return false;
        }

        $this->access_token = $body['access_token'];
        $this->token_expiry = time() + ($body['expires_in'] ?? 3600);

        // Cache in transient
        set_transient('spc_fcm_access_token', $this->access_token, $body['expires_in'] - 60);

        return $this->access_token;
    }

    /**
     * Create JWT for service account authentication
     *
     * @param array $credentials Service account credentials.
     * @return string|false
     */
    private function create_jwt($credentials) {
        if (empty($credentials['private_key']) || empty($credentials['client_email'])) {
            $this->log('Invalid service account credentials');
            return false;
        }

        $now = time();
        $header = array(
            'alg' => 'RS256',
            'typ' => 'JWT',
        );

        $payload = array(
            'iss' => $credentials['client_email'],
            'sub' => $credentials['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        );

        $base64_header = $this->base64url_encode(wp_json_encode($header));
        $base64_payload = $this->base64url_encode(wp_json_encode($payload));

        $signature_input = $base64_header . '.' . $base64_payload;

        // Sign with private key
        $private_key = openssl_pkey_get_private($credentials['private_key']);
        if (!$private_key) {
            $this->log('Failed to load private key');
            return false;
        }

        $signature = '';
        if (!openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256)) {
            $this->log('Failed to sign JWT');
            return false;
        }

        return $base64_header . '.' . $base64_payload . '.' . $this->base64url_encode($signature);
    }

    /**
     * Base64 URL encode
     *
     * @param string $data Data to encode.
     * @return string
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Log message
     *
     * @param string $message Log message.
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SPC FCM] ' . $message);
        }
    }
}
