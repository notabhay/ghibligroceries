<!--
 * Admin View: Order Index
 *
 * This view displays a list of all customer orders within the admin panel.
 * It provides filtering options (by status, date range) and pagination.
 * Admins can view details of each order from this page.
 *
 * Expected variables:
 * - $orders (array): An array of order data, each containing details like order_id, user_name, user_email, order_date, total_amount, status.
 * - $filters (array, optional): An array containing the currently applied filter values (status, start_date, end_date). Used to pre-fill the filter form.
 * - $pagination (array, optional): An array containing pagination data (total_pages, current_page). Required if pagination is enabled.
 -->

<!-- Main container for the orders management page -->
<div class="container-fluid">
    <!-- Page Header Row -->
    <div class="row mb-4">
        <!-- Page Title -->
        <div class="col-md-6">
            <h1>Manage Orders</h1>
        </div>
        <!-- Back to Dashboard Button -->
        <div class="col-md-6 text-end">
            <a href="/admin/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <!-- Filter Section Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Orders</h5>
        </div>
        <div class="card-body">
            <!-- Filter Form (Submits via GET to the same page) -->
            <form action="/admin/orders" method="get" class="row g-3">
                <!-- Status Filter Dropdown -->
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <?php // PHP logic to pre-select the current filter status 
                        ?>
                        <option value="pending"
                            <?= isset($filters['status']) && $filters['status'] === 'pending' ? 'selected' : '' ?>>
                            Pending</option>
                        <option value="processing"
                            <?= isset($filters['status']) && $filters['status'] === 'processing' ? 'selected' : '' ?>>
                            Processing</option>
                        <option value="completed"
                            <?= isset($filters['status']) && $filters['status'] === 'completed' ? 'selected' : '' ?>>
                            Completed</option>
                        <option value="cancelled"
                            <?= isset($filters['status']) && $filters['status'] === 'cancelled' ? 'selected' : '' ?>>
                            Cancelled</option>
                    </select>
                </div>
                <!-- Start Date Filter -->
                <div class="col-md-3">
                    <label for="start_date" class="form-label">From Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $filters['start_date'] ?? '' // Pre-fill start date if set 
                                                                                                        ?>">
                </div>
                <!-- End Date Filter -->
                <div class="col-md-3">
                    <label for="end_date" class="form-label">To Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $filters['end_date'] ?? '' // Pre-fill end date if set 
                                                                                                    ?>">
                </div>
                <!-- Filter Action Buttons -->
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="/admin/orders" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Orders</h5>
        </div>
        <div class="card-body">
            <?php // Check if there are any orders to display 
            ?>
            <?php if (empty($orders)): ?>
                <!-- Display message if no orders are found -->
                <div class="alert alert-info">No orders found matching your criteria.</div>
            <?php else: ?>
                <!-- Responsive table container -->
                <div class="table-responsive">
                    <!-- Orders Table -->
                    <table class="table table-striped table-hover">
                        <!-- Table Header -->
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Order Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body -->
                        <tbody>
                            <?php // Loop through each order and display its details 
                            ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <!-- Order ID -->
                                    <td>#<?= htmlspecialchars($order['order_id']) // Display Order ID, escape HTML 
                                            ?></td>
                                    <!-- Customer Name and Email -->
                                    <td>
                                        <?= htmlspecialchars($order['user_name']) // Display User Name, escape HTML 
                                        ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($order['user_email']) // Display User Email, escape HTML 
                                                                    ?></small>
                                    </td>
                                    <!-- Order Date (Formatted) -->
                                    <td><?= date('M d, Y H:i', strtotime($order['order_date'])) // Format date 
                                        ?></td>
                                    <!-- Total Amount (Formatted as Currency) -->
                                    <td>$<?= number_format($order['total_amount'], 2) // Format currency 
                                            ?></td>
                                    <!-- Order Status with Badge -->
                                    <td>
                                        <?php // Use helper function to get the appropriate badge class based on status 
                                        ?>
                                        <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($order['status'])) // Display capitalized status, escape HTML 
                                            ?>
                                        </span>
                                    </td>
                                    <!-- Action Buttons -->
                                    <td>
                                        <!-- View Order Details Button -->
                                        <a href="/admin/orders/<?= $order['order_id'] // Link to the specific order view 
                                                                ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php // Check if pagination data exists and if there's more than one page 
                ?>
                <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                    <!-- Pagination Navigation -->
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php // Previous Page Link 
                            ?>
                            <?php if ($pagination['current_page'] > 1): ?>
                                <li class="page-item">
                                    <?php // Build URL with current page - 1 and existing filters 
                                    ?>
                                    <a class="page-link"
                                        href="?page=<?= $pagination['current_page'] - 1 ?><?= buildFilterQueryString($filters) ?>"
                                        aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <!-- Disabled Previous Page Link -->
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Calculate pagination range (show 2 pages before and after current page)
                            $startPage = max(1, $pagination['current_page'] - 2);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

                            // Show link to first page and ellipsis if needed
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1' . buildFilterQueryString($filters) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            // Generate page number links
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                if ($i == $pagination['current_page']) {
                                    // Active page link
                                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                                } else {
                                    // Regular page link
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $i . buildFilterQueryString($filters) . '">' . $i . '</a></li>';
                                }
                            }

                            // Show ellipsis and link to last page if needed
                            if ($endPage < $pagination['total_pages']) {
                                if ($endPage < $pagination['total_pages'] - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $pagination['total_pages'] . buildFilterQueryString($filters) . '">' . $pagination['total_pages'] . '</a></li>';
                            }
                            ?>

                            <?php // Next Page Link 
                            ?>
                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                <li class="page-item">
                                    <?php // Build URL with current page + 1 and existing filters 
                                    ?>
                                    <a class="page-link"
                                        href="?page=<?= $pagination['current_page'] + 1 ?><?= buildFilterQueryString($filters) ?>"
                                        aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <!-- Disabled Next Page Link -->
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; // End pagination check 
                ?>
            <?php endif; // End orders check 
            ?>
        </div> <!-- End card-body -->
    </div> <!-- End card -->
</div> <!-- End container-fluid -->

<?php
/**
 * Helper function to determine the Bootstrap background class for an order status badge.
 *
 * @param string $status The order status ('pending', 'processing', 'completed', 'cancelled').
 * @return string The corresponding Bootstrap background class (e.g., 'warning', 'info', 'success', 'danger', 'secondary').
 */
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'pending':
            return 'warning'; // Yellow badge for pending
        case 'processing':
            return 'info';    // Blue badge for processing
        case 'completed':
            return 'success'; // Green badge for completed
        case 'cancelled':
            return 'danger';  // Red badge for cancelled
        default:
            return 'secondary'; // Grey badge for unknown status
    }
}

/**
 * Helper function to build a query string fragment containing the current filter parameters.
 * This is used to preserve filters when navigating through pagination links.
 *
 * @param array $filters An associative array of filter parameters (e.g., ['status' => 'pending', 'start_date' => '...']).
 * @return string A URL-encoded query string fragment (e.g., "&status=pending&start_date=...") or an empty string if no filters are set.
 */
function buildFilterQueryString($filters)
{
    $queryString = '';
    // Check if the filters array is not empty
    if (!empty($filters)) {
        // Loop through each filter key-value pair
        foreach ($filters as $key => $value) {
            // Only add non-empty filter values to the query string
            if (!empty($value)) {
                // Append the URL-encoded key-value pair
                $queryString .= "&{$key}=" . urlencode($value);
            }
        }
    }
    // Return the constructed query string fragment
    return $queryString;
}
?>