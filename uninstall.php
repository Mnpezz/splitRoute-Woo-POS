<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('nano_split_pos_api_key');
delete_option('nano_split_pos_enable_tips');
delete_option('nano_split_pos_tip_percentages');
delete_option('nano_split_pos_enable_products');

// Delete database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nano_split_addresses");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nano_split_payments");

// Clear any cached data that has been cached
wp_cache_flush(); 