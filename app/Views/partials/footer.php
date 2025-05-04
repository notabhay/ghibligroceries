<?php

/**
 * Footer Partial View
 *
 * This file contains the HTML and PHP logic for the site footer.
 * It includes quick links, contact information, and copyright details.
 * It also features a toggle mechanism to show/hide the footer content.
 *
 * Variables:
 * - Assumes session state (`$_SESSION['user_id']`) is available to determine login status.
 */

// Determine if the user is logged in based on session variable
$logged_in = isset($_SESSION['user_id']); // Simplified boolean check
?>

<!-- Footer Toggle Button -->
<!-- This element allows users to expand/collapse the footer. Controlled by JavaScript. -->
<div class="footer-toggle">
    <!-- Icon changes based on footer state (e.g., chevron-up when open) -->
    <i class="fas fa-chevron-down" id="footer-toggle-icon"></i>
</div>

<!-- Main Footer Element -->
<!-- Starts hidden and is shown/hidden by the toggle button via JavaScript. -->
<footer id="site-footer" class="hidden">
    <!-- Container to constrain footer content width -->
    <div class="container">
        <!-- Flex container for footer sections -->
        <div class="footer-content">

            <!-- Footer Section: Brand/About -->
            <div class="footer-section">
                <h3>GhibliGroceries</h3>
                <p>Your one-stop shop for fresh vegetables and quality meat products.</p>
            </div>

            <!-- Footer Section: Quick Links -->
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/categories">Categories</a></li>
                    <li><a href="/about">About</a></li>
                    <li><a href="/contact">Contact</a></li>
                    <?php
                    // Conditionally display links based on user login status
                    if ($logged_in): ?>
                    <!-- Links for logged-in users -->
                    <li><a href="/my-orders">My Orders</a></li>
                    <?php else: ?>
                    <!-- Links for logged-out users -->
                    <li><a href="/register">Register</a></li>
                    <li><a href="/login">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Footer Section: Contact Info -->
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-envelope"></i> contact@ghibligroceries.com</p>
                <p><i class="fas fa-phone"></i> (123) 456-7890</p>
            </div>

        </div> <!-- End footer-content -->

        <!-- Copyright Information -->
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); // Dynamically display the current year 
                        ?> GhibliGroceries. All rights reserved.</p>
        </div>

    </div> <!-- End container -->
</footer> <!-- End site-footer -->