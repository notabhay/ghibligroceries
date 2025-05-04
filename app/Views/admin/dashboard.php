<!--
Admin Dashboard View
--------------------
This view presents the main landing page for the administrative panel.
It displays a welcome message, key statistics about the store, quick links
to common management sections, and a table of recent orders.

Expected PHP Variables:
- $stats: An associative array containing various store statistics like
  'total_users', 'total_orders', 'total_products', 'total_categories',
  'pending_orders', 'processing_orders', 'completed_orders', 'low_stock_products'.
- $recent_orders: An array of associative arrays, each representing a recent order
  with details like 'order_id', 'user_name', 'order_date', 'total_amount', 'status'.
-->
<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="dashboard-welcome">
        <h2>Welcome to the Admin Panel</h2>
        <p>This is the administration area for GhibliGroceries. From here, you can manage users, orders, products, and
            categories.</p>
    </div>
    <!-- /Welcome Section -->

    <!-- Quick Statistics Section -->
    <div class="dashboard-stats">
        <div class="stats-heading">
            <h3>Quick Statistics</h3>
            <p>Overview of your store's performance</p>
        </div>
        <div class="stats-grid">
            <!-- Total Users Stat Card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h4>Total Users</h4>
                    <p class="stat-value"><?= $stats['total_users'] // Display total registered users 
                                            ?></p>
                    <p class="stat-description">Registered users</p>
                </div>
            </div>
            <!-- /Total Users Stat Card -->

            <!-- Total Orders Stat Card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h4>Total Orders</h4>
                    <p class="stat-value"><?= $stats['total_orders'] // Display total number of orders 
                                            ?></p>
                    <p class="stat-description">All orders</p>
                </div>
            </div>
            <!-- /Total Orders Stat Card -->

            <!-- Total Products Stat Card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h4>Products</h4>
                    <p class="stat-value"><?= $stats['total_products'] // Display total number of products 
                                            ?></p>
                    <p class="stat-description">Available products</p>
                </div>
            </div>
            <!-- /Total Products Stat Card -->

            <!-- Total Categories Stat Card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-content">
                    <h4>Categories</h4>
                    <p class="stat-value"><?= $stats['total_categories'] // Display total number of categories 
                                            ?></p>
                    <p class="stat-description">Product categories</p>
                </div>
            </div>
            <!-- /Total Categories Stat Card -->

            <!-- Pending Orders Stat Card -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h4>Pending Orders</h4>
                    <p class="stat-value"><?= $stats['pending_orders'] // Display count of pending orders 
                                            ?></p>
                    <p class="stat-description">Awaiting processing</p>
                </div>
            </div>
            <!-- /Pending Orders Stat Card -->

            <!-- Processing Orders Stat Card -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(0, 123, 255, 0.1); color: #007bff;">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-content">
                    <h4>Processing Orders</h4>
                    <p class="stat-value"><?= $stats['processing_orders'] // Display count of processing orders 
                                            ?></p>
                    <p class="stat-description">Currently processing</p>
                </div>
            </div>
            <!-- /Processing Orders Stat Card -->

            <!-- Completed Orders Stat Card -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h4>Completed Orders</h4>
                    <p class="stat-value"><?= $stats['completed_orders'] // Display count of completed orders 
                                            ?></p>
                    <p class="stat-description">Successfully delivered</p>
                </div>
            </div>
            <!-- /Completed Orders Stat Card -->

            <!-- Low Stock Products Stat Card -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h4>Low Stock</h4>
                    <p class="stat-value"><?= $stats['low_stock_products'] // Display count of products with low stock 
                                            ?></p>
                    <p class="stat-description">Products need restocking</p>
                </div>
            </div>
            <!-- /Low Stock Products Stat Card -->
        </div>
    </div>
    <!-- /Quick Statistics Section -->

    <!-- Quick Actions Section -->
    <div class="dashboard-quick-actions">
        <div class="quick-actions-heading">
            <h3>Quick Actions</h3>
            <p>Common administrative tasks</p>
        </div>
        <div class="quick-actions-grid">
            <!-- Manage Users Action Card -->
            <a href="/admin/users" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="action-content">
                    <h4>Manage Users</h4>
                    <p>View, edit, and manage user accounts</p>
                </div>
            </a>
            <!-- /Manage Users Action Card -->

            <!-- View Orders Action Card -->
            <a href="/admin/orders" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="action-content">
                    <h4>View Orders</h4>
                    <p>Process and manage customer orders</p>
                </div>
            </a>
            <!-- /View Orders Action Card -->

            <!-- Manage Products Action Card -->
            <a href="/admin/products" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="action-content">
                    <h4>Manage Products</h4>
                    <p>Add, edit, or remove products</p>
                </div>
            </a>
            <!-- /Manage Products Action Card -->

            <!-- Manage Categories Action Card -->
            <a href="/admin/categories" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="action-content">
                    <h4>Manage Categories</h4>
                    <p>Organize your product categories</p>
                </div>
            </a>
            <!-- /Manage Categories Action Card -->
        </div>
    </div>
    <!-- /Quick Actions Section -->

    <!-- Recent Orders Section -->
    <div class="dashboard-recent-orders">
        <div class="recent-orders-heading">
            <h3>Recent Orders</h3>
            <p>Latest customer orders</p>
        </div>
        <div class="recent-orders-table-container">
            <table class="recent-orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php // Check if there are any recent orders to display 
                    ?>
                    <?php if (empty($recent_orders)): ?>
                        <!-- Display message if no recent orders -->
                        <tr>
                            <td colspan="6" class="no-orders">No recent orders found</td>
                        </tr>
                    <?php else: ?>
                        <?php // Loop through each recent order and display its details 
                        ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] // Display Order ID 
                                        ?></td>
                                <td><?= htmlspecialchars($order['user_name']) // Display Customer Name (escaped) 
                                    ?></td>
                                <td><?= date('M d, Y', strtotime($order['order_date'])) // Format and display Order Date 
                                    ?></td>
                                <td>$<?= number_format($order['total_amount'], 2) // Format and display Total Amount 
                                        ?></td>
                                <td>
                                    <!-- Display Order Status with dynamic class for styling -->
                                    <span class="order-status status-<?= strtolower($order['status']) ?>">
                                        <?= ucfirst($order['status']) // Display capitalized status 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Link to view the full order details -->
                                    <a href="/admin/orders/<?= $order['order_id'] ?>" class="view-order-btn">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Link to view all orders -->
            <div class="view-all-orders">
                <a href="/admin/orders" class="view-all-link">View All Orders <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <!-- /Recent Orders Section -->
