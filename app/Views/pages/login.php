<?php

/**
 * View file for the User Login page.
 * Displays a form for users to log in to their account.
 * Includes fields for email, password, and a CAPTCHA verification code.
 * Handles displaying login errors, CAPTCHA errors, and repopulating the email field on failure.
 *
 * Expected PHP Variables:
 * - $page_title (string):           The title for the page. Defaults to 'Login - GhibliGroceries'.
 * - $meta_description (string):     Meta description for SEO.
 * - $meta_keywords (string):        Meta keywords for SEO.
 * - $additional_css_files (array): Array of additional CSS files to include (e.g., login-specific styles). Defaults to ['/assets/css/login.css'].
 * - $email (string):                The email address entered previously (used for repopulation on error). Defaults to empty string.
 * - $login_error (string):          Error message related to invalid credentials. Defaults to empty string.
 * - $captcha_error (string):        Error message related to incorrect CAPTCHA input. Defaults to empty string.
 * - $csrf_token (string):           CSRF token for form security. Defaults to empty string.
 *
 * JavaScript Interaction:
 * - Includes inline JavaScript for:
 *   1. Toggling password visibility (shows/hides password text).
 *   2. Refreshing the CAPTCHA image without reloading the page.
 */

// Initialize variables with default values
$page_title = $page_title ?? 'Login - GhibliGroceries';
$meta_description = $meta_description ?? 'Login to GhibliGroceries - Access your account to place orders.';
$meta_keywords = $meta_keywords ?? 'login, grocery, online shopping, account access';
$additional_css_files = $additional_css_files ?? ['/assets/css/login.css']; // Specific CSS for this page
$email = $email ?? ''; // Repopulate email field on error
$login_error = $login_error ?? ''; // General login error (e.g., wrong password/email)
$captcha_error = $captcha_error ?? ''; // CAPTCHA validation error
$csrf_token = $csrf_token ?? ''; // CSRF protection token
?>
<!-- Main content area -->
<main>
    <!-- Section containing the login form -->
    <section class="login-section">
        <div class="container">
            <!-- Page Title -->
            <div class="page-title">
                <h2>Login to Your Account</h2>
                <p>Enter your credentials to access your account</p>
            </div>

            <?php // Display general login error message if it exists 
            ?>
            <?php if (!empty($login_error)): ?>
            <div class="alert alert-error">
                <p><?php echo htmlspecialchars($login_error); ?></p>
            </div>
            <?php endif; ?>

            <!-- Container for the login form and registration link -->
            <div class="login-form-container">
                <!-- Login form submitting data via POST to /login -->
                <form action="/login" method="post" class="login-form">
                    <!-- CSRF Token (hidden input for security) -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <!-- Form Group: Email Address -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); // Repopulate email 
                                                                            ?>" required>
                    </div>

                    <!-- Form Group: Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" name="password" id="password" required>
                            <!-- Span to toggle password visibility (controlled by JS) -->
                            <span class="toggle-password">
                                <i class="fas fa-eye"></i> <!-- Eye icon -->
                            </span>
                        </div>
                    </div>

                    <!-- Form Group: CAPTCHA Verification -->
                    <div class="form-group captcha-group">
                        <label for="captcha">Verification Code</label>
                        <div class="captcha-container">
                            <!-- CAPTCHA Image and Refresh Button -->
                            <div class="captcha-image">
                                <img src="/captcha" alt="CAPTCHA Image" id="captcha-img">
                                <!-- Button to refresh CAPTCHA (controlled by JS) -->
                                <button type="button" class="refresh-captcha">
                                    <i class="fas fa-sync-alt"></i> <!-- Refresh icon -->
                                </button>
                            </div>
                            <!-- CAPTCHA Input Field -->
                            <input type="text" name="captcha" id="captcha" required autocomplete="off">
                        </div>
                        <?php // Display CAPTCHA error message if it exists 
                        ?>
                        <?php if (!empty($captcha_error)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($captcha_error); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Form Group: Remember Me & Forgot Password -->
                    <div class="form-group remember-me">
                        <label>
                            <input type="checkbox" name="remember_me"> Remember me
                        </label>
                        <a href="/forgot-password" class="forgot-password">Forgot Password?</a>
                    </div>

                    <!-- Form Group: Submit Button -->
                    <div class="form-group">
                        <button type="submit" name="login_submit" class="btn btn-primary">Login</button>
                    </div>
                </form>

                <!-- Link to Registration Page -->
                <aside class="register-link">
                    <p>Don't have an account? <a href="/register">Register here</a></p>
                </aside>
            </div> <!-- End login-form-container -->
        </div> <!-- End container -->
    </section> <!-- End login-section -->
</main> <!-- End main -->

<!-- Inline JavaScript for specific login page functionalities -->
<script>
// Wait for the DOM to be fully loaded before running script
document.addEventListener('DOMContentLoaded', function() {
    // --- Password Visibility Toggle ---
    const togglePassword = document.querySelector('.toggle-password');
    if (togglePassword) { // Check if the element exists
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i'); // Get the icon inside the span
            // Check current type and toggle
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text'; // Show password
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash'); // Change icon to slashed eye
            } else {
                passwordInput.type = 'password'; // Hide password
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye'); // Change icon back to eye
            }
        });
    }

    // --- CAPTCHA Refresh ---
    const refreshCaptcha = document.querySelector('.refresh-captcha');
    const captchaImage = document.getElementById('captcha-img');
    if (refreshCaptcha && captchaImage) { // Check if both elements exist
        refreshCaptcha.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default button behavior
            // Update the image source with a timestamp to bypass browser cache
            captchaImage.src = '/captcha?' + new Date().getTime();
        });
    }
});
</script>