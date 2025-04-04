<div class="wrap">
    <h1><?php _e('Nano Split POS Employees', 'nano-split-pos'); ?></h1>
    
    <div class="nano-split-pos-employees-container">
        <div class="nano-split-pos-employees-list">
            <h2><?php _e('Employees', 'nano-split-pos'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'nano-split-pos'); ?></th>
                        <th><?php _e('Nano Address', 'nano-split-pos'); ?></th>
                        <th><?php _e('Actions', 'nano-split-pos'); ?></th>
                    </tr>
                </thead>
                <tbody id="nano-split-pos-employees-list">
                    <?php if (empty($employees)) : ?>
                        <tr>
                            <td colspan="3"><?php _e('No employees found', 'nano-split-pos'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($employees as $employee) : ?>
                            <tr data-id="<?php echo esc_attr($employee['id']); ?>">
                                <td><?php echo esc_html($employee['name']); ?></td>
                                <td><?php echo esc_html($employee['nano_address']); ?></td>
                                <td>
                                    <button type="button" class="button nano-split-pos-edit-employee"><?php _e('Edit', 'nano-split-pos'); ?></button>
                                    <button type="button" class="button nano-split-pos-delete-employee"><?php _e('Delete', 'nano-split-pos'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="nano-split-pos-add-employee">
            <h2><?php _e('Add Employee', 'nano-split-pos'); ?></h2>
            
            <form id="nano-split-pos-add-employee-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Name', 'nano-split-pos'); ?></th>
                        <td>
                            <input type="text" id="nano-split-pos-employee-name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Nano Address', 'nano-split-pos'); ?></th>
                        <td>
                            <input type="text" id="nano-split-pos-employee-address" class="regular-text" required>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Add Employee', 'nano-split-pos'); ?></button>
                </p>
            </form>
        </div>
        
        <div id="nano-split-pos-edit-employee-modal" class="nano-split-pos-modal">
            <div class="nano-split-pos-modal-content">
                <span class="nano-split-pos-modal-close">&times;</span>
                <h2><?php _e('Edit Employee', 'nano-split-pos'); ?></h2>
                
                <form id="nano-split-pos-edit-employee-form">
                    <input type="hidden" id="nano-split-pos-edit-employee-id">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Name', 'nano-split-pos'); ?></th>
                            <td>
                                <input type="text" id="nano-split-pos-edit-employee-name" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Nano Address', 'nano-split-pos'); ?></th>
                            <td>
                                <input type="text" id="nano-split-pos-edit-employee-address" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Update Employee', 'nano-split-pos'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            // Add employee
            $('#nano-split-pos-add-employee-form').on('submit', function(e) {
                e.preventDefault();
                
                const name = $('#nano-split-pos-employee-name').val();
                const address = $('#nano-split-pos-employee-address').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nano_split_add_employee',
                        nonce: '<?php echo wp_create_nonce('nano_split_pos_admin_nonce'); ?>',
                        name: name,
                        nano_address: address
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Edit employee
            $('.nano-split-pos-edit-employee').on('click', function() {
                const row = $(this).closest('tr');
                const id = row.data('id');
                const name = row.find('td:eq(0)').text();
                const address = row.find('td:eq(1)').text();
                
                $('#nano-split-pos-edit-employee-id').val(id);
                $('#nano-split-pos-edit-employee-name').val(name);
                $('#nano-split-pos-edit-employee-address').val(address);
                
                $('#nano-split-pos-edit-employee-modal').show();
            });
            
            // Update employee
            $('#nano-split-pos-edit-employee-form').on('submit', function(e) {
                e.preventDefault();
                
                const id = $('#nano-split-pos-edit-employee-id').val();
                const name = $('#nano-split-pos-edit-employee-name').val();
                const address = $('#nano-split-pos-edit-employee-address').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nano_split_edit_employee',
                        nonce: '<?php echo wp_create_nonce('nano_split_pos_admin_nonce'); ?>',
                        id: id,
                        name: name,
                        nano_address: address
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Delete employee
            $('.nano-split-pos-delete-employee').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to delete this employee?', 'nano-split-pos'); ?>')) {
                    return;
                }
                
                const row = $(this).closest('tr');
                const id = row.data('id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'nano_split_delete_employee',
                        nonce: '<?php echo wp_create_nonce('nano_split_pos_admin_nonce'); ?>',
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Close modal
            $('.nano-split-pos-modal-close').on('click', function() {
                $(this).closest('.nano-split-pos-modal').hide();
            });
            
            // Close modal when clicking outside
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('nano-split-pos-modal')) {
                    $('.nano-split-pos-modal').hide();
                }
            });
        });
    </script>
</div> 