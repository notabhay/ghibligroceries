/**
 * @file order.js
 * @description Handles various functionalities related to orders on the website,
 *              including mobile menu toggling, quantity adjustments, order form submission,
 *              order cancellation, printing order details, and filtering orders by status.
 */

// Wait for the HTML document to be fully loaded and parsed.
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all relevant functionalities when the DOM is ready.
    initMobileMenu();
    initQuantityInputs();
    initOrderForm();
    initOrderCancellation();
    initPrintButtons();
    initOrderStatusFilter();
});

/**
 * @function initMobileMenu
 * @description Initializes the toggle functionality for the mobile navigation menu.
 *              Adds a click event listener to the toggle button to show/hide the menu
 *              and switch the icon between bars (menu) and times (close).
 */
function initMobileMenu() {
    // Select the mobile menu toggle button and the navigation menu itself.
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.mobile-menu');

    // Ensure both elements exist before adding the event listener.
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function () {
            // Toggle the 'show' class on the navigation menu to control visibility.
            navMenu.classList.toggle('show');
            // Find the icon within the toggle button.
            const icon = menuToggle.querySelector('i');
            if (icon) {
                // Toggle Font Awesome classes to change the icon appearance.
                icon.classList.toggle('fa-bars'); // Hamburger icon
                icon.classList.toggle('fa-times'); // Close icon
            }
        });
    }
}

/**
 * @function initQuantityInputs
 * @description Initializes the quantity input field on product/order pages.
 *              Adds event listeners to decrease/increase buttons and the input field itself
 *              to update the quantity and the order summary (total price).
 *              Ensures quantity stays within valid bounds (1 to max).
 */
function initQuantityInputs() {
    // Select the main quantity input field.
    const quantityInput = document.getElementById('quantity');
    // If the quantity input doesn't exist, exit the function.
    if (!quantityInput) return;

    // Select elements used for displaying quantity and total price.
    const quantityValue = document.getElementById('quantity-value'); // Likely a display element, not input
    const totalPrice = document.getElementById('total-price'); // Display element for total

    // Get product price and maximum allowed quantity from data attributes.
    const productPrice = parseFloat(quantityInput.getAttribute('data-price') || 0);
    const maxQuantity = parseInt(quantityInput.getAttribute('max') || 1); // Default max to 1 if not set

    // --- Decrease Button ---
    const decreaseBtn = document.querySelector('.quantity-btn.decrease-btn');
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', function () {
            let value = parseInt(quantityInput.value);
            // Decrease quantity only if it's greater than 1.
            if (value > 1) {
                value--;
                quantityInput.value = value; // Update the input field value.
                updateOrderSummary(value, productPrice); // Update the displayed summary.
            }
        });
    }

    // --- Increase Button ---
    const increaseBtn = document.querySelector('.quantity-btn.increase-btn');
    if (increaseBtn) {
        increaseBtn.addEventListener('click', function () {
            let value = parseInt(quantityInput.value);
            // Increase quantity only if it's less than the maximum allowed.
            if (value < maxQuantity) {
                value++;
                quantityInput.value = value; // Update the input field value.
                updateOrderSummary(value, productPrice); // Update the displayed summary.
            }
        });
    }

    // --- Direct Input Handling ---
    if (quantityInput) {
        quantityInput.addEventListener('input', function () {
            let value = parseInt(this.value);
            // Validate the entered value.
            if (isNaN(value) || value < 1) {
                value = 1; // Reset to minimum if invalid or less than 1.
            } else if (value > maxQuantity) {
                value = maxQuantity; // Cap at maximum allowed quantity.
            }
            this.value = value; // Update the input field with the validated value.
            updateOrderSummary(value, productPrice); // Update the displayed summary.
        });
    }
}

/**
 * @function updateOrderSummary
 * @description Updates the displayed quantity and total price based on the selected quantity and unit price.
 * @param {number} quantity - The current quantity selected.
 * @param {number} unitPrice - The price per unit of the product.
 */
function updateOrderSummary(quantity, unitPrice) {
    // Select the elements displaying the quantity and total price.
    const quantityValue = document.getElementById('quantity-value');
    const totalPrice = document.getElementById('total-price');

    // Ensure both display elements exist.
    if (quantityValue && totalPrice) {
        // Calculate the total price.
        const total = unitPrice * quantity;
        // Update the text content of the display elements.
        quantityValue.textContent = quantity;
        totalPrice.textContent = '$' + total.toFixed(2); // Format to 2 decimal places.
    }
}

/**
 * @function initOrderForm
 * @description Initializes the order submission forms (both single product order and final checkout).
 *              Adds submit event listeners for validation and AJAX submission (if configured).
 *              Handles form submission, displays loading states, processes responses,
 *              and shows success/error messages or redirects.
 */
