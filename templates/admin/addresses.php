<div class="wrap">
    <h1><?php _e('Payment Addresses', 'nano-split-pos'); ?></h1>
    
    <div class="nano-split-pos-addresses">
        <h2><?php _e('Add New Address', 'nano-split-pos'); ?></h2>
        
        <form id="nano-split-pos-add-address-form" class="nano-split-pos-form">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Nickname', 'nano-split-pos'); ?></th>
                    <td>
                        <input type="text" name="nickname" class="regular-text" required />
                        <p class="description"><?php _e('A friendly name for this address (e.g., "Sales Tax", "Supplier Payment")', 'nano-split-pos'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Nano Address', 'nano-split-pos'); ?></th>
                    <td>
                        <input type="text" name="nano_address" class="regular-text" required />
                        <p class="description"><?php _e('The Nano address to receive payments', 'nano-split-pos'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Percentage', 'nano-split-pos'); ?></th>
                    <td>
                        <input type="number" name="percentage" min="0.01" max="100" step="0.01" required />
                        <p class="description"><?php _e('Percentage of the payment to send to this address', 'nano-split-pos'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Add Address', 'nano-split-pos'); ?></button>
                <span class="spinner"></span>
            </p>
        </form>
        
        <div id="nano-split-pos-address-message" class="notice" style="display: none;"></div>
        
        <h2><?php _e('Existing Addresses', 'nano-split-pos'); ?></h2>
        
        <div class="nano-split-pos-addresses-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Nickname', 'nano-split-pos'); ?></th>
                        <th><?php _e('Nano Address', 'nano-split-pos'); ?></th>
                        <th><?php _e('Percentage', 'nano-split-pos'); ?></th>
                        <th><?php _e('Status', 'nano-split-pos'); ?></th>
                        <th><?php _e('Actions', 'nano-split-pos'); ?></th>
                    </tr>
                </thead>
                <tbody id="nano-split-pos-addresses-tbody">
                    <?php if (empty($addresses)) : ?>
                        <tr>
                            <td colspan="5"><?php _e('No addresses found.', 'nano-split-pos'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($addresses as $address) : ?>
                            <tr data-id="<?php echo esc_attr($address->id); ?>">
                                <td><?php echo esc_html($address->nickname); ?></td>
                                <td><?php echo esc_html($address->nano_address); ?></td>
                                <td><?php echo esc_html($address->percentage); ?>%</td>
                                <td><?php echo $address->is_active ? __('Active', 'nano-split-pos') : __('Inactive', 'nano-split-pos'); ?></td>
                                <td>
                                    <button type="button" class="button edit-address"><?php _e('Edit', 'nano-split-pos'); ?></button>
                                    <button type="button" class="button button-link-delete delete-address"><?php _e('Delete', 'nano-split-pos'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit Address Modal -->
    <div id="nano-split-pos-edit-address-modal" class="nano-split-pos-modal" style="display: none;">
        <div class="nano-split-pos-modal-content">
            <span class="nano-split-pos-modal-close">&times;</span>
            <h2><?php _e('Edit Address', 'nano-split-pos'); ?></h2>
            
            <form id="nano-split-pos-edit-address-form" class="nano-split-pos-form">
                <input type="hidden" name="id" value="" />
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Nickname', 'nano-split-pos'); ?></th>
                        <td>
                            <input type="text" name="nickname" class="regular-text" required />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Nano Address', 'nano-split-pos'); ?></th>
                        <td>
                            <input type="text" name="nano_address" class="regular-text" required />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Percentage', 'nano-split-pos'); ?></th>
                        <td>
                            <input type="number" name="percentage" min="0.01" max="100" step="0.01" required />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Status', 'nano-split-pos'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_active" value="1" />
                                <?php _e('Active', 'nano-split-pos'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Update Address', 'nano-split-pos'); ?></button>
                    <span class="spinner"></span>
                </p>
            </form>
        </div>
    </div>
</div> 