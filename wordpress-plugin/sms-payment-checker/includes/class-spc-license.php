<?php
/**
 * License Manager for SMS Payment Checker
 *
 * Handles license activation, validation, and status checks
 * with the XMAN Studio licensing API.
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

class SPC_License {

    /**
     * Singleton instance
     *
     * @var SPC_License
     */
    private static $instance = null;

    /**
     * License API base URL
     *
     * @var string
     */
    private $api_url = 'https://xmanstudio.com/api/v1/product/sms-payment-checker';

    /**
     * Option keys
     */
    const OPT_LICENSE_KEY = 'spc_license_key';
    const OPT_LICENSE_STATUS = 'spc_license_status';
    const OPT_LICENSE_EXPIRES = 'spc_license_expires_at';
    const OPT_LICENSE_TYPE = 'spc_license_type';
    const OPT_LICENSE_LAST_CHECK = 'spc_license_last_check';

    /**
     * Get instance
     *
     * @return SPC_License
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
        // Daily license validation via cron
        add_action('spc_daily_license_check', array($this, 'validate_license'));
        add_action('init', array($this, 'schedule_cron'));

        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));

        // AJAX handlers
        add_action('wp_ajax_spc_activate_license', array($this, 'ajax_activate'));
        add_action('wp_ajax_spc_deactivate_license', array($this, 'ajax_deactivate'));
    }

    /**
     * Schedule daily license check
     */
    public function schedule_cron() {
        if (!wp_next_scheduled('spc_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'spc_daily_license_check');
        }
    }

    /**
     * Get machine ID (WordPress site URL hash)
     *
     * @return string
     */
    public function get_machine_id() {
        return md5(get_site_url() . '|spc');
    }

    /**
     * Get machine fingerprint
     *
     * @return string
     */
    public function get_machine_fingerprint() {
        return sha1(get_site_url() . '|' . DB_NAME . '|' . AUTH_KEY);
    }

    /**
     * Activate license
     *
     * @param string $license_key License key to activate.
     * @return array Result with success status and message.
     */
    public function activate($license_key) {
        $license_key = strtoupper(trim($license_key));

        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => __('Please enter a license key.', 'sms-payment-checker'),
            );
        }

        $response = $this->api_request('activate', array(
            'license_key' => $license_key,
            'machine_id' => $this->get_machine_id(),
            'machine_fingerprint' => $this->get_machine_fingerprint(),
            'app_version' => SPC_VERSION,
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        if (!empty($response['success'])) {
            $data = $response['data'] ?? array();

            update_option(self::OPT_LICENSE_KEY, $license_key);
            update_option(self::OPT_LICENSE_STATUS, 'active');
            update_option(self::OPT_LICENSE_TYPE, $data['type'] ?? 'unknown');
            update_option(self::OPT_LICENSE_EXPIRES, $data['expires_at'] ?? '');
            update_option(self::OPT_LICENSE_LAST_CHECK, time());

            return array(
                'success' => true,
                'message' => $response['message'] ?? __('License activated successfully.', 'sms-payment-checker'),
                'data' => $data,
            );
        }

        return array(
            'success' => false,
            'message' => $response['error'] ?? __('Failed to activate license.', 'sms-payment-checker'),
            'code' => $response['code'] ?? 'UNKNOWN',
        );
    }

    /**
     * Validate current license
     *
     * @return array Result with validity status.
     */
    public function validate_license() {
        $license_key = get_option(self::OPT_LICENSE_KEY);

        if (empty($license_key)) {
            update_option(self::OPT_LICENSE_STATUS, 'inactive');
            return array('is_valid' => false);
        }

        $response = $this->api_request('validate', array(
            'license_key' => $license_key,
            'machine_id' => $this->get_machine_id(),
        ));

        if (is_wp_error($response)) {
            // Don't invalidate on network errors - keep current status
            return array('is_valid' => get_option(self::OPT_LICENSE_STATUS) === 'active');
        }

        $is_valid = !empty($response['is_valid']);
        $data = $response['data'] ?? array();

        update_option(self::OPT_LICENSE_STATUS, $is_valid ? 'active' : 'expired');
        update_option(self::OPT_LICENSE_LAST_CHECK, time());

        if (!empty($data['expires_at'])) {
            update_option(self::OPT_LICENSE_EXPIRES, $data['expires_at']);
        }
        if (!empty($data['type'])) {
            update_option(self::OPT_LICENSE_TYPE, $data['type']);
        }

        return array(
            'is_valid' => $is_valid,
            'data' => $data,
        );
    }

    /**
     * Deactivate license
     *
     * @return array Result.
     */
    public function deactivate() {
        $license_key = get_option(self::OPT_LICENSE_KEY);

        if (empty($license_key)) {
            return array('success' => false, 'message' => __('No license to deactivate.', 'sms-payment-checker'));
        }

        $response = $this->api_request('deactivate', array(
            'license_key' => $license_key,
            'machine_id' => $this->get_machine_id(),
        ));

        // Clear local license data regardless of API response
        delete_option(self::OPT_LICENSE_KEY);
        update_option(self::OPT_LICENSE_STATUS, 'inactive');
        delete_option(self::OPT_LICENSE_EXPIRES);
        delete_option(self::OPT_LICENSE_TYPE);

        if (is_wp_error($response)) {
            return array('success' => true, 'message' => __('License deactivated locally.', 'sms-payment-checker'));
        }

        return array(
            'success' => true,
            'message' => $response['message'] ?? __('License deactivated successfully.', 'sms-payment-checker'),
        );
    }

    /**
     * Check if license is active
     *
     * @return bool
     */
    public function is_active() {
        return get_option(self::OPT_LICENSE_STATUS) === 'active';
    }

    /**
     * Get license info
     *
     * @return array
     */
    public function get_info() {
        return array(
            'key' => get_option(self::OPT_LICENSE_KEY, ''),
            'status' => get_option(self::OPT_LICENSE_STATUS, 'inactive'),
            'type' => get_option(self::OPT_LICENSE_TYPE, ''),
            'expires_at' => get_option(self::OPT_LICENSE_EXPIRES, ''),
            'last_check' => get_option(self::OPT_LICENSE_LAST_CHECK, 0),
        );
    }

    /**
     * Get days remaining
     *
     * @return int|null
     */
    public function days_remaining() {
        $expires = get_option(self::OPT_LICENSE_EXPIRES);

        if (empty($expires)) {
            $type = get_option(self::OPT_LICENSE_TYPE);
            return $type === 'lifetime' ? null : 0;
        }

        $expires_time = strtotime($expires);
        if (!$expires_time) {
            return 0;
        }

        $diff = $expires_time - time();
        return max(0, (int) ceil($diff / 86400));
    }

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data.
     * @return array|WP_Error
     */
    private function api_request($endpoint, $data = array()) {
        $url = trailingslashit($this->api_url) . $endpoint;

        $response = wp_remote_post($url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'body' => wp_json_encode($data),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if ($code >= 400) {
            return new WP_Error(
                'api_error',
                $result['error'] ?? sprintf(__('API error (HTTP %d)', 'sms-payment-checker'), $code)
            );
        }

        return $result ?: array();
    }

    /**
     * Admin notices for license status
     */
    public function admin_notices() {
        // Only show on SPC admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'spc') === false) {
            return;
        }

        $status = get_option(self::OPT_LICENSE_STATUS, 'inactive');
        $key = get_option(self::OPT_LICENSE_KEY);

        if (empty($key) || $status === 'inactive') {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>SMS Payment Checker:</strong> ';
            echo esc_html__('Please activate your license key to unlock all features.', 'sms-payment-checker');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=spc-settings#license')) . '">';
            echo esc_html__('Activate License', 'sms-payment-checker');
            echo '</a></p>';
            echo '</div>';
        } elseif ($status === 'expired') {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>SMS Payment Checker:</strong> ';
            echo esc_html__('Your license has expired. Please renew to continue receiving updates and support.', 'sms-payment-checker');
            echo ' <a href="https://xmanstudio.com/products/sms-payment-checker" target="_blank">';
            echo esc_html__('Renew License', 'sms-payment-checker');
            echo '</a></p>';
            echo '</div>';
        } else {
            $days = $this->days_remaining();
            if ($days !== null && $days <= 14 && $days > 0) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>SMS Payment Checker:</strong> ';
                echo sprintf(
                    esc_html__('Your license expires in %d days. Renew now to avoid interruption.', 'sms-payment-checker'),
                    $days
                );
                echo ' <a href="https://xmanstudio.com/products/sms-payment-checker" target="_blank">';
                echo esc_html__('Renew License', 'sms-payment-checker');
                echo '</a></p>';
                echo '</div>';
            }
        }
    }

    /**
     * AJAX: Activate license
     */
    public function ajax_activate() {
        check_ajax_referer('spc_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'sms-payment-checker')));
        }

        $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
        $result = $this->activate($license_key);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Deactivate license
     */
    public function ajax_deactivate() {
        check_ajax_referer('spc_license_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'sms-payment-checker')));
        }

        $result = $this->deactivate();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
