<!--
 * Admin View: User Index
 *
 * This view displays a list of all registered users in the admin panel.
 * It shows key user details like ID, name, email, phone, role, status, and registration date.
 * Provides actions to view and edit each user.
 * Includes pagination for navigating through large lists of users.
 * Displays flash messages for success/error feedback.
 * Contains embedded CSS for layout, styling, and responsive table adjustments.
 *
 * Expected variables:
 * - $users (array): An array of user data, each containing user_id, name, email, phone, role, account_status, registration_date.
 * - $pagination (array): An array containing pagination data (total_pages, current_page).
 * - $_SESSION['_flash']['success'] (string, optional): Success message from a previous action.
 * - $_SESSION['_flash']['error'] (string, optional): Error message from a previous action.
 -->

<!-- Main container for the user management page -->
<div class="users-container">
    <!-- Page Header -->
    <div class="users-header">
        <h2>User Management</h2>
        <p>View and manage all registered users.</p>
    </div>

    <?php // Display flash messages if they exist 
    ?>
    <?php if (isset($_SESSION['_flash']['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['_flash']['success']); // Escape HTML 
            ?>
        </div>
        <?php // unset($_SESSION['_flash']['success']); // Optional: Unset after display 
        ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['_flash']['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['_flash']['error']); // Escape HTML 
            ?>
        </div>
        <?php // unset($_SESSION['_flash']['error']); // Optional: Unset after display 
        ?>
    <?php endif; ?>

    <!-- Users Table Container (for responsiveness) -->
    <div class="users-table-container">
        <!-- Users Table -->
        <table class="users-table">
            <!-- Table Header -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <!-- Table Body -->
            <tbody>
                <?php // Check if there are any users to display 
                ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php // Loop through each user and display their details 
                    ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <!-- User ID -->
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <!-- User Name -->
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <!-- User Email -->
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <!-- User Phone -->
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <!-- User Role Badge -->
                            <td>
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-customer'; // Dynamic class based on role 
                                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['role'])); // Display capitalized role 
                                    ?>
                                </span>
                            </td>
                            <!-- Account Status Badge -->
                            <td>
                                <span class="badge <?php echo isset($user['account_status']) && $user['account_status'] === 'active' ? 'badge-active' : 'badge-inactive'; // Dynamic class, default to active if not set 
                                                    ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['account_status'] ?? 'active')); // Display capitalized status, default 'active' 
                                    ?>
                                </span>
                            </td>
                            <!-- Registration Date (Formatted) -->
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($user['registration_date']))); // Format date 
                                ?></td>
                            <!-- Action Buttons (View/Edit) -->
                            <td class="actions">
                                <!-- View User Button -->
                                <a href="/admin/users/<?php echo $user['user_id']; // Link to user detail view 
                                                        ?>" class="btn btn-sm btn-view" title="View User">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <!-- Edit User Button -->
                                <a href="/admin/users/<?php echo $user['user_id']; ?>/edit" class="btn btn-sm btn-edit"
                                    title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; // End users check 
                ?>
            </tbody>
        </table>
    </div> <!-- End users-table-container -->

    <?php // Check if pagination is needed (more than one page) 
    ?>
    <?php if ($pagination['total_pages'] > 1): ?>
        <!-- Pagination Navigation -->
        <div class="pagination">
            <?php // First and Previous Page Links 
            ?>
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="/admin/users?page=1" class="pagination-link">&laquo; First</a>
                <a href="/admin/users?page=<?php echo $pagination['current_page'] - 1; ?>" class="pagination-link">&lsaquo;
                    Previous</a>
            <?php endif; ?>

            <?php
            // Calculate pagination range (show 2 pages before and after current page)
            $startPage = max(1, $pagination['current_page'] - 2);
            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
            // Generate page number links within the calculated range
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="/admin/users?page=<?php echo $i; ?>" class="pagination-link <?php echo $i === $pagination['current_page'] ? 'active' : ''; // Highlight current page 
                                                                                        ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php // Next and Last Page Links 
            ?>
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="/admin/users?page=<?php echo $pagination['current_page'] + 1; ?>" class="pagination-link">Next
                    &rsaquo;</a>
                <a href="/admin/users?page=<?php echo $pagination['total_pages']; ?>" class="pagination-link">Last &raquo;</a>
            <?php endif; ?>
        </div> <!-- End pagination -->
    <?php endif; // End pagination check 
    ?>
