<div class="nano-split-pos-container">
    <div class="nano-split-pos-header">
        <h2><?php _e('Nano Split Point of Sale', 'nano-split-pos'); ?></h2>
    </div>
    
    <div class="nano-split-pos-content">
        <div class="nano-split-pos-cart">
            <h3><?php _e('Cart', 'nano-split-pos'); ?></h3>
            
            <div class="nano-split-pos-cart-items">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Item', 'nano-split-pos'); ?></th>
                            <th><?php _e('Price', 'nano-split-pos'); ?></th>
                            <th><?php _e('Quantity', 'nano-split-pos'); ?></th>
                            <th><?php _e('Total', 'nano-split-pos'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="nano-split-pos-cart-items">
                        <!-- Cart items will be added here dynamically -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><?php _e('Subtotal', 'nano-split-pos'); ?></td>
                            <td id="nano-split-pos-subtotal">0.00</td>
                            <td></td>
                        </tr>
                        <tr id="nano-split-pos-tax-row" <?php echo get_option('nano_split_pos_use_woo_tax', false) ? '' : 'style="display:none;"'; ?>>
                            <td colspan="3" class="nano-split-pos-summary-label"><?php _e('Tax', 'nano-split-pos'); ?></td>
                            <td id="nano-split-pos-tax-amount">0.000000</td>
                        </tr>
                        <?php if (get_option('nano_split_pos_enable_tips', false)) : ?>
                        <tr id="nano-split-pos-tip-row" style="display: none;">
                            <td colspan="3"><?php _e('Tip', 'nano-split-pos'); ?></td>
                            <td id="nano-split-pos-tip-amount">0.00</td>
                            <td></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="3"><?php _e('Total', 'nano-split-pos'); ?></td>
                            <td id="nano-split-pos-total">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="nano-split-pos-cart-actions">
                <button id="nano-split-pos-clear-cart" class="button"><?php _e('Clear Cart', 'nano-split-pos'); ?></button>
            </div>
        </div>
        
        <div class="nano-split-pos-input">
            <h3><?php _e('Add Item', 'nano-split-pos'); ?></h3>
            
            <div class="nano-split-pos-manual-input">
                <form id="nano-split-pos-manual-form">
                    <div class="form-row">
                        <label for="nano-split-pos-item-name"><?php _e('Item Name', 'nano-split-pos'); ?></label>
                        <input type="text" id="nano-split-pos-item-name" required />
                    </div>
                    
                    <div class="form-row">
                        <label for="nano-split-pos-item-price"><?php _e('Price (NANO)', 'nano-split-pos'); ?></label>
                        <input type="number" id="nano-split-pos-item-price" min="0.000001" step="0.000001" required />
                    </div>
                    
                    <div class="form-row">
                        <label for="nano-split-pos-item-quantity"><?php _e('Quantity', 'nano-split-pos'); ?></label>
                        <input type="number" id="nano-split-pos-item-quantity" min="1" value="1" required />
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="button button-primary"><?php _e('Add to Cart', 'nano-split-pos'); ?></button>
                    </div>
                </form>
            </div>
            
            <?php if (get_option('nano_split_pos_enable_products', false)) : ?>
            <div class="nano-split-pos-products">
                <h3><?php _e('Products', 'nano-split-pos'); ?></h3>
                
                <div class="nano-split-pos-products-grid">
                    <?php
                    $args = array(
                        'post_type' => 'product',
                        'posts_per_page' => 12,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    );
                    
                    $products = new WP_Query($args);
                    
                    if ($products->have_posts()) :
                        while ($products->have_posts()) : $products->the_post();
                            $product = wc_get_product(get_the_ID());
                            ?>
                            <div class="nano-split-pos-product" data-id="<?php echo esc_attr($product->get_id()); ?>" data-name="<?php echo esc_attr($product->get_name()); ?>" data-price="<?php echo esc_attr($product->get_price()); ?>">
                                <?php if ($product->get_image_id()) : ?>
                                    <div class="nano-split-pos-product-image">
                                        <?php echo $product->get_image('thumbnail'); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="nano-split-pos-product-details">
                                    <h4><?php echo esc_html($product->get_name()); ?></h4>
                                    <p class="price"><?php echo esc_html($product->get_price()); ?> NANO</p>
                                </div>
                            </div>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p>' . __('No products found.', 'nano-split-pos') . '</p>';
                    endif;
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nano-split-pos-checkout">
        <?php if (get_option('nano_split_pos_enable_tips', false)) : ?>
        <div class="nano-split-pos-tip-section">
            <h3><?php _e('Add Tip', 'nano-split-pos'); ?></h3>
            
            <div class="nano-split-pos-tip-options">
                <?php
                $tip_percentages = explode(',', get_option('nano_split_pos_tip_percentages', '5,10,15,20'));
                foreach ($tip_percentages as $percentage) :
                    $percentage = trim($percentage);
                    ?>
                    <button type="button" class="nano-split-pos-tip-btn" data-percentage="<?php echo esc_attr($percentage); ?>"><?php echo esc_html($percentage); ?>%</button>
                    <?php
                endforeach;
                ?>
                <button type="button" class="nano-split-pos-tip-btn nano-split-pos-tip-custom"><?php _e('Custom', 'nano-split-pos'); ?></button>
            </div>
            
            <div class="nano-split-pos-employee-address">
                <label for="nano-split-pos-employee-select"><?php _e('Employee:', 'nano-split-pos'); ?></label>
                <select id="nano-split-pos-employee-select">
                    <option value=""><?php _e('Select employee', 'nano-split-pos'); ?></option>
                    <?php 
                    $employees = get_option('nano_split_pos_employees', array());
                    error_log('Nano Split POS - Employees loaded: ' . print_r($employees, true));
                    foreach ($employees as $employee) : 
                    ?>
                        <option value="<?php echo esc_attr($employee['nano_address']); ?>" data-id="<?php echo esc_attr($employee['id']); ?>"><?php echo esc_html($employee['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="custom"><?php _e('Custom address', 'nano-split-pos'); ?></option>
                </select>
                
                <div id="nano-split-pos-custom-address-container" style="display: none; margin-top: 10px;">
                    <label for="nano-split-pos-employee-address"><?php _e('Nano Address:', 'nano-split-pos'); ?></label>
                    <input type="text" id="nano-split-pos-employee-address" placeholder="<?php _e('Enter Nano address', 'nano-split-pos'); ?>">
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="nano-split-pos-payment-section">
            <h3><?php _e('Payment', 'nano-split-pos'); ?></h3>
            
            <div class="nano-split-pos-payment-actions">
                <button id="nano-split-pos-checkout-btn" class="button button-primary button-large"><?php _e('Checkout with Nano', 'nano-split-pos'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div id="nano-split-pos-payment-modal" class="nano-split-pos-modal" style="display: none;">
        <div class="nano-split-pos-modal-content">
            <span class="nano-split-pos-modal-close">&times;</span>
            <h2><?php _e('Complete Payment', 'nano-split-pos'); ?></h2>
            
            <div class="nano-split-pos-payment-details">
                <div class="nano-split-pos-payment-qr">
                    <div id="nano-split-pos-qr-code"></div>
                </div>
                
                <div class="nano-split-pos-payment-info">
                    <p><strong><?php _e('Amount:', 'nano-split-pos'); ?></strong> <span id="nano-split-pos-modal-amount"></span> NANO</p>
                    <p><strong><?php _e('Address:', 'nano-split-pos'); ?></strong> <span id="nano-split-pos-modal-address"></span></p>
                    <p class="nano-split-pos-payment-status">
                        <strong><?php _e('Status:', 'nano-split-pos'); ?></strong> 
                        <span id="nano-split-pos-modal-status"><?php _e('Waiting for payment...', 'nano-split-pos'); ?></span>
                    </p>
                </div>
            </div>
            
            <div class="nano-split-pos-payment-actions">
                <button id="nano-split-pos-copy-address" class="button"><?php _e('Copy Address', 'nano-split-pos'); ?></button>
                <button id="nano-split-pos-cancel-payment" class="button"><?php _e('Cancel', 'nano-split-pos'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Custom Tip Modal -->
    <div id="nano-split-pos-custom-tip-modal" class="nano-split-pos-modal" style="display: none;">
        <div class="nano-split-pos-modal-content">
            <span class="nano-split-pos-modal-close">&times;</span>
            <h2><?php _e('Enter Custom Tip', 'nano-split-pos'); ?></h2>
            
            <div class="nano-split-pos-custom-tip-form">
                <div class="form-row">
                    <label for="nano-split-pos-custom-tip-amount"><?php _e('Tip Amount (NANO)', 'nano-split-pos'); ?></label>
                    <input type="number" id="nano-split-pos-custom-tip-amount" min="0.000001" step="0.000001" required />
                </div>
                
                <div class="form-row">
                    <button id="nano-split-pos-apply-custom-tip" class="button button-primary"><?php _e('Apply Tip', 'nano-split-pos'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div> 
