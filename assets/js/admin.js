(function($) {
    'use strict';
    
    // Initialize admin scripts
    $(document).ready(function() {
        // Address form submission
        $('#nano-split-pos-add-address-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $spinner = $form.find('.spinner');
            const $submitButton = $form.find('button[type="submit"]');
            
            // Get form data
            const formData = {
                action: 'nano_split_add_address',
                nonce: nano_split_pos_admin.nonce,
                nickname: $form.find('input[name="nickname"]').val(),
                nano_address: $form.find('input[name="nano_address"]').val(),
                percentage: $form.find('input[name="percentage"]').val()
            };
            
            // Disable form and show spinner
            $submitButton.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Send AJAX request
            $.post(nano_split_pos_admin.ajax_url, formData, function(response) {
                // Re-enable form and hide spinner
                $submitButton.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    // Show success message
                    $('#nano-split-pos-address-message')
                        .removeClass('notice-error')
                        .addClass('notice-success')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                    
                    // Add new address to the table
                    const address = response.data.address;
                    const $tbody = $('#nano-split-pos-addresses-tbody');
                    
                    // If table is empty, clear the "No addresses found" message
                    if ($tbody.find('tr td').length === 1 && $tbody.find('tr td').text().indexOf('No addresses found') !== -1) {
                        $tbody.empty();
                    }
                    
                    // Add new row
                    $tbody.append(`
                        <tr data-id="${address.id}">
                            <td>${address.nickname}</td>
                            <td>${address.nano_address}</td>
                            <td>${address.percentage}%</td>
                            <td>${address.is_active ? 'Active' : 'Inactive'}</td>
                            <td>
                                <button type="button" class="button edit-address">Edit</button>
                                <button type="button" class="button button-link-delete delete-address">Delete</button>
                            </td>
                        </tr>
                    `);
                    
                    // Clear form
                    $form.find('input[name="nickname"]').val('');
                    $form.find('input[name="nano_address"]').val('');
                    $form.find('input[name="percentage"]').val('');
                    
                    // Add event listeners to new buttons
                    $tbody.find('tr:last-child .edit-address').on('click', openEditModal);
                    $tbody.find('tr:last-child .delete-address').on('click', deleteAddress);
                } else {
                    // Show error message
                    $('#nano-split-pos-address-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                }
                
                // Hide message after 3 seconds
                setTimeout(function() {
                    $('#nano-split-pos-address-message').fadeOut();
                }, 3000);
            }).fail(function() {
                // Re-enable form and hide spinner
                $submitButton.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                // Show error message
                $('#nano-split-pos-address-message')
                    .removeClass('notice-success')
                    .addClass('notice-error')
                    .html('<p>An error occurred. Please try again.</p>')
                    .show();
                
                // Hide message after 3 seconds
                setTimeout(function() {
                    $('#nano-split-pos-address-message').fadeOut();
                }, 3000);
            });
        });
        
        // Edit address button click
        $('.edit-address').on('click', openEditModal);
        
        // Delete address button click
        $('.delete-address').on('click', deleteAddress);
        
        // Edit address form submission
        $('#nano-split-pos-edit-address-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $spinner = $form.find('.spinner');
            const $submitButton = $form.find('button[type="submit"]');
            
            // Get form data
            const formData = {
                action: 'nano_split_edit_address',
                nonce: nano_split_pos_admin.nonce,
                id: $form.find('input[name="id"]').val(),
                nickname: $form.find('input[name="nickname"]').val(),
                nano_address: $form.find('input[name="nano_address"]').val(),
                percentage: $form.find('input[name="percentage"]').val(),
                is_active: $form.find('input[name="is_active"]').is(':checked') ? 1 : 0
            };
            
            // Disable form and show spinner
            $submitButton.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Send AJAX request
            $.post(nano_split_pos_admin.ajax_url, formData, function(response) {
                // Re-enable form and hide spinner
                $submitButton.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    // Update address in the table
                    const address = response.data.address;
                    const $row = $('#nano-split-pos-addresses-tbody tr[data-id="' + address.id + '"]');
                    
                    $row.find('td:nth-child(1)').text(address.nickname);
                    $row.find('td:nth-child(2)').text(address.nano_address);
                    $row.find('td:nth-child(3)').text(address.percentage + '%');
                    $row.find('td:nth-child(4)').text(address.is_active ? 'Active' : 'Inactive');
                    
                    // Close modal
                    $('#nano-split-pos-edit-address-modal').hide();
                    
                    // Show success message
                    $('#nano-split-pos-address-message')
                        .removeClass('notice-error')
                        .addClass('notice-success')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                } else {
                    // Show error message
                    $('#nano-split-pos-address-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>' + response.data.message + '</p>')
                        .show();
                }
                
                // Hide message after 3 seconds
                setTimeout(function() {
                    $('#nano-split-pos-address-message').fadeOut();
                }, 3000);
            }).fail(function() {
                // Re-enable form and hide spinner
                $submitButton.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                // Show error message
                $('#nano-split-pos-address-message')
                    .removeClass('notice-success')
                    .addClass('notice-error')
                    .html('<p>An error occurred. Please try again.</p>')
                    .show();
                
                // Hide message after 3 seconds
                setTimeout(function() {
                    $('#nano-split-pos-address-message').fadeOut();
                }, 3000);
            });
        });
        
        // Modal close button
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
    
    // Open edit modal
    function openEditModal() {
        const $row = $(this).closest('tr');
        const id = $row.data('id');
        const nickname = $row.find('td:nth-child(1)').text();
        const nano_address = $row.find('td:nth-child(2)').text();
        const percentage = parseFloat($row.find('td:nth-child(3)').text());
        const is_active = $row.find('td:nth-child(4)').text() === 'Active';
        
        // Fill form
        const $form = $('#nano-split-pos-edit-address-form');
        $form.find('input[name="id"]').val(id);
        $form.find('input[name="nickname"]').val(nickname);
        $form.find('input[name="nano_address"]').val(nano_address);
        $form.find('input[name="percentage"]').val(percentage);
        $form.find('input[name="is_active"]').prop('checked', is_active);
        
        // Show modal
        $('#nano-split-pos-edit-address-modal').show();
    }
    
    // Delete address
    function deleteAddress() {
        if (!confirm('Are you sure you want to delete this address?')) {
            return;
        }
        
        const $row = $(this).closest('tr');
        const id = $row.data('id');
        
        // Send AJAX request
        $.post(nano_split_pos_admin.ajax_url, {
            action: 'nano_split_delete_address',
            nonce: nano_split_pos_admin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                // Remove row
                $row.remove();
                
                // If no addresses left, show "No addresses found" message
                if ($('#nano-split-pos-addresses-tbody tr').length === 0) {
                    $('#nano-split-pos-addresses-tbody').html('<tr><td colspan="5">No addresses found.</td></tr>');
                }
                
                // Show success message
                $('#nano-split-pos-address-message')
                    .removeClass('notice-error')
                    .addClass('notice-success')
                    .html('<p>' + response.data.message + '</p>')
                    .show();
            } else {
                // Show error message
                $('#nano-split-pos-address-message')
                    .removeClass('notice-success')
                    .addClass('notice-error')
                    .html('<p>' + response.data.message + '</p>')
                    .show();
            }
            
            // Hide message after 3 seconds
            setTimeout(function() {
                $('#nano-split-pos-address-message').fadeOut();
            }, 3000);
        }).fail(function() {
            // Show error message
            $('#nano-split-pos-address-message')
                .removeClass('notice-success')
                .addClass('notice-error')
                .html('<p>An error occurred. Please try again.</p>')
                .show();
            
            // Hide message after 3 seconds
            setTimeout(function() {
                $('#nano-split-pos-address-message').fadeOut();
            }, 3000);
        });
    }
})(jQuery); 