function initOrderForm() {
    // --- Single Product Order Form ---
    const orderForm = document.querySelector('.order-form'); // Specific form for adding a single product
    if (orderForm) {
        orderForm.addEventListener('submit', function (e) {
            // Basic quantity validation before submission.
            const quantityInput = document.getElementById('quantity');
            if (quantityInput) {
                const quantity = parseInt(quantityInput.value);
                if (isNaN(quantity) || quantity < 1) {
                    e.preventDefault(); // Prevent form submission.
                    showMessage('error', 'Please enter a valid quantity.'); // Show error message.
                    return; // Stop further processing.
                }
            }

            // Check if the form is configured for AJAX submission via 'data-ajax' attribute.
            if (orderForm.getAttribute('data-ajax') === 'true') {
                e.preventDefault(); // Prevent default synchronous form submission.

                // --- AJAX Submission Logic ---
                const submitBtn = orderForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML; // Store original button text.
                submitBtn.disabled = true; // Disable button during processing.
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...'; // Show loading state.

                // Gather form data.
                const formData = new FormData(orderForm);
                const orderData = { // Prepare data object (might be redundant if using formData directly)
                    product_id: formData.get('product_id'),
                    quantity: parseInt(formData.get('quantity')),
                    notes: formData.get('notes') || null
                };

                // Send POST request to the server endpoint.
                fetch('/order/product/' + orderData.product_id, {
                    method: 'POST',
                    headers: {
                        // Use form-urlencoded for compatibility with typical PHP backend.
                        'Content-Type': 'application/x-www-form-urlencoded',
                        // Identify the request as AJAX.
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    // Send form data as URL-encoded string.
                    body: new URLSearchParams(formData)
                })
                    .then(response => response.json()) // Expect a JSON response.
                    .then(data => {
                        if (data.success) {
                            // On success, redirect to the confirmation page or provided redirect URL.
                            window.location.href = data.redirect || '/order/confirmation/' + data.order_id;
                        } else {
                            // On failure, show error message and re-enable the button.
                            showMessage('error', data.message || 'An error occurred while processing your order.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        // Handle network errors or issues during the fetch.
                        console.error('Error processing order:', error);
                        showMessage('error', 'An error occurred while processing your order. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
            }
            // If data-ajax is not true, the form submits normally (synchronously).
        });
    }

    // --- Final Checkout Form ---
    const checkoutForm = document.getElementById('order-form'); // The main checkout form
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function (e) {
            // Basic validation for the shipping address field.
            const shippingAddress = document.getElementById('shipping_address');
            if (shippingAddress && shippingAddress.value.trim() === '') {
                e.preventDefault(); // Prevent submission if address is empty.
                showMessage('error', 'Please enter your shipping address.');
                shippingAddress.focus(); // Set focus to the address field.
                return;
            }
            // Add more client-side validation here if needed (e.g., payment details).
        });
    }
}

/**
 * @function initOrderCancellation
 * @description Initializes forms used for cancelling orders.
 *              Adds submit event listeners to handle cancellation requests,
 *              optionally via AJAX if configured with 'data-ajax="true"'.
 *              Includes a confirmation prompt before proceeding.
 */
function initOrderCancellation() {
    // Select all forms with the class 'cancel-form'.
    const cancelForms = document.querySelectorAll('.cancel-form');
    cancelForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            // Check if the form is configured for AJAX submission.
            if (form.getAttribute('data-ajax') === 'true') {
                e.preventDefault(); // Prevent default synchronous form submission.

                // Show a confirmation dialog to the user.
                if (!confirm('Are you sure you want to cancel this order?')) {
                    return; // Stop if the user cancels the action.
                }

                // --- AJAX Cancellation Logic ---
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

                const formData = new FormData(form); // Get form data (likely just CSRF token).
                // Extract order ID from the form's action URL.
                const orderId = form.getAttribute('action').split('/').pop();

                // Send POST request to the cancellation endpoint.
                fetch('/order/cancel/' + orderId, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX.
                    },
                    body: formData // Send form data.
                })
                    .then(response => response.json()) // Expect JSON response.
                    .then(data => {
                        if (data.success) {
                            // On success, show success message, update UI elements, and hide the form.
                            showMessage('success', data.message || 'Order cancelled successfully.');
                            updateOrderStatusUI('cancelled'); // Update status display.
                            form.style.display = 'none'; // Hide the cancel button/form.
                        } else {
                            // On failure, show error message and re-enable the button.
                            showMessage('error', data.message || 'Failed to cancel order.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    })
                    .catch(error => {
                        // Handle network errors or issues during the fetch.
                        console.error('Error cancelling order:', error);
                        showMessage('error', 'An error occurred while cancelling your order. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
            }
            // If data-ajax is not true, the form submits normally (synchronously).
        });
    });
}

/**
 * @function updateOrderStatusUI
 * @description Updates various UI elements on the page to reflect a new order status.
 *              Targets status badges, status text, and potentially adds a 'Cancelled' step to a timeline.
 * @param {string} status - The new status string (e.g., 'cancelled', 'processing').
 */
function updateOrderStatusUI(status) {
    // Update the main status badge (e.g., in order details header).
    const statusBadge = document.querySelector('.badge'); // Assumes a single primary badge
    if (statusBadge) {
        // Update class for styling and text content.
        statusBadge.className = 'badge status-' + status; // e.g., 'badge status-cancelled'
        statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1); // Capitalize
    }

    // Update a dedicated status text element (if present).
    const statusText = document.querySelector('.status-text'); // Assumes a specific element for text status
    if (statusText) {
        // Update class and text content.
        statusText.className = 'info-value status-text-' + status; // e.g., 'info-value status-text-cancelled'
        statusText.textContent = status.charAt(0).toUpperCase() + status.slice(1); // Capitalize
    }

    // Special handling for 'cancelled' status to update a timeline display.
    if (status === 'cancelled') {
        const timeline = document.querySelector('.timeline'); // Find the timeline container.
        if (timeline) {
            // Check if a 'cancelled' step already exists to prevent duplicates.
            if (!timeline.querySelector('.timeline-item.cancelled')) {
                // Create the HTML structure for the 'Cancelled' timeline step.
                const cancelledStep = document.createElement('div');
                cancelledStep.className = 'timeline-item active cancelled'; // Mark as active and cancelled.
                cancelledStep.innerHTML = `
                    <div class="timeline-icon"><i class="fas fa-times"></i></div>
                    <div class="timeline-content">
                        <h4>Cancelled</h4>
                        <p class="timeline-date">${new Date().toLocaleString()}</p>
                        <p class="timeline-description">This order has been cancelled.</p>
                    </div>
                `;
                // Append the new step to the timeline.
                timeline.appendChild(cancelledStep);
            }
        }
    }
    // Add handling for other statuses here if needed (e.g., updating timeline steps).
}

/**
 * @function initPrintButtons
 * @description Initializes buttons intended to trigger the browser's print dialog.
 *              Targets buttons with specific classes or inline onclick handlers for printing.
 */
function initPrintButtons() {
    // Select buttons likely used for printing (adjust selectors if needed).
    const printButtons = document.querySelectorAll('.print-confirmation, [onclick*="window.print"]');
    printButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent any default button behavior or link navigation.
            window.print(); // Trigger the browser's print functionality.
        });
    });
}

