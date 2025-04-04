<div class="wrap">
    <h1><?php _e('Payment History', 'nano-split-pos'); ?></h1>
    
    <div class="nano-split-pos-history">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Order ID', 'nano-split-pos'); ?></th>
                    <th><?php _e('Amount', 'nano-split-pos'); ?></th>
                    <th><?php _e('Status', 'nano-split-pos'); ?></th>
                    <th><?php _e('Employee Address', 'nano-split-pos'); ?></th>
                    <th><?php _e('Tip Amount', 'nano-split-pos'); ?></th>
                    <th><?php _e('Date', 'nano-split-pos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)) : ?>
                    <tr>
                        <td colspan="6"><?php _e('No payments found.', 'nano-split-pos'); ?></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($payments as $payment) : ?>
                        <tr>
                            <td><?php echo esc_html($payment->order_id); ?></td>
                            <td><?php echo esc_html(number_format($payment->amount, 6)); ?> NANO</td>
                            <td>
                                <span class="payment-status payment-status-<?php echo esc_attr(strtolower($payment->status)); ?>">
                                    <?php echo esc_html($payment->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($payment->employee_address)) : ?>
                                    <?php echo esc_html(substr($payment->employee_address, 0, 10) . '...'); ?>
                                    <span class="nano-split-pos-tooltip" title="<?php echo esc_attr($payment->employee_address); ?>">
                                        <span class="dashicons dashicons-info"></span>
                                    </span>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($payment->tip_amount > 0) : ?>
                                    <?php echo esc_html(number_format($payment->tip_amount, 6)); ?> NANO
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($payment->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div> 