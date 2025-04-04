<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Nano_Split_POS {
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load plugin textdomain
        add_action('init', array($this, 'load_textdomain'));
        
        // Initialize admin settings
        $admin_settings = new Nano_Split_POS_Admin_Settings();
        $admin_settings->init();
        
        // Initialize POS page
        $pos_page = new Nano_Split_POS_Page();
        $pos_page->init();
        
        // Register shortcode for POS page
        add_shortcode('nano_split_pos', array($this, 'pos_shortcode'));
        
        // Add AJAX handlers
        add_action('wp_ajax_nano_split_create_payment', array($this, 'create_payment'));
        add_action('wp_ajax_nopriv_nano_split_create_payment', array($this, 'create_payment'));
        add_action('wp_ajax_nano_split_check_payment', array($this, 'check_payment_status'));
        add_action('wp_ajax_nopriv_nano_split_check_payment', array($this, 'check_payment_status'));
        
        // Register REST API routes for webhook
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('nano-split-pos', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * POS shortcode callback
     */
    public function pos_shortcode() {
        // Enqueue styles and scripts
        wp_enqueue_style('nano-split-pos-style');
        wp_enqueue_script('nano-split-pos-qrcode');
        wp_enqueue_script('nano-split-pos-script');
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('nano-split-pos-script', 'nano_split_pos', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nano_split_pos_nonce'),
            'currency' => 'NANO',
            'tax_rate' => floatval(get_option('nano_split_pos_tax_rate', 0))
        ));
        
        // Get products if enabled
        $products = array();
        if (get_option('nano_split_pos_enable_products', false) && function_exists('wc_get_products')) {
            $products = wc_get_products(array(
                'status' => 'publish',
                'limit' => 20,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
        }
        
        // Get tip percentages
        $tip_percentages = array();
        if (get_option('nano_split_pos_enable_tips', false)) {
            $tip_percentages_string = get_option('nano_split_pos_tip_percentages', '5,10,15,20');
            $tip_percentages = array_map('trim', explode(',', $tip_percentages_string));
        }
        
        // Start output buffering
        ob_start();
        
        // Include POS template
        include NANO_SPLIT_POS_PLUGIN_DIR . 'templates/pos-page.php';
        
        // Return buffered content
        return ob_get_clean();
    }
    
    /**
     * Get tax rate
     */
    private function get_tax_rate() {
        // Get tax rate directly from settings
        $tax_rate = floatval(get_option('nano_split_pos_tax_rate', 0));
        
        // For debugging
        error_log('Nano Split POS - Using tax rate: ' . $tax_rate);
        
        return $tax_rate;
    }
    
    /**
     * Create payment AJAX handler with SplitRoute API integration
     */
    public function create_payment() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        try {
            // Get payment data
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $employee_address = isset($_POST['employee_address']) ? sanitize_text_field($_POST['employee_address']) : '';
            $tip_amount = isset($_POST['tip_amount']) ? floatval($_POST['tip_amount']) : 0;
            $tax_amount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
            
            // Calculate total amount including tip
            $total_amount = $amount + $tip_amount;
            
            // Debug log
            error_log('Payment data - Base Amount: ' . $amount . ', Tip: ' . $tip_amount . ', Total: ' . $total_amount . ', Employee: ' . $employee_address . ', Tax: ' . $tax_amount);
            
            if ($amount <= 0) {
                wp_send_json_error(array('message' => __('Invalid amount', 'nano-split-pos')));
            }
            
            // Get active payment addresses
            global $wpdb;
            $addresses_table = $wpdb->prefix . 'nano_split_addresses';
            $addresses = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $addresses_table WHERE is_active = %d",
                    1
                )
            );
            
            if (empty($addresses)) {
                wp_send_json_error(array('message' => __('No payment addresses configured', 'nano-split-pos')));
            }
            
            // Generate unique order ID
            $order_id = 'order_' . time() . '_' . mt_rand(1000, 9999);
            
            // Check if we're in test mode
            $test_mode = get_option('nano_split_pos_test_mode', true);
            error_log('Test mode: ' . ($test_mode ? 'Enabled' : 'Disabled'));
            
            if ($test_mode) {
                // In test mode, use the first address as the payment address
                $payment_address = $addresses[0]->nano_address;
                $invoice_id = 'test_invoice_' . $order_id;
                $payment_amount = $total_amount; // Use total amount including tip
                $payment_uri = 'nano:' . $payment_address . '?amount=' . $total_amount;
                
                error_log('Using test mode with address: ' . $payment_address);
            } else {
                // In production mode, use SplitRoute API
                $api_key = get_option('nano_split_pos_api_key');
                
                if (empty($api_key)) {
                    error_log('API key not configured, falling back to test mode');
                    $payment_address = $addresses[0]->nano_address;
                    $invoice_id = 'test_invoice_' . $order_id;
                    $payment_amount = $total_amount; // Use total amount including tip
                    $payment_uri = 'nano:' . $payment_address . '?amount=' . $total_amount;
                } else {
                    // Prepare destinations array for SplitRoute API
                    $destinations = array();
                    $total_percentage = 0;
                    
                    // First address is primary, others are secondary with adjusted percentages
                    $first = true;
                    foreach ($addresses as $address) {
                        if ($first) {
                            $destinations[] = array(
                                'account' => $address->nano_address,
                                'primary' => true
                            );
                            $first = false;
                        } else {
                            // Calculate adjusted percentage
                            $adjusted_percentage = ($total_percentage > 0) 
                                ? (floatval($address->percentage) / $total_percentage) * 100 
                                : 0;
                            
                            $destinations[] = array(
                                'account' => $address->nano_address,
                                'percentage' => $adjusted_percentage,
                                'description' => $address->nickname
                            );
                        }
                        $total_percentage += floatval($address->percentage);
                    }
                    
                    // Add employee tip address if provided
                    if (!empty($employee_address) && $tip_amount > 0) {
                        $destinations[] = array(
                            'account' => $employee_address,
                            'nominal_amount' => $tip_amount,
                            'description' => 'Tip'
                        );
                    }
                    
                    // Create invoice via SplitRoute API
                    $request_body = array(
                        'nominal_amount' => $amount, // Base amount without tip
                        'nominal_currency' => 'USD',
                        'destinations' => $destinations,
                        'show_qr' => true,
                        'reference' => $order_id
                    );
                    
                    error_log('SplitRoute API request: ' . json_encode($request_body));
                    
                    $response = wp_remote_post('https://api.splitroute.com/api/v1/invoices', array(
                        'headers' => array(
                            'Content-Type' => 'application/json',
                            'X-API-Key' => $api_key
                        ),
                        'body' => json_encode($request_body),
                        'timeout' => 30
                    ));
                    
                    if (is_wp_error($response)) {
                        error_log('API request error: ' . $response->get_error_message());
                        throw new Exception(__('Payment service connection error.', 'nano-split-pos'));
                    }
                    
                    $response_code = wp_remote_retrieve_response_code($response);
                    $response_body = wp_remote_retrieve_body($response);
                    
                    error_log('API response code: ' . $response_code);
                    error_log('API response: ' . $response_body);
                    
                    $invoice = json_decode($response_body, true);
                    
                    if (!$invoice || !isset($invoice['invoice_id'])) {
                        error_log('Invalid API response: ' . $response_body);
                        throw new Exception(__('Invalid response from payment service.', 'nano-split-pos'));
                    }
                    
                    // Extract payment details from the invoice
                    $invoice_id = $invoice['invoice_id'];
                    $payment_address = $invoice['account_address'];
                    $payment_amount = $invoice['required']['formatted_amount'];
                    $payment_uri = isset($invoice['uri_nano']) ? $invoice['uri_nano'] : 'nano:' . $payment_address . '?amount=' . $payment_amount;
                    
                    error_log('Created invoice: ' . $invoice_id . ', Address: ' . $payment_address . ', Amount: ' . $payment_amount);
                }
            }
            
            // Create payment record in database
            $payment_table = $wpdb->prefix . 'nano_split_payments';
            $table_structure = $wpdb->get_results("DESCRIBE $payment_table");
            
            // Create a payment record
            $data = array(
                'order_id' => $order_id,
                'amount' => $total_amount, // Store total amount including tip
                'status' => 'pending',
                'employee_address' => $employee_address,
                'tip_amount' => $tip_amount,
                'tax_amount' => $tax_amount
            );
            
            // Add invoice_id if the column exists
            $invoice_column_exists = false;
            foreach ($table_structure as $column) {
                if ($column->Field === 'invoice_id') {
                    $invoice_column_exists = true;
                    break;
                }
            }
            
            if ($invoice_column_exists) {
                $data['invoice_id'] = $invoice_id;
            }
            
            $result = $wpdb->insert($payment_table, $data);
            
            if ($result === false) {
                error_log('Database error in create_payment: ' . $wpdb->last_error);
                wp_send_json_error(array('message' => __('Database error: ', 'nano-split-pos') . $wpdb->last_error));
                return;
            }
            
            // Return payment data
            $response_data = array(
                'order_id' => $order_id,
                'payment_data' => array(
                    'address' => $payment_address,
                    'amount' => $payment_amount,
                    'uri_nano' => $payment_uri,
                    'invoice_id' => $invoice_id
                )
            );
            error_log('Sending response to client: ' . json_encode($response_data));
            wp_send_json_success($response_data);
        } catch (Exception $e) {
            error_log('Exception in create_payment: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred: ', 'nano-split-pos') . $e->getMessage()));
        }
    }
    
    /**
     * Check payment status AJAX handler with SplitRoute API integration
     */
    public function check_payment_status() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nano_split_pos_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'nano-split-pos')));
        }
        
        // Get order ID
        $order_id = isset($_POST['order_id']) ? sanitize_text_field($_POST['order_id']) : '';
        
        if (empty($order_id)) {
            wp_send_json_error(array('message' => __('Invalid order ID', 'nano-split-pos')));
        }
        
        // Get payment details from database
        global $wpdb;
        $payment_table = $wpdb->prefix . 'nano_split_payments';
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT id, invoice_id, status, created_at, amount, employee_address FROM $payment_table WHERE order_id = %s",
            $order_id
        ));
        
        if (!$payment) {
            wp_send_json_error(array('message' => __('Payment not found', 'nano-split-pos')));
        }
        
        // Check if we're in test mode
        $test_mode = get_option('nano_split_pos_test_mode', true);
        
        if ($test_mode) {
            // For testing purposes, simulate payment completion after 10 seconds
            $created_time = strtotime($payment->created_at);
            $elapsed_seconds = time() - $created_time;
            
            $status = $payment->status;
            
            if ($status === 'pending' && $elapsed_seconds > 10) {
                $wpdb->update(
                    $payment_table,
                    array('status' => 'completed'),
                    array('order_id' => $order_id)
                );
                $status = 'completed';
            }
        } else {
            // In production mode, check payment status using SplitRoute API
            $api_key = get_option('nano_split_pos_api_key');
            
            if (empty($api_key) || empty($payment->invoice_id)) {
                // If no API key or invoice ID, fall back to current status
                $status = $payment->status;
            } else {
                // Check invoice status with SplitRoute API
                $response = wp_remote_get('https://api.splitroute.com/api/v1/invoices/' . $payment->invoice_id, array(
                    'headers' => array(
                        'X-API-Key' => $api_key
                    ),
                    'timeout' => 15
                ));
                
                if (is_wp_error($response)) {
                    error_log('SplitRoute API error: ' . $response->get_error_message());
                    $status = $payment->status; // Use current status if API call fails
                } else {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    
                    if (wp_remote_retrieve_response_code($response) !== 200) {
                        error_log('SplitRoute API error: ' . print_r($body, true));
                        $status = $payment->status; // Use current status if API call fails
                    } else {
                        // Update status based on API response
                        if ($body['is_paid']) {
                            $status = 'completed';
                            
                            // Update status in database if it's changed
                            if ($payment->status !== 'completed') {
                                $wpdb->update(
                                    $payment_table,
                                    array('status' => 'completed'),
                                    array('order_id' => $order_id)
                                );
                            }
                        } else if ($body['is_expired']) {
                            $status = 'expired';
                            
                            // Update status in database if it's changed
                            if ($payment->status !== 'expired') {
                                $wpdb->update(
                                    $payment_table,
                                    array('status' => 'expired'),
                                    array('order_id' => $order_id)
                                );
                            }
                        } else {
                            $status = $payment->status; // Keep current status
                        }
                    }
                }
            }
        }
        
        wp_send_json_success(array('status' => $status));
    }
    
    /**
     * Register REST API routes for webhook
     */
    public function register_rest_routes() {
        register_rest_route('nano-split-pos/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Handle SplitRoute webhook
     */
    public function handle_webhook($request) {
        $body = $request->get_json_params();
        $headers = $request->get_headers();
        
        // Verify webhook signature (in a real implementation)
        // $signature = $headers['x_webhook_signature'][0] ?? '';
        // $timestamp = $headers['x_webhook_timestamp'][0] ?? '';
        // $webhook_secret = '...'; // Get from database
        // if (!verify_signature($body, $signature, $timestamp, $webhook_secret)) {
        //     return new WP_Error('invalid_signature', 'Invalid webhook signature', array('status' => 401));
        // }
        
        // Process webhook
        if (isset($body['payload']['event_type'])) {
            $event_type = $body['payload']['event_type'];
            $invoice_id = $body['payload']['id'];
            
            error_log('Received webhook for invoice ' . $invoice_id . ', event: ' . $event_type);
            
            // Update payment status in database
            global $wpdb;
            $payment_table = $wpdb->prefix . 'nano_split_payments';
            
            switch ($event_type) {
                case 'invoice.paid':
                    $wpdb->update(
                        $payment_table,
                        array('status' => 'completed'),
                        array('invoice_id' => $invoice_id)
                    );
                    break;
                    
                case 'invoice.expired':
                    $wpdb->update(
                        $payment_table,
                        array('status' => 'expired'),
                        array('invoice_id' => $invoice_id)
                    );
                    break;
            }
        }
        
        return rest_ensure_response(array('status' => 'success'));
    }
} 