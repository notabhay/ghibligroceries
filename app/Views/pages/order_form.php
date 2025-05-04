<?php

/**
 * View: Order Form Page (Checkout Page)
 *
 * This page displays the items currently in the user's cart and provides a form
 * for them to enter shipping details, optional notes, select a payment method (currently only Cash on Delivery),
 * and finalize their order.
 * It shows an order summary table and calculates the total amount.
 * If the cart is empty, it displays an empty cart message with a link to browse products.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the page. Defaults to 'Place Your Order'.
 * - $cartItems (array, optional): An array of items currently in the shopping cart. Defaults to empty.
 *   Each item array should contain:
 *     - image_url (string): URL of the product image.
 *     - name (string): Name of the product.
 *     - price (float): Price per unit of the product.
 *     - quantity (int): Quantity of the product in the cart.
 *     - subtotal (float): Calculated subtotal for this item line (price * quantity).
 * - $totalAmount (float, optional): The total calculated amount for all items in the cart. Defaults to 0.
 * - $csrfToken (string, optional): CSRF token for the order submission form. Defaults to empty.
 */

// Set the page title, using a default if not provided.
$page_title = $page_title ?? 'Place Your Order';
// Initialize cart items array, defaulting to empty if not provided.
$cartItems = $cartItems ?? [];
// Initialize total amount, defaulting to 0 if not provided.
$totalAmount = $totalAmount ?? 0;
// Initialize CSRF token, defaulting to empty if not provided.
$csrfToken = $csrfToken ?? '';
// Sanitize the page title for safe HTML output.
$page_title_safe = htmlspecialchars($page_title);
// Sanitize the CSRF token for safe inclusion in the form.
$csrfToken_safe = htmlspecialchars($csrfToken);
// Get the session object to display flash messages.
$session = App\Core\Registry::get('session');
?>
<!-- Main content area for the Order Form (Checkout) page -->
<main class="full-width-main">
    <!-- Container for page content -->
    <div class="container">
        <!-- Page heading -->
        <h1 class="page-title"><?= $page_title_safe ?></h1>
        <!-- Page subtitle -->
        <p class="page-subtitle">Review your cart and complete your purchase</p>

        <!-- Display flash messages (warning, error, info) -->
        <?php if ($session->hasFlash('warning')): ?>
        <div class="alert alert-warning" role="alert">
            <?= htmlspecialchars($session->getFlash('warning')); ?>
        </div>
        <?php endif; ?>
        <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-error" role="alert">
            <?= htmlspecialchars($session->getFlash('error')); ?>
        </div>
        <?php endif; ?>
        <?php if ($session->hasFlash('info')): ?>
        <div class="alert alert-info" role="alert">
            <?= htmlspecialchars($session->getFlash('info')); ?>
        </div>
        <?php endif; ?>

        <!-- Check if the cart is empty -->
        <?php if (empty($cartItems)): ?>
        <!-- Display empty cart message -->
        <div class="empty-cart">
            <img src="/assets/images/cart/empty_shopping_cart.png" alt="Empty Shopping Cart" class="empty-cart-image">
            <p>Your cart is currently empty.</p>
            <!-- Link to browse products -->
            <a href="/categories" class="btn btn-primary">Browse Products</a>
        </div>
        <?php else: // Display order form if cart is not empty 
        ?>
        <!-- Container for the two main sections: summary and form -->
        <div class="order-content">
            <!-- Section displaying the order summary -->
            <section class="order-summary-section">
                <h2>Order Summary</h2>
                <!-- Responsive table container -->
                <div class="table-responsive">
                    <!-- Table showing cart items -->
                    <table class="order-table">
                        <!-- Table header -->
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="text-end">Price</th>
                                <th scope="col" class="text-center">Quantity</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <!-- Table body -->
                        <tbody>
                            <!-- Loop through each item in the cart -->
                            <?php foreach ($cartItems as $item):
                                    // Basic validation for item data
                                    if (!is_array($item) || !isset($item['image_url'], $item['name'], $item['price'], $item['quantity'], $item['subtotal']))
                                        continue; // Skip if essential data missing

                                    // Sanitize and format item data for display
                                    $item_image_url_safe = htmlspecialchars($item['image_url']);
                                    $item_name_safe = htmlspecialchars($item['name']);
                                    // Format price and subtotal as currency
                                    $item_price_formatted = '$' . number_format($item['price'], 2);
                                    $item_quantity_safe = htmlspecialchars($item['quantity']);
                                    $item_subtotal_formatted = '$' . number_format($item['subtotal'], 2);
                                ?>
                            <!-- Table row for a single cart item -->
                            <tr>
                                <!-- Product details (image and name) -->
                                <td class="product-details">
                                    <img src="<?= $item_image_url_safe ?>" alt="<?= $item_name_safe ?>"
                                        class="product-thumbnail">
                                    <span class="product-name"><?= $item_name_safe ?></span>
                                </td>
                                <!-- Item price -->
                                <td class="text-end"><?= $item_price_formatted ?></td>
                                <!-- Item quantity -->
                                <td class="text-center"><?= $item_quantity_safe ?></td>
                                <!-- Item subtotal -->
                                <td class="text-end"><?= $item_subtotal_formatted ?></td>
                            </tr>
                            <?php endforeach; // End of cart items loop 
                                ?>
                        </tbody>
                        <!-- Table footer -->
                        <tfoot>
                            <tr>
                                <!-- Grand total row -->
                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                <!-- Display formatted total amount -->
                                <td class="text-end fw-bold">$<?= number_format($totalAmount, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div> <!-- End table-responsive -->
            </section> <!-- End order-summary-section -->

            <!-- Section for shipping and payment details form -->
            <section class="shipping-payment-section">
                <h2>Shipping & Payment Details</h2>
                <!-- Order processing form -->
                <form action="/order/process" method="POST" id="order-form">
                    <!-- CSRF token for security -->
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken_safe ?>">

                    <!-- Shipping Address field -->
                    <div class="form-group">
                        <label for="shipping_address" class="form-label">Shipping Address</label>
                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"
                            placeholder="Enter your shipping address" required></textarea>
                        <!-- Added required attribute -->
                    </div>

                    <!-- Order Notes field (optional) -->
                    <div class="form-group">
                        <label for="order_notes" class="form-label">Order Notes (Optional)</label>
                        <textarea class="form-control" id="order_notes" name="order_notes" rows="3"
                            placeholder="Add any special instructions here..."></textarea>
                    </div>

                    <!-- Payment Method selection -->
                    <div class="payment-methods">
                        <h3>Payment Method</h3>
                        <!-- Currently only Cash on Delivery is supported -->
                        <div class="payment-option">
                            <input type="radio" id="payment_cash" name="payment_method" value="cash" checked>
                            <label for="payment_cash">Cash on Delivery</label>
                            <!-- Add other payment methods here if implemented -->
                        </div>
                    </div>

                    <!-- Form action buttons -->
                    <div class="form-actions">
                        <!-- Link to go back to the cart page -->
                        <a href="/cart" class="btn btn-secondary">Back to Cart</a>
                        <!-- Button to submit the order -->
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </div>
                </form> <!-- End order-form -->
            </section> <!-- End shipping-payment-section -->
        </div> <!-- End order-content -->
        <?php endif; // End of check for empty cart 
        ?>
    </div> <!-- End container -->
</main> <!-- End main content area -->