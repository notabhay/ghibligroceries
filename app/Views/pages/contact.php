<?php

/**
 * View file for the Contact Us page.
 * Displays contact information (address, phone, email, social media) and a contact form.
 * Handles displaying flash messages (e.g., success/error after form submission) and form validation errors.
 *
 * Expected PHP Variables:
 * - $page_title (string):    The title for the page (used in <title> tag and <h2>). Defaults to 'Contact Us'.
 * - $csrf_token (string):    A Cross-Site Request Forgery token for form security. Defaults to empty string.
 * - $flash_message (array|null): An array containing 'text' and 'type' (e.g., 'success', 'error') for displaying status messages. Defaults to null.
 * - $form_errors (array):    An associative array of validation errors for the form fields (e.g., ['name' => 'Name is required']). Defaults to empty array.
 * - $old_input (array):      An associative array containing previously submitted form data to repopulate fields after validation failure (e.g., ['name' => 'John Doe']). Defaults to empty array.
 *
 * Note: This file also links a specific CSS file 'contact.css'.
 */

// Initialize variables with default values using null coalescing operator
$page_title = $page_title ?? 'Contact Us';
$csrf_token = $csrf_token ?? '';
$flash_message = $flash_message ?? null; // Used for success/error messages after submission
$form_errors = $form_errors ?? []; // Stores validation errors
$old_input = $old_input ?? []; // Stores previously submitted form data

// Pre-escape variables for safe output in HTML
$page_title_safe = htmlspecialchars($page_title);
$csrf_token_safe = htmlspecialchars($csrf_token);
?>
<!-- Link to the specific stylesheet for the contact page -->
<link rel="stylesheet" href="/assets/css/contact.css">

<!-- Main content area -->
<main>
    <!-- Section containing all contact page elements -->
    <section class="contact-section">
        <div class="container">
            <!-- Page Title Block -->
            <div class="page-title">
                <h2><?= $page_title_safe // Output pre-escaped page title 
                    ?></h2>
                <p>We're here to help! Get in touch with us.</p>
            </div>

            <?php // Display flash message if set (e.g., after form submission) 
            ?>
            <?php // Check if $flash_message is set, is an array, and has the required keys 
            ?>
            <?php if (isset($flash_message) && is_array($flash_message) && isset($flash_message['text'], $flash_message['type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($flash_message['type']) // Use type for alert class 
                                        ?>" role="alert">
                    <?= htmlspecialchars($flash_message['text']) // Display the message text 
                    ?>
                </div>
            <?php endif; ?>

            <!-- Wrapper for contact info and form -->
            <div class="contact-content">
                <!-- Contact Information Block -->
                <address class="contact-info">
                    <h3>Contact Information</h3>
                    <!-- Address -->
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i> <!-- Icon -->
                        <div>
                            <h4>Address</h4>
                            <p>123 Grocery Lane, Foodville</p>
                            <p>ST4 2DE, Staffordshire, UK</p>
                        </div>
                    </div>
                    <!-- Phone -->
                    <div class="info-item">
                        <i class="fas fa-phone"></i> <!-- Icon -->
                        <div>
                            <h4>Phone</h4>
                            <p>(123) 456-7890</p>
                            <p>Monday to Friday, 9am to 6pm</p>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="info-item">
                        <i class="fas fa-envelope"></i> <!-- Icon -->
                        <div>
                            <h4>Email</h4>
                            <p>contact@ghibligroceries.com</p>
                            <p>We'll respond as soon as possible</p>
                        </div>
                    </div>
                    <!-- Social Media Links -->
                    <div class="social-media">
                        <h4>Follow Us</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </address>

                <!-- Contact Form Wrapper -->
                <div class="contact-form-wrapper">
                    <h3>Send Us a Message</h3>
                    <!-- The contact form itself, submitting data via POST to '/contact/submit' -->
                    <form class="contact-form" method="POST" action="/contact/submit">
                        <!-- CSRF Token (hidden input for security) -->
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token_safe // Output pre-escaped CSRF token 
                                                                        ?>">

                        <!-- Form Group: Name -->
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($old_input['name'] ?? '') // Repopulate with old input if available 
                                                                            ?>" required>
                            <?php // Display validation error for 'name' if it exists 
                            ?>
                            <?php if (isset($form_errors['name'])): ?>
                                <span class="error-message"><?= htmlspecialchars($form_errors['name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Form Group: Email -->
                        <div class="form-group">
                            <label for="email">Your Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old_input['email'] ?? '') // Repopulate with old input if available 
                                                                                ?>" required>
                            <?php // Display validation error for 'email' if it exists 
                            ?>
                            <?php if (isset($form_errors['email'])): ?>
                                <span class="error-message"><?= htmlspecialchars($form_errors['email']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Form Group: Message -->
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" required><?= htmlspecialchars($old_input['message'] ?? '') // Repopulate with old input if available 
                                                                                    ?></textarea>
                            <?php // Display validation error for 'message' if it exists 
                            ?>
                            <?php if (isset($form_errors['message'])): ?>
                                <span class="error-message"><?= htmlspecialchars($form_errors['message']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div> <!-- End contact-form-wrapper -->
            </div> <!-- End contact-content -->

            <!-- Location Map Section -->
            <section class="location-map">
                <h3>Find Us</h3>
                <!-- Container for the map (could be replaced by an embedded map) -->
                <div class="map-container">
                    <!-- Placeholder if an actual map isn't implemented -->
                    <div class="map-placeholder">
                        <i class="fas fa-map"></i>
                        <p>Map location would be displayed here</p>
                        <?php // This div could be replaced by an iframe or JS map library integration 
                        ?>
                    </div>
                </div>
            </section>
        </div> <!-- End container -->
    </section> <!-- End contact-section -->
</main> <!-- End main -->