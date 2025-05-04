<!--
 * Admin View: Product Create
 *
 * This view provides a form for administrators to add a new product to the store.
 * It includes fields for product name, description, price, stock quantity, category,
 * image upload, and an active status toggle.
 * Includes basic client-side validation hints (required fields, max length) and
 * server-side validation error display.
 * Contains embedded CSS for styling the form elements and layout, and JavaScript
 * for updating the file input label.
 *
 * Expected variables:
 * - $categories (array): An array of available product categories, each with 'category_id' and 'category_name'.
 * - $csrf_token (string): The CSRF token for form security.
 * - $_SESSION['flash_error'] (string, optional): A general error message from the previous request (e.g., image upload failure).
 * - $_SESSION['flash_errors'] (array, optional): An array of specific validation error messages for form fields.
 * - $_POST (array, optional): Contains submitted form data if validation failed, used to repopulate fields.
 -->

<!-- Page Header -->
<div class="admin-content-header">
    <h2>Add New Product</h2>
    <!-- Back Button -->
    <div class="admin-content-actions">
        <a href="/admin/products" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
</div>

<?php // Display general flash error message if set 
?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['flash_error']); // Escape HTML 
        ?>
    </div>
    <?php // unset($_SESSION['flash_error']); // Optional: Unset after display 
    ?>
<?php endif; ?>

<?php // Display specific validation errors if set 
?>
<?php if (isset($_SESSION['flash_errors']) && is_array($_SESSION['flash_errors'])): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($_SESSION['flash_errors'] as $error): ?>
                <li><?php echo htmlspecialchars($error); // Escape HTML 
                    ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php // unset($_SESSION['flash_errors']); // Optional: Unset after display 
    ?>
<?php endif; ?>

<!-- Product Creation Form Card -->
<div class="card">
    <div class="card-body">
        <!-- Form submits to /admin/products via POST, allows file uploads -->
        <form action="/admin/products" method="POST" enctype="multipart/form-data">
            <!-- CSRF Token Hidden Input -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Product Name Input -->
            <div class="form-group">
                <label for="name">Product Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control" maxlength="100" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; // Repopulate if validation failed 
                                                                                                                ?>">
                <small class="form-text text-muted">Maximum 100 characters</small>
            </div>

            <!-- Product Description Textarea -->
            <div class="form-group">
                <label for="description">Description <span class="text-danger">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; // Repopulate if validation failed 
                                                                                                        ?></textarea>
            </div>

            <!-- Price and Stock Quantity Row -->
            <div class="form-row">
                <!-- Price Input -->
                <div class="form-group col-md-6">
                    <label for="price">Price ($) <span class="text-danger">*</span></label>
                    <input type="number" id="price" name="price" class="form-control" min="0.01" step="0.01" required
                        value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; // Repopulate if validation failed 
                                ?>">
                </div>
                <!-- Stock Quantity Input -->
                <div class="form-group col-md-6">
                    <label for="stock_quantity">Stock Quantity <span class="text-danger">*</span></label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" required
                        value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : '100'; // Repopulate or default to 100 
                                ?>">
                </div>
            </div>

            <!-- Category Selection Dropdown -->
            <div class="form-group">
                <label for="category_id">Category <span class="text-danger">*</span></label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">Select a category</option>
                    <?php // Populate options from the $categories array 
                    ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php // Select the category if repopulating from failed validation 
                                                                                ?>
                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); // Escape HTML 
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Product Image Upload Input -->
            <div class="form-group">
                <label for="image">Product Image <span class="text-danger">*</span></label>
                <div class="custom-file">
                    <?php // File input accepts specific image types 
                    ?>
                    <input type="file" class="custom-file-input" id="image" name="image"
                        accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <label class="custom-file-label" for="image">Choose file</label>
                </div>
                <small class="form-text text-muted">Accepted formats: JPG, PNG, GIF, WEBP. Maximum size: 5MB.</small>
            </div>

            <!-- Active Status Switch -->
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                        checked <?php // Checked by default for new products 
                                ?>>
                    <label class="custom-control-label" for="is_active">Active (available for purchase)</label>
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Product
                </button>
                <a href="/admin/products" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div> <!-- End card-body -->
</div> <!-- End card -->

