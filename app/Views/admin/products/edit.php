<!--
 * Admin View: Product Edit
 *
 * This view provides a form for administrators to edit an existing product.
 * It pre-populates the form fields with the current product data.
 * Allows updating name, description, price, stock, category, active status,
 * and optionally uploading a new product image.
 * Displays the current product image.
 * Includes server-side validation error display.
 * Contains embedded JavaScript for the file input label.
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
                <small class="form-text">Maximum 100 characters</small>
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
                <small class="form-text">Accepted formats: JPG, PNG, GIF, WEBP. Maximum size: 5MB. Leave
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
                <a href="/admin/products" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div> <!-- End card-body -->
</div> <!-- End card -->

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