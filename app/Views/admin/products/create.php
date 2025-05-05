<!--
 * Admin View: Product Create
 *
 * This view provides a form for administrators to add a new product to the store.
 * It includes fields for product name, description, price, stock quantity, category,
 * image upload, and an active status toggle.
 * Includes basic client-side validation hints (required fields, max length) and
 * server-side validation error display.
 * Contains embedded JavaScript for updating the file input label.
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
                <small class="form-text">Maximum 100 characters</small>
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
                <small class="form-text">Accepted formats: JPG, PNG, GIF, WEBP. Maximum size: 5MB.</small>
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