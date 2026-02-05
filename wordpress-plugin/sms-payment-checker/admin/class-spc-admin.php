<?php
/**
 * Admin functionality for SMS Payment Checker
 *
 * @package SMS_Payment_Checker
 */

defined('ABSPATH') || exit;

/**
 * SPC_Admin class
 */
class SPC_Admin {

    /**
     * Instance
     *
     * @var SPC_Admin
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return SPC_Admin
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
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_spc_generate_device', array($this, 'ajax_generate_device'));
        add_action('wp_ajax_spc_delete_device', array($this, 'ajax_delete_device'));
        add_action('wp_ajax_spc_regenerate_qr', array($this, 'ajax_regenerate_qr'));
        add_action('wp_ajax_spc_get_device_qr', array($this, 'ajax_get_device_qr'));
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        add_menu_page(
            __('SMS Checker', 'sms-payment-checker'),
            __('SMS Checker', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker',
            array($this, 'render_dashboard'),
            'dashicons-smartphone',
            56
        );

        add_submenu_page(
            'sms-payment-checker',
            __('Dashboard', 'sms-payment-checker'),
            __('Dashboard', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'sms-payment-checker',
            __('Devices', 'sms-payment-checker'),
            __('Devices', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker-devices',
            array($this, 'render_devices')
        );

        add_submenu_page(
            'sms-payment-checker',
            __('Notifications', 'sms-payment-checker'),
            __('Notifications', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker-notifications',
            array($this, 'render_notifications')
        );

        add_submenu_page(
            'sms-payment-checker',
            __('Settings', 'sms-payment-checker'),
            __('Settings', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('spc_general_settings', 'spc_default_approval_mode');
        register_setting('spc_general_settings', 'spc_amount_expiry');
        register_setting('spc_general_settings', 'spc_max_pending_per_amount');
        register_setting('spc_general_settings', 'spc_sync_interval');

        // Pusher settings
        register_setting('spc_pusher_settings', 'spc_pusher_app_id');
        register_setting('spc_pusher_settings', 'spc_pusher_app_key');
        register_setting('spc_pusher_settings', 'spc_pusher_app_secret');
        register_setting('spc_pusher_settings', 'spc_pusher_cluster');

        // FCM settings
        register_setting('spc_fcm_settings', 'spc_firebase_project_id');
        register_setting('spc_fcm_settings', 'spc_firebase_credentials');
        register_setting('spc_fcm_settings', 'spc_fcm_enabled');
        register_setting('spc_fcm_settings', 'spc_fcm_on_match');
        register_setting('spc_fcm_settings', 'spc_fcm_on_new_order');
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current page hook.
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'sms-payment-checker') === false) {
            return;
        }

        wp_enqueue_style(
            'spc-admin',
            SPC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SPC_VERSION
        );

        wp_enqueue_script(
            'spc-admin',
            SPC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SPC_VERSION,
            true
        );

        wp_localize_script('spc-admin', 'spcAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spc_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this device?', 'sms-payment-checker'),
                'confirmRegenerate' => __('This will invalidate the current QR code. Continue?', 'sms-payment-checker'),
                'error' => __('An error occurred. Please try again.', 'sms-payment-checker'),
            ),
        ));

        // QR Code library
        wp_enqueue_script(
            'qrcode',
            'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js',
            array(),
            '1.5.3',
            true
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        $stats = SPC_Matching::instance()->get_stats(7);
        ?>
        <div class="wrap spc-admin">
            <h1><?php _e('SMS Payment Checker Dashboard', 'sms-payment-checker'); ?></h1>

            <div class="spc-stats-grid">
                <div class="spc-stat-card">
                    <div class="spc-stat-value"><?php echo esc_html($stats['total_orders']); ?></div>
                    <div class="spc-stat-label"><?php _e('Total Orders (7 days)', 'sms-payment-checker'); ?></div>
                </div>
                <div class="spc-stat-card spc-stat-success">
                    <div class="spc-stat-value"><?php echo esc_html($stats['auto_approved']); ?></div>
                    <div class="spc-stat-label"><?php _e('Auto Approved', 'sms-payment-checker'); ?></div>
                </div>
                <div class="spc-stat-card spc-stat-warning">
                    <div class="spc-stat-value"><?php echo esc_html($stats['pending_review']); ?></div>
                    <div class="spc-stat-label"><?php _e('Pending Review', 'sms-payment-checker'); ?></div>
                </div>
                <div class="spc-stat-card spc-stat-danger">
                    <div class="spc-stat-value"><?php echo esc_html($stats['rejected']); ?></div>
                    <div class="spc-stat-label"><?php _e('Rejected', 'sms-payment-checker'); ?></div>
                </div>
            </div>

            <div class="spc-stat-card spc-stat-highlight">
                <div class="spc-stat-value"><?php echo wc_price($stats['total_amount']); ?></div>
                <div class="spc-stat-label"><?php _e('Total Verified Amount', 'sms-payment-checker'); ?></div>
            </div>

            <h2><?php _e('Quick Setup', 'sms-payment-checker'); ?></h2>
            <div class="spc-setup-steps">
                <div class="spc-setup-step">
                    <span class="spc-step-number">1</span>
                    <div class="spc-step-content">
                        <h3><?php _e('Create Device', 'sms-payment-checker'); ?></h3>
                        <p><?php _e('Go to Devices and create a new device to get QR code for Android app.', 'sms-payment-checker'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=sms-payment-checker-devices'); ?>" class="button"><?php _e('Manage Devices', 'sms-payment-checker'); ?></a>
                    </div>
                </div>
                <div class="spc-setup-step">
                    <span class="spc-step-number">2</span>
                    <div class="spc-step-content">
                        <h3><?php _e('Scan QR Code', 'sms-payment-checker'); ?></h3>
                        <p><?php _e('Open SmsChecker app on your Android device and scan the QR code.', 'sms-payment-checker'); ?></p>
                    </div>
                </div>
                <div class="spc-setup-step">
                    <span class="spc-step-number">3</span>
                    <div class="spc-step-content">
                        <h3><?php _e('Configure Settings', 'sms-payment-checker'); ?></h3>
                        <p><?php _e('Set up Pusher and FCM for real-time notifications.', 'sms-payment-checker'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=sms-payment-checker-settings'); ?>" class="button"><?php _e('Configure', 'sms-payment-checker'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render devices page
     */
    public function render_devices() {
        $devices = SPC_Device::instance()->get_all();
        ?>
        <div class="wrap spc-admin">
            <h1>
                <?php _e('Devices', 'sms-payment-checker'); ?>
                <button type="button" class="page-title-action" id="spc-add-device"><?php _e('Add Device', 'sms-payment-checker'); ?></button>
            </h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Device Name', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Device ID', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Status', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Approval Mode', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Last Active', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Actions', 'sms-payment-checker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($devices)) : ?>
                        <tr>
                            <td colspan="6"><?php _e('No devices found.', 'sms-payment-checker'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($devices as $device) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($device->device_name); ?></strong></td>
                                <td><code><?php echo esc_html($device->device_id); ?></code></td>
                                <td>
                                    <span class="spc-status spc-status-<?php echo esc_attr($device->status); ?>">
                                        <?php echo esc_html(ucfirst($device->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(ucfirst($device->approval_mode)); ?></td>
                                <td>
                                    <?php echo $device->last_active_at ? esc_html(human_time_diff(strtotime($device->last_active_at))) . ' ago' : __('Never', 'sms-payment-checker'); ?>
                                </td>
                                <td>
                                    <button type="button" class="button spc-show-qr" data-device-id="<?php echo esc_attr($device->id); ?>"><?php _e('QR Code', 'sms-payment-checker'); ?></button>
                                    <button type="button" class="button spc-regenerate-qr" data-device-id="<?php echo esc_attr($device->id); ?>"><?php _e('Regenerate', 'sms-payment-checker'); ?></button>
                                    <button type="button" class="button button-link-delete spc-delete-device" data-device-id="<?php echo esc_attr($device->id); ?>"><?php _e('Delete', 'sms-payment-checker'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Device Modal -->
        <div id="spc-device-modal" class="spc-modal" style="display:none;">
            <div class="spc-modal-content">
                <span class="spc-modal-close">&times;</span>
                <h2><?php _e('Add New Device', 'sms-payment-checker'); ?></h2>
                <form id="spc-add-device-form">
                    <p>
                        <label for="device_name"><?php _e('Device Name', 'sms-payment-checker'); ?></label>
                        <input type="text" id="device_name" name="device_name" required class="regular-text">
                    </p>
                    <p>
                        <label for="approval_mode"><?php _e('Approval Mode', 'sms-payment-checker'); ?></label>
                        <select id="approval_mode" name="approval_mode">
                            <option value="auto"><?php _e('Auto (automatic approval)', 'sms-payment-checker'); ?></option>
                            <option value="manual"><?php _e('Manual (requires approval)', 'sms-payment-checker'); ?></option>
                            <option value="smart"><?php _e('Smart (auto for exact match)', 'sms-payment-checker'); ?></option>
                        </select>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Create Device', 'sms-payment-checker'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <!-- QR Code Modal -->
        <div id="spc-qr-modal" class="spc-modal" style="display:none;">
            <div class="spc-modal-content">
                <span class="spc-modal-close">&times;</span>
                <h2><?php _e('Device QR Code', 'sms-payment-checker'); ?></h2>
                <div id="spc-qr-container"></div>
                <p class="description"><?php _e('Scan this QR code with the SmsChecker Android app to connect this device.', 'sms-payment-checker'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render notifications page
     */
    public function render_notifications() {
        global $wpdb;
        $table = $wpdb->prefix . 'spc_notifications';
        $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, d.device_name FROM $table n
             LEFT JOIN {$wpdb->prefix}spc_devices d ON n.device_id = d.device_id
             ORDER BY n.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $total_pages = ceil($total / $per_page);
        ?>
        <div class="wrap spc-admin">
            <h1><?php _e('SMS Notifications', 'sms-payment-checker'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Bank', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Type', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Amount', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Status', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Device', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Matched Order', 'sms-payment-checker'); ?></th>
                        <th><?php _e('Date', 'sms-payment-checker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)) : ?>
                        <tr>
                            <td colspan="8"><?php _e('No notifications found.', 'sms-payment-checker'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($notifications as $notification) : ?>
                            <tr>
                                <td><?php echo esc_html($notification->id); ?></td>
                                <td><strong><?php echo esc_html($notification->bank); ?></strong></td>
                                <td>
                                    <span class="spc-type spc-type-<?php echo esc_attr($notification->type); ?>">
                                        <?php echo esc_html(ucfirst($notification->type)); ?>
                                    </span>
                                </td>
                                <td><?php echo wc_price($notification->amount); ?></td>
                                <td>
                                    <span class="spc-status spc-status-<?php echo esc_attr($notification->status); ?>">
                                        <?php echo esc_html(ucfirst($notification->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($notification->device_name ?: $notification->device_id); ?></td>
                                <td>
                                    <?php if ($notification->matched_order_id) : ?>
                                        <a href="<?php echo admin_url('post.php?post=' . $notification->matched_order_id . '&action=edit'); ?>">
                                            #<?php echo esc_html($notification->matched_order_id); ?>
                                        </a>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $page,
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap spc-admin">
            <h1><?php _e('SMS Payment Checker Settings', 'sms-payment-checker'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=sms-payment-checker-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'sms-payment-checker'); ?>
                </a>
                <a href="?page=sms-payment-checker-settings&tab=pusher" class="nav-tab <?php echo $active_tab === 'pusher' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Pusher', 'sms-payment-checker'); ?>
                </a>
                <a href="?page=sms-payment-checker-settings&tab=fcm" class="nav-tab <?php echo $active_tab === 'fcm' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Firebase FCM', 'sms-payment-checker'); ?>
                </a>
            </nav>

            <form method="post" action="options.php">
                <?php
                if ($active_tab === 'general') {
                    settings_fields('spc_general_settings');
                    $this->render_general_settings();
                } elseif ($active_tab === 'pusher') {
                    settings_fields('spc_pusher_settings');
                    $this->render_pusher_settings();
                } elseif ($active_tab === 'fcm') {
                    settings_fields('spc_fcm_settings');
                    $this->render_fcm_settings();
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render general settings
     */
    private function render_general_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="spc_default_approval_mode"><?php _e('Default Approval Mode', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <select id="spc_default_approval_mode" name="spc_default_approval_mode">
                        <option value="auto" <?php selected(get_option('spc_default_approval_mode', 'auto'), 'auto'); ?>><?php _e('Auto', 'sms-payment-checker'); ?></option>
                        <option value="manual" <?php selected(get_option('spc_default_approval_mode', 'auto'), 'manual'); ?>><?php _e('Manual', 'sms-payment-checker'); ?></option>
                        <option value="smart" <?php selected(get_option('spc_default_approval_mode', 'auto'), 'smart'); ?>><?php _e('Smart', 'sms-payment-checker'); ?></option>
                    </select>
                    <p class="description"><?php _e('Default mode for new devices.', 'sms-payment-checker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spc_amount_expiry"><?php _e('Amount Expiry (minutes)', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <input type="number" id="spc_amount_expiry" name="spc_amount_expiry" value="<?php echo esc_attr(get_option('spc_amount_expiry', 30)); ?>" min="5" max="1440" class="small-text">
                    <p class="description"><?php _e('How long unique amounts remain valid.', 'sms-payment-checker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spc_max_pending_per_amount"><?php _e('Max Pending Per Amount', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <input type="number" id="spc_max_pending_per_amount" name="spc_max_pending_per_amount" value="<?php echo esc_attr(get_option('spc_max_pending_per_amount', 99)); ?>" min="10" max="99" class="small-text">
                    <p class="description"><?php _e('Maximum concurrent pending orders with same base amount (1-99 satang variations).', 'sms-payment-checker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spc_sync_interval"><?php _e('Sync Interval (seconds)', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <input type="number" id="spc_sync_interval" name="spc_sync_interval" value="<?php echo esc_attr(get_option('spc_sync_interval', 30)); ?>" min="10" max="300" class="small-text">
                    <p class="description"><?php _e('How often Android app should sync with server.', 'sms-payment-checker'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Pusher settings
     */
    private function render_pusher_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="spc_pusher_app_id"><?php _e('App ID', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <input type="text" id="spc_pusher_app_id" name="spc_pusher_app_id" value="<?php echo esc_attr(get_option('spc_pusher_app_id')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spc_pusher_app_key"><?php _e('App Key', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <input type="text" id="spc_pusher_app_key" name="spc_pusher_app_key" value="<?php echo esc_attr(get_option('spc_pusher_app_key')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spc_pusher_app_secret"><?php _e('App Secret', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <input type="password" id="spc_pusher_app_secret" name="spc_pusher_app_secret" value="<?php echo esc_attr(get_option('spc_pusher_app_secret')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spc_pusher_cluster"><?php _e('Cluster', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <select id="spc_pusher_cluster" name="spc_pusher_cluster">
                        <?php
                        $clusters = array('ap1', 'ap2', 'ap3', 'ap4', 'eu', 'us2', 'us3', 'mt1');
                        $current = get_option('spc_pusher_cluster', 'ap1');
                        foreach ($clusters as $cluster) {
                            printf('<option value="%s" %s>%s</option>', esc_attr($cluster), selected($current, $cluster, false), esc_html($cluster));
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <p class="description">
            <?php printf(__('Get your Pusher credentials from %s', 'sms-payment-checker'), '<a href="https://dashboard.pusher.com" target="_blank">Pusher Dashboard</a>'); ?>
        </p>
        <?php
    }

    /**
     * Render FCM settings
     */
    private function render_fcm_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="spc_firebase_project_id"><?php _e('Project ID', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <input type="text" id="spc_firebase_project_id" name="spc_firebase_project_id" value="<?php echo esc_attr(get_option('spc_firebase_project_id')); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spc_firebase_credentials"><?php _e('Service Account JSON', 'sms-payment-checker'); ?></label>
                </th>
                <td>
                    <textarea id="spc_firebase_credentials" name="spc_firebase_credentials" rows="10" class="large-text code"><?php echo esc_textarea(get_option('spc_firebase_credentials')); ?></textarea>
                    <p class="description"><?php _e('Paste the entire Firebase service account JSON here.', 'sms-payment-checker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Notifications', 'sms-payment-checker'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="spc_fcm_enabled" value="1" <?php checked(get_option('spc_fcm_enabled', 1)); ?>>
                        <?php _e('Enable FCM Push Notifications', 'sms-payment-checker'); ?>
                    </label>
                    <br><br>
                    <label>
                        <input type="checkbox" name="spc_fcm_on_match" value="1" <?php checked(get_option('spc_fcm_on_match', 1)); ?>>
                        <?php _e('Send notification when payment matched', 'sms-payment-checker'); ?>
                    </label>
                    <br><br>
                    <label>
                        <input type="checkbox" name="spc_fcm_on_new_order" value="1" <?php checked(get_option('spc_fcm_on_new_order', 1)); ?>>
                        <?php _e('Send notification for new orders', 'sms-payment-checker'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <p class="description">
            <?php printf(__('Get your Firebase credentials from %s', 'sms-payment-checker'), '<a href="https://console.firebase.google.com" target="_blank">Firebase Console</a>'); ?>
        </p>
        <?php
    }

    /**
     * AJAX: Generate new device
     */
    public function ajax_generate_device() {
        check_ajax_referer('spc_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'sms-payment-checker')));
        }

        $device_name = sanitize_text_field($_POST['device_name'] ?? '');
        $approval_mode = sanitize_key($_POST['approval_mode'] ?? 'auto');

        if (empty($device_name)) {
            wp_send_json_error(array('message' => __('Device name is required', 'sms-payment-checker')));
        }

        $device = SPC_Device::instance()->create($device_name, $approval_mode);

        if (!$device) {
            wp_send_json_error(array('message' => __('Failed to create device', 'sms-payment-checker')));
        }

        $qr_data = $this->generate_qr_data($device);

        wp_send_json_success(array(
            'device' => $device,
            'qr_data' => $qr_data,
        ));
    }

    /**
     * AJAX: Delete device
     */
    public function ajax_delete_device() {
        check_ajax_referer('spc_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'sms-payment-checker')));
        }

        $device_id = (int) ($_POST['device_id'] ?? 0);

        if (!$device_id) {
            wp_send_json_error(array('message' => __('Invalid device ID', 'sms-payment-checker')));
        }

        $result = SPC_Device::instance()->delete($device_id);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete device', 'sms-payment-checker')));
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Regenerate QR code
     */
    public function ajax_regenerate_qr() {
        check_ajax_referer('spc_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'sms-payment-checker')));
        }

        $device_id = (int) ($_POST['device_id'] ?? 0);

        if (!$device_id) {
            wp_send_json_error(array('message' => __('Invalid device ID', 'sms-payment-checker')));
        }

        $device = SPC_Device::instance()->regenerate_credentials($device_id);

        if (!$device) {
            wp_send_json_error(array('message' => __('Failed to regenerate credentials', 'sms-payment-checker')));
        }

        $qr_data = $this->generate_qr_data($device);

        wp_send_json_success(array(
            'qr_data' => $qr_data,
        ));
    }

    /**
     * AJAX: Get device QR code
     */
    public function ajax_get_device_qr() {
        check_ajax_referer('spc_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'sms-payment-checker')));
        }

        $device_id = (int) ($_POST['device_id'] ?? 0);

        if (!$device_id) {
            wp_send_json_error(array('message' => __('Invalid device ID', 'sms-payment-checker')));
        }

        $device = SPC_Device::instance()->get($device_id);

        if (!$device) {
            wp_send_json_error(array('message' => __('Device not found', 'sms-payment-checker')));
        }

        $qr_data = $this->generate_qr_data($device);

        wp_send_json_success(array(
            'qr_data' => $qr_data,
        ));
    }

    /**
     * Generate QR code data
     *
     * @param object $device Device object.
     * @return string JSON string for QR code.
     */
    private function generate_qr_data($device) {
        return wp_json_encode(array(
            'server_url' => rest_url('sms-payment/v1'),
            'device_id' => $device->device_id,
            'api_key' => $device->api_key,
            'secret_key' => $device->secret_key,
            'pusher' => array(
                'key' => get_option('spc_pusher_app_key'),
                'cluster' => get_option('spc_pusher_cluster', 'ap1'),
            ),
        ));
    }
}
