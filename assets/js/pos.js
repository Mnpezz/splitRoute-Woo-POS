(function($) {
    'use strict';
    
    // Cart items array
    let cartItems = [];
    let subtotal = 0;
    let tipAmount = 0;
    let total = 0;
    let taxRate = 0;
    let taxAmount = 0;
    
    // Initialize the POS
    $(document).ready(function() {
        // Initialize tax rate from localized script
        if (nano_split_pos.use_tax === '1') {
            taxRate = parseFloat(nano_split_pos.tax_rate);
        }
        
        initPOS();
        
        // Load saved employee from localStorage if available
        const savedEmployeeId = localStorage.getItem('nano_split_pos_employee_id');
        if (savedEmployeeId) {
            $('#nano-split-pos-employee-select').val(savedEmployeeId);
        }
        
        // Handle employee selection
        $('#nano-split-pos-employee-select').on('change', function() {
            const selectedValue = $(this).val();
            console.log('Employee selected:', selectedValue);
            
            if (selectedValue === 'custom') {
                $('#nano-split-pos-custom-address-container').show();
                
                // Load saved custom address if available
                const savedCustomAddress = localStorage.getItem('nano_split_pos_custom_address');
                if (savedCustomAddress) {
                    $('#nano-split-pos-employee-address').val(savedCustomAddress);
                }
            } else {
                $('#nano-split-pos-custom-address-container').hide();
                
                // Save selected employee ID
                const selectedOption = $(this).find('option:selected');
                const employeeId = selectedOption.data('id');
                console.log('Selected employee ID:', employeeId);
                
                if (employeeId) {
                    localStorage.setItem('nano_split_pos_employee_id', selectedValue);
                }
            }
        });
        
        // Save custom address to localStorage when entered
        $('#nano-split-pos-employee-address').on('change', function() {
            const address = $(this).val();
            if (address) {
                localStorage.setItem('nano_split_pos_custom_address', address);
            }
        });
    });
    
    // Initialize the POS
    function initPOS() {
        // Add event listeners
        $('#nano-split-pos-manual-form').on('submit', addManualItem);
        $('#nano-split-pos-clear-cart').on('click', clearCart);
        $('#nano-split-pos-checkout-btn').on('click', checkout);
        
        // Product click event
        $('.nano-split-pos-product').on('click', function() {
            const productId = $(this).data('id');
            const productName = $(this).data('name');
            const productPrice = parseFloat($(this).data('price'));
            
            addItemToCart({
                id: 'product_' + productId,
                name: productName,
                price: productPrice,
                quantity: 1
            });
        });
        
        // Tip buttons
        $('.nano-split-pos-tip-btn').on('click', function() {
            if ($(this).hasClass('nano-split-pos-tip-custom')) {
                // Show custom tip modal
                $('#nano-split-pos-custom-tip-modal').show();
            } else {
                const percentage = parseFloat($(this).data('percentage'));
                applyTipPercentage(percentage);
            }
        });
        
        // Custom tip modal
        $('#nano-split-pos-apply-custom-tip').on('click', function() {
            const customTipAmount = parseFloat($('#nano-split-pos-custom-tip-amount').val());
            if (customTipAmount >= 0) {
                applyCustomTip(customTipAmount);
                $('#nano-split-pos-custom-tip-modal').hide();
            }
        });
        
        // Modal close buttons
        $('.nano-split-pos-modal-close').on('click', function() {
            $(this).closest('.nano-split-pos-modal').hide();
        });
        
        // Copy address button
        $('#nano-split-pos-copy-address').on('click', function() {
            const address = $('#nano-split-pos-modal-address').text();
            copyToClipboard(address);
            alert('Address copied to clipboard!');
        });
        
        // Cancel payment button
        $('#nano-split-pos-cancel-payment').on('click', function() {
            $('#nano-split-pos-payment-modal').hide();
        });
    }
    
    // Add manual item to cart
    function addManualItem(e) {
        e.preventDefault();
        
        const itemName = $('#nano-split-pos-item-name').val();
        const itemPrice = parseFloat($('#nano-split-pos-item-price').val());
        const itemQuantity = parseInt($('#nano-split-pos-item-quantity').val());
        
        if (itemName && itemPrice > 0 && itemQuantity > 0) {
            addItemToCart({
                id: 'manual_' + Date.now(),
                name: itemName,
                price: itemPrice,
                quantity: itemQuantity
            });
            
            // Clear form
            $('#nano-split-pos-item-name').val('');
            $('#nano-split-pos-item-price').val('');
            $('#nano-split-pos-item-quantity').val(1);
        }
    }
    
    // Add item to cart
    function addItemToCart(item) {
        // Check if item already exists in cart
        const existingItemIndex = cartItems.findIndex(cartItem => cartItem.id === item.id);
        
        if (existingItemIndex !== -1) {
            // Update quantity
            cartItems[existingItemIndex].quantity += item.quantity;
        } else {
            // Add new item
            cartItems.push(item);
        }
        
        // Update cart display
        updateCartDisplay();
    }
    
    // Update cart display
    function updateCartDisplay() {
        // Clear cart display
        $('#nano-split-pos-cart-items').empty();
        
        subtotal = 0;
        
        // Add items to cart display
        cartItems.forEach(function(item, index) {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            $('#nano-split-pos-cart-items').append(`
                <tr>
                    <td>${item.name}</td>
                    <td>${item.price.toFixed(6)}</td>
                    <td>
                        <input type="number" min="1" value="${item.quantity}" class="nano-split-pos-quantity" data-index="${index}">
                    </td>
                    <td>${itemTotal.toFixed(6)}</td>
                    <td><button class="nano-split-pos-remove-item" data-index="${index}">×</button></td>
                </tr>
            `);
        });
        
        // Add event listeners for quantity changes and remove buttons
        $('.nano-split-pos-quantity').on('change', updateItemQuantity);
        $('.nano-split-pos-remove-item').on('click', removeItem);
        
        // Update subtotal
        $('#nano-split-pos-subtotal').text(subtotal.toFixed(6));
        
        // Calculate and update tax
        taxRate = parseFloat(nano_split_pos.tax_rate);
        if (taxRate > 0) {
            taxAmount = (subtotal * taxRate) / 100;
            $('#nano-split-pos-tax-amount').text(taxAmount.toFixed(6));
            $('#nano-split-pos-tax-row').show();
        } else {
            taxAmount = 0;
            $('#nano-split-pos-tax-row').hide();
        }
        
        // Update tip if applicable
        if (window.tipPercentage > 0) {
            tipAmount = (subtotal * window.tipPercentage) / 100;
            $('#nano-split-pos-tip-amount').text(tipAmount.toFixed(6));
            $('#nano-split-pos-tip-row').show();
        }
        
        // Update total
        total = subtotal + taxAmount + tipAmount;
        $('#nano-split-pos-total').text(total.toFixed(6));
        
        // Enable/disable checkout button
        if (cartItems.length > 0) {
            $('#nano-split-pos-checkout-btn').prop('disabled', false);
        } else {
            $('#nano-split-pos-checkout-btn').prop('disabled', true);
        }
        
        // Log values for debugging
        console.log('Subtotal:', subtotal);
        console.log('Tax Rate:', taxRate);
        console.log('Tax Amount:', taxAmount);
        console.log('Tip Amount:', tipAmount);
        console.log('Total:', total);
    }
    
    // Apply tip percentage
    function applyTipPercentage(percentage) {
        window.tipPercentage = percentage;
        window.tipIsPercentage = true;
        
        // Calculate tip amount based on subtotal
        tipAmount = (subtotal * percentage) / 100;
        
        // Update tip display
        $('#nano-split-pos-tip-amount').text(tipAmount.toFixed(6));
        $('#nano-split-pos-tip-row').show();
        
        // Update total
        total = subtotal + taxAmount + tipAmount;
        $('#nano-split-pos-total').text(total.toFixed(6));
        
        // Log values for debugging
        console.log('Applied tip percentage:', percentage);
        console.log('New tip amount:', tipAmount);
        console.log('New total:', total);
    }
    
    // Apply custom tip
    function applyCustomTip(amount) {
        window.tipPercentage = 0;
        window.tipIsPercentage = false;
        tipAmount = amount;
        
        // Update tip display
        $('#nano-split-pos-tip-amount').text(tipAmount.toFixed(6));
        $('#nano-split-pos-tip-row').show();
        
        // Update total
        total = subtotal + taxAmount + tipAmount;
        $('#nano-split-pos-total').text(total.toFixed(6));
        
        // Log values for debugging
        console.log('Applied custom tip:', amount);
        console.log('New total:', total);
    }
    
    // Clear cart
    function clearCart() {
        cartItems = [];
        subtotal = 0;
        tipAmount = 0;
        total = 0;
        
        // Reset tip
        window.tipIsPercentage = false;
        window.tipPercentage = 0;
        
        // Update display
        updateCartDisplay();
        $('#nano-split-pos-tip-row').hide();
        $('#nano-split-pos-employee-address').val('');
    }
    
    // Checkout
    function checkout() {
        if (cartItems.length === 0) {
            return;
        }
        
        let employeeAddress = '';
        const selectedValue = $('#nano-split-pos-employee-select').val();
        
        if (selectedValue === 'custom') {
            employeeAddress = $('#nano-split-pos-employee-address').val();
        } else {
            employeeAddress = selectedValue;
        }
        
        // Call AJAX to create payment
        $.ajax({
            url: nano_split_pos.ajax_url,
            type: 'POST',
            data: {
                action: 'nano_split_create_payment',
                nonce: nano_split_pos.nonce,
                amount: subtotal + taxAmount,
                employee_address: employeeAddress,
                tip_amount: tipAmount,
                tax_amount: taxAmount
            },
            beforeSend: function() {
                $('#nano-split-pos-checkout-btn').prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                $('#nano-split-pos-checkout-btn').prop('disabled', false).text('Checkout with Nano');
                
                if (response.success) {
                    // Show payment modal
                    showPaymentModal(response.data);
                } else {
                    alert(response.data.message || 'An error occurred. Please try again.');
                }
            },
            error: function() {
                $('#nano-split-pos-checkout-btn').prop('disabled', false).text('Checkout with Nano');
                alert('An error occurred. Please try again.');
            }
        });
    }
    
    // Show payment modal
    function showPaymentModal(data) {
        const paymentData = data.payment_data;
        
        // Set modal content
        $('#nano-split-pos-modal-amount').text(total.toFixed(6));
        $('#nano-split-pos-modal-address').text(paymentData.address);
        $('#nano-split-pos-modal-status').text('Waiting for payment...');
        
        // Generate QR code
        generateQRCode(paymentData.uri_nano || `nano:${paymentData.address}?amount=${total}`);
        
        // Show modal
        $('#nano-split-pos-payment-modal').show();
        
        // Start polling for payment status
        pollPaymentStatus(data.order_id);
    }
    
    // Generate QR code
    function generateQRCode(uri) {
        // Clear previous QR code
        $('#nano-split-pos-qr-code').empty();
        
        try {
            // Create new QR code
            new QRCode(document.getElementById('nano-split-pos-qr-code'), {
                text: uri,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        } catch (error) {
            console.error("QR Code generation error:", error);
            $('#nano-split-pos-qr-code').html('<p>QR code not available. Please copy the address manually.</p>');
        }
    }
    
    // Poll payment status
    function pollPaymentStatus(orderId) {
        // Set up polling interval
        const pollInterval = setInterval(function() {
            $.ajax({
                url: nano_split_pos.ajax_url,
                type: 'POST',
                data: {
                    action: 'nano_split_check_payment',
                    nonce: nano_split_pos.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        const status = response.data.status;
                        $('#nano-split-pos-modal-status').text(status);
                        
                        // If payment is completed, show success message and clear cart
                        if (status === 'completed') {
                            clearInterval(pollInterval);
                            
                            // Show success notification with close button
                            $('<div class="nano-split-pos-notification success">')
                                .html('Payment received successfully! <span class="nano-split-pos-notification-close">&times;</span>')
                                .appendTo('body')
                                .fadeIn();
                            
                            // Add click handler for close button
                            $('.nano-split-pos-notification-close').on('click', function() {
                                $(this).parent().fadeOut(function() {
                                    $(this).remove();
                                });
                            });
                            
                            // Close modal and clear cart after a delay
                            setTimeout(function() {
                                $('#nano-split-pos-payment-modal').hide();
                                clearCart();
                            }, 2000);
                        } else if (status === 'failed') {
                            clearInterval(pollInterval);
                            
                            // Show failure notification
                            $('<div class="nano-split-pos-notification error">')
                                .text('Payment failed. Please try again.')
                                .appendTo('body')
                                .fadeIn()
                                .delay(5000)
                                .fadeOut(function() {
                                    $(this).remove();
                                });
                        }
                    }
                },
                error: function() {
                    console.error('Error checking payment status');
                }
            });
        }, 5000); // Check every 5 seconds
        
        // Store interval ID to clear it if needed
        $('#nano-split-pos-payment-modal').data('pollInterval', pollInterval);
        
        // Clear interval when modal is closed
        $('#nano-split-pos-cancel-payment').on('click', function() {
            clearInterval(pollInterval);
        });
    }
    
    // Copy to clipboard
    function copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }

    // Add these missing functions to your JavaScript file
    function updateItemQuantity() {
        const index = $(this).data('index');
        const newQuantity = parseInt($(this).val());
        
        if (newQuantity > 0) {
            cartItems[index].quantity = newQuantity;
            updateCartDisplay();
        }
    }

    function removeItem() {
        const index = $(this).data('index');
        cartItems.splice(index, 1);
        updateCartDisplay();
    }
})(jQuery); 
