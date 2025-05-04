<?php

/**
 * View: Single Product Order Page
 *
 * This page allows a user to place an order for a *single specific product*.
 * It displays details of the selected product (image, description, price, stock).
 * Provides a form to select quantity, enter shipping address and notes,
 * and choose a payment method (currently only Cash on Delivery).
 * Includes JavaScript to dynamically update the total price based on the selected quantity.
 * Likely used for a "Buy Now" or direct order feature from a product page.
 *
 * Expected variables:
 * - $product (array): An array containing details of the product being ordered. Must contain:
 *     - product_id (int): Unique ID of the product.
 *     - name (string): Name of the product.
 *     - description (string): Description of the product.
 *     - price (float): Price per unit of the product.
 *     - stock_quantity (int): Current available stock quantity.
 *     - image_path (string, optional): Path to the product image (relative to /assets/images/products/).
 * - $csrfToken (string): CSRF token for the order submission form.
 * - $page_title (string, optional): The title for the page (though not explicitly used in the default template).
 */

// Import necessary core classes.
use App\Core\Session;
use App\Core\Registry;

// Note: $page_title is declared in the docblock but not used in the default template below.
// It could be used if this view is included within a layout that expects $page_title.

// Basic validation: Check if product data is available.
// In a real application, more robust checks (e.g., isset($product['product_id'])) would be ideal here.
if (!isset($product) || !is_array($product)) {
    // Handle error: Product data is missing. Redirect or show an error message.
    // For simplicity here, we might assume the controller ensures $product exists.
    // echo "<div class='container'><div class='alert alert-error'>Error: Product data not found.</div></div>";
    // return; // Stop rendering if product data is invalid.
}

// Ensure CSRF token is set (controller should provide this).
if (!isset($csrfToken)) {
    // Handle error: CSRF token is missing.
    // echo "<div class='container'><div class='alert alert-error'>Error: Security token missing.</div></div>";
    // return; // Stop rendering if CSRF token is missing.
    $csrfToken = ''; // Provide a default empty value to avoid errors, though this is insecure.
}
?>
<!-- Main content area for the Single Product Order page -->
<main class="full-width-main">
    <!-- Container for page content -->
    <div class="container">
        <!-- Page heading -->
        <h1 class="page-title">Place Your Order</h1>
        <!-- Page subtitle indicating the specific product being ordered -->
        <p class="page-subtitle">Review and confirm your order details for
            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
        </p>

        <!-- Display flash messages (error, success, info) -->
        <?php
        // Get session object from registry
        $session = Registry::get('session');
        // Display error flash message if it exists
        if ($session->hasFlash('error')) {
            echo '<div class="alert alert-error">' . htmlspecialchars($session->getFlash('error')) . '</div>';
        }
        // Display success flash message if it exists
        if ($session->hasFlash('success')) {
            echo '<div class="alert alert-success">' . htmlspecialchars($session->getFlash('success')) . '</div>';
        }
        // Display info flash message if it exists
        if ($session->hasFlash('info')) {
            echo '<div class="alert alert-info">' . htmlspecialchars($session->getFlash('info')) . '</div>';
        }
        ?>

        <!-- Container for the two main sections: summary and form -->
        <div class="order-content">
            <!-- Section displaying product details and order summary -->
            <section class="order-summary-section">
                <h2>Product Details</h2>
                <!-- Container for product image and info -->
                <div class="product-details">
                    <!-- Product image -->
                    <img src="/assets/images/products/<?php echo htmlspecialchars(basename($product['image_path'] ?? 'default.png')); // Use basename for security and provide default 
                                                        ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"
                        class="product-thumbnail">
                    <!-- Product information block -->
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?> per unit</div>
                        <div class="product-stock">
                            <span class="stock-label">In Stock:</span>
                            <span class="stock-value"><?php echo htmlspecialchars($product['stock_quantity']); ?>
                                units</span>
                        </div>
                    </div>
                </div>
                <!-- Dynamic order summary block (updated by JavaScript) -->
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Product:</span>
                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Price:</span>
                        <span>$<?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Quantity:</span>
                        <!-- Quantity value updated by JS -->
                        <span id="quantity-value">1</span>
                    </div>
                    <div class="summary-row total">
                        <span id="total-price-label">Total:</span>
                        <!-- Total price value updated by JS -->
                        <span id="total-price">$<?php echo number_format($product['price'], 2); // Initial total 
                                                ?></span>
                    </div>
                </div>
            </section> <!-- End order-summary-section -->

            <!-- Section containing the order form -->
            <section class="shipping-payment-section">
                <h2>Order Details</h2>
                <!-- Form to submit the single product order -->
                <form action="/order/product/<?php echo htmlspecialchars($product['product_id']); // Action URL includes product ID 
                                                ?>" method="post" class="order-form">
                    <!-- CSRF token for security -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <!-- Hidden field for product ID -->
                    <input type="hidden" name="product_id"
                        value="<?php echo htmlspecialchars($product['product_id']); ?>">

                    <!-- Quantity selection group -->
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <div class="quantity-controls">
                            <!-- Decrease quantity button -->
                            <button type="button" class="quantity-btn decrease-btn" aria-label="Decrease quantity">
                                <i class="fas fa-minus"></i>
                            </button>
                            <!-- Quantity input field -->
                            <input type="number" id="quantity" name="quantity" class="quantity-input" value="1" min="1"
                                max="<?php echo htmlspecialchars($product['stock_quantity']); // Max based on stock 
                                        ?>" required data-price="<?php echo htmlspecialchars($product['price']); // Store price for JS calculation 
                                                                    ?>" aria-describedby="total-price-label">
                            <!-- Increase quantity button -->
                            <button type="button" class="quantity-btn increase-btn" aria-label="Increase quantity">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Shipping Address field -->
                    <div class="form-group">
                        <label for="shipping_address" class="form-label">Shipping Address</label>
                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"
                            placeholder="Enter your shipping address" required></textarea>
                        <!-- Added required attribute -->
                    </div>

                    <!-- Order Notes field (optional) -->
                    <div class="form-group">
                        <label for="notes">Order Notes (Optional):</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"
                            placeholder="Add any special instructions for your order"></textarea>
                    </div>

                    <!-- Payment Method selection -->
                    <div class="payment-methods">
                        <h3>Payment Method</h3>
                        <!-- Currently only Cash on Delivery -->
                        <div class="payment-option">
                            <input type="radio" id="payment_cash" name="payment_method" value="cash" checked>
                            <label for="payment_cash">Cash on Delivery</label>
                        </div>
                    </div>

                    <!-- Form action buttons -->
                    <div class="form-actions">
                        <!-- Link to continue shopping (goes to categories) -->
                        <a href="/categories" class="btn btn-secondary">Continue Shopping</a>
                        <!-- Button to submit the order -->
                        <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
                    </div>
                </form> <!-- End order-form -->
            </section> <!-- End shipping-payment-section -->
        </div> <!-- End order-content -->
    </div> <!-- End container -->