</div> <!-- End users-container -->

<!-- Embedded CSS for styling -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Main container layout */
    .users-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        /* Spacing between elements */
    }

    /* Header styling */
    .users-header {
        margin-bottom: 1rem;
    }

    .users-header h2 {
        margin-top: 0;
        margin-bottom: 0.5rem;
        color: var(--admin-secondary, #343a40);
        /* Use CSS variable or fallback */
    }

    .users-header p {
        margin: 0;
        color: #6c757d;
        /* Muted text color */
    }

    /* Alert box styling */
    .alert {
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }

    /* Success alert */
    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    /* Danger alert */
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    /* Table container for horizontal scrolling on small screens */
    .users-table-container {
        overflow-x: auto;
    }

    /* Table styling */
    .users-table {
        width: 100%;
        border-collapse: collapse;
        /* Collapse borders */
    }

    /* Table cell padding and border */
    .users-table th,
    .users-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
        /* Light border between rows */
        vertical-align: middle;
        /* Align content vertically */
    }

    /* Table header styling */
    .users-table th {
        background-color: #f8f9fa;
        /* Light grey background */
        font-weight: 600;
        color: var(--admin-secondary, #495057);
    }

    /* Table row hover effect */
    .users-table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
        /* Subtle hover background */
    }

    /* General badge styling */
    .badge {
        display: inline-block;
        padding: 0.25em 0.6em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    /* Admin role badge */
    .badge-admin {
        color: white;
        background-color: var(--admin-primary, #007bff);
    }

    /* Customer role badge */
    .badge-customer {
        color: white;
        background-color: #6c757d;
        /* Grey */
    }

    /* Active status badge */
    .badge-active {
        color: white;
        background-color: #28a745;
        /* Green */
    }

    /* Inactive status badge */
    .badge-inactive {
        color: white;
        background-color: #dc3545;
        /* Red */
    }

    /* Ensure action buttons don't wrap */
    .actions {
        white-space: nowrap;
    }

    /* General button styling */
    .btn {
        display: inline-block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        user-select: none;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        text-decoration: none;
        margin: 0 0.1rem;
        /* Small margin between action buttons */
    }

    /* Small button variant */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }

    /* View button styling */
    .btn-view {
        color: var(--admin-primary, #007bff);
        background-color: transparent;
        border: 1px solid var(--admin-primary, #007bff);
    }

    .btn-view:hover {
        color: white;
        background-color: var(--admin-primary, #007bff);
    }

    /* Edit button styling */
    .btn-edit {
        color: #ffc107;
        /* Yellow */
        background-color: transparent;
        border: 1px solid #ffc107;
    }

    .btn-edit:hover {
        color: #212529;
        /* Dark text on hover */
        background-color: #ffc107;
    }

    /* Pagination container centering */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
        flex-wrap: wrap;
        /* Allow pagination links to wrap */
    }

    /* Pagination link styling */
    .pagination-link {
        display: inline-block;
        padding: 0.5rem 0.75rem;
        margin: 0 0.25rem 0.5rem 0.25rem;
        /* Add bottom margin for wrapped links */
        color: var(--admin-primary, #007bff);
        background-color: white;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    /* Pagination link hover state */
    .pagination-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Active pagination link */
    .pagination-link.active {
        color: white;
        background-color: var(--admin-primary, #007bff);
        border-color: var(--admin-primary, #007bff);
    }

    /* Responsive: Hide less important columns on smaller screens */
    @media (max-width: 992px) {

        /* Hide Phone and Registration Date on medium screens */
        .users-table th:nth-child(4),
        .users-table td:nth-child(4),
        .users-table th:nth-child(7),
        .users-table td:nth-child(7) {
            display: none;
        }
    }

    @media (max-width: 768px) {

        /* Hide Email on small screens */
        .users-table th:nth-child(3),
        .users-table td:nth-child(3) {
            display: none;
        }
    }

    @media (max-width: 576px) {

        /* Adjust pagination link size on extra small screens */
        .pagination-link {
            padding: 0.4rem 0.6rem;
            margin: 0 0.1rem 0.4rem 0.1rem;
        }
    }
</style>