/**
 * @function initOrderStatusFilter
 * @description Initializes a dropdown filter for displaying orders based on their status.
 *              Adds a change event listener to the filter dropdown. When the value changes,
 *              it shows/hides order rows based on their 'data-status' attribute.
 */
function initOrderStatusFilter() {
    // Select the status filter dropdown element.
    const statusFilter = document.getElementById('order-status-filter');
    // If the filter doesn't exist, exit the function.
    if (!statusFilter) return;

    // Select all order rows (assuming they have the class 'order-row' and a 'data-status' attribute).
    const orderRows = document.querySelectorAll('.order-row');

    // Add event listener for when the filter selection changes.
    statusFilter.addEventListener('change', function () {
        const selectedStatus = this.value; // Get the selected status value (e.g., 'all', 'pending').

        // Iterate over each order row.
        orderRows.forEach(row => {
            const rowStatus = row.getAttribute('data-status'); // Get the status of the current row.
            // Show the row if 'all' is selected or if the row status matches the selected status.
            if (selectedStatus === 'all' || selectedStatus === rowStatus) {
                row.style.display = ''; // Reset display to default (visible).
            } else {
                row.style.display = 'none'; // Hide the row if status doesn't match.
            }
        });
    });
}

/**
 * @function showMessage
 * @description Displays a temporary feedback message (alert) to the user at the top of the main container.
 *              Removes any previous messages before showing the new one. Success messages auto-dismiss.
 * @param {string} type - The type of message ('success', 'error', 'info', etc.). Used for styling.
 * @param {string} message - The text content of the message to display.
 */
function showMessage(type, message) {
    // Remove any existing alert messages to prevent stacking.
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create the new alert element.
    const alertElement = document.createElement('div');
    // Set class names for styling based on the message type.
    alertElement.className = `alert alert-${type}`; // e.g., 'alert alert-success'
    alertElement.innerHTML = message; // Set the message content.

    // Find the main container to insert the message into.
    const container = document.querySelector('.container'); // Adjust selector if needed
    if (container) {
        // Insert the alert at the beginning of the container.
        const firstChild = container.firstChild;
        container.insertBefore(alertElement, firstChild);
    } else {
        // Fallback: append to body if container not found (less ideal).
        console.warn('Container element not found for showing message. Appending to body.');
        document.body.insertBefore(alertElement, document.body.firstChild);
    }

    // Scroll the message into view smoothly.
    alertElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Set a timeout to automatically remove success messages after 5 seconds.
    if (type === 'success') {
        setTimeout(() => {
            // Check if the element still exists before trying to remove it.
            if (alertElement.parentNode) {
                alertElement.remove();
            }
        }, 5000); // 5000 milliseconds = 5 seconds
    }
    // Error messages persist until manually dismissed or another message is shown.
}
