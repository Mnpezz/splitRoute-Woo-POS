<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Nano_Split_POS_Admin_Employees {
    /**
     * Initialize admin employees
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers for employee management
        add_action('wp_ajax_nano_split_add_employee', array($this, 'add_employee'));
        add_action('wp_ajax_nano_split_edit_employee', array($this, 'edit_employee'));
        add_action('wp_ajax_nano_split_delete_employee', array($this, 'delete_employee'));
    }
    
    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        add_submenu_page(
            'nano-split-pos',
            __('Employees', 'nano-split-pos'),
            __('Employees', 'nano-split-pos'),
            'manage_options',
            'nano-split-pos-employees',
            array($this, 'render_employees_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('nano_split_pos_employees', 'nano_split_pos_employees');
    }
    
    /**
     * Render employees page
     */
    public function render_employees_page() {
        // Get employees
        $employees = get_option('nano_split_pos_employees', array());
        
        // Include template
        include NANO_SPLIT_POS_PLUGIN_DIR . 'templates/admin/employees.php';
    }
    
    /**
     * Add employee AJAX handler
     */
    public function add_employee() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nano-split-pos')));
        }
        
        // Get form data
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $nano_address = isset($_POST['nano_address']) ? sanitize_text_field($_POST['nano_address']) : '';
        
        // Validate data
        if (empty($name) || empty($nano_address)) {
            wp_send_json_error(array('message' => __('Please enter valid employee details', 'nano-split-pos')));
        }
        
        // Get existing employees
        $employees = get_option('nano_split_pos_employees', array());
        
        // Add new employee
        $employees[] = array(
            'id' => uniqid('emp_'),
            'name' => $name,
            'nano_address' => $nano_address
        );
        
        // Save employees
        update_option('nano_split_pos_employees', $employees);
        
        wp_send_json_success(array(
            'message' => __('Employee added successfully', 'nano-split-pos'),
            'employees' => $employees
        ));
    }
    
    /**
     * Edit employee AJAX handler
     */
    public function edit_employee() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nano-split-pos')));
        }
        
        // Get form data
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $nano_address = isset($_POST['nano_address']) ? sanitize_text_field($_POST['nano_address']) : '';
        
        // Validate data
        if (empty($id) || empty($name) || empty($nano_address)) {
            wp_send_json_error(array('message' => __('Please enter valid employee details', 'nano-split-pos')));
        }
        
        // Get existing employees
        $employees = get_option('nano_split_pos_employees', array());
        
        // Find and update employee
        $updated = false;
        foreach ($employees as $key => $employee) {
            if ($employee['id'] === $id) {
                $employees[$key]['name'] = $name;
                $employees[$key]['nano_address'] = $nano_address;
                $updated = true;
                break;
            }
        }
        
        if (!$updated) {
            wp_send_json_error(array('message' => __('Employee not found', 'nano-split-pos')));
        }
        
        // Save employees
        update_option('nano_split_pos_employees', $employees);
        
        wp_send_json_success(array(
            'message' => __('Employee updated successfully', 'nano-split-pos'),
            'employees' => $employees
        ));
    }
    
    /**
     * Delete employee AJAX handler
     */
    public function delete_employee() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nano-split-pos')));
        }
        
        // Get employee ID
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        
        if (empty($id)) {
            wp_send_json_error(array('message' => __('Invalid employee ID', 'nano-split-pos')));
        }
        
        // Get existing employees
        $employees = get_option('nano_split_pos_employees', array());
        
        // Find and remove employee
        $removed = false;
        foreach ($employees as $key => $employee) {
            if ($employee['id'] === $id) {
                unset($employees[$key]);
                $employees = array_values($employees); // Reindex array
                $removed = true;
                break;
            }
        }
        
        if (!$removed) {
            wp_send_json_error(array('message' => __('Employee not found', 'nano-split-pos')));
        }
        
        // Save employees
        update_option('nano_split_pos_employees', $employees);
        
        wp_send_json_success(array(
            'message' => __('Employee removed successfully', 'nano-split-pos'),
            'employees' => $employees
        ));
    }
} 