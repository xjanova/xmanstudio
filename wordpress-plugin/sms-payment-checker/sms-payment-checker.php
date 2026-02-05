<?php
/**
 * Plugin Name: SMS Payment Checker
 * Plugin URI: https://github.com/xjanova/smschecker
 * Description: Automatic bank transfer verification via SMS for WooCommerce. Works with SmsChecker Android app.
 * Version: 1.7.0
 * Author: XMANStudio
 * Author URI: https://xmanstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sms-payment-checker
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

// Plugin constants
define('SPC_VERSION', '1.7.0');
define('SPC_PLUGIN_FILE', __FILE__);
define('SPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
final class SMS_Payment_Checker {

    /**
     * Plugin instance
     *
     * @var SMS_Payment_Checker
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return SMS_Payment_Checker
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
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once SPC_PLUGIN_DIR . 'includes/class-spc-encryption.php';
        require_once SPC_PLUGIN_DIR . 'includes/class-spc-device.php';
        require_once SPC_PLUGIN_DIR . 'includes/class-spc-notification.php';
        require_once SPC_PLUGIN_DIR . 'includes/class-spc-matching.php';
        require_once SPC_PLUGIN_DIR . 'includes/class-spc-api.php';
        require_once SPC_PLUGIN_DIR . 'includes/class-spc-license.php';

        // Admin
        if (is_admin()) {
            require_once SPC_PLUGIN_DIR . 'admin/class-spc-admin.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(SPC_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SPC_PLUGIN_FILE, array($this, 'deactivate'));

        // Init
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // WooCommerce hooks
        add_action('woocommerce_order_status_changed', array($this, 'on_order_status_changed'), 10, 4);
        add_action('woocommerce_thankyou', array($this, 'on_thankyou'), 10, 1);

        // Register payment gateway
        add_filter('woocommerce_payment_gateways', array($this, 'add_payment_gateway'));
        add_action('plugins_loaded', array($this, 'load_payment_gateway'), 11);

        // HPOS compatibility
        add_action('before_woocommerce_init', function() {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', SPC_PLUGIN_FILE, true);
            }
        });
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        $this->create_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        wp_clear_scheduled_hook('spc_daily_license_check');
        flush_rewrite_rules();
    }

    /**
     * Check if license is active
     *
     * @return bool
     */
    public function has_valid_license() {
        return SPC_License::instance()->is_active();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Devices table
        $table_devices = $wpdb->prefix . 'spc_devices';
        $sql_devices = "CREATE TABLE $table_devices (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            device_id varchar(50) NOT NULL,
            device_name varchar(100) DEFAULT '',
            api_key varchar(64) NOT NULL,
            secret_key varchar(64) NOT NULL,
            fcm_token varchar(255) DEFAULT NULL,
            status enum('active','inactive','blocked') DEFAULT 'active',
            approval_mode enum('auto','manual','smart') DEFAULT 'auto',
            platform varchar(20) DEFAULT 'android',
            app_version varchar(20) DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            last_active_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY device_id (device_id),
            UNIQUE KEY api_key (api_key)
        ) $charset_collate;";

        // Notifications table
        $table_notifications = $wpdb->prefix . 'spc_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            device_id varchar(50) NOT NULL,
            bank varchar(20) NOT NULL,
            type enum('credit','debit') NOT NULL,
            amount decimal(15,2) NOT NULL,
            account_number varchar(50) DEFAULT '',
            sender_or_receiver varchar(255) DEFAULT '',
            reference_number varchar(100) DEFAULT '',
            sms_timestamp datetime NOT NULL,
            status enum('pending','matched','confirmed','rejected','expired') DEFAULT 'pending',
            matched_order_id bigint(20) UNSIGNED DEFAULT NULL,
            nonce varchar(50) NOT NULL,
            raw_payload text,
            ip_address varchar(45) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY device_id (device_id),
            KEY status (status),
            KEY amount (amount),
            KEY bank (bank),
            KEY matched_order_id (matched_order_id),
            UNIQUE KEY nonce (nonce)
        ) $charset_collate;";

        // Unique amounts table
        $table_amounts = $wpdb->prefix . 'spc_unique_amounts';
        $sql_amounts = "CREATE TABLE $table_amounts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            base_amount decimal(15,2) NOT NULL,
            unique_amount decimal(15,2) NOT NULL,
            decimal_suffix int(2) NOT NULL,
            status enum('reserved','used','expired','cancelled') DEFAULT 'reserved',
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY unique_amount (unique_amount),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        // Nonces table (replay attack prevention)
        $table_nonces = $wpdb->prefix . 'spc_nonces';
        $sql_nonces = "CREATE TABLE $table_nonces (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            nonce varchar(50) NOT NULL,
            device_id varchar(50) NOT NULL,
            used_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nonce (nonce),
            KEY device_id (device_id),
            KEY used_at (used_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_devices);
        dbDelta($sql_notifications);
        dbDelta($sql_amounts);
        dbDelta($sql_nonces);
    }

    /**
     * Create default options
     */
    private function create_options() {
        $defaults = array(
            'timestamp_tolerance' => 300,
            'amount_expiry' => 30,
            'max_pending_per_amount' => 99,
            'rate_limit_per_minute' => 30,
            'default_approval_mode' => 'auto',
            'nonce_expiry_hours' => 24,
            'line_on_match' => false,
            'sync_interval' => 5, // 5 seconds for faster order updates
        );

        foreach ($defaults as $key => $value) {
            if (get_option('spc_' . $key) === false) {
                add_option('spc_' . $key, $value);
            }
        }

        // Add version
        update_option('spc_version', SPC_VERSION);
    }

    /**
     * Plugin initialization
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('sms-payment-checker', false, dirname(SPC_PLUGIN_BASENAME) . '/languages');

        // Initialize components
        SPC_License::instance();
        SPC_Device::instance();
        SPC_Notification::instance();
        SPC_Matching::instance();

        if (is_admin()) {
            SPC_Admin::instance();
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        SPC_API::instance()->register_routes();
    }

    /**
     * Handle order status change
     *
     * @param int      $order_id   Order ID.
     * @param string   $old_status Old status.
     * @param string   $new_status New status.
     * @param WC_Order $order      Order object.
     */
    public function on_order_status_changed($order_id, $old_status, $new_status, $order) {
        // Increment sync version for polling
        $this->increment_sync_version();

        // Handle payment completion
        if ($new_status === 'completed' || $new_status === 'processing') {
            // Check if this order has SMS verification
            $sms_status = $order->get_meta('_spc_verification_status');
            if ($sms_status === 'matched') {
                $order->update_meta_data('_spc_verification_status', 'confirmed');
                $order->save();
            }
        }
    }

    /**
     * Handle thank you page
     *
     * @param int $order_id Order ID.
     */
    public function on_thankyou($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if bank transfer payment
        if ($order->get_payment_method() === 'bacs') {
            // Generate unique amount if not already generated
            $unique_amount_id = $order->get_meta('_spc_unique_amount_id');
            if (!$unique_amount_id) {
                $unique_amount = SPC_Matching::instance()->generate_unique_amount(
                    $order->get_total(),
                    $order_id
                );

                if ($unique_amount) {
                    $order->update_meta_data('_spc_unique_amount_id', $unique_amount->id);
                    $order->update_meta_data('_spc_unique_amount', $unique_amount->unique_amount);
                    $order->update_meta_data('_spc_verification_status', 'pending');
                    $order->save();

                    // Increment sync version for polling
                    $this->increment_sync_version();
                }
            }
        }
    }

    /**
     * Load WooCommerce payment gateway
     */
    public function load_payment_gateway() {
        if (class_exists('WC_Payment_Gateway')) {
            require_once SPC_PLUGIN_DIR . 'includes/class-spc-wc-gateway.php';
        }
    }

    /**
     * Add payment gateway to WooCommerce
     *
     * @param array $gateways Payment gateways.
     * @return array
     */
    public function add_payment_gateway($gateways) {
        if (class_exists('SPC_WC_Gateway')) {
            $gateways[] = 'SPC_WC_Gateway';
        }
        return $gateways;
    }

    /**
     * Increment sync version for polling-based sync
     */
    private function increment_sync_version() {
        $version = (int) get_option('spc_sync_version', 0);
        update_option('spc_sync_version', $version + 1);
    }

    /**
     * Get supported banks
     *
     * @return array
     */
    public static function get_supported_banks() {
        return array(
            'KBANK' => __('Kasikorn Bank', 'sms-payment-checker'),
            'SCB' => __('Siam Commercial Bank', 'sms-payment-checker'),
            'KTB' => __('Krungthai Bank', 'sms-payment-checker'),
            'BBL' => __('Bangkok Bank', 'sms-payment-checker'),
            'GSB' => __('Government Savings Bank', 'sms-payment-checker'),
            'BAY' => __('Bank of Ayudhya', 'sms-payment-checker'),
            'TTB' => __('TMBThanachart Bank', 'sms-payment-checker'),
            'PROMPTPAY' => __('PromptPay', 'sms-payment-checker'),
            'CIMB' => __('CIMB Thai', 'sms-payment-checker'),
            'KKP' => __('Kiatnakin Phatra Bank', 'sms-payment-checker'),
            'LH' => __('Land and Houses Bank', 'sms-payment-checker'),
            'TISCO' => __('TISCO Bank', 'sms-payment-checker'),
            'UOB' => __('United Overseas Bank', 'sms-payment-checker'),
            'ICBC' => __('ICBC Thai', 'sms-payment-checker'),
            'BAAC' => __('Bank for Agriculture', 'sms-payment-checker'),
        );
    }
}

/**
 * Returns the main instance of SMS_Payment_Checker.
 *
 * @return SMS_Payment_Checker
 */
function SPC() {
    return SMS_Payment_Checker::instance();
}

// Initialize plugin
SPC();
