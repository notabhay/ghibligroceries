<!--
 * Admin View: Product Index
 *
 * This view displays a list of all products in the admin panel.
 * It allows administrators to view, edit, add, and activate/deactivate products.
 * Includes filtering options (by category, status) and pagination.
 * Displays flash messages for success/error feedback from actions.
 * Contains embedded CSS for layout and styling.
 *
 * Expected variables:
 * - $products (array): An array of product data, each containing product_id, image_path, name, category_name, price, stock_quantity, is_active.
 * - $categories (array): An array of available product categories for the filter dropdown (category_id, category_name).
 * - $filters (array, optional): An array containing the currently applied filter values (category_id, is_active).
 * - $pagination (array, optional): An array containing pagination data (total_pages, current_page, has_previous, has_next).
 * - $csrf_token (string): The CSRF token for the activate/deactivate form submissions.
 * - $_SESSION['flash_success'] (string, optional): Success message from a previous action.
 * - $_SESSION['flash_error'] (string, optional): Error message from a previous action.
 -->

<!-- Page Header -->
<div class="admin-content-header">
    <h2>Product Management</h2>
    <!-- Add New Product Button -->
    <div class="admin-content-actions">
        <a href="/admin/products/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
</div>

<?php // Display flash messages if they exist 
?>
<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['flash_success']); // Escape HTML 
        ?>
    </div>
    <?php // unset($_SESSION['flash_success']); // Optional: Unset after display 
    ?>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['flash_error']); // Escape HTML 
        ?>
    </div>
    <?php // unset($_SESSION['flash_error']); // Optional: Unset after display 
    ?>
<?php endif; ?>