<!-- Embedded CSS for form styling -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Styles for the header section (Title and Back button) */
    .admin-content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    /* Standard margin for form groups */
    .form-group {
        margin-bottom: 1.5rem;
    }

    /* Flexbox layout for form rows (e.g., Price and Stock) */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        /* Negative margin for gutter spacing */
        margin-left: -15px;
    }

    /* Padding for columns within a form row */
    .form-row>.col,
    .form-row>[class*="col-"] {
        padding-right: 15px;
        padding-left: 15px;
    }

    /* Half-width column for medium devices and up */
    .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    /* Styling for the custom file input container */
    .custom-file {
        position: relative;
        display: inline-block;
        width: 100%;
        height: calc(1.5em + 0.75rem + 2px);
        /* Bootstrap standard input height */
        margin-bottom: 0;
    }

    /* Hides the default file input appearance */
    .custom-file-input {
        position: relative;
        z-index: 2;
        width: 100%;
        height: calc(1.5em + 0.75rem + 2px);
        margin: 0;
        opacity: 0;
        /* Make the actual input invisible */
    }

    /* Styles the visible label that replaces the default file input */
    .custom-file-label {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1;
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        overflow: hidden;
        /* Prevent text overflow */
        white-space: nowrap;
        /* Keep label text on one line */
    }

    /* Styles the "Browse" button part of the custom file input */
    .custom-file-label::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        z-index: 3;
        display: block;
        height: calc(1.5em + 0.75rem);
        /* Adjust height to match padding */
        padding: 0.375rem 0.75rem;
        line-height: 1.5;
        color: #495057;
        content: "Browse";
        /* Text for the button */
        background-color: #e9ecef;
        border-left: inherit;
        border-radius: 0 0.25rem 0.25rem 0;
    }

    /* Styling for the custom switch container */
    .custom-switch {
        padding-left: 2.25rem;
        /* Space for the switch */
    }

    /* Base styling for custom controls (checkboxes, radios, switches) */
    .custom-control {
        position: relative;
        display: block;
        min-height: 1.5rem;
        padding-left: 1.5rem;
    }

    /* Hides the default checkbox input */
    .custom-control-input {
        position: absolute;
        z-index: -1;
        opacity: 0;
    }

    /* Styles the label associated with the custom control */
    .custom-control-label {
        position: relative;
        margin-bottom: 0;
        vertical-align: top;
    }

    /* Creates the background/box of the custom control */
    .custom-control-label::before {
        position: absolute;
        top: 0.25rem;
        left: -1.5rem;
        /* Position left of the label text */
        display: block;
        width: 1rem;
        height: 1rem;
        pointer-events: none;
        content: "";
        background-color: #fff;
        border: 1px solid #adb5bd;
    }

    /* Specific styles for the switch background */
    .custom-switch .custom-control-label::before {
        left: -2.25rem;
        /* Adjust position for switch */
        width: 1.75rem;
        /* Wider background for switch */
        border-radius: 0.5rem;
        /* Rounded corners for switch */
    }

    /* Styles the switch handle (the moving part) when checked */
    .custom-switch .custom-control-input:checked~.custom-control-label::after {
        background-color: #fff;
        transform: translateX(0.75rem);
        /* Move handle to the right */
    }

    /* Creates the handle of the switch */
    .custom-switch .custom-control-label::after {
        top: calc(0.25rem + 2px);
        left: calc(-2.25rem + 2px);
        /* Position inside the switch background */
        width: calc(1rem - 4px);
        /* Size of the handle */
        height: calc(1rem - 4px);
        background-color: #adb5bd;
        /* Default color of the handle */
        border-radius: 0.5rem;
        /* Rounded handle */
        transition: transform 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        /* Smooth transition */
    }

    /* Base styles for the ::after pseudo-element (used for checkmark/handle) */
    .custom-control-label::after {
        position: absolute;
        top: 0.25rem;
        left: -1.5rem;
        display: block;
        width: 1rem;
        height: 1rem;
        content: "";
        background: no-repeat 50% / 50% 50%;
        /* Used for checkmark background image in checkboxes/radios */
    }
</style>

<!-- Embedded JavaScript for custom file input label update -->
<script>
    // Find the custom file input element
    const fileInput = document.querySelector('.custom-file-input');
    if (fileInput) {
        // Add an event listener for the 'change' event (when a file is selected)
        fileInput.addEventListener('change', function(e) {
            // Get the name of the selected file
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose file';
            // Find the corresponding label element
            const label = e.target.nextElementSibling;
            // Update the label's text content with the file name
            if (label) {
                label.textContent = fileName;
            }
        });
    }
</script>