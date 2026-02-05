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
        add_action('wp_ajax_spc_confirm_order', array($this, 'ajax_confirm_order'));
        add_action('wp_ajax_spc_reject_order', array($this, 'ajax_reject_order'));
    }

    /**
     * Add admin menu
     */
    public function add_menu() {
        // Main menu
        add_menu_page(
            __('SMS Payment', 'sms-payment-checker'),
            __('SMS Payment', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker',
            array($this, 'render_dashboard'),
            'dashicons-smartphone',
            56
        );

        // Submenu: Dashboard (same as main)
        add_submenu_page(
            'sms-payment-checker',
            __('Dashboard', 'sms-payment-checker'),
            __('📊 Dashboard', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker',
            array($this, 'render_dashboard')
        );

        // Submenu: Settings
        add_submenu_page(
            'sms-payment-checker',
            __('Settings', 'sms-payment-checker'),
            __('⚙️ Settings', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker-settings',
            array($this, 'render_settings')
        );

        // Submenu: Devices
        add_submenu_page(
            'sms-payment-checker',
            __('Devices', 'sms-payment-checker'),
            __('📱 Devices', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker-devices',
            array($this, 'render_devices')
        );

        // Submenu: Notifications
        add_submenu_page(
            'sms-payment-checker',
            __('Notifications', 'sms-payment-checker'),
            __('📨 Notifications', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker-notifications',
            array($this, 'render_notifications')
        );

        // Submenu: Pending Orders
        add_submenu_page(
            'sms-payment-checker',
            __('Pending Orders', 'sms-payment-checker'),
            __('⏳ Pending Orders', 'sms-payment-checker'),
            'manage_woocommerce',
            'sms-payment-checker-pending',
            array($this, 'render_pending_orders')
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
        register_setting('spc_general_settings', 'spc_line_on_match');
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
                        <p><?php _e('Adjust sync settings and approval modes.', 'sms-payment-checker'); ?></p>
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
        ?>
        <div class="wrap spc-admin">
            <h1><?php _e('SMS Payment Checker Settings', 'sms-payment-checker'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('spc_general_settings');
                $this->render_general_settings();
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
                    <input type="number" id="spc_sync_interval" name="spc_sync_interval" value="<?php echo esc_attr(get_option('spc_sync_interval', 5)); ?>" min="3" max="60" class="small-text">
                    <p class="description"><?php _e('How often Android app should sync with server. (Recommended: 5 seconds)', 'sms-payment-checker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Notifications', 'sms-payment-checker'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="spc_line_on_match" value="1" <?php checked(get_option('spc_line_on_match', 0)); ?>>
                        <?php _e('Send LINE notification when payment matched', 'sms-payment-checker'); ?>
                    </label>
                </td>
            </tr>
        </table>
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
            'sync_interval' => (int) get_option('spc_sync_interval', 5),
        ));
    }

    /**
     * Render pending orders page
     */
    public function render_pending_orders() {
        // Get pending orders with bank transfer payment
        $args = array(
            'status' => array('on-hold', 'pending'),
            'payment_method' => 'bacs',
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        // Search filter
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        if ($search) {
            $args['s'] = $search;
        }

        $orders = wc_get_orders($args);

        // Get unmatched notifications
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'spc_notifications';
        $unmatched_notifications = $wpdb->get_results(
            "SELECT * FROM $notifications_table
             WHERE status = 'pending' AND type = 'credit'
             ORDER BY created_at DESC
             LIMIT 20"
        );
        ?>
        <div class="wrap spc-admin">
            <h1><?php _e('⏳ Pending Orders', 'sms-payment-checker'); ?></h1>
            <p class="description"><?php _e('Orders waiting for SMS payment verification', 'sms-payment-checker'); ?></p>

            <!-- Search Form -->
            <div class="spc-search-box" style="margin: 20px 0;">
                <form method="GET">
                    <input type="hidden" name="page" value="sms-payment-checker-pending">
                    <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search Order ID, Name, Email...', 'sms-payment-checker'); ?>" class="regular-text">
                    <button type="submit" class="button"><?php _e('🔍 Search', 'sms-payment-checker'); ?></button>
                </form>
            </div>

            <div class="spc-pending-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Pending Orders Table -->
                <div class="spc-orders-section">
                    <h2><?php _e('📦 Orders Pending Payment', 'sms-payment-checker'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Order', 'sms-payment-checker'); ?></th>
                                <th><?php _e('Customer', 'sms-payment-checker'); ?></th>
                                <th style="text-align: right;"><?php _e('Amount', 'sms-payment-checker'); ?></th>
                                <th><?php _e('Method', 'sms-payment-checker'); ?></th>
                                <th><?php _e('Date', 'sms-payment-checker'); ?></th>
                                <th><?php _e('Actions', 'sms-payment-checker'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)) : ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <span style="font-size: 48px; display: block; margin-bottom: 10px;">🎉</span>
                                        <?php _e('No pending orders! All payments are verified.', 'sms-payment-checker'); ?>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($orders as $order) :
                                    $unique_amount = $order->get_meta('_spc_unique_amount');
                                    $display_amount = $unique_amount ? $unique_amount : $order->get_total();
                                ?>
                                    <tr data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                        <td>
                                            <a href="<?php echo esc_url($order->get_edit_order_url()); ?>" class="order-link">
                                                <strong>#<?php echo esc_html($order->get_order_number()); ?></strong>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></strong>
                                            <br><small><?php echo esc_html($order->get_billing_email()); ?></small>
                                        </td>
                                        <td style="text-align: right;">
                                            <strong style="color: #28a745; font-size: 1.1em;">
                                                <?php echo wc_price($display_amount); ?>
                                            </strong>
                                            <?php if ($unique_amount && $unique_amount != $order->get_total()) : ?>
                                                <br><small style="color: #666;">(<?php _e('Original:', 'sms-payment-checker'); ?> <?php echo wc_price($order->get_total()); ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="spc-badge spc-badge-info">🏦 <?php _e('Bank Transfer', 'sms-payment-checker'); ?></span>
                                        </td>
                                        <td>
                                            <?php echo esc_html($order->get_date_created()->date_i18n('d/m/Y')); ?>
                                            <br><small><?php echo esc_html($order->get_date_created()->date_i18n('H:i')); ?></small>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-primary spc-confirm-order" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                                ✅ <?php _e('Confirm', 'sms-payment-checker'); ?>
                                            </button>
                                            <button type="button" class="button spc-reject-order" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                                ❌
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Unmatched SMS Notifications -->
                <div class="spc-notifications-section">
                    <h2><?php _e('📨 Unmatched SMS', 'sms-payment-checker'); ?></h2>
                    <p class="description"><?php _e('SMS received but not matched to any order', 'sms-payment-checker'); ?></p>

                    <?php if (empty($unmatched_notifications)) : ?>
                        <div style="text-align: center; padding: 30px; background: #f9f9f9; border-radius: 8px;">
                            <span style="font-size: 32px; display: block; margin-bottom: 10px;">📭</span>
                            <?php _e('No unmatched SMS notifications', 'sms-payment-checker'); ?>
                        </div>
                    <?php else : ?>
                        <div class="spc-notification-list" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($unmatched_notifications as $notification) : ?>
                                <div class="spc-notification-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span class="spc-badge spc-badge-warning"><?php echo esc_html($notification->bank); ?></span>
                                        <small style="color: #666;"><?php echo esc_html(human_time_diff(strtotime($notification->created_at)) . ' ago'); ?></small>
                                    </div>
                                    <div style="font-size: 1.3em; font-weight: bold; color: <?php echo $notification->type === 'credit' ? '#28a745' : '#dc3545'; ?>;">
                                        <?php echo $notification->type === 'credit' ? '+' : '-'; ?>฿<?php echo esc_html(number_format($notification->amount, 2)); ?>
                                    </div>
                                    <?php if ($notification->sender_or_receiver) : ?>
                                        <div style="color: #666; font-size: 0.9em; margin-top: 5px;">
                                            <?php echo esc_html($notification->sender_or_receiver); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($notification->reference_number) : ?>
                                        <div style="color: #999; font-size: 0.8em; font-family: monospace; margin-top: 5px;">
                                            Ref: <?php echo esc_html($notification->reference_number); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="text-align: center; margin-top: 15px;">
                            <a href="<?php echo admin_url('admin.php?page=sms-payment-checker-notifications'); ?>" class="button">
                                <?php _e('View All →', 'sms-payment-checker'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div id="spc-reject-modal" class="spc-modal" style="display: none;">
            <div class="spc-modal-content">
                <span class="spc-modal-close">&times;</span>
                <h2><?php _e('❌ Reject Payment', 'sms-payment-checker'); ?></h2>
                <form id="spc-reject-form">
                    <input type="hidden" id="reject_order_id" name="order_id" value="">
                    <p>
                        <label for="reject_reason"><?php _e('Reason (optional)', 'sms-payment-checker'); ?></label>
                        <textarea id="reject_reason" name="reason" rows="3" class="large-text" placeholder="<?php _e('Enter rejection reason...', 'sms-payment-checker'); ?>"></textarea>
                    </p>
                    <p>
                        <button type="button" class="button" onclick="document.getElementById('spc-reject-modal').style.display='none';">
                            <?php _e('Cancel', 'sms-payment-checker'); ?>
                        </button>
                        <button type="submit" class="button button-primary" style="background: #dc3545; border-color: #dc3545;">
                            <?php _e('Confirm Reject', 'sms-payment-checker'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <style>
            .spc-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }
            .spc-badge-info { background: #e3f2fd; color: #1565c0; }
            .spc-badge-warning { background: #fff3e0; color: #ef6c00; }
            .spc-badge-success { background: #e8f5e9; color: #2e7d32; }
            .spc-badge-danger { background: #ffebee; color: #c62828; }
            .spc-confirm-order { margin-right: 5px; }
            @media (max-width: 1200px) {
                .spc-pending-grid { grid-template-columns: 1fr; }
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Confirm order
            $('.spc-confirm-order').on('click', function() {
                var orderId = $(this).data('order-id');
                if (!confirm('<?php _e('Confirm payment for this order?', 'sms-payment-checker'); ?>')) {
                    return;
                }

                var $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Processing...', 'sms-payment-checker'); ?>');

                $.ajax({
                    url: spcAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'spc_confirm_order',
                        nonce: spcAdmin.nonce,
                        order_id: orderId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('tr[data-order-id="' + orderId + '"]').fadeOut(function() {
                                $(this).remove();
                            });
                        } else {
                            alert(response.data.message || '<?php _e('An error occurred', 'sms-payment-checker'); ?>');
                            $button.prop('disabled', false).text('✅ <?php _e('Confirm', 'sms-payment-checker'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred', 'sms-payment-checker'); ?>');
                        $button.prop('disabled', false).text('✅ <?php _e('Confirm', 'sms-payment-checker'); ?>');
                    }
                });
            });

            // Show reject modal
            $('.spc-reject-order').on('click', function() {
                var orderId = $(this).data('order-id');
                $('#reject_order_id').val(orderId);
                $('#reject_reason').val('');
                $('#spc-reject-modal').show();
            });

            // Close modal
            $('.spc-modal-close').on('click', function() {
                $(this).closest('.spc-modal').hide();
            });

            // Submit reject form
            $('#spc-reject-form').on('submit', function(e) {
                e.preventDefault();

                var orderId = $('#reject_order_id').val();
                var reason = $('#reject_reason').val();

                $.ajax({
                    url: spcAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'spc_reject_order',
                        nonce: spcAdmin.nonce,
                        order_id: orderId,
                        reason: reason
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#spc-reject-modal').hide();
                            $('tr[data-order-id="' + orderId + '"]').fadeOut(function() {
                                $(this).remove();
                            });
                        } else {
                            alert(response.data.message || '<?php _e('An error occurred', 'sms-payment-checker'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred', 'sms-payment-checker'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Confirm order payment
     */
    public function ajax_confirm_order() {
        check_ajax_referer('spc_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'sms-payment-checker')));
        }

        $order_id = (int) ($_POST['order_id'] ?? 0);

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order ID', 'sms-payment-checker')));
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found', 'sms-payment-checker')));
        }

        // Update order status to processing
        $order->update_status('processing', __('Payment confirmed via SMS Payment Checker admin.', 'sms-payment-checker'));
        $order->update_meta_data('_spc_verification_status', 'confirmed');
        $order->update_meta_data('_spc_confirmed_by', get_current_user_id());
        $order->update_meta_data('_spc_confirmed_at', current_time('mysql'));
        $order->save();

        // Increment sync version
        $this->increment_sync_version();

        wp_send_json_success(array(
            'message' => __('Order confirmed successfully', 'sms-payment-checker'),
            'order_id' => $order_id,
        ));
    }

    /**
     * AJAX: Reject order payment
     */
    public function ajax_reject_order() {
        check_ajax_referer('spc_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'sms-payment-checker')));
        }

        $order_id = (int) ($_POST['order_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order ID', 'sms-payment-checker')));
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found', 'sms-payment-checker')));
        }

        // Update order status to cancelled
        $note = __('Payment rejected via SMS Payment Checker admin.', 'sms-payment-checker');
        if ($reason) {
            $note .= ' ' . __('Reason:', 'sms-payment-checker') . ' ' . $reason;
        }

        $order->update_status('cancelled', $note);
        $order->update_meta_data('_spc_verification_status', 'rejected');
        $order->update_meta_data('_spc_rejected_by', get_current_user_id());
        $order->update_meta_data('_spc_rejected_at', current_time('mysql'));
        $order->update_meta_data('_spc_rejection_reason', $reason);
        $order->save();

        // Cancel unique amount if exists
        global $wpdb;
        $unique_amount_id = $order->get_meta('_spc_unique_amount_id');
        if ($unique_amount_id) {
            $wpdb->update(
                $wpdb->prefix . 'spc_unique_amounts',
                array('status' => 'cancelled'),
                array('id' => $unique_amount_id)
            );
        }

        // Increment sync version
        $this->increment_sync_version();

        wp_send_json_success(array(
            'message' => __('Order rejected successfully', 'sms-payment-checker'),
            'order_id' => $order_id,
        ));
    }

    /**
     * Increment sync version for polling
     */
    private function increment_sync_version() {
        $version = (int) get_option('spc_sync_version', 0);
        update_option('spc_sync_version', $version + 1);
    }
}
