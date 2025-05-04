<?php

/**
 * View: Order Details Page
 *
 * Displays detailed information about a specific order for the logged-in user.
 * Shows order status, customer info, shipping address, items ordered, notes,
 * and an order timeline visualization.
 * Allows users to cancel the order if its status is 'pending'.
 * Includes a confirmation modal for order cancellation.
 * Provides buttons to go back to the orders list and print the details.
 * Includes print-specific CSS.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the page. Defaults to 'Order Details'.
 * - $order (array, optional): An array containing the details of the specific order. Defaults to empty.
 *   Must contain 'order_id' and 'status'. Other expected keys are similar to order_confirmation.php:
 *     - order_id (int): Unique ID of the order. (Required).
 *     - status (string): The raw status value (e.g., 'pending', 'processing', 'completed', 'cancelled'). (Required).
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
 *       - product_id (int): Unique ID of the product.
 *       - price_formatted (string): Formatted price per unit.
 *       - quantity (int): Quantity ordered.
 *       - subtotal_formatted (string): Formatted subtotal for the item line.
 * - $csrfToken (string, optional): CSRF token for form submissions (like cancellation). Defaults to empty.
 */

// Set the page title, using a default if not provided.
$page_title = $page_title ?? 'Order Details';
// Initialize the order array, defaulting to empty if not provided.
$order = $order ?? [];
// Initialize the CSRF token, defaulting to empty if not provided.
$csrfToken = $csrfToken ?? '';
// Sanitize the page title for safe output.
$page_title_safe = htmlspecialchars($page_title);
// Sanitize the CSRF token for safe output in the form.
$csrfToken_safe = htmlspecialchars($csrfToken);
// Get the session object for flash messages.
$session = App\Core\Registry::get('session');

// --- Crucial Check: Ensure essential order data exists ---
// If the order array is empty or doesn't have an order_id, display an error and stop rendering.
if (empty($order) || !isset($order['order_id'])) {
    echo "<div class='container'><div class='alert alert-error'>Error: Order data is missing or invalid. Cannot display details.</div></div>";
    // Exit the script to prevent further processing with invalid data.
    return;
}

// --- Prepare and Sanitize Order Data for Display ---
// Sanitize basic order details, providing defaults for potentially missing optional fields.
$order_id_safe = htmlspecialchars($order['order_id']);
$status_class_safe = htmlspecialchars($order['status_class'] ?? 'status-unknown');
$status_text_safe = htmlspecialchars($order['status_text'] ?? 'Unknown');
$order_date_formatted_safe = htmlspecialchars($order['order_date_formatted'] ?? 'N/A');
$total_amount_formatted_safe = htmlspecialchars($order['total_amount_formatted'] ?? '$0.00');

// Sanitize customer information.
$user_name_safe = htmlspecialchars($order['user_name'] ?? 'N/A');
$user_email_safe = htmlspecialchars($order['user_email'] ?? 'N/A');
$user_phone_safe = htmlspecialchars($order['user_phone'] ?? 'N/A'); // Phone is optional

// Sanitize notes and shipping address, applying nl2br to preserve line breaks.
$notes_safe = isset($order['notes']) ? nl2br(htmlspecialchars($order['notes'])) : '';
$shipping_address_safe = isset($order['shipping_address']) ? nl2br(htmlspecialchars($order['shipping_address'])) : '';

