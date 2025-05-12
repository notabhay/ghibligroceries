<div class="error-container">
    <div class="error-content">
        <!-- Optional: SVG Icon/Illustration -->
        <!-- Example: You can add an SVG here if you have one -->
        <!-- <img src="/assets/images/your-404-icon.svg" alt="Not Found" class="error-icon"> -->

        <h1 class="error-main-heading">
            <?php echo isset($error_main_heading) ? htmlspecialchars($error_main_heading) : '404'; ?></h1>
        <p class="error-sub-heading">
            <?php echo isset($error_sub_heading) ? htmlspecialchars($error_sub_heading) : 'Page Not Found'; ?></p>
        <p class="error-message">
            <?php echo isset($error_message) ? htmlspecialchars($error_message) : "Oops! The page you're looking for doesn't seem to exist or may have been moved."; ?>
        </p>
        <div class="error-actions">
            <a href="<?php echo isset($link_home_href) ? htmlspecialchars($link_home_href) : '/'; ?>"
                class="btn btn-primary">
                <?php echo isset($link_home_text) ? htmlspecialchars($link_home_text) : 'Go to Homepage'; ?>
            </a>
            <a href="<?php echo isset($link_browse_href) ? htmlspecialchars($link_browse_href) : '/categories'; ?>"
                class="btn btn-secondary">
                <?php echo isset($link_browse_text) ? htmlspecialchars($link_browse_text) : 'Browse Products'; ?>
            </a>
        </div>
    </div>
</div>