</main> <!-- End main content area -->
<!-- JavaScript for Quantity Input and Dynamic Price Update -->
<script>
// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get references to the relevant elements
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.querySelector('.quantity-btn.decrease-btn');
    const increaseBtn = document.querySelector('.quantity-btn.increase-btn');
    const quantityValueSpan = document.getElementById('quantity-value'); // Span in the summary
    const totalPriceSpan = document.getElementById('total-price'); // Span in the summary
    const productPrice = parseFloat(quantityInput.getAttribute('data-price')); // Get price from data attribute
    const maxQuantity = parseInt(quantityInput.max, 10); // Get max quantity (stock)

    /**
     * Updates the quantity display in the summary and recalculates/displays the total price.
     * Also enforces min (1) and max (stock) quantity limits on the input field itself.
     */
    function updateTotal() {
        let quantity = parseInt(quantityInput.value, 10);

        // Validate and correct quantity input
        if (isNaN(quantity) || quantity < 1) {
            quantity = 1; // Reset to minimum if invalid or below 1
            quantityInput.value = 1;
        }
        if (quantity > maxQuantity) {
            quantity = maxQuantity; // Cap at maximum stock
            quantityInput.value = maxQuantity;
        }

        // Calculate the total price
        const total = (productPrice * quantity).toFixed(2); // Calculate and format to 2 decimal places

        // Update the summary display
        quantityValueSpan.textContent = quantity; // Update quantity span
        totalPriceSpan.textContent = '$' + total; // Update total price span
    }

    // Add event listener for the decrease button
    decreaseBtn.addEventListener('click', function() {
        let currentQuantity = parseInt(quantityInput.value, 10);
        if (currentQuantity > 1) { // Can only decrease if above 1
            quantityInput.value = currentQuantity - 1;
            updateTotal(); // Update display after changing value
        }
    });

    // Add event listener for the increase button
    increaseBtn.addEventListener('click', function() {
        let currentQuantity = parseInt(quantityInput.value, 10);
        if (currentQuantity < maxQuantity) { // Can only increase if below max stock
            quantityInput.value = currentQuantity + 1;
            updateTotal(); // Update display after changing value
        }
    });

    // Add event listener for direct input changes in the quantity field
    quantityInput.addEventListener('input', updateTotal);

    // Initial call to set the correct total price when the page loads
    updateTotal();
});
</script>