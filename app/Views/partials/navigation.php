<?php

/**
 * Navigation Partial View (Primarily Head Content)
 *
 * NOTE: Despite the filename "navigation.php", this partial seems to primarily
 * handle the setup of the HTML `<head>` section for the default layout.
 * It includes meta tags, the page title, links to CSS stylesheets (including Font Awesome and Google Fonts),
 * and potentially includes additional inline styles or JavaScript files passed from the controller.
 * It also includes the main site JavaScript file.
 *
 * Expected variables:
 * - $page_title (string, optional): The title for the specific page. Defaults if not set.
 * - $meta_description (string, optional): The meta description for SEO. Defaults if not set.
 * - $meta_keywords (string, optional): The meta keywords for SEO. Defaults if not set.
 * - $additional_styles (string, optional): A string containing inline CSS styles to be added.
 * - $additional_js_files (array, optional): An array of paths (relative to /public/assets/js/)
 *   to additional JavaScript files to include.
 */
?>
<!-- This DOCTYPE and html/head structure seems redundant if this file is included *within*
     the `<body>` of `default.php` as it appeared in the read content.
     Assuming this might be included differently or the structure in default.php needs review.
     For commenting purposes, we'll treat this as setting up the head content. -->
<!DOCTYPE html> <!-- This is likely incorrect if included within another HTML structure -->
<html lang="en">
<!-- This is likely incorrect if included within another HTML structure -->

<head>
    <!-- This is likely incorrect if included within another HTML structure -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php // Set the meta description dynamically or use a default -->
    if (isset($meta_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php else: ?>
    <meta name="description"
        content="Fresh groceries delivered to your door. Shop vegetables, meat, and more at our online grocery store.">
    <?php endif; ?>

    <?php // Set the meta keywords dynamically or use a default -->
    if (isset($meta_keywords)): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <?php else: ?>
    <meta name="keywords" content="grocery, online shopping, vegetables, meat, fresh produce">
    <?php endif; ?>

    <!-- Set the page title dynamically or use a default -->
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'GhibliGroceries - Fresh Food Delivered'; ?>
    </title>

    <!-- CSS Links -->
    <!-- Main application stylesheet -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons (Note: A different version might be linked in default.php layout) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <?php // Include additional inline styles if provided -->
    if (isset($additional_styles)): ?>
    <style>
    <?php echo $additional_styles; // Output raw CSS string
    ?>
    </style>
    <?php endif; ?>

    <!-- JavaScript Files -->
    <!-- Main site-wide script file (deferred execution) -->
    <script src="/assets/js/script.js" defer></script>

    <?php // Include additional JavaScript files if provided -->
    if (isset($additional_js_files) && is_array($additional_js_files)):
        foreach ($additional_js_files as $js_file): ?>
    <!-- Include JS file relative to the /public/assets/js/ directory (deferred execution) -->
    <script src="/assets/js/<?php echo htmlspecialchars($js_file); ?>" defer></script>
    <?php endforeach;
    endif; ?>
</head> <!-- This is likely incorrect if included within another HTML structure -->

<body>
    <!-- This is likely incorrect if included within another HTML structure -->
    <!-- This div seems out of place if this file only contains head elements.
         It might be part of an older structure or intended for a different inclusion method. -->
    <div class="main-panel">
        <!-- Content following this div would typically be the actual page body,
             but this partial seems focused on the <head> section. -->