<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Nano_Split_POS_Admin_Settings {
    /**
     * Initialize admin settings
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers for address management
        add_action('wp_ajax_nano_split_add_address', array($this, 'add_address'));
        add_action('wp_ajax_nano_split_edit_address', array($this, 'edit_address'));
        add_action('wp_ajax_nano_split_delete_address', array($this, 'delete_address'));
        add_action('wp_ajax_nano_split_register_api_key', array($this, 'register_api_key'));
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_menu_page(
            __('Nano Split POS', 'nano-split-pos'),
            __('Nano Split POS', 'nano-split-pos'),
            'manage_options',
            'nano-split-pos',
            array($this, 'render_settings_page'),
            'dashicons-cart',
            30
        );
        
        add_submenu_page(
            'nano-split-pos',
            __('Settings', 'nano-split-pos'),
            __('Settings', 'nano-split-pos'),
            'manage_options',
            'nano-split-pos',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'nano-split-pos',
            __('Payment Addresses', 'nano-split-pos'),
            __('Payment Addresses', 'nano-split-pos'),
            'manage_options',
            'nano-split-pos-addresses',
            array($this, 'render_addresses_page')
        );
        
        add_submenu_page(
            'nano-split-pos',
            __('Payment History', 'nano-split-pos'),
            __('Payment History', 'nano-split-pos'),
            'manage_options',
            'nano-split-pos-history',
            array($this, 'render_history_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('nano_split_pos_settings', 'nano_split_pos_api_key');
        register_setting('nano_split_pos_settings', 'nano_split_pos_enable_tips', array(
            'type' => 'boolean',
            'default' => false
        ));
        register_setting('nano_split_pos_settings', 'nano_split_pos_tip_percentages', array(
            'type' => 'string',
            'default' => '5,10,15,20'
        ));
        register_setting('nano_split_pos_settings', 'nano_split_pos_enable_products', array(
            'type' => 'boolean',
            'default' => false
        ));
        register_setting('nano_split_pos_settings', 'nano_split_pos_use_woo_tax', array(
            'type' => 'boolean',
            'default' => false
        ));
        register_setting('nano_split_pos_settings', 'nano_split_pos_tax_class');
        register_setting('nano_split_pos_settings', 'nano_split_pos_tax_address');
        register_setting('nano_split_pos_settings', 'nano_split_pos_tax_rate', array(
            'type' => 'number',
            'default' => 0
        ));
        register_setting('nano_split_pos_settings', 'nano_split_pos_test_mode', array(
            'type' => 'boolean',
            'default' => true
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Enqueue admin scripts and styles
        wp_enqueue_style('nano-split-pos-admin', NANO_SPLIT_POS_PLUGIN_URL . 'assets/css/admin.css', array(), NANO_SPLIT_POS_VERSION);
        wp_enqueue_script('nano-split-pos-admin', NANO_SPLIT_POS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), NANO_SPLIT_POS_VERSION, true);
        
        // Include settings template
        include NANO_SPLIT_POS_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Render addresses page
     */
    public function render_addresses_page() {
        // Enqueue admin scripts and styles
        wp_enqueue_style('nano-split-pos-admin', NANO_SPLIT_POS_PLUGIN_URL . 'assets/css/admin.css', array(), NANO_SPLIT_POS_VERSION);
        wp_enqueue_script('nano-split-pos-admin', NANO_SPLIT_POS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), NANO_SPLIT_POS_VERSION, true);
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('nano-split-pos-admin', 'nano_split_pos_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nano_split_pos_admin_nonce'),
        ));
        
        // Get all addresses
        global $wpdb;
        $addresses_table = $wpdb->prefix . 'nano_split_addresses';
        $addresses = $wpdb->get_results("SELECT * FROM $addresses_table ORDER BY id ASC");
        
        // Include addresses template
        include NANO_SPLIT_POS_PLUGIN_DIR . 'templates/admin/addresses.php';
    }
    
    /**
     * Render history page
     */
    public function render_history_page() {
        // Enqueue admin scripts and styles
        wp_enqueue_style('nano-split-pos-admin', NANO_SPLIT_POS_PLUGIN_URL . 'assets/css/admin.css', array(), NANO_SPLIT_POS_VERSION);
        
        // Get all payments
        global $wpdb;
        $payment_table = $wpdb->prefix . 'nano_split_payments';
        $payments = $wpdb->get_results("SELECT * FROM $payment_table ORDER BY created_at DESC");
        
        // Include history template
        include NANO_SPLIT_POS_PLUGIN_DIR . 'templates/admin/history.php';
    }
    
    /**
     * Add address AJAX handler
     */
    public function add_address() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nano-split-pos')));
        }
        
        // Get form data
        $nickname = isset($_POST['nickname']) ? sanitize_text_field($_POST['nickname']) : '';
        $nano_address = isset($_POST['nano_address']) ? sanitize_text_field($_POST['nano_address']) : '';
        $percentage = isset($_POST['percentage']) ? floatval($_POST['percentage']) : 0;
        
        // Validate data
        if (empty($nickname) || empty($nano_address) || $percentage <= 0 || $percentage > 100) {
            wp_send_json_error(array('message' => __('Please enter valid address details', 'nano-split-pos')));
        }
        
        // Check if total percentage would exceed 100%
        global $wpdb;
        $addresses_table = $wpdb->prefix . 'nano_split_addresses';
        $total_percentage = $wpdb->get_var("SELECT SUM(percentage) FROM $addresses_table WHERE is_active = 1");
        $total_percentage = floatval($total_percentage);
        
        if ($total_percentage + $percentage > 100) {
            wp_send_json_error(array('message' => __('Total percentage cannot exceed 100%. Currently at ' . $total_percentage . '%', 'nano-split-pos')));
        }
        
        // Insert address
        $result = $wpdb->insert(
            $addresses_table,
            array(
                'nickname' => $nickname,
                'nano_address' => $nano_address,
                'percentage' => $percentage
            )
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to add address', 'nano-split-pos')));
        }
        
        wp_send_json_success(array(
            'message' => __('Address added successfully', 'nano-split-pos'),
            'address' => array(
                'id' => $wpdb->insert_id,
                'nickname' => $nickname,
                'nano_address' => $nano_address,
                'percentage' => $percentage,
                'is_active' => 1
            )
        ));
    }
    
    /**
     * Edit address AJAX handler
     */
    public function edit_address() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nano-split-pos')));
        }
        
        // Get form data
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nickname = isset($_POST['nickname']) ? sanitize_text_field($_POST['nickname']) : '';
        $nano_address = isset($_POST['nano_address']) ? sanitize_text_field($_POST['nano_address']) : '';
        $percentage = isset($_POST['percentage']) ? floatval($_POST['percentage']) : 0;
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
        
        // Validate data
        if ($id <= 0 || empty($nickname) || empty($nano_address) || $percentage <= 0 || $percentage > 100) {
            wp_send_json_error(array('message' => __('Please enter valid address details', 'nano-split-pos')));
        }
        
        // Check if total percentage would exceed 100%
        global $wpdb;
        $addresses_table = $wpdb->prefix . 'nano_split_addresses';
        
        // Get current percentage for this address
        $current_percentage = $wpdb->get_var($wpdb->prepare("SELECT percentage FROM $addresses_table WHERE id = %d", $id));
        $current_percentage = floatval($current_percentage);
        
        // Get total percentage excluding this address
        $total_percentage = $wpdb->get_var($wpdb->prepare("SELECT SUM(percentage) FROM $addresses_table WHERE is_active = 1 AND id != %d", $id));
        $total_percentage = floatval($total_percentage);
        
        // Only check if address is active
        if ($is_active && $total_percentage + $percentage > 100) {
            wp_send_json_error(array('message' => __('Total percentage cannot exceed 100%. Currently at ' . $total_percentage . '%', 'nano-split-pos')));
        }
        
        // Update address
        $result = $wpdb->update(
            $addresses_table,
            array(
                'nickname' => $nickname,
                'nano_address' => $nano_address,
                'percentage' => $percentage,
                'is_active' => $is_active
            ),
            array('id' => $id)
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to update address', 'nano-split-pos')));
        }
        
        wp_send_json_success(array(
            'message' => __('Address updated successfully', 'nano-split-pos'),
            'address' => array(
                'id' => $id,
                'nickname' => $nickname,
                'nano_address' => $nano_address,
                'percentage' => $percentage,
                'is_active' => $is_active
            )
        ));
    }
    
    /**
     * Delete address AJAX handler
     */
    public function delete_address() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nano-split-pos')));
        }
        
        // Get address ID
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('Invalid address ID', 'nano-split-pos')));
        }
        
        // Delete address
        global $wpdb;
        $addresses_table = $wpdb->prefix . 'nano_split_addresses';
        $result = $wpdb->delete(
            $addresses_table,
            array('id' => $id)
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete address', 'nano-split-pos')));
        }
        
        wp_send_json_success(array(
            'message' => __('Address deleted successfully', 'nano-split-pos')
        ));
    }
    
    /**
     * Register API key AJAX handler
     */
    public function register_api_key() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nano-split-pos')));
        }
        
        // Register for API key
        $response = wp_remote_post('https://api.splitroute.com/api/v1/api-keys/register', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'email' => get_option('admin_email'),
                'name' => 'nano_split_pos_' . sanitize_title(get_bloginfo('name'))
            )),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code !== 200) {
            $error_message = isset($body['message']) ? $body['message'] : 'Unknown error';
            wp_send_json_error(array('message' => $error_message));
            return;
        }
        
        if (isset($body['key'])) {
            update_option('nano_split_pos_api_key', $body['key']);
            wp_send_json_success(array('message' => __('API key registered successfully', 'nano-split-pos')));
        } else {
            wp_send_json_error(array('message' => __('API key not found in response', 'nano-split-pos')));
        }
    }
} 