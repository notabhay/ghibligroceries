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

    <!-- Inline Styles specific to the Admin Layout -->
    <style>
        /* CSS Variables for admin theme */
        :root {
            --admin-primary: #3498db;
            --admin-secondary: #2c3e50;
            --admin-accent: #e74c3c;
            --admin-bg: #f8f9fa;
            --admin-text: #333;
            --admin-sidebar-width: 250px;
        }

        /* Basic body styling */
        body {
            background-color: var(--admin-bg);
            color: var(--admin-text);
            display: flex;
            /* Enables flex layout for sidebar and content */
            min-height: 100vh;
            /* Ensure body takes full viewport height */
            margin: 0;
            /* Remove default body margin */
            padding: 0;
        }

        /* Main container for the admin interface */
        .admin-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar styling */
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            background-color: var(--admin-secondary);
            color: white;
            padding: 1rem 0;
            flex-shrink: 0;
            /* Prevent sidebar from shrinking */
        }

        /* Sidebar header section */
        .admin-sidebar-header {
            padding: 0 1rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .admin-sidebar-header h1 {
            font-size: 1.5rem;
            margin: 0;
        }

        /* Sidebar navigation list */
        .admin-sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .admin-sidebar-nav li {
            padding: 0;
        }

        /* Sidebar navigation links */
        .admin-sidebar-nav a {
            display: block;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        /* Sidebar navigation link hover/active state */
        .admin-sidebar-nav a:hover,
        .admin-sidebar-nav a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* Sidebar navigation link icons */
        .admin-sidebar-nav i {
            width: 20px;
            margin-right: 8px;
            text-align: center;
        }

        /* Main content area styling */
        .admin-content {
            flex-grow: 1;
            /* Allow content area to take remaining space */
            padding: 1rem;
            overflow-y: auto;
            /* Add scrollbar if content overflows */
        }

        /* Header within the main content area */
        .admin-header {
            background-color: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header-title h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        /* User info section in the content header */
        .admin-user-info {
            display: flex;
            align-items: center;
        }

        .admin-user-info span {
            margin-right: 1rem;
        }

        /* Container for the specific page content */
        .admin-main {
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        /* Container for toast notifications */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>

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
        </aside> <!-- End Sidebar -->

        <!-- Main Content Area -->
        <div class="admin-content">
            <!-- Content Header -->
            <header class="admin-header">
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
</body>

</html>