// Get the items array from the order data, defaulting to an empty array.
$items = $order['items'] ?? [];
?>
<!-- Main content area for the Order Details page -->
<main class="full-width-main">
    <!-- Container for page content -->
    <div class="container">
        <!-- Page heading -->
        <h1 class="page-title"><?= $page_title_safe ?></h1>
        <!-- Page subtitle showing the specific Order ID -->
        <p class="page-subtitle">Order #<?= $order_id_safe ?></p>

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

        <!-- Main container for all order details -->
        <div class="details-container">
            <!-- Header section with status and action buttons -->
            <div class="details-header">
                <!-- Display current order status -->
                <div class="order-status">
                    Status: <span class="badge <?= $status_class_safe ?>"><?= $status_text_safe ?></span>
                </div>
                <!-- Action buttons -->
                <div class="order-actions">
                    <!-- Back button to the main orders list -->
                    <a href="/orders" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                    <!-- Cancel Order button (only shown if status is 'pending') -->
                    <?php if (isset($order['status']) && $order['status'] === 'pending'): ?>
                    <!-- Form to submit the cancellation request -->
                    <form action="/order/cancel/<?= $order_id_safe ?>" method="post" class="d-inline cancel-form"
                        id="cancelOrderForm">
                        <!-- CSRF token for security -->
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken_safe ?>">
                        <!-- Button to trigger the cancellation confirmation modal -->
                        <button type="button" name="cancel_order" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                            data-bs-target="#cancelOrderModal" data-cancel-url="/order/cancel/<?= $order_id_safe ?>">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                        <!-- Note: Actual form submission is handled via JavaScript after modal confirmation -->
                    </form>
                    <?php endif; ?>
                    <!-- Print button -->
                    <button class="btn btn-secondary btn-sm" onclick="window.print(); return false;">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div> <!-- End details-header -->

            <!-- Grid layout for Order and Customer Information -->
            <div class="order-summary-grid">
                <!-- Block for Order Information -->
                <div class="summary-block">
                    <h3>Order Information</h3>
                    <div class="info-group">
                        <div class="info-item">
                            <span class="info-label">Order Number:</span>
                            <span class="info-value">#<?= $order_id_safe ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order Date:</span>
                            <span class="info-value"><?= $order_date_formatted_safe ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <!-- Display status text with a status-specific class -->
                            <span
                                class="info-value status-text-<?= strtolower($order['status'] ?? 'unknown') ?>"><?= $status_text_safe ?></span>
                        </div>
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
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?= $user_name_safe ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?= $user_email_safe ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?= $user_phone_safe ?></span>
                        </div>
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

            <!-- Order Items Section -->
            <div class="order-items-section">
                <h3>Order Items</h3>
                <!-- Check if there are items in the order -->
                <?php if (!empty($items)): ?>
                <!-- Responsive table container -->
                <div class="table-responsive">
                    <!-- Table displaying items in the order -->
                    <table class="order-table">
                        <!-- Table header -->
                        <thead>
                            <tr>
                                <th scope="col" colspan="2">Product</th> <!-- Span image and name columns -->
                                <th scope="col" class="text-end">Unit Price</th>
                                <th scope="col" class="text-center">Quantity</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <!-- Table body -->
                        <tbody>
                            <!-- Loop through each item -->
                            <?php foreach ($items as $item):
                                    // Basic validation for item data
                                    if (!is_array($item) || !isset($item['image_url'], $item['product_name'], $item['price_formatted'], $item['quantity'], $item['subtotal_formatted'], $item['product_id']))
                                        continue; // Skip if essential data missing

                                    // Sanitize item data for safe output
                                    $item_image_url_safe = htmlspecialchars($item['image_url']);
                                    $item_name_safe = htmlspecialchars($item['product_name']);
                                    $item_id_safe = htmlspecialchars($item['product_id']);
                                    $item_price_safe = htmlspecialchars($item['price_formatted']);
                                    $item_quantity_safe = htmlspecialchars($item['quantity']);
                                    $item_subtotal_safe = htmlspecialchars($item['subtotal_formatted']);
                                ?>
                            <!-- Table row for a single item -->
                            <tr>
                                <!-- Product thumbnail image -->
                                <td style="width: 80px;">
                                    <img src="<?= $item_image_url_safe ?>" alt="<?= $item_name_safe ?>"
                                        class="product-thumbnail">
                                </td>
                                <!-- Product name and ID -->
                                <td>
                                    <?= $item_name_safe ?>
                                    <small class="product-id">ID: <?= $item_id_safe ?></small>
                                </td>
                                <!-- Unit price -->
                                <td class="text-end"><?= $item_price_safe ?></td>
                                <!-- Quantity -->
                                <td class="text-center"><?= $item_quantity_safe ?></td>
                                <!-- Subtotal -->
                                <td class="text-end"><?= $item_subtotal_safe ?></td>
                            </tr>
                            <?php endforeach; // End of items loop 
                                ?>
                        </tbody>
                        <!-- Table footer -->
                        <tfoot>
                            <tr>
                                <!-- Grand total row -->
                                <td colspan="4" class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold"><?= $total_amount_formatted_safe ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div> <!-- End table-responsive -->
                <?php else: // Displayed if $items array is empty 
                ?>
                <p>No items found for this order.</p>
                <?php endif; // End of check for empty items 
                ?>
            </div> <!-- End order-items-section -->

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

            <!-- Order Timeline Section -->
            <div class="order-timeline">
                <h3>Order Timeline</h3>
                <div class="timeline">
                    <!-- Timeline Item: Order Placed (Always shown and active) -->
                    <div class="timeline-item active">
                        <div class="timeline-icon"><i class="fas fa-check"></i></div>
                        <div class="timeline-content">
                            <h4>Order Placed</h4>
                            <p class="timeline-date"><?= $order_date_formatted_safe ?></p>
                            <p class="timeline-description">Your order has been received and is being processed.</p>
                        </div>
                    </div>

                    <!-- Timeline Item: Processing (Shown if status is 'processing' or 'completed') -->
                    <?php if (isset($order['status']) && ($order['status'] === 'processing' || $order['status'] === 'completed')): ?>
                    <div class="timeline-item active">
                        <div class="timeline-icon"><i class="fas fa-cog"></i></div>
                        <div class="timeline-content">
                            <h4>Processing</h4>
                            <p class="timeline-description">
                                <?= $order['status'] === 'processing' ? 'Your order is being prepared for shipping.' : 'Your order was prepared for shipping.' ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Timeline Item: Completed (Shown only if status is 'completed') -->
                    <?php if (isset($order['status']) && $order['status'] === 'completed'): ?>
                    <div class="timeline-item active terminal-status">
                        <div class="timeline-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="timeline-content">
                            <h4>Completed</h4>
                            <p class="timeline-description">Your order has been delivered successfully.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Timeline Item: Cancelled (Shown only if status is 'cancelled') -->
                    <?php if (isset($order['status']) && $order['status'] === 'cancelled'): ?>
                    <div class="timeline-item active cancelled terminal-status">
                        <div class="timeline-icon"><i class="fas fa-times"></i></div>
                        <div class="timeline-content">
                            <h4>Cancelled</h4>
                            <p class="timeline-description">This order has been cancelled.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div> <!-- End timeline -->
            </div> <!-- End order-timeline -->
        </div> <!-- End details-container -->
    </div> <!-- End container -->
