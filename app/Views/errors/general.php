<?php
// Set the layout for this view
$this->layout('layouts/default', ['page_title' => isset($page_title) ? $page_title : 'Error']);
?>

<div class="error-page-container content-spacing">
    <h1><?php echo isset($error_heading) ? htmlspecialchars($error_heading) : 'An Error Occurred'; ?></h1>
    <?php if (isset($error_status_code)): ?>
    <p class="error-status-code">Error <?php echo htmlspecialchars($error_status_code); ?></p>
    <?php endif; ?>
    <p class="error-message-text">
        <?php echo isset($error_message) ? htmlspecialchars($error_message) : 'Sorry, something went wrong.'; ?></p>
    <?php if (isset($link_text) && isset($link_href)): ?>
    <a href="<?php echo htmlspecialchars($link_href); ?>"
        class="btn btn-primary error-page-link"><?php echo htmlspecialchars($link_text); ?></a>
    <?php endif; ?>
</div>