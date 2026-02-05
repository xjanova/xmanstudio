<?php
/**
 * Pusher Broadcasting for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_Pusher class
 */
class SPC_Pusher {

    /**
     * Instance
     *
     * @var SPC_Pusher
     */
    private static $instance = null;

    /**
     * Pusher app key
     *
     * @var string
     */
    private $app_key;

    /**
     * Pusher app secret
     *
     * @var string
     */
    private $app_secret;

    /**
     * Pusher app ID
     *
     * @var string
     */
    private $app_id;

    /**
     * Pusher cluster
     *
     * @var string
     */
    private $cluster;

    /**
     * Broadcast channel
     *
     * @var string
     */
    private $channel = 'sms-checker.broadcast';

    /**
     * Get instance
     *
     * @return SPC_Pusher
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
        $this->app_key = get_option('spc_pusher_app_key');
        $this->app_secret = get_option('spc_pusher_app_secret');
        $this->app_id = get_option('spc_pusher_app_id');
        $this->cluster = get_option('spc_pusher_cluster', 'ap1');
    }

    /**
     * Check if Pusher is configured
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->app_key) && !empty($this->app_secret) && !empty($this->app_id);
    }

    /**
     * Authenticate Pusher channel subscription
     *
     * @param string $socket_id Socket ID.
     * @param string $channel_name Channel name.
     * @param object $device Device object.
     * @return array|false
     */
    public function auth($socket_id, $channel_name, $device) {
        if (!$this->is_configured()) {
            return false;
        }

        // Only allow our broadcast channel
        if ($channel_name !== $this->channel && strpos($channel_name, 'private-') !== 0) {
            return false;
        }

        $string_to_sign = $socket_id . ':' . $channel_name;
        $signature = hash_hmac('sha256', $string_to_sign, $this->app_secret);

        return array(
            'auth' => $this->app_key . ':' . $signature,
        );
    }

    /**
     * Broadcast event
     *
     * @param string $event Event name.
     * @param array  $data Event data.
     * @param string $channel Channel name (optional).
     * @return bool
     */
    public function broadcast($event, $data, $channel = null) {
        if (!$this->is_configured()) {
            $this->log('Pusher not configured');
            return false;
        }

        $channel = $channel ?: $this->channel;
        $url = sprintf(
            'https://api-%s.pusher.com/apps/%s/events',
            $this->cluster,
            $this->app_id
        );

        $body = array(
            'name' => $event,
            'channel' => $channel,
            'data' => wp_json_encode($data),
        );

        $body_json = wp_json_encode($body);
        $timestamp = time();

        // Generate auth signature
        $auth_string = $this->generate_auth_string('POST', '/apps/' . $this->app_id . '/events', $body_json, $timestamp);

        $response = wp_remote_post($url . '?' . $auth_string, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => $body_json,
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            $this->log('Pusher broadcast failed: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $this->log('Pusher broadcast failed with code ' . $code . ': ' . $body);
            return false;
        }

        return true;
    }

    /**
     * Generate authentication string for Pusher API
     *
     * @param string $method HTTP method.
     * @param string $path Request path.
     * @param string $body Request body.
     * @param int    $timestamp Unix timestamp.
     * @return string
     */
    private function generate_auth_string($method, $path, $body, $timestamp) {
        $params = array(
            'auth_key' => $this->app_key,
            'auth_timestamp' => $timestamp,
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        );

        ksort($params);
        $query_string = http_build_query($params);

        $string_to_sign = implode("\n", array(
            $method,
            $path,
            $query_string,
        ));

        $signature = hash_hmac('sha256', $string_to_sign, $this->app_secret);
        $params['auth_signature'] = $signature;

        return http_build_query($params);
    }

    /**
     * Broadcast payment matched event
     *
     * @param WC_Order $order Order object.
     * @param object   $notification Notification object.
     * @return bool
     */
    public function broadcast_payment_matched($order, $notification) {
        return $this->broadcast('payment.matched', array(
            'order' => array(
                'id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'total' => (float) $order->get_total(),
                'unique_amount' => (float) $order->get_meta('_spc_unique_amount'),
                'status' => $order->get_status(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            ),
            'notification' => array(
                'id' => $notification->id,
                'bank' => $notification->bank,
                'amount' => (float) $notification->amount,
                'type' => $notification->type,
            ),
            'timestamp' => current_time('c'),
        ));
    }

    /**
     * Broadcast new order event
     *
     * @param WC_Order $order Order object.
     * @return bool
     */
    public function broadcast_new_order($order) {
        return $this->broadcast('order.created', array(
            'order' => array(
                'id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'total' => (float) $order->get_total(),
                'unique_amount' => (float) $order->get_meta('_spc_unique_amount'),
                'status' => $order->get_status(),
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'customer_email' => $order->get_billing_email(),
                'created_at' => $order->get_date_created()->format('c'),
            ),
            'timestamp' => current_time('c'),
        ));
    }

    /**
     * Broadcast order status changed event
     *
     * @param WC_Order $order Order object.
     * @param string   $new_status New status.
     * @return bool
     */
    public function broadcast_order_status_changed($order, $new_status) {
        return $this->broadcast('order.status_changed', array(
            'order' => array(
                'id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'total' => (float) $order->get_total(),
                'status' => $order->get_status(),
                'verification_status' => $new_status,
            ),
            'old_status' => $order->get_meta('_spc_verification_status'),
            'new_status' => $new_status,
            'timestamp' => current_time('c'),
        ));
    }

    /**
     * Log message
     *
     * @param string $message Log message.
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SPC Pusher] ' . $message);
        }
    }
}
