<!--
Admin Category Create View
--------------------------
This view provides a form for administrators to add a new product category.
It includes fields for the category name and an optional parent category,
allowing for hierarchical category structures.

Expected PHP Variables:
- $csrf_token: A security token to prevent Cross-Site Request Forgery attacks.
- $categories: An array of existing categories, used to populate the parent category dropdown.
               Each element should have at least 'id' and 'category_name'.
- $_SESSION['flash_error'] (optional): A single error message string from a previous attempt.
- $_SESSION['flash_errors'] (optional): An array of validation error messages.
- $_SESSION['flash_old'] (optional): An array containing previously submitted form data
                                     (e.g., 'name', 'parent_id') to repopulate the form
                                     in case of validation errors.
-->

<!-- Page Header -->
<div class="admin-content-header">
    <h2>Add New Category</h2>
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

<!-- Category Creation Form Card -->
<div class="card">
    <div class="card-body">
        <!-- Form submits to the category creation endpoint -->
        <form action="/admin/categories" method="POST">
            <!-- CSRF Token: Essential for security -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Category Name Field -->
            <div class="form-group mb-3">
                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required
                    value="<?php // Repopulate field with old input if available, otherwise empty
                            echo isset($_SESSION['flash_old']['name']) ? htmlspecialchars($_SESSION['flash_old']['name']) : ''; ?>">
                <small class="form-text text-muted">Enter a unique and descriptive name for the category.</small>
            </div>
            <!-- /Category Name Field -->

            <!-- Parent Category Selection Field -->
            <div class="form-group mb-3">
                <label for="parent_id" class="form-label">Parent Category</label>
                <select class="form-control" id="parent_id" name="parent_id">
                    <!-- Default option for a top-level category -->
                    <option value="0">None (Top Level Category)</option>
                    <?php // Populate dropdown with existing categories 
                    ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                            <?php // Select the old parent ID if available
                            echo (isset($_SESSION['flash_old']['parent_id']) && $_SESSION['flash_old']['parent_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); // Display category name (escaped) 
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Select a parent category if this is a subcategory, or leave as
                    "None" for a top-level category.</small>
            </div>
            <!-- /Parent Category Selection Field -->

            <!-- Form Action Buttons -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Category
                </button>
                <!-- Cancel button links back to the category list -->
                <a href="/admin/categories" class="btn btn-outline-secondary">Cancel</a>
            </div>
            <!-- /Form Action Buttons -->
        </form>
    </div>
</div>
<!-- /Category Creation Form Card -->

<!-- Embedded CSS for View-Specific Styling -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Header styling: Title on left, Actions on right */
    .admin-content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    /* Card styling for the form container */
    .card {
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Form label styling */
    .form-label {
        font-weight: 500;
    }

    /* Style for required field indicator */
    .text-danger {
        color: #dc3545;
    }

    /* Style for helper text below form fields */
    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>