<!--
 * Admin View: Order Show (Details)
 *
 * This view displays the detailed information for a specific order.
 * It shows order summary, customer details, order notes, and the list of items in the order.
 * Admins can update the order status from this page.
 *
 * Expected variables:
 * - $order (array): An associative array containing all details of the specific order, including:
 *   - order_id
 *   - order_date
 *   - status
 *   - total_amount
 *   - user_name
 *   - user_email
 *   - user_phone
 *   - shipping_address
 *   - notes (optional)
 *   - items (array): An array of order items, each containing product_id, product_name, product_image, price, quantity.
 * - $csrf_token (string): The CSRF token for form submissions.
 * - $_SESSION['flash_success'] (string, optional): Success message from a previous action (e.g., status update).
 * - $_SESSION['flash_error'] (string, optional): Error message from a previous action.
 -->

<!-- Main container for the order details page -->
<div class="container-fluid">
    <!-- Page Header Row -->
    <div class="row mb-4">
        <!-- Page Title displaying the Order ID -->
        <div class="col-md-6">
            <h1>Order #<?= htmlspecialchars($order['order_id']) // Display Order ID, escape HTML 
                        ?></h1>
        </div>
        <!-- Back to Orders List Button -->
        <div class="col-md-6 text-end">
            <a href="/admin/orders" class="btn btn-secondary">Back to Orders</a>
        </div>
    </div>

    <?php // Display flash messages if they exist 
    ?>
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_success'] // Display success message 
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php // unset($_SESSION['flash_success']); // Optional: Unset flash message after displaying 
        ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_error'] // Display error message 
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php // unset($_SESSION['flash_error']); // Optional: Unset flash message after displaying 
        ?>
    <?php endif; ?>

    <!-- Row containing Order Summary, Customer Info, and Notes cards -->
    <div class="row">
        <!-- Order Summary Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <!-- Order Date -->
                    <div class="mb-3">
                        <strong>Order Date:</strong>
                        <div><?= date('F d, Y H:i:s', strtotime($order['order_date'])) // Format date 
                                ?></div>
                    </div>
                    <!-- Current Status -->
                    <div class="mb-3">
                        <strong>Current Status:</strong>
                        <div>
                            <?php // Use helper function for status badge class 
                            ?>
                            <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?> fs-6">
                                <?= ucfirst(htmlspecialchars($order['status'])) // Display capitalized status, escape HTML 
                                ?>
                            </span>
                        </div>
                    </div>
                    <!-- Total Amount -->
                    <div class="mb-3">
                        <strong>Total Amount:</strong>
                        <div class="fs-5">$<?= number_format($order['total_amount'], 2) // Format currency 
                                            ?></div>
                    </div>
                    <!-- Update Status Form -->
                    <form action="/admin/orders/<?= $order['order_id'] ?>/status" method="post" class="mt-4">
                        <?php // CSRF Token for security 
                        ?>
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <div class="mb-3">
                            <label for="status" class="form-label"><strong>Update Status:</strong></label>
                            <select name="status" id="status" class="form-select">
                                <?php // Options for order status, pre-select current status 
                                ?>
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending
                                </option>
                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>
                                    Processing</option>
                                <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>
                                    Completed</option>
                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>
                                    Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Customer Information Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <!-- Customer Name -->
                    <div class="mb-3">
                        <strong>Name:</strong>
                        <div><?= htmlspecialchars($order['user_name']) // Escape HTML 
                                ?></div>
                    </div>
                    <!-- Customer Email -->
                    <div class="mb-3">
                        <strong>Email:</strong>
                        <div><?= htmlspecialchars($order['user_email']) // Escape HTML 
                                ?></div>
                    </div>
                    <!-- Customer Phone -->
                    <div class="mb-3">
                        <strong>Phone:</strong>
                        <div><?= htmlspecialchars($order['user_phone']) // Escape HTML 
                                ?></div>
                    </div>
                    <!-- Shipping Address -->
                    <div class="mb-3">
                        <strong>Shipping Address:</strong>
                        <?php // Use nl2br to preserve line breaks, escape HTML 
                        ?>
                        <div class="text-wrap"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Notes Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Order Notes</h5>
                </div>
                <div class="card-body">
                    <?php // Check if order notes exist 
                    ?>
                    <?php if (!empty($order['notes'])): ?>
                        <?php // Display notes, preserve line breaks, escape HTML 
                        ?>
                        <div class="text-wrap"><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
                    <?php else: ?>
                        <!-- Message if no notes are present -->
                        <p class="text-muted">No notes for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div> <!-- End row for summary/customer/notes -->

    <!-- Order Items Card -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Order Items</h5>
        </div>
        <div class="card-body">
            <?php // Check if there are items in the order 
            ?>
            <?php if (empty($order['items'])): ?>
                <!-- Message if no items are found -->
                <div class="alert alert-info">No items found for this order.</div>
            <?php else: ?>
                <!-- Responsive table container for items -->
                <div class="table-responsive">
                    <!-- Order Items Table -->
                    <table class="table table-striped">
                        <!-- Table Header -->
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <!-- Table Body -->
                        <tbody>
                            <?php
                            // Initialize a variable to calculate the subtotal from items
                            $calculatedTotal = 0;
                            // Loop through each item in the order
                            foreach ($order['items'] as $item):
                                // Calculate the total for this line item
                                $lineTotal = $item['price'] * $item['quantity'];
                                // Add the line total to the overall calculated total
                                $calculatedTotal += $lineTotal;
                            ?>
                                <tr>
                                    <!-- Product Details (Image and Name) -->
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php // Display product image if available 
                                            ?>
                                            <?php if (!empty($item['product_image'])): ?>
                                                <img src="<?= htmlspecialchars($item['product_image']) // Escape image URL 
                                                            ?>" alt="<?= htmlspecialchars($item['product_name']) // Escape alt text 
                                                                        ?>" class="me-3"
                                                    style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <div><?= htmlspecialchars($item['product_name']) // Escape product name 
                                                        ?></div>
                                                <div class="text-muted small">Product ID:
                                                    <?= htmlspecialchars($item['product_id']) // Escape product ID 
                                                    ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Item Price -->
                                    <td>$<?= number_format($item['price'], 2) // Format currency 
                                            ?></td>
                                    <!-- Item Quantity -->
                                    <td><?= htmlspecialchars($item['quantity']) // Escape quantity 
                                        ?></td>
                                    <!-- Line Total -->
                                    <td class="text-end">$<?= number_format($lineTotal, 2) // Format currency 
                                                            ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <!-- Table Footer -->
                        <tfoot>
                            <!-- Calculated Subtotal Row -->
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th class="text-end">$<?= number_format($calculatedTotal, 2) // Display calculated subtotal 
                                                        ?></th>
                            </tr>
                            <!-- Order Total Row (from main order data) -->
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="text-end">$<?= number_format($order['total_amount'], 2) // Display final order total 
                                                        ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; // End items check 
            ?>
        </div> <!-- End card-body -->
    </div> <!-- End card -->
</div> <!-- End container-fluid -->

<?php
/**
 * Helper function to determine the Bootstrap background class for an order status badge.
 * (This function might be duplicated from orders/index.php; consider moving to a shared helper file if applicable)
 *
 * @param string $status The order status ('pending', 'processing', 'completed', 'cancelled').
 * @return string The corresponding Bootstrap background class (e.g., 'warning', 'info', 'success', 'danger', 'secondary').
 */
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>