<!-- Filter Section -->
<div class="filter-container">
    <!-- Filter Form (Submits via GET to the same page) -->
    <form action="/admin/products" method="GET" class="filter-form">
        <!-- Category Filter Dropdown -->
        <div class="filter-group">
            <label for="category_id">Category:</label>
            <select name="category_id" id="category_id" class="form-control">
                <option value="">All Categories</option>
                <?php // Populate categories from $categories array 
                ?>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>" <?php // Pre-select if this category is the current filter 
                                                                            ?>
                        <?php echo (isset($filters['category_id']) && $filters['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); // Escape HTML 
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Status Filter Dropdown -->
        <div class="filter-group">
            <label for="is_active">Status:</label>
            <select name="is_active" id="is_active" class="form-control">
                <option value="">All</option>
                <option value="1" <?php // Pre-select if 'Active' is the current filter 
                                    ?>
                    <?php echo (isset($filters['is_active']) && $filters['is_active'] === 1) ? 'selected' : ''; ?>>
                    Active</option>
                <option value="0" <?php // Pre-select if 'Inactive' is the current filter 
                                    ?>
                    <?php echo (isset($filters['is_active']) && $filters['is_active'] === 0) ? 'selected' : ''; ?>>
                    Inactive</option>
            </select>
        </div>
        <!-- Filter Action Buttons -->
        <div class="filter-group">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="/admin/products" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Products Table Container -->
<div class="table-responsive">
    <table class="table table-striped">
        <!-- Table Header -->
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <!-- Table Body -->
        <tbody>
            <?php // Check if there are products to display 
            ?>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="8" class="text-center">No products found matching your criteria.</td>
                </tr>
            <?php else: ?>
                <?php // Loop through each product 
                ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <!-- Product ID -->
                        <td><?php echo $product['product_id']; ?></td>
                        <!-- Product Image Thumbnail -->
                        <td>
                            <img src="/<?php echo htmlspecialchars($product['image_path']); // Escape HTML 
                                        ?>" alt="<?php echo htmlspecialchars($product['name']); // Escape HTML 
                                                    ?>" class="product-thumbnail" width="50" height="50">
                        </td>
                        <!-- Product Name -->
                        <td><?php echo htmlspecialchars($product['name']); // Escape HTML 
                            ?></td>
                        <!-- Product Category Name -->
                        <td><?php echo htmlspecialchars($product['category_name']); // Escape HTML 
                            ?></td>
                        <!-- Product Price (Formatted) -->
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <!-- Stock Quantity -->
                        <td><?php echo $product['stock_quantity']; ?></td>
                        <!-- Product Status Badge -->
                        <td>
                            <span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; // Dynamic class based on status 
                                                ?>">
                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; // Display status text 
                                ?>
                            </span>
                        </td>
                        <!-- Action Buttons -->
                        <td>
                            <div class="btn-group">
                                <!-- Edit Button -->
                                <a href="/admin/products/<?php echo $product['product_id']; ?>/edit"
                                    class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <!-- Activate/Deactivate Form & Button -->
                                <form action="/admin/products/<?php echo $product['product_id']; ?>/toggle-active" method="POST"
                                    class="d-inline"> <?php // Form submitted inline 
                                                        ?>
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $product['is_active'] ? 'btn-warning' : 'btn-success'; // Dynamic button class 
                                                                            ?>">
                                        <i class="fas <?php echo $product['is_active'] ? 'fa-ban' : 'fa-check'; // Dynamic icon 
                                                        ?>"></i>
                                        <?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; // Dynamic button text 
                                        ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; // End products check 
            ?>
        </tbody>
    </table>
</div>

<?php // Check if pagination is needed 
?>
<?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
    <!-- Pagination Container -->
    <div class="pagination-container">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php // Previous Page Link 
                ?>
                <?php if ($pagination['has_previous']): ?>
                    <li class="page-item">
                        <?php // Build URL with previous page number and existing filters 
                        ?>
                        <a class="page-link"
                            href="/admin/products?page=<?php echo $pagination['current_page'] - 1; ?><?php echo isset($filters['category_id']) ? '&category_id=' . urlencode($filters['category_id']) : ''; ?><?php echo isset($filters['is_active']) ? '&is_active=' . urlencode($filters['is_active']) : ''; ?>"
                            aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php // Page Number Links 
                ?>
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; // Highlight current page 
                                            ?>">
                        <?php // Build URL with page number and existing filters 
                        ?>
                        <a class="page-link"
                            href="/admin/products?page=<?php echo $i; ?><?php echo isset($filters['category_id']) ? '&category_id=' . urlencode($filters['category_id']) : ''; ?><?php echo isset($filters['is_active']) ? '&is_active=' . urlencode($filters['is_active']) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php // Next Page Link 
                ?>
                <?php if ($pagination['has_next']): ?>
                    <li class="page-item">
                        <?php // Build URL with next page number and existing filters 
                        ?>
                        <a class="page-link"
                            href="/admin/products?page=<?php echo $pagination['current_page'] + 1; ?><?php echo isset($filters['category_id']) ? '&category_id=' . urlencode($filters['category_id']) : ''; ?><?php echo isset($filters['is_active']) ? '&is_active=' . urlencode($filters['is_active']) : ''; ?>"
                            aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php endif; // End pagination check 
?>

<!-- Embedded CSS for styling -->
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

    /* Filter container styling */
    .filter-container {
        background-color: #f8f9fa;
        /* Light background for filter area */
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1.5rem;
    }

    /* Filter form layout */
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        /* Allow wrapping on smaller screens */
        gap: 1rem;
        /* Spacing between filter elements */
        align-items: flex-end;
        /* Align items to the bottom */
    }

    /* Individual filter group styling */
    .filter-group {
        display: flex;
        flex-direction: column;
        /* Stack label and input vertically */
        min-width: 200px;
        /* Minimum width for filter inputs */
    }

    /* Product thumbnail image styling */
    .product-thumbnail {
        object-fit: cover;
        /* Ensure image covers the area without distortion */
        border-radius: 4px;
        /* Slightly rounded corners */
    }

    /* General badge styling */
    .badge {
        padding: 0.5em 0.75em;
        border-radius: 4px;
        font-weight: normal;
        /* Override default bold */
        font-size: 0.85em;
        /* Slightly smaller font */
    }

    /* Success badge (Active) */
    .badge-success {
        background-color: #28a745;
        color: white;
    }

    /* Danger badge (Inactive) */
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }

    /* Pagination container centering */
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
    }

    /* Button group styling for actions */
    .btn-group {
        display: flex;
        /* Align buttons horizontally */
        gap: 0.5rem;
        /* Space between buttons */
    }
</style>