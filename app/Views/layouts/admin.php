<!--
 * Admin Layout File
 *
 * This file defines the main HTML structure for the admin panel pages.
 * It includes the common header, sidebar navigation, and content area
 * where specific admin page views will be injected.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the specific admin page. Defaults if not set.
 * - $additional_css_files (array, optional): An array of paths to additional CSS files to include.
 * - $currentPath (string): The current request path, used to highlight the active sidebar link.
 * - $admin_user (array, optional): An array containing the logged-in admin user's details (e.g., 'name').
 * - $content (string): The HTML content of the specific admin page view to be rendered.
 -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        // Dynamically set the page title. Uses $page_title if provided, otherwise uses a default.
        echo isset($page_title) ? htmlspecialchars($page_title) . ' - Admin Panel' : 'GhibliGroceries Admin Panel';
        ?>
    </title>
    <!-- Prevent search engines from indexing or following links on admin pages -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="GhibliGroceries Admin Panel">

    <!-- External Stylesheets -->
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Main application stylesheet -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    <!-- Admin-specific stylesheet -->
    <link rel="stylesheet" href="/assets/css/admin-styles.css">

    <?php
    // Conditionally include additional CSS files if specified by the controller
    if (!empty($additional_css_files) && is_array($additional_css_files)):
        foreach ($additional_css_files as $css_file): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
    <?php endforeach;
    endif;
    ?>
</head>

<body>
    <!-- Main Admin Container -->
    <div class="admin-container">

        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <!-- Sidebar Header -->
            <div class="admin-sidebar-header">
                <h1>GhibliGroceries</h1>
                <p>Admin Panel</p>
            </div>
            <!-- Sidebar Navigation Menu -->
            <ul class="admin-sidebar-nav">
                <li>
                    <!-- Dashboard Link - Active state based on $currentPath -->
                    <a href="/admin/dashboard"
                        class="<?php echo (isset($currentPath) && $currentPath === '/admin/dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <!-- Users Link - Active state if $currentPath starts with /admin/users -->
                    <a href="/admin/users"
                        class="<?php echo (isset($currentPath) && strpos($currentPath, '/admin/users') === 0) ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li>
                    <!-- Orders Link - Active state if $currentPath starts with /admin/orders -->
                    <a href="/admin/orders"
                        class="<?php echo (isset($currentPath) && strpos($currentPath, '/admin/orders') === 0) ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li>
                    <!-- Products Link - Active state if $currentPath starts with /admin/products -->
                    <a href="/admin/products"
                        class="<?php echo (isset($currentPath) && strpos($currentPath, '/admin/products') === 0) ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li>
                    <!-- Categories Link - Active state if $currentPath starts with /admin/categories -->
                    <a href="/admin/categories"
                        class="<?php echo (isset($currentPath) && strpos($currentPath, '/admin/categories') === 0) ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </li>
                <li>
                    <!-- Link to view the live store front -->
                    <a href="/" target="_blank">
                        <i class="fas fa-home"></i> View Store
                    </a>
                </li>
                <li>
                    <!-- Logout Link -->
                    <a href="/logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>

            <!-- Dark Mode Toggle in Sidebar Footer -->
            <div class="sidebar-footer">
                <div class="dark-mode-toggle-container">
                    <button id="dark-mode-toggle" class="dark-mode-toggle" aria-label="Toggle Dark Mode">
                        <i class="fas fa-sun light-icon"></i>
                        <i class="fas fa-moon dark-icon"></i>
                        <span class="toggle-text">Dark Mode</span>
                    </button>
                </div>
            </div>
        </aside> <!-- End Sidebar -->

        <!-- Main Content Area -->
        <div class="admin-content">
            <!-- Content Header -->
            <header class="admin-header">
                <!-- Sidebar Toggle Button (only visible on mobile) -->
                <button id="sidebar-toggle" class="sidebar-toggle-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <!-- Page Title Area -->
                <div class="admin-header-title">
                    <!-- Display the dynamic page title, default to 'Dashboard' -->
                    <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></h1>
                </div>
                <!-- Admin User Info Area -->
                <div class="admin-user-info">
                    <?php
                    // Display a welcome message if the admin user's data is available
                    if (isset($admin_user) && is_array($admin_user)): ?>
                    <span>Welcome, <?php echo htmlspecialchars($admin_user['name']); ?></span>
                    <?php endif; ?>

                    <!-- Dark Mode Toggle in Header (Alternative location) -->
                    <div class="dark-mode-toggle-header">
                        <button id="dark-mode-toggle-header" class="dark-mode-toggle-btn" aria-label="Toggle Dark Mode">
                            <i class="fas fa-sun light-icon"></i>
                            <i class="fas fa-moon dark-icon"></i>
                        </button>
                    </div>
                </div>
            </header> <!-- End Content Header -->

            <!-- Main Content Injection Point -->
            <main class="admin-main">
                <?php
                // Output the specific page content passed from the controller.
                // Includes a fallback error message if $content is not set.
                echo $content ?? '<p>Error: Page content not loaded.</p>';
                ?>
            </main> <!-- End Main Content -->

        </div> <!-- End Main Content Area -->
    </div> <!-- End Admin Container -->

    <!-- Placeholder for JavaScript-driven toast notifications -->
    <div id="toast-container"></div>

    <!-- Placeholder for a JavaScript-driven confirmation modal -->
    <div id="confirmation-modal" class="modal">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <p id="modal-message"></p> <!-- Message will be set dynamically -->
            <div class="modal-buttons">
                <button id="modal-confirm-button" class="modal-btn confirm-btn">Confirm</button>
                <button id="modal-cancel-button" class="modal-btn cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Basic script block, currently empty but could be used for admin-specific JS -->
    <script>
    // Ensure DOM is loaded before running any potential future JS
    document.addEventListener('DOMContentLoaded', function() {
        // Admin layout specific JavaScript could go here
    });
    </script>

    <!-- Include the admin layout JavaScript for sidebar toggle functionality -->
    <script src="/assets/js/admin-layout.js"></script>

    <!-- Include the dark mode JavaScript -->
    <script src="/assets/js/dark-mode.js"></script>
</body>

</html>