</div>

<!-- Embedded CSS for Dashboard Styling -->
<!-- Note: It's generally better practice to keep CSS in separate files, -->
<!-- but embedding can be acceptable for view-specific styles in smaller applications. -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Main container for the dashboard content */
    .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 2rem;
        /* Spacing between dashboard sections */
    }

    /* Styling for the welcome message box */
    .dashboard-welcome {
        background-color: #f8f9fa;
        border-left: 4px solid var(--admin-primary);
        /* Accent border */
        padding: 1.5rem;
        border-radius: 4px;
    }

    .dashboard-welcome h2 {
        margin-top: 0;
        color: var(--admin-secondary);
    }

    /* Common styling for section headings (Stats, Quick Actions) */
    .stats-heading,
    .quick-actions-heading {
        margin-bottom: 1rem;
    }

    .stats-heading h3,
    .quick-actions-heading h3 {
        margin-bottom: 0.25rem;
        color: var(--admin-secondary);
    }

    .stats-heading p,
    .quick-actions-heading p {
        margin-top: 0;
        color: #6c757d;
        /* Subdued text color */
    }

    /* Grid layout for statistics and quick action cards */
    .stats-grid,
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        /* Responsive grid */
        gap: 1.5rem;
    }

    /* Styling for individual statistic and action cards */
    .stat-card,
    .action-card {
        display: flex;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        /* Subtle shadow */
        padding: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        /* Hover effect transition */
    }

    /* Specific styling for action cards (links) */
    .action-card {
        text-decoration: none;
        color: inherit;
        /* Inherit text color */
    }

    /* Hover effect for cards */
    .stat-card:hover,
    .action-card:hover {
        transform: translateY(-5px);
        /* Slight lift effect */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Enhanced shadow */
    }

    /* Styling for icons within cards */
    .stat-icon,
    .action-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background-color: rgba(52, 152, 219, 0.1);
        /* Light background for icon */
        color: var(--admin-primary);
        border-radius: 8px;
        margin-right: 1rem;
        font-size: 1.5rem;
    }

    /* Content area within cards */
    .stat-content,
    .action-content {
        flex: 1;
        /* Allow content to take remaining space */
    }

    .stat-content h4,
    .action-content h4 {
        margin-top: 0;
        margin-bottom: 0.5rem;
        color: var(--admin-secondary);
    }

    /* Styling for the main statistic value */
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0.5rem 0;
        color: var(--admin-primary);
    }

    /* Styling for descriptions within cards */
    .stat-description,
    .action-content p {
        margin: 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    /* Responsive adjustments for smaller screens */
    @media (max-width: 768px) {

        .stats-grid,
        .quick-actions-grid {
            grid-template-columns: 1fr;
            /* Stack cards vertically */
        }
    }

    /* Styling for the Recent Orders section */
    .dashboard-recent-orders {
        margin-top: 2rem;
    }

    .recent-orders-heading h3 {
        margin-bottom: 0.25rem;
        color: var(--admin-secondary);
    }

    .recent-orders-heading p {
        margin-top: 0;
        color: #6c757d;
        margin-bottom: 1rem;
    }

    /* Container for the recent orders table */
    .recent-orders-table-container {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        /* Ensures border radius applies correctly */
    }

    /* Styling for the recent orders table */
    .recent-orders-table {
        width: 100%;
        border-collapse: collapse;
    }

    .recent-orders-table th,
    .recent-orders-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
        /* Light border between rows */
    }

    .recent-orders-table th {
        background-color: #f8f9fa;
        /* Light header background */
        font-weight: 600;
        color: var(--admin-secondary);
    }

    .recent-orders-table tr:last-child td {
        border-bottom: none;
        /* Remove border from last row */
    }

    /* Base styling for order status badges */
    .order-status {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        /* Pill shape */
        font-size: 0.8rem;
        font-weight: 500;
    }

    /* Specific styles for different order statuses */
    .status-pending {
        background-color: rgba(255, 193, 7, 0.1);
        /* Yellow */
        color: #ffc107;
    }

    .status-processing {
        background-color: rgba(0, 123, 255, 0.1);
        /* Blue */
        color: #007bff;
    }

    .status-completed {
        background-color: rgba(40, 167, 69, 0.1);
        /* Green */
        color: #28a745;
    }

    .status-cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        /* Red */
        color: #dc3545;
    }

    /* Styling for the 'View' order button */
    .view-order-btn {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background-color: var(--admin-primary);
        color: white;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: background-color 0.2s ease;
    }

    .view-order-btn:hover {
        background-color: var(--admin-secondary);
    }

    /* Styling for the 'No recent orders' message */
    .no-orders {
        text-align: center;
        color: #6c757d;
        padding: 2rem !important;
        /* Ensure padding overrides */
    }

    /* Container for the 'View All Orders' link below the table */
    .view-all-orders {
        padding: 1rem;
        text-align: center;
        border-top: 1px solid #f0f0f0;
        /* Separator line */
    }

    .view-all-link {
        color: var(--admin-primary);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .view-all-link:hover {
        color: var(--admin-secondary);
    }

    .view-all-link i {
        margin-left: 0.5rem;
        font-size: 0.8rem;
    }
</style>