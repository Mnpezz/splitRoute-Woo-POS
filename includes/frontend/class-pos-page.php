<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Nano_Split_POS_Page {
    /**
     * Initialize POS page
     */
    public function init() {
        // Register REST API endpoint for payment callback
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Add custom rewrite rule for POS page
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Filter content for POS page
        add_filter('the_content', array($this, 'filter_content'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('nano-split-pos/v1', '/payment-callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'payment_callback'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^nano-pos/?$', 'index.php?pagename=nano-pos', 'top');
        flush_rewrite_rules();
    }
    
    /**
     * Filter content for POS page
     */
    public function filter_content($content) {
        global $post;
        
        if (is_page() && $post->post_name === 'nano-pos') {
            return do_shortcode('[nano_split_pos]');
        }
        
        return $content;
    }
    
    /**
     * Payment callback handler
     */
    public function payment_callback($request) {
        $params = $request->get_params();
        
        // Validate callback data
        if (!isset($params['reference']) || !isset($params['status'])) {
            return new WP_Error('invalid_callback', __('Invalid callback data', 'nano-split-pos'), array('status' => 400));
        }
        
        $order_id = sanitize_text_field($params['reference']);
        $status = sanitize_text_field($params['status']);
        
        // Update payment status
        global $wpdb;
        $payment_table = $wpdb->prefix . 'nano_split_payments';
        $wpdb->update(
            $payment_table,
            array('status' => $status),
            array('order_id' => $order_id)
        );
        
        // Return success response
        return array(
            'success' => true,
            'message' => __('Payment status updated', 'nano-split-pos')
        );
    }
} 