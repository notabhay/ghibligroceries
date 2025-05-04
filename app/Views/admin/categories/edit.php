<!--
Admin Category Edit View
------------------------
This view provides a form for administrators to edit an existing product category.
It pre-populates the form with the current category details and allows updating
the name and parent category. It also includes a section for deleting the category,
which is only enabled if the category has no associated products.

Expected PHP Variables:
- $category: An associative array containing the details of the category being edited,
             including 'id', 'category_name', and 'parent_id'.
- $categories: An array of all existing categories (including the one being edited,
               which needs to be filtered out in the parent dropdown), used to populate
               the parent category dropdown. Each element should have 'id' and 'category_name'.
- $csrf_token: A security token for CSRF protection on both the update and delete forms.
- $has_products: A boolean indicating whether the category being edited has any products
                 associated with it. Used to conditionally enable/disable the delete button.
- $_SESSION['flash_error'] (optional): A single error message string from a previous attempt.
- $_SESSION['flash_errors'] (optional): An array of validation error messages.
-->

<!-- Page Header -->
<div class="admin-content-header">
    <h2>Edit Category</h2>
    <!-- Action Button: Link back to the category list -->
    <div class="admin-content-actions">
        <a href="/admin/categories" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Categories
        </a>
    </div>
</div>
<!-- /Page Header -->

<!-- Flash Messages Area -->
<?php // Display single general error message if set 
?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['flash_error']); // Escape output 
        ?>
    </div>
<?php endif; ?>

<?php // Display multiple validation error messages if set 
?>
<?php if (isset($_SESSION['flash_errors']) && is_array($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($_SESSION['flash_errors'] as $error): ?>
                <li><?php echo htmlspecialchars($error); // Escape each error 
                    ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<!-- /Flash Messages Area -->

<!-- Category Edit Form Card -->
<div class="card">
    <div class="card-body">
        <!-- Form submits to the category update endpoint, including the category ID in the URL -->
        <form action="/admin/categories/<?php echo $category['id']; ?>" method="POST">
            <!-- CSRF Token: Essential for security -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Category Name Field -->
            <div class="form-group mb-3">
                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($category['category_name']); // Pre-populate with current name (escaped) 
                                                                                                ?>">
                <small class="form-text text-muted">Enter a unique and descriptive name for the category.</small>
            </div>
            <!-- /Category Name Field -->

            <!-- Parent Category Selection Field -->
            <div class="form-group mb-3">
                <label for="parent_id" class="form-label">Parent Category</label>
                <select class="form-control" id="parent_id" name="parent_id">
                    <!-- Default option for a top-level category -->
                    <option value="0">None (Top Level Category)</option>
                    <?php // Populate dropdown with existing categories, excluding the current one 
                    ?>
                    <?php foreach ($categories as $parentCategory): ?>
                        <?php // Prevent selecting the category itself as its parent 
                        ?>
                        <?php if ($parentCategory['id'] != $category['id']): ?>
                            <option value="<?php echo $parentCategory['id']; ?>"
                                <?php // Select the current parent ID
                                echo ($category['parent_id'] == $parentCategory['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parentCategory['category_name']); // Display category name (escaped) 
                                ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Select a parent category if this is a subcategory, or leave as
                    "None" for a top-level category.</small>
            </div>
            <!-- /Parent Category Selection Field -->

            <!-- Form Action Buttons -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Category
                </button>
                <!-- Cancel button links back to the category list -->
                <a href="/admin/categories" class="btn btn-outline-secondary">Cancel</a>
            </div>
            <!-- /Form Action Buttons -->
        </form>
    </div>
</div>
<!-- /Category Edit Form Card -->

<!-- Delete Category Section -->
<?php // Check if the category can be deleted (i.e., has no associated products) 
?>
<?php if (!$has_products): ?>
    <!-- Deletion Enabled Card -->
    <div class="card mt-4">
        <div class="card-header bg-danger text-white">
            <h3 class="card-title h5 mb-0">Delete Category</h3>
        </div>
        <div class="card-body">
            <p class="card-text">Warning: This action cannot be undone. Only categories with no associated products can be
                deleted.</p>
            <!-- Delete form submits to the category delete endpoint -->
            <form action="/admin/categories/<?php echo $category['id']; ?>/delete" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.');">
                <!-- CSRF Token for delete action -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Category
                </button>
            </form>
        </div>
    </div>
    <!-- /Deletion Enabled Card -->
<?php else: ?>
    <!-- Deletion Disabled Card -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h3 class="card-title h5 mb-0">Delete Category</h3>
        </div>
        <div class="card-body">
            <p class="card-text">This category cannot be deleted because it has associated products. You must reassign or
                delete those products first.</p>
            <!-- Disabled delete button -->
            <button type="button" class="btn btn-secondary" disabled>
                <i class="fas fa-trash"></i> Delete Category
            </button>
        </div>
    </div>
    <!-- /Deletion Disabled Card -->
<?php endif; ?>
<!-- /Delete Category Section -->

<!-- Embedded CSS for View-Specific Styling -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Header styling */
    .admin-content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    /* Card base styling */
    .card {
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .card-body {
        padding: 1.5rem;
    }

    .card-header {
        padding: 1rem 1.5rem;
    }

    /* Form label styling */
    .form-label {
        font-weight: 500;
    }

    /* Required field indicator */
    .text-danger {
        color: #dc3545;
    }

    /* Helper text styling */
    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
    }

    /* Margin top utility */
    .mt-4 {
        margin-top: 1.5rem !important;
        /* Use !important cautiously, ensure it's needed */
    }

    /* Background color utilities */
    .bg-danger {
        background-color: #dc3545 !important;
    }

    .bg-secondary {
        background-color: #6c757d !important;
    }

    /* Text color utility */
    .text-white {
        color: white !important;
    }

    /* Heading size utility */
    .h5 {
        font-size: 1.25rem;
    }

    /* Margin bottom utility */
    .mb-0 {
        margin-bottom: 0 !important;
    }
</style>