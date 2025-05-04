<?php

/**
 * View: User Registration Page
 *
 * Provides a form for new users to create an account.
 * Primarily uses a React component (`RegistrationForm.js`) for an interactive form experience.
 * Includes a basic HTML form within `<noscript>` tags as a fallback for users without JavaScript enabled.
 * Displays success or error messages related to the registration attempt using flash data.
 * Retrieves and pre-fills form fields with previously submitted data in case of validation errors (via flash data).
 *
 * Expected variables (typically set by the controller):
 * - $page_title (string, optional): The title for the HTML page.
 * - $meta_description (string, optional): Meta description tag content.
 * - $meta_keywords (string, optional): Meta keywords tag content.
 * - $additional_css_files (array, optional): Array of additional CSS file paths to include.
 * - $csrf_token (string): The CSRF token required for form submission security.
 * - $registration_error (string, optional): Error message(s) if registration failed.
 * - $registration_success (bool, optional): Flag indicating if registration was successful.
 * - Flash Data:
 *   - 'input_data' (array): Previously submitted form data (used for pre-filling on error). Contains keys like 'name', 'phone', 'email'.
 */

// Import necessary core classes
use App\Core\Session;
use App\Core\Registry;

// --- Page Configuration & Data Initialization ---
// Set page metadata using null coalescing operator for defaults
$page_title = $page_title ?? 'Register - GhibliGroceries';
$meta_description = $meta_description ?? 'Create an account with GhibliGroceries to start ordering fresh groceries online.';
$meta_keywords = $meta_keywords ?? 'register, grocery, create account, sign up';
// Define specific CSS for this page
$additional_css_files = $additional_css_files ?? ['/assets/css/register.css'];

// Initialize variables related to form state and data
$csrf_token = $csrf_token ?? ''; // CSRF token from controller
$registration_error = $registration_error ?? ''; // Error message from controller/session flash
$registration_success = $registration_success ?? false; // Success flag from controller/session flash

// Retrieve flashed input data (if any) from the previous request (e.g., after a validation error)
$input_data = Registry::get('session')->getFlash('input_data') ?? [];
// Extract individual input fields from flashed data for pre-filling the noscript form
$input_name = $input_data['name'] ?? '';
$input_phone = $input_data['phone'] ?? '';
$input_email = $input_data['email'] ?? '';
// Note: Password is not typically re-filled for security reasons.
?>
<!-- Main content area -->
<main>
    <!-- Section containing the registration form -->
    <section class="register-section">
        <!-- Content container -->
        <div class="container">
            <!-- Page title block -->
            <div class="page-title">
                <h2>Create an Account</h2>
                <p>Register to start shopping for fresh groceries</p>
            </div>

            <!-- Display registration status messages -->
            <?php if ($registration_success): // Check if registration was successful 
            ?>
            <div class="alert alert-success">
                <p>Registration successful! You can now <a href="/login">login</a> to your account.</p>
            </div>
            <?php elseif (!empty($registration_error)): // Check if there were registration errors 
            ?>
            <div class="alert alert-error">
                <!-- Display error message(s), using nl2br to preserve line breaks if multiple errors are concatenated -->
                <p><?php echo nl2br(htmlspecialchars($registration_error)); ?></p>
            </div>
            <?php endif; // End of status message block 
            ?>

            <!-- Container where the React registration form will be rendered -->
            <div id="register-form-container">
                <!-- Placeholder text shown while the React component loads -->
                <p>Loading registration form...</p>
            </div>

            <!-- Fallback content for users with JavaScript disabled -->
            <noscript>
                <!-- Warning message indicating JavaScript is required for the main form -->
                <p style="color: red; text-align: center; margin-bottom: 1em;">JavaScript is required for the
                    interactive registration form. You can use this basic form instead.</p>
                <!-- Container for the basic HTML fallback form -->
                <div class="register-form-container">
                    <!-- Basic HTML registration form -->
                    <form action="/register" method="post" class="register-form">
                        <!-- CSRF token for security -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <!-- Full Name input group -->
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" name="name" id="name" required value="<?php echo htmlspecialchars($input_name); // Pre-fill if value exists 
                                                                                        ?>">
                        </div>

                        <!-- Phone Number input group -->
                        <div class="form-group">
                            <label for="phone">Phone Number (10 digits)</label>
                            <input type="tel" name="phone" id="phone" pattern="[0-9]{10}" required <!-- Basic pattern
                                validation -->
                            value="<?php echo htmlspecialchars($input_phone); // Pre-fill if value exists 
                                    ?>">
                        </div>

                        <!-- Email Address input group -->
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($input_email); // Pre-fill if value exists 
                                                                                        ?>">
                        </div>

                        <!-- Password input group -->
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" required>
                            <!-- Password is not pre-filled -->
                        </div>

                        <!-- Submit button group -->
                        <div class="form-group">
                            <button type="submit" name="register_submit" class="btn btn-primary">Register</button>
                        </div>
                    </form> <!-- End of noscript form -->

                    <!-- Link to the login page for existing users -->
                    <aside class="login-link">
                        <p>Already have an account? <a href="/login">Login here</a></p>
                    </aside>
                </div> <!-- End of noscript form container -->
            </noscript> <!-- End of noscript block -->
        </div> <!-- End container -->
    </section> <!-- End register-section -->
</main> <!-- End main -->
<!-- Include React, ReactDOM, and Babel for client-side rendering of the registration form -->
<!-- Using development versions from unpkg CDN for simplicity -->
<script src="https://unpkg.com/react@17/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@17/umd/react-dom.development.js" crossorigin></script>
<!-- Babel Standalone is used here to transpile JSX directly in the browser (not recommended for production) -->
<script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>

<!-- Include the external React component file (needs type="text/babel" for Babel transpilation) -->
<script type="text/babel" src="/assets/js/react_components/RegistrationForm.js"></script>

<!-- Inline Babel script to render the React component -->
<script type="text/babel">
    // Pass the PHP-generated CSRF token to the React component as a prop.
    // Ensure proper escaping (ENT_QUOTES) for use within JavaScript string literal.
    const csrfToken = "<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>";

    // Render the RegistrationForm component into the designated container div.
    ReactDOM.render(
        // Instantiate the component, passing the csrfToken as a prop
        <RegistrationForm csrfToken={csrfToken} />,
        // Target DOM element where the component should be mounted
        document.getElementById('register-form-container')
    );
</script>