<?php
/**
 * Plugin Name: Nano Split POS
 * Description: A Point of Sale system for Nano cryptocurrency with payment splitting capabilities
 * API URL: https://console.splitroute.com/dashboard/user
 * Version: 1.1.0
 * Author: mnpezz
 * Text Domain: nano-split-pos
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NANO_SPLIT_POS_VERSION', '1.0.0');
define('NANO_SPLIT_POS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NANO_SPLIT_POS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once NANO_SPLIT_POS_PLUGIN_DIR . 'includes/class-nano-split-pos.php';
require_once NANO_SPLIT_POS_PLUGIN_DIR . 'includes/admin/class-admin-settings.php';
require_once NANO_SPLIT_POS_PLUGIN_DIR . 'includes/admin/class-admin-employees.php';
require_once NANO_SPLIT_POS_PLUGIN_DIR . 'includes/frontend/class-pos-page.php';

// Initialize the plugin
function nano_split_pos_init() {
    $plugin = new Nano_Split_POS();
    $plugin->init();
    
    // Initialize admin employees
    if (is_admin()) {
        $admin_employees = new Nano_Split_POS_Admin_Employees();
        $admin_employees->init();
    }
}
add_action('plugins_loaded', 'nano_split_pos_init');

// Activation hook
register_activation_hook(__FILE__, 'nano_split_pos_activate');
function nano_split_pos_activate() {
    // Create necessary database tables
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for storing payment addresses
    $table_name = $wpdb->prefix . 'nano_split_addresses';
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nickname varchar(100) NOT NULL,
        nano_address varchar(255) NOT NULL,
        percentage decimal(5,2) NOT NULL,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    // Table for storing payment records
    $payment_table = $wpdb->prefix . 'nano_split_payments';
    $payment_sql = "CREATE TABLE $payment_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id varchar(100) NOT NULL,
        invoice_id varchar(100) NOT NULL,
        amount decimal(20,6) NOT NULL,
        status varchar(50) NOT NULL,
        employee_address varchar(255) NULL,
        tip_amount decimal(20,6) DEFAULT 0,
        tax_amount decimal(20,6) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($payment_sql);
}

// Update the database schema function to add the missing invoice_id column
function nano_split_pos_update_db_schema() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if tax_amount column exists in the payments table
    $payment_table = $wpdb->prefix . 'nano_split_payments';
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $payment_table LIKE 'tax_amount'");
    
    if (empty($column_exists)) {
        // Add tax_amount column if it doesn't exist
        $wpdb->query("ALTER TABLE $payment_table ADD COLUMN tax_amount decimal(20,6) DEFAULT 0 AFTER tip_amount");
        error_log('Added tax_amount column to nano_split_payments table');
    }
    
    // Check if invoice_id column exists in the payments table
    $invoice_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $payment_table LIKE 'invoice_id'");
    
    if (empty($invoice_column_exists)) {
        // Add invoice_id column if it doesn't exist
        $wpdb->query("ALTER TABLE $payment_table ADD COLUMN invoice_id varchar(100) NOT NULL AFTER order_id");
        error_log('Added invoice_id column to nano_split_payments table');
    }
    
    return true;
}

// Run the update function on plugin load
add_action('plugins_loaded', 'nano_split_pos_update_db_schema', 5); // Run before init

// Register scripts and styles
function nano_split_pos_register_scripts() {
    // Admin styles and scripts
    wp_register_style('nano-split-pos-admin', NANO_SPLIT_POS_PLUGIN_URL . 'assets/css/admin.css', array(), NANO_SPLIT_POS_VERSION);
    wp_register_script('nano-split-pos-admin', NANO_SPLIT_POS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), NANO_SPLIT_POS_VERSION, true);
    
    // Frontend styles and scripts
    wp_register_style('nano-split-pos-style', NANO_SPLIT_POS_PLUGIN_URL . 'assets/css/pos.css', array(), NANO_SPLIT_POS_VERSION);
    
    // Use CDN for QR code library instead of local file
    wp_register_script('nano-split-pos-qrcode', 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js', array(), '1.0.0', true);
    wp_register_script('nano-split-pos-script', NANO_SPLIT_POS_PLUGIN_URL . 'assets/js/pos.js', array('jquery', 'nano-split-pos-qrcode'), NANO_SPLIT_POS_VERSION, true);
}
add_action('wp_enqueue_scripts', 'nano_split_pos_register_scripts');
add_action('admin_enqueue_scripts', 'nano_split_pos_register_scripts'); 
