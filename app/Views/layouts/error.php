<!--
 * Error Layout File
 *
 * This file defines the main HTML structure for error pages
 * of the GhibliGroceries website. It includes the common header, navigation,
 * and the main content area where specific page views are injected.
 * It EXCLUDES the footer.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the specific page. Defaults if not set.
 * - $meta_description (string, optional): The meta description for SEO. Defaults if not set.
 * - $meta_keywords (string, optional): The meta keywords for SEO. Defaults if not set.
 * - $additional_css_files (array, optional): An array of paths to additional CSS files to include.
 * - $additional_styles (string, optional): A string containing inline CSS styles to be added.
 * - $additional_js_files (array, optional): An array of paths to additional JS files to include.
 * - $logged_in (bool): Indicates if the user is currently logged in (used for body data attribute).
 * - $content (string): The HTML content of the specific page view to be rendered.
 *
 * Includes Partials:
 * - app/Views/partials/header.php: Contains the site header, logo, main navigation, and user actions.
 *
 -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamically set the page title. Uses $page_title if provided, otherwise uses a default. -->
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'GhibliGroceries - Error'; ?></title>
    <!-- Dynamically set the meta description. Uses $meta_description if provided, otherwise uses a default. -->
    <meta name="description"
        content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'An error occurred on GhibliGroceries.'; ?>">
    <!-- Dynamically set the meta keywords. Uses $meta_keywords if provided, otherwise uses a default. -->
    <meta name="keywords"
        content="<?php echo isset($meta_keywords) ? htmlspecialchars($meta_keywords) : 'grocery, food, online shopping, error'; ?>">
    <meta name="author" content="GhibliGroceries Team">

    <meta name="csrf-token"
        content="<?php echo isset($csrf_token_for_layout) ? htmlspecialchars($csrf_token_for_layout) : ''; ?>">
    <!-- External Stylesheets -->
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Main application stylesheet -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <?php
    // Conditionally include additional CSS files if specified by the controller
    if (!empty($additional_css_files) && is_array($additional_css_files)):
        foreach ($additional_css_files as $css_file): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
    <?php endforeach;
    endif;
    ?>

    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">

    <?php // Include additional inline styles if provided
    if (isset($additional_styles)): ?>
    <style>
    <?php echo $additional_styles; // Output raw CSS string
    ?>
    </style>
    <?php endif; ?>

    <!-- JavaScript Files -->
    <!-- Main site-wide script file (deferred execution) -->
    <script src="/assets/js/script.js" defer></script>

    <?php // Include additional JavaScript files if provided
    if (isset($additional_js_files) && is_array($additional_js_files)):
        foreach ($additional_js_files as $js_file): ?>
    <!-- Include JS file relative to the /public/assets/js/ directory (deferred execution) -->
    <script src="/assets/js/<?php echo htmlspecialchars($js_file); ?>" defer></script>
    <?php endforeach;
    endif; ?>
</head>
<!-- Add a data attribute to the body indicating login status, useful for CSS/JS -->

<body class="<?php echo isset($body_class) ? htmlspecialchars($body_class) : ''; ?>"
    data-logged-in="<?php echo isset($logged_in) && $logged_in ? 'true' : 'false'; ?>">
    <!-- Main application container -->
    <div class="app-container">

        <?php
        // Include the header partial (logo, main menu, user actions)
        $headerPath = BASE_PATH . '/app/Views/partials/header.php';
        if (file_exists($headerPath)) {
            require $headerPath;
        } else {
            // Error handling or fallback if the header partial is missing
            echo "<!-- Header partial not found at: " . htmlspecialchars($headerPath) . " -->";
        }
        ?>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php
            // Output the specific page content passed from the controller.
            // Includes a fallback error message if $content is not set.
            echo $content ?? '<p>Error: Page content not loaded.</p>';
            ?>
        </main> <!-- End Main Content -->

        <!-- Footer section removed for error pages -->

        <!-- Placeholder for JavaScript-driven toast notifications -->
        <div id="toast-container"></div>

    </div> <!-- End App Container -->

    <!-- Placeholder for a JavaScript-driven confirmation modal (outside app-container for potential full-screen overlay) -->
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

    <!-- Note: Global JavaScript files are now included in the <head> with defer. -->
    <!-- Specific page JS are also included in <head> via $additional_js_files with defer. -->
</body>

</html>