</main> <!-- End main content area -->
<!-- Modal Dialog for Order Cancellation Confirmation -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <!-- Backdrop for the modal -->
    <div class="modal-backdrop"></div>
    <!-- Modal content container -->
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="cancelOrderModalLabel">
        <!-- Modal title -->
        <h4 id="cancelOrderModalLabel">Confirm Cancellation</h4>
        <!-- Confirmation message -->
        <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
        <!-- Modal action buttons -->
        <div class="modal-buttons">
            <!-- Button to close the modal without cancelling -->
            <button id="modalCloseBtn" class="modal-btn cancel-btn" aria-label="Keep Order">Keep Order</button>
            <!-- Button to confirm the cancellation (triggers form submission via JS) -->
            <button id="confirmCancelBtn" class="modal-btn confirm-btn" aria-label="Confirm Cancellation">Confirm
                Cancellation</button>
        </div>
    </div>
</div>
<!-- Note: JavaScript to handle modal visibility and form submission on confirm is expected elsewhere (e.g., in a global script file or potentially added below if specific to this page) -->
<!-- Inline CSS for specific styling adjustments and print styles -->
<style>
/* Style for smaller buttons */
.btn-sm {
    padding: 6px 12px;
    font-size: 14px;
}

/* Style for danger/cancel buttons */
.btn-danger {
    background-color: var(--tomato-red);
    /* Use CSS variable for color */
    color: white;
    border: none;
    /* Remove default border */
}

.btn-danger:hover {
    background-color: var(--dark-tomato-red);
    /* Darker shade on hover */
}

/* Add any other specific styles for this page below */
.product-id {
    display: block;
    font-size: 0.8em;
    color: #666;
}

/* --- Print-specific CSS rules --- */
@media print {

    /* Hide elements not relevant for printing */
    header,
    footer,
    .order-actions,
    /* Hide action buttons */
    .mobile-menu-toggle,
    /* Hide mobile menu toggle if present */
    .details-actions,
    /* Hide any other action sections */
    .modal,
    /* Hide modals */
    .modal-backdrop {
        /* Hide modal backdrops */
        display: none !important;
    }

    /* Adjust container for full width printing */
    .container {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: none !important;
    }

    /* Simplify details container for print */
    .details-container {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        /* Light border for structure */
        padding: 10px !important;
        margin-top: 0 !important;
    }

    /* Set base font size and colors for print */
    body {
        font-size: 10pt !important;
        color: #000 !important;
        background-color: #fff !important;
    }

    /* Adjust table font size and add borders */
    .order-table {
        font-size: 9pt !important;
    }

    .order-table th,
    .order-table td {
        border: 1px solid #ccc !important;
        padding: 4px 6px !important;
        /* Slightly reduce padding */
    }

    /* Reduce thumbnail size */
    .product-thumbnail {
        max-width: 40px !important;
        height: auto !important;
        vertical-align: middle;
    }

    /* Simplify timeline for print */
    .timeline::before,
    /* Hide the main timeline bar */
    .timeline-icon {
        /* Hide the icons */
        display: none !important;
    }

    .timeline-item {
        padding-left: 0 !important;
        /* Remove padding intended for icon space */
        margin-bottom: 10px !important;
        page-break-inside: avoid !important;
        /* Prevent items splitting */
    }

    .timeline-content {
        padding-left: 0 !important;
    }

    .timeline-date {
        font-size: 0.9em;
        color: #555;
    }

    /* Ensure summary blocks print well */
    .summary-block {
        border: 1px solid #eee !important;
        padding: 10px !important;
        margin-bottom: 15px !important;
        page-break-inside: avoid !important;
    }

    .order-summary-grid {
        display: block !important;
        /* Stack blocks */
    }

    /* Remove badge background */
    .badge {
        background-color: transparent !important;
        color: #000 !important;
        border: 1px solid #ccc !important;
        padding: 2px 4px !important;
    }
}
</style>