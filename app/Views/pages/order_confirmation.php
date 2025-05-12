<?php

/**
 * View: Order Confirmation Page
 *
 * Displays a confirmation message and summary after a user successfully places an order.
 * Shows order details, customer information, shipping address (if provided),
 * items ordered, and order notes (if provided).
 * Includes buttons for continuing shopping, viewing order history, and printing the confirmation.
 * Contains print-specific CSS to format the page for printing.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the page. Defaults to 'Order Confirmation'.
 * - $order (array, optional): An array containing the details of the confirmed order. Defaults to empty.
 *   Should contain keys like:
 *     - order_id (int): Unique ID of the order. (Required for the page to render).
 *     - status_class (string): CSS class for the order status badge.
 *     - status_text (string): User-friendly text for the order status.
 *     - order_date_formatted (string): Formatted date of the order.
 *     - total_amount_formatted (string): Formatted total amount of the order.
 *     - user_name (string): Customer's name.
 *     - user_email (string): Customer's email.
 *     - user_phone (string, optional): Customer's phone number.
 *     - notes (string, optional): Any notes added to the order.
 *     - shipping_address (string, optional): The shipping address for the order.
 *     - items (array): An array of items included in the order. Each item should have:
 *       - image_url (string): URL of the product image.
 *       - product_name (string): Name of the product.
 *       - quantity (int): Quantity ordered.
 *       - price_formatted (string): Formatted price per unit.
 *       - subtotal_formatted (string): Formatted subtotal for the item line.
 */

// Set the page title, using a default if not provided.
$page_title = $page_title ?? 'Order Confirmation';
// Initialize the order array, defaulting to empty if not provided.
$order = $order ?? [];
// Sanitize the page title for safe output.
$page_title_safe = htmlspecialchars($page_title);
// Get the session object for flash messages.
$session = App\Core\Registry::get('session');

// --- Crucial Check: Ensure essential order data exists ---
// If the order array is empty or doesn't have an order_id, display an error and stop rendering.
if (empty($order) || !isset($order['order_id'])) {
    echo "<div class='container'><div class='alert alert-error'>Error: Order data is missing or invalid. Cannot display confirmation.</div></div>";
    // Exit the script to prevent further processing with invalid data.
    return;
}

// --- Prepare and Sanitize Order Data for Display ---
// Sanitize basic order details, providing defaults for potentially missing optional fields.
$order_id_safe = htmlspecialchars($order['order_id']);
$status_class_safe = htmlspecialchars($order['status_class'] ?? 'status-unknown'); // Default class if status is missing
$status_text_safe = htmlspecialchars($order['status_text'] ?? 'Unknown'); // Default text if status is missing
$order_date_formatted_safe = htmlspecialchars($order['order_date_formatted'] ?? 'N/A'); // Default if date is missing
$total_amount_formatted_safe = htmlspecialchars($order['total_amount_formatted'] ?? '$0.00'); // Default if total is missing

// Sanitize customer information.
$user_name_safe = htmlspecialchars($order['user_name'] ?? 'N/A');
$user_email_safe = htmlspecialchars($order['user_email'] ?? 'N/A');
$user_phone_safe = htmlspecialchars($order['user_phone'] ?? 'N/A'); // Phone is optional

// Sanitize notes and shipping address, applying nl2br to preserve line breaks entered by the user.
$notes_safe = isset($order['notes']) ? nl2br(htmlspecialchars($order['notes'])) : '';
$shipping_address_safe = isset($order['shipping_address']) ? nl2br(htmlspecialchars($order['shipping_address'])) : '';

