<?php

/**
 * Header Partial View
 *
 * This file contains the HTML and PHP logic for the site's main header.
 * It includes the logo, primary navigation (desktop), user-specific actions
 * (like login/logout, cart, admin link), and the mobile menu toggle.
 * It also defines the structure for the slide-out mobile menu.
 *
 * Variables:
 * - Assumes session state (`$_SESSION['user_id']`, `$_SESSION['cart']`) is available.
 * - $currentPath (string, optional): The current request path, used to highlight the active navigation link.
 * - Expects the Database connection to be available via `\App\Core\Registry::get('database')`.
 * - Expects the User model (`\App\Models\User`) to be available.
 */

// Determine if the user is logged in based on session variable
$logged_in = isset($_SESSION['user_id']); // Simplified boolean check
?>

<!-- Fixed Header Container -->
<!-- The 'fixed-header' class likely keeps the header visible on scroll via CSS -->
<header class="fixed-header">
    <!-- Container to constrain header content width -->
    <div class="container">

        <!-- Logo Section -->
        <div class="logo">
            <a href="/">
                <!-- Link to homepage -->
                <img src="/assets/images/Logo.png" alt="GhibliGroceries Logo" class="logo-image">
                <span class="logo-text">GhibliGroceries</span>
            </a>
        </div>

        <!-- Desktop Navigation Menu -->
        <nav>
            <ul>
                <!-- Navigation items - 'active' class is added based on $currentPath -->
                <li><a href="/"
                        class="<?php echo (isset($currentPath) && $currentPath === '/') ? 'active' : ''; ?>">Home</a>
                </li>
                <li><a href="/categories"
                        class="<?php echo (isset($currentPath) && $currentPath === '/categories') ? 'active' : ''; ?>">Categories</a>
                </li>
                <li><a href="/about"
                        class="<?php echo (isset($currentPath) && $currentPath === '/about') ? 'active' : ''; ?>">About</a>
                </li>
                <li><a href="/contact"
                        class="<?php echo (isset($currentPath) && $currentPath === '/contact') ? 'active' : ''; ?>">Contact</a>
                </li>
            </ul>
        </nav>

        <!-- Header Actions (Cart, Auth Buttons, Admin Link) -->
        <div class="header-actions">
            <?php
            // Check if the logged-in user has an 'admin' role
            $isAdmin = false;
            if ($logged_in) { // Only check if user is logged in
                try {
                    // Get database connection from registry
                    $db = \App\Core\Registry::get('database');
                    if ($db) {
                        // Instantiate User model
                        $userModel = new \App\Models\User($db);
                        // Find user by ID stored in session
                        $user = $userModel->findById($_SESSION['user_id']);
                        // Check if user exists and role is 'admin'
                        if ($user && isset($user['role']) && $user['role'] === 'admin') {
                            $isAdmin = true;
                        }
                    } else {
                        // Log error if database connection is not found
                        error_log("Database not found in Registry in header.php (actions)");
                    }
                } catch (Exception $e) {
                    // Log any exceptions during the admin check
                    error_log("Error checking admin status in header.php (actions): " . $e->getMessage());
                }
            }

            // Display Admin Panel link if the user is an admin
            if ($isAdmin) {
                echo '<a href="/admin/dashboard" class="sign-in-btn">Admin Panel</a>';
            }
            ?>

            <!-- Cart Icon and Link -->
            <div class="cart-icon">
                <!-- Link points to cart page if logged in, otherwise to login page -->
                <a href="<?php echo $logged_in ? '/cart' : '/login'; ?>" class="nav-button sign-in-btn"
                    id="cart-icon-link">
                    <span>Cart</span>
                    <?php
                    // Display filled or empty cart icon based on session cart status
                    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <img src="/assets/images/cart/filled_shopping_cart.png" alt="Shopping Cart" class="cart-image">
                    <?php else: ?>
                    <img src="/assets/images/cart/empty_shopping_cart.png" alt="Empty Shopping Cart" class="cart-image">
                    <?php endif; ?>
                    <!-- Cart count badge - likely updated by JavaScript -->
                    <span id="cart-count-badge"></span>
                </a>
            </div>

            <?php
            // Display different actions based on login status
            if ($logged_in): ?>
            <!-- Actions for logged-in users -->
            <a href="/my-orders" class="sign-in-btn">My Orders</a>
            <a href="/logout" class="sign-up-btn">Logout</a>
            <?php else: ?>
            <!-- Actions for logged-out users -->
            <a href="/login" class="sign-in-btn">Sign In</a>
            <a href="/register" class="sign-up-btn">Sign Up</a>
            <?php endif; ?>
        </div> <!-- End header-actions -->

        <!-- Mobile Menu Toggle Button -->
        <!-- This button is typically shown only on smaller screens via CSS media queries -->
        <!-- JavaScript is used to toggle the visibility of the '.mobile-menu' element -->
        <div class="mobile-menu-toggle">
            <i class="fas fa-bars"></i> <!-- Hamburger icon -->
        </div>

    </div> <!-- End container -->
</header> <!-- End fixed-header -->

<!-- Mobile Menu Container -->
<!-- This menu is likely hidden by default and slides/appears when the toggle is clicked -->
<div class="mobile-menu">
    <nav>
        <ul>
            <!-- Mobile navigation items - mirrors desktop but styled for mobile -->
            <li><a href="/"
                    class="<?php echo (isset($currentPath) && $currentPath === '/') ? 'active' : ''; ?>">Home</a></li>
            <li><a href="/categories"
                    class="<?php echo (isset($currentPath) && $currentPath === '/categories') ? 'active' : ''; ?>">Categories</a>
            </li>
            <li><a href="/about"
                    class="<?php echo (isset($currentPath) && $currentPath === '/about') ? 'active' : ''; ?>">About</a>
            </li>
            <li><a href="/contact"
                    class="<?php echo (isset($currentPath) && $currentPath === '/contact') ? 'active' : ''; ?>">Contact</a>
            </li>

            <?php
            // Conditional items based on login status for mobile menu
            if ($logged_in): ?>
            <!-- Mobile Cart Link -->
            <li>
                <a href="/cart" class="mobile-cart-button">
                    <?php // Display filled/empty cart icon
                        if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <img src="/assets/images/cart/filled_shopping_cart.png" alt="Shopping Cart" class="cart-image">
                    <?php else: ?>
                    <img src="/assets/images/cart/empty_shopping_cart.png" alt="Empty Shopping Cart" class="cart-image">
                    <?php endif; ?>
                    <span>Cart</span>
                    <!-- Note: Mobile cart count might need separate JS handling if badge isn't reused -->
                </a>
            </li>
            <!-- Other logged-in links -->
            <li><a href="/my-orders" class="sign-in-btn">My Orders</a></li>
            <li><a href="/logout">Logout</a></li>
            <?php else: ?>
            <!-- Logged-out links -->
            <li><a href="/login">Sign In</a></li>
            <li><a href="/register">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div> <!-- End mobile-menu -->