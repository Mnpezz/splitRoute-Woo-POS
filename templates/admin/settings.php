<div class="wrap">
    <h1><?php _e('Nano Split POS Settings', 'nano-split-pos'); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('nano_split_pos_settings'); ?>
        <?php do_settings_sections('nano_split_pos_settings'); ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('SplitRoute API Key', 'nano-split-pos'); ?></th>
                <td>
                    <input type="password" name="nano_split_pos_api_key" value="<?php echo esc_attr(get_option('nano_split_pos_api_key')); ?>" class="regular-text" />
                    <p class="description"><?php _e('Enter your SplitRoute API key for payment splitting', 'nano-split-pos'); ?></p>
                    <?php if (empty(get_option('nano_split_pos_api_key'))): ?>
                        <p><a href="#" class="button" id="nano-split-pos-register-api-key"><?php _e('Register for API Key', 'nano-split-pos'); ?></a></p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Enable Tips', 'nano-split-pos'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="nano_split_pos_enable_tips" value="1" <?php checked(get_option('nano_split_pos_enable_tips'), 1); ?> />
                        <?php _e('Allow employees to receive tips directly to their Nano address', 'nano-split-pos'); ?>
                    </label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Tip Percentages', 'nano-split-pos'); ?></th>
                <td>
                    <input type="text" name="nano_split_pos_tip_percentages" value="<?php echo esc_attr(get_option('nano_split_pos_tip_percentages', '5,10,15,20')); ?>" class="regular-text" />
                    <p class="description"><?php _e('Enter comma-separated tip percentages for quick selection', 'nano-split-pos'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Enable Products', 'nano-split-pos'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="nano_split_pos_enable_products" value="1" <?php checked(get_option('nano_split_pos_enable_products'), 1); ?> />
                        <?php _e('Allow adding WordPress products to the POS', 'nano-split-pos'); ?>
                    </label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Tax Rate', 'nano-split-pos'); ?></th>
                <td>
                    <input type="number" name="nano_split_pos_tax_rate" value="<?php echo esc_attr(get_option('nano_split_pos_tax_rate', '0')); ?>" step="0.01" min="0" max="100" class="small-text" />
                    <p class="description"><?php _e('Enter the tax rate percentage to apply to all transactions', 'nano-split-pos'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('Test Mode', 'nano-split-pos'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="nano_split_pos_test_mode" value="1" <?php checked(get_option('nano_split_pos_test_mode', true), 1); ?> />
                        <?php _e('Enable test mode (automatically marks payments as complete after 10 seconds)', 'nano-split-pos'); ?>
                    </label>
                    <p class="description"><?php _e('Disable this in production to require actual Nano payments via SplitRoute', 'nano-split-pos'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="nano-split-pos-info">
        <h2><?php _e('Shortcode', 'nano-split-pos'); ?></h2>
        <p><?php _e('Use the following shortcode to display the POS on any page:', 'nano-split-pos'); ?></p>
        <code>[nano_split_pos]</code>
        
        <h2><?php _e('Dedicated POS Page', 'nano-split-pos'); ?></h2>
        <p><?php _e('You can also access the POS at:', 'nano-split-pos'); ?></p>
        <code><?php echo home_url('/nano-pos/'); ?></code>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#nano-split-pos-register-api-key').on('click', function(e) {
        e.preventDefault();
        
        $(this).text('Registering...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nano_split_register_api_key',
                nonce: '<?php echo wp_create_nonce('nano_split_pos_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('API key registered successfully! Refreshing page...');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    $('#nano-split-pos-register-api-key').text('Register for API Key').prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('#nano-split-pos-register-api-key').text('Register for API Key').prop('disabled', false);
            }
        });
    });
});
</script> 