// Get the items array from the order data, defaulting to an empty array.
$items = $order['items'] ?? [];
?>
<!-- Main content area for the Order Confirmation page -->
<main class="full-width-main">
    <!-- Container for page content -->
    <div class="container">
        <!-- Page heading -->
        <h1 class="page-title"><?= $page_title_safe ?></h1>
        <!-- Page subtitle -->
        <p class="page-subtitle">Thank you for your purchase!</p>

        <!-- Success message block -->
        <div class="confirmation-success">
            <i class="fas fa-check-circle"></i> <!-- Success icon -->
            <p class="lead">Your order has been successfully placed!</p>
        </div>

        <!-- Display flash messages (success, error, info) -->
        <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($session->getFlash('success')); ?>
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

        <!-- Grid layout for Order and Customer Information -->
        <div class="order-summary-grid">
            <!-- Block for Order Information -->
            <div class="summary-block">
                <h3>Order Information</h3>
                <div class="info-group">
                    <!-- Order Number -->
                    <div class="info-item">
                        <span class="info-label">Order Number:</span>
                        <span class="info-value">#<?= $order_id_safe ?></span>
                    </div>
                    <!-- Order Date -->
                    <div class="info-item">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value"><?= $order_date_formatted_safe ?></span>
                    </div>
                    <!-- Order Status -->
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="badge <?= $status_class_safe ?>"><?= $status_text_safe ?></span>
                    </div>
                    <!-- Order Total Amount -->
                    <div class="info-item">
                        <span class="info-label">Total Amount:</span>
                        <span class="info-value order-total"><?= $total_amount_formatted_safe ?></span>
                    </div>
                </div>
            </div> <!-- End Order Information block -->

            <!-- Block for Customer Information -->
            <div class="summary-block">
                <h3>Customer Information</h3>
                <div class="info-group">
                    <!-- Customer Name -->
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?= $user_name_safe ?></span>
                    </div>
                    <!-- Customer Email -->
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= $user_email_safe ?></span>
                    </div>
                    <!-- Customer Phone (only displayed if provided) -->
                    <?php if (!empty($user_phone_safe) && $user_phone_safe !== 'N/A'): ?>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= $user_phone_safe ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div> <!-- End Customer Information block -->
        </div> <!-- End order-summary-grid -->

        <!-- Shipping Address Section (only displayed if provided) -->
        <?php if (!empty($shipping_address_safe)): ?>
        <div class="summary-block">
            <h3>Shipping Address</h3>
            <div class="shipping-address">
                <?= $shipping_address_safe // Output sanitized address with preserved line breaks 
                    ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Items Ordered Section (only displayed if items exist) -->
        <?php if (!empty($items)): ?>
        <div class="order-summary-section">
            <h2>Items Ordered</h2>
            <!-- Responsive table container -->
            <div>
                <!-- Table displaying items in the order -->
                <table class="order-table">
                    <!-- Table header -->
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <!-- Table body -->
                    <tbody>
                        <!-- Loop through each item in the order -->
                        <?php foreach ($items as $item):
                                // Basic validation for item data
                                if (!is_array($item) || !isset($item['image_url'], $item['product_name'], $item['quantity'], $item['price_formatted'], $item['subtotal_formatted']))
                                    continue; // Skip if essential data is missing

                                // Sanitize item data for safe output
                                $item_image_url_safe = htmlspecialchars($item['image_url']);
                                $item_name_safe = htmlspecialchars($item['product_name']);
                                $item_quantity_safe = htmlspecialchars($item['quantity']);
                                $item_price_safe = htmlspecialchars($item['price_formatted']);
                                $item_subtotal_safe = htmlspecialchars($item['subtotal_formatted']);
                            ?>
                        <!-- Table row for a single item -->
                        <tr>
                            <!-- Product details (image and name) -->
                            <td class="product-details">
                                <img src="<?= $item_image_url_safe ?>" alt="<?= $item_name_safe ?>"
                                    class="product-thumbnail">
                                <span class="product-name"><?= $item_name_safe ?></span>
                            </td>
                            <!-- Item price -->
                            <td class="text-end"><?= $item_price_safe ?></td>
                            <!-- Item quantity -->
                            <td class="text-center"><?= $item_quantity_safe ?></td>
                            <!-- Item subtotal -->
                            <td class="text-end"><?= $item_subtotal_safe ?></td>
                        </tr>
                        <?php endforeach; // End of items loop 
                            ?>
                    </tbody>
                    <!-- Table footer -->
                    <tfoot>
                        <tr>
                            <!-- Grand total row -->
                            <td colspan="3" class="text-end">Total:</td>
                            <td class="text-end order-total"><?= $total_amount_formatted_safe ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div> <!-- End table-responsive -->
        </div> <!-- End order-summary-section -->
        <?php endif; // End of check for empty items 
        ?>

        <!-- Order Notes Section (only displayed if notes exist) -->
        <?php if (!empty($notes_safe)): ?>
        <div class="summary-block">
            <h3>Order Notes</h3>
            <div class="notes-content">
                <?= $notes_safe // Output sanitized notes with preserved line breaks 
                    ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action buttons section -->
        <div class="confirmation-actions">
            <!-- Link to continue shopping -->
            <a href="/categories" class="btn btn-primary">Continue Shopping</a>
            <!-- Link to view user's order history -->
            <a href="/orders" class="btn btn-secondary">View My Orders</a>
            <!-- Button to trigger browser's print dialog -->
            <button class="btn btn-secondary print-confirmation" onclick="window.print(); return false;">
                <i class="fas fa-print"></i> Print Confirmation
            </button>
        </div>
    </div> <!-- End container -->
</main> <!-- End main content area -->
<!-- Print-specific CSS rules -->
<style type="text/css" media="print">
/* Hide elements not relevant for printing */
header,
footer,
.confirmation-actions,
/* Hide action buttons */
.mobile-menu-toggle {
    /* Hide mobile menu toggle if present */
    display: none !important;
}

/* Adjust container for full width printing */
.container {
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    max-width: none !important;
    box-shadow: none !important;
    /* Remove shadows for print */
    border: none !important;
    /* Remove borders for print */
}

/* Center titles for print */
.page-title,
.page-subtitle {
    text-align: center !important;
    margin: 20px 0 !important;
}

/* Adjust spacing for success message */
.confirmation-success {
    margin: 20px 0 !important;
    padding: 10px 0 !important;
    text-align: center;
    /* Center success message */
}

.confirmation-success i {
    font-size: 2em !important;
    /* Ensure icon size is appropriate */
}

/* Set base font size for print */
body {
    font-size: 10pt !important;
    color: #000 !important;
    /* Ensure text is black */
    background-color: #fff !important;
    /* Ensure background is white */
}

/* Adjust table padding for print */
.order-table th,
.order-table td {
    padding: 5px 8px !important;
    border: 1px solid #ccc !important;
    /* Add light borders for clarity */
}

/* Reduce thumbnail size for print */
.product-thumbnail {
    max-width: 40px !important;
    height: auto !important;
    vertical-align: middle;
    /* Align image nicely in cell */
}

/* Ensure summary blocks are distinct */
.summary-block {
    border: 1px solid #eee !important;
    padding: 10px !important;
    margin-bottom: 15px !important;
    page-break-inside: avoid !important;
    /* Try to keep blocks from splitting across pages */
}

/* Ensure grid layout works reasonably in print */
.order-summary-grid {
    display: block !important;
    /* Stack blocks vertically for print */
}

/* Remove background colors from badges */
.badge {
    background-color: transparent !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
    padding: 2px 4px !important;
}
</style>