<!--
 * Admin View: Product Edit
 *
 * This view provides a form for administrators to edit an existing product.
 * It pre-populates the form fields with the current product data.
 * Allows updating name, description, price, stock, category, active status,
 * and optionally uploading a new product image.
 * Displays the current product image.
 * Includes server-side validation error display.
 * Contains embedded CSS for styling and JavaScript for the file input label.
 *
 * Expected variables:
 * - $product (array): An associative array containing the data of the product being edited (product_id, name, description, price, stock_quantity, category_id, image_path, is_active).
 * - $categories (array): An array of available product categories, each with 'category_id' and 'category_name'.
 * - $csrf_token (string): The CSRF token for form security.
 * - $_SESSION['flash_error'] (string, optional): A general error message from the previous request.
 * - $_SESSION['flash_errors'] (array, optional): An array of specific validation error messages.
 -->

<!-- Page Header -->
<div class="admin-content-header">
    <h2>Edit Product</h2>
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

<!-- Product Edit Form Card -->
<div class="card">
    <div class="card-body">
        <?php // Form submits to /admin/products/{product_id} via POST, allows file uploads 
        ?>
        <form action="/admin/products/<?php echo $product['product_id']; ?>" method="POST"
            enctype="multipart/form-data">
            <!-- CSRF Token Hidden Input -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Product Name Input -->
            <div class="form-group">
                <label for="name">Product Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control" maxlength="100" required value="<?php echo htmlspecialchars($product['name']); // Pre-fill with current name 
                                                                                                                ?>">
                <small class="form-text text-muted">Maximum 100 characters</small>
            </div>

            <!-- Product Description Textarea -->
            <div class="form-group">
                <label for="description">Description <span class="text-danger">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($product['description']); // Pre-fill with current description 
                                                                                                        ?></textarea>
            </div>

            <!-- Price and Stock Quantity Row -->
            <div class="form-row">
                <!-- Price Input -->
                <div class="form-group col-md-6">
                    <label for="price">Price ($) <span class="text-danger">*</span></label>
                    <input type="number" id="price" name="price" class="form-control" min="0.01" step="0.01" required
                        value="<?php echo htmlspecialchars($product['price']); // Pre-fill with current price 
                                ?>">
                </div>
                <!-- Stock Quantity Input -->
                <div class="form-group col-md-6">
                    <label for="stock_quantity">Stock Quantity <span class="text-danger">*</span></label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" required
                        value="<?php echo htmlspecialchars($product['stock_quantity']); // Pre-fill with current stock 
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
                        <option value="<?php echo $category['category_id']; ?>" <?php // Select the product's current category 
                                                                                ?>
                            <?php echo ($product['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); // Escape HTML 
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Display Current Product Image -->
            <div class="form-group">
                <label>Current Image</label>
                <div class="current-image-container">
                    <?php // Display the image using the path stored in the database 
                    ?>
                    <img src="/<?php echo htmlspecialchars($product['image_path']); // Prepend '/' assuming image_path is relative to public root 
                                ?>" alt="<?php echo htmlspecialchars($product['name']); // Use product name as alt text 
                                            ?>" class="current-product-image">
                </div>
            </div>

            <!-- Change Product Image Upload Input (Optional) -->
            <div class="form-group">
                <label for="image">Change Product Image (optional)</label>
                <div class="custom-file">
                    <?php // File input is not required for editing 
                    ?>
                    <input type="file" class="custom-file-input" id="image" name="image"
                        accept="image/jpeg,image/png,image/gif,image/webp">
                    <label class="custom-file-label" for="image">Choose file</label>
                </div>
                <small class="form-text text-muted">Accepted formats: JPG, PNG, GIF, WEBP. Maximum size: 5MB. Leave
                    empty to keep the current image.</small>
            </div>

            <!-- Active Status Switch -->
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" <?php echo $product['is_active'] ? 'checked' : ''; // Check based on current status 
                                                                                                                    ?>>
                    <label class="custom-control-label" for="is_active">Active (available for purchase)</label>
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
                <a href="/admin/products" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div> <!-- End card-body -->
</div> <!-- End card -->

<!-- Embedded CSS (Similar to create view, with added styles for current image) -->
<style>
    /* Body */
    body {
        margin: 0;
        padding: 0;
    }

    /* Styles for the header section */
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

    /* Flexbox layout for form rows */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
    }

    /* Padding for columns within a form row */
    .form-row>.col,
    .form-row>[class*="col-"] {
        padding-right: 15px;
        padding-left: 15px;
    }

    /* Half-width column */
    .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    /* Container for the current image preview */
    .current-image-container {
        margin-bottom: 1rem;
    }

    /* Styling for the current product image preview */
    .current-product-image {
        max-width: 200px;
        /* Limit preview size */
        max-height: 200px;
        border-radius: 4px;
        border: 1px solid #ddd;
        /* Light border */
        padding: 5px;
        /* Small padding around image */
        display: block;
        /* Ensure it behaves like a block element */
    }

    /* Styling for the custom file input container */
    .custom-file {
        position: relative;
        display: inline-block;
        width: 100%;
        height: calc(1.5em + 0.75rem + 2px);
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
    }

    /* Styles the visible label */
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
        white-space: nowrap;
    }

    /* Styles the "Browse" button part */
    .custom-file-label::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        z-index: 3;
        display: block;
        height: calc(1.5em + 0.75rem);
        padding: 0.375rem 0.75rem;
        line-height: 1.5;
        color: #495057;
        content: "Browse";
        background-color: #e9ecef;
        border-left: inherit;
        border-radius: 0 0.25rem 0.25rem 0;
    }

    /* Styling for the custom switch container */
    .custom-switch {
        padding-left: 2.25rem;
    }

    /* Base styling for custom controls */
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
        width: 1.75rem;
        border-radius: 0.5rem;
    }

    /* Styles the switch handle when checked */
    .custom-switch .custom-control-input:checked~.custom-control-label::after {
        background-color: #fff;
        transform: translateX(0.75rem);
    }

    /* Creates the handle of the switch */
    .custom-switch .custom-control-label::after {
        top: calc(0.25rem + 2px);
        left: calc(-2.25rem + 2px);
        width: calc(1rem - 4px);
        height: calc(1rem - 4px);
        background-color: #adb5bd;
        border-radius: 0.5rem;
        transition: transform 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    /* Base styles for the ::after pseudo-element */
    .custom-control-label::after {
        position: absolute;
        top: 0.25rem;
        left: -1.5rem;
        display: block;
        width: 1rem;
        height: 1rem;
        content: "";
        background: no-repeat 50% / 50% 50%;
    }
</style>

<!-- Embedded JavaScript for custom file input label update -->
<script>
    // Find the custom file input element
    const fileInput = document.querySelector('.custom-file-input');
    if (fileInput) {
        // Add event listener for file selection
        fileInput.addEventListener('change', function(e) {
            // Check if a file was actually selected
            if (e.target.files.length > 0) {
                // Get the filename
                const fileName = e.target.files[0].name;
                // Get the associated label
                const label = e.target.nextElementSibling;
                // Update the label text
                if (label) {
                    label.textContent = fileName;
                }
            } else {
                // Optional: Reset label if no file is chosen (e.g., user cancels)
                const label = e.target.nextElementSibling;
                if (label) {
                    label.textContent = 'Choose file';
                }
            }
        });
    }
</script>