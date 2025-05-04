<!--
Admin Category Index View
-------------------------
This view displays a list of all product categories in a paginated table.
It allows administrators to see category details (ID, Name, Parent) and provides
links to edit each category. A button to add a new category is also present.

Expected PHP Variables:
- $categories: An array containing the categories for the current page. Each element
               is an associative array with 'id', 'category_name', and potentially 'parent_name'.
- $pagination (optional): An associative array containing pagination details if needed:
    - 'total_pages': Total number of pages.
    - 'current_page': The current page number.
    - 'has_previous': Boolean indicating if there's a previous page.
    - 'has_next': Boolean indicating if there's a next page.
- $_SESSION['flash_success'] (optional): A success message string (e.g., after creating/updating).
- $_SESSION['flash_error'] (optional): An error message string (e.g., after a failed delete).
-->

<!-- Page Header -->
<div class="admin-content-header">
    <h2>Category Management</h2>
    <!-- Action Button: Link to the category creation page -->
    <div class="admin-content-actions">
        <a href="/admin/categories/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Category
        </a>
    </div>
</div>
<!-- /Page Header -->

<!-- Flash Messages Area -->
<?php // Display success message if set 
?>
<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['flash_success']); // Escape output 
        ?>
    </div>
<?php endif; ?>

<?php // Display error message if set 
?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($_SESSION['flash_error']); // Escape output 
        ?>
    </div>
<?php endif; ?>
<!-- /Flash Messages Area -->

<!-- Categories Table -->
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Parent Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php // Check if there are any categories to display 
            ?>
            <?php if (empty($categories)): ?>
                <!-- Display message if no categories exist -->
                <tr>
                    <td colspan="4" class="text-center">No categories found.</td>
                </tr>
            <?php else: ?>
                <?php // Loop through each category and display its details in a table row 
                ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['id']; // Display Category ID 
                            ?></td>
                        <td><?php echo htmlspecialchars($category['category_name']); // Display Category Name (escaped) 
                            ?></td>
                        <td>
                            <?php // Display parent category name if it exists, otherwise show 'None' 
                            ?>
                            <?php if (!empty($category['parent_name'])): ?>
                                <?php echo htmlspecialchars($category['parent_name']); // Display Parent Name (escaped) 
                                ?>
                            <?php else: ?>
                                <span class="text-muted">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- Action Buttons Group -->
                            <div class="btn-group">
                                <!-- Edit Button: Links to the edit page for this category -->
                                <a href="/admin/categories/<?php echo $category['id']; ?>/edit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <!-- Note: Delete button might be added here or on the edit page -->
                            </div>
                            <!-- /Action Buttons Group -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<!-- /Categories Table -->

<!-- Pagination Controls -->
<?php // Display pagination controls only if there's more than one page 
?>
<?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
    <div class="pagination-container">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php // Previous Page Link 
                ?>
                <?php if ($pagination['has_previous']): ?>
                    <li class="page-item">
                        <a class="page-link" href="/admin/categories?page=<?php echo $pagination['current_page'] - 1; ?>"
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
                        <a class="page-link" href="/admin/categories?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php // Next Page Link 
                ?>
                <?php if ($pagination['has_next']): ?>
                    <li class="page-item">
                        <a class="page-link" href="/admin/categories?page=<?php echo $pagination['current_page'] + 1; ?>"
                            aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>
<!-- /Pagination Controls -->

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

    /* Center pagination controls */
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
    }

    /* Style for action button groups in the table */
    .btn-group {
        display: flex;
        gap: 0.5rem;
        /* Spacing between buttons */
    }

    /* Style for muted text (e.g., 'None' for parent category) */
    .text-muted {
        color: #6c757d;
    }
</style>