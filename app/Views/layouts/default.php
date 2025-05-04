<!--
 * Default Layout File
 *
 * This file defines the main HTML structure for the public-facing pages
 * of the GhibliGroceries website. It includes the common header, navigation,
 * footer, and the main content area where specific page views are injected.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the specific page. Defaults if not set.
 * - $meta_description (string, optional): The meta description for SEO. Defaults if not set.
 * - $meta_keywords (string, optional): The meta keywords for SEO. Defaults if not set.
 * - $additional_css_files (array, optional): An array of paths to additional CSS files to include.
 * - $logged_in (bool): Indicates if the user is currently logged in (used for body data attribute).
 * - $content (string): The HTML content of the specific page view to be rendered.
 *
 * Includes Partials:
 * - app/Views/partials/navigation.php: Contains head elements like meta tags, base CSS/JS links.
 * - app/Views/partials/header.php: Contains the site header, logo, main navigation, and user actions.
 * - app/Views/partials/footer.php: Contains the site footer with links and copyright info.
 -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamically set the page title. Uses $page_title if provided, otherwise uses a default. -->
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'GhibliGroceries'; ?></title>
    <!-- Dynamically set the meta description. Uses $meta_description if provided, otherwise uses a default. -->
    <meta name="description"
        content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'Your one-stop shop for fresh groceries inspired by Studio Ghibli.'; ?>">
    <!-- Dynamically set the meta keywords. Uses $meta_keywords if provided, otherwise uses a default. -->
    <meta name="keywords"
        content="<?php echo isset($meta_keywords) ? htmlspecialchars($meta_keywords) : 'grocery, food, online shopping, delivery, ghibli'; ?>">
    <meta name="author" content="GhibliGroceries Team">

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
</head>
<!-- Add a data attribute to the body indicating login status, useful for CSS/JS -->

<body data-logged-in="<?php echo isset($logged_in) && $logged_in ? 'true' : 'false'; ?>">
    <!-- Main application container -->
    <div class="app-container">

        <?php
        // Include the navigation partial (which primarily contains head elements in this structure)
        // Note: This seems unusual placement for navigation.php based on its typical content.
        // It might be intended to include head elements defined elsewhere or is misnamed.
        $navPath = BASE_PATH . '/app/Views/partials/navigation.php';
        if (file_exists($navPath)) {
            require $navPath;
        } else {
            // Error handling or fallback if the navigation partial is missing
            echo "<!-- Navigation partial not found at: " . htmlspecialchars($navPath) . " -->";
        }
        ?>

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

        <?php
        // Include the footer partial
        $footerPath = BASE_PATH . '/app/Views/partials/footer.php';
        if (file_exists($footerPath)) {
            require $footerPath;
        } else {
            // Error handling or fallback if the footer partial is missing
            echo "<!-- Footer partial not found at: " . htmlspecialchars($footerPath) . " -->";
        }
        ?>

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

    <!-- Note: Global JavaScript files are likely included within navigation.php or header.php -->
    <!-- Specific page JS might be included via $additional_js_files in navigation.php -->
</body>

</html>