<?php

/**
 * View: My Orders Page
 *
 * Displays a list of the logged-in user's past orders.
 * Allows filtering orders by status.
 * Provides links to view detailed information for each order.
 * Shows an empty state message if the user has no orders.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the page. Defaults to 'My Orders'.
 * - $orders (array, optional): An array of order data for the user. Defaults to an empty array.
 *   Each order array should contain:
 *     - order_id (int): The unique ID of the order.
 *     - order_date_formatted (string): The formatted date of the order.
 *     - total_amount_formatted (string): The formatted total amount of the order.
 *     - status_class (string): CSS class representing the order status (e.g., 'status-completed').
 *     - status_text (string): User-friendly text for the order status (e.g., 'Completed').
 *     - status (string): The raw status value (e.g., 'completed', 'pending').
 */

// Set the page title, using a default if not provided.
$page_title = $page_title ?? 'My Orders';
// Initialize the orders array, defaulting to empty if not provided.
$orders = $orders ?? [];
// Sanitize the page title for safe output in HTML.
$page_title_safe = htmlspecialchars($page_title);
// Get the session object from the registry to access flash messages.
$session = App\Core\Registry::get('session');
?>
<!-- Main content area for the My Orders page -->
<main class="full-width-main">
    <!-- Container for page content -->
    <div class="container">
        <!-- Page heading -->
        <h1 class="page-title"><?= $page_title_safe ?></h1>
        <!-- Page subtitle/description -->
        <p class="page-subtitle">View and track your order history</p>

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

        <!-- Check if there are any orders to display -->
        <?php if (!empty($orders)): ?>
        <!-- Container for the orders list and filter -->
        <div class="orders-container">
            <!-- Filter section -->
            <div class="orders-filter">
                <div class="filter-group">
                    <label for="order-status-filter">Filter by Status:</label>
                    <!-- Dropdown to filter orders by status -->
                    <select id="order-status-filter" class="form-control">
                        <option value="all">All Orders</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <!-- Orders list section -->
            <div class="orders-list">
                <!-- Responsive table container -->
                <div>
                    <!-- Table displaying order history -->
                    <table class="order-table">
                        <!-- Table header -->
                        <thead>
                            <tr>
                                <th scope="col">Order #</th>
                                <th scope="col">Date</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table body -->
                        <tbody>
                            <!-- Loop through each order -->
                            <?php foreach ($orders as $order):
                                    // Basic validation to ensure the order data is usable
                                    if (!is_array($order) || !isset($order['order_id'], $order['order_date_formatted'], $order['total_amount_formatted'], $order['status_class'], $order['status_text']))
                                        continue; // Skip this iteration if data is missing

                                    // Sanitize order data for safe output
                                    $order_id_safe = htmlspecialchars($order['order_id']);
                                    $order_date_safe = htmlspecialchars($order['order_date_formatted']);
                                    $total_amount_safe = htmlspecialchars($order['total_amount_formatted']);
                                    $status_class_safe = htmlspecialchars($order['status_class']);
                                    $status_text_safe = htmlspecialchars($order['status_text']);
                                    // Get the raw status value for the data attribute (used by JS filter)
                                    $status_value = strtolower($order['status'] ?? 'unknown');
                                ?>
                            <!-- Table row for a single order, with data-status attribute for filtering -->
                            <tr class="order-row" data-status="<?= $status_value ?>">
                                <!-- Order ID -->
                                <td>#<?= $order_id_safe ?></td>
                                <!-- Order Date -->
                                <td><?= $order_date_safe ?></td>
                                <!-- Order Total Amount -->
                                <td class="text-end"><?= $total_amount_safe ?></td>
                                <!-- Order Status (displayed as a badge) -->
                                <td class="text-center">
                                    <span class="badge <?= $status_class_safe ?>">
                                        <?= $status_text_safe ?>
                                    </span>
                                </td>
                                <!-- Action buttons -->
                                <td class="text-center">
                                    <!-- Link to view order details -->
                                    <a href="/order/details/<?= $order_id_safe ?>" class="btn btn-secondary">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; // End of orders loop 
                                ?>
                        </tbody>
                    </table>
                </div> <!-- End table-responsive -->
            </div> <!-- End orders-list -->
        </div> <!-- End orders-container -->
        <?php else: // Displayed if $orders array is empty 
        ?>
        <!-- Empty state section when no orders are found -->
        <div class="empty-state-container">
            <div class="empty-state-illustration">
                <img src="/assets/images/cart/empty_shopping_cart.png" alt="Empty State Illustration">
            </div>
            <h2 class="empty-state-heading">Your order history is empty</h2>
            <p class="empty-state-text">You haven't placed any orders with us yet. Explore our fresh selection and
                enjoy convenient delivery to your doorstep!</p>
            <!-- Call to action button to browse products -->
            <a href="/categories" class="btn btn-primary empty-state-cta">Browse Products</a>
        </div>
        <?php endif; // End of check for empty orders 
        ?>
    </div> <!-- End container -->
</main> <!-- End main content area -->
<!-- JavaScript for client-side order filtering -->
<script>
// Wait for the DOM to be fully loaded before executing the script
document.addEventListener('DOMContentLoaded', function() {
    // Get references to the status filter dropdown and all order rows
    const statusFilter = document.getElementById('order-status-filter');
    const orderRows = document.querySelectorAll('.order-row');

    // Check if the status filter element exists
    if (statusFilter) {
        // Add an event listener to the filter dropdown for the 'change' event
        statusFilter.addEventListener('change', function() {
            // Get the selected status value from the dropdown
            const selectedStatus = this.value;

            // Iterate over each order row
            orderRows.forEach(row => {
                // Get the status value stored in the 'data-status' attribute of the row
                const rowStatus = row.getAttribute('data-status');

                // Check if the row should be visible based on the selected filter
                if (selectedStatus === 'all' || selectedStatus === rowStatus) {
                    // Show the row if 'All Orders' is selected or if the row status matches the selected status
                    row.style.display = ''; // Reset display to default (visible)
                } else {
                    // Hide the row if its status doesn't match the selected filter
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
<!-- Inline CSS for specific styling adjustments -->
<style>
/* Add any other specific styles for this page below */
</style>