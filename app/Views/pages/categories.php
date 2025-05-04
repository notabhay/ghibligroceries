<?php

/**
 * View file for the Product Categories page.
 * Displays products, allowing filtering by category.
 * Includes a sidebar for filtering and a main grid for product display.
 *
 * Expected PHP Variables:
 * - $categories (array):   An array of available product categories, typically containing 'category_id' and 'category_name'. Defaults to empty array.
 * - $products (array):     An array of products to display, filtered by category or showing all. Each product should have 'product_id', 'name', 'price', 'image_path'. Defaults to empty array.
 * - $logged_in (bool):     Indicates if the user is currently logged in. Used to show 'Add to Cart' or 'Login' buttons. Defaults to false.
 * - $activeFilter (string|null): The name of the currently active category filter, used to pre-select the dropdown. Defaults to null.
 *
 * JavaScript Interaction:
 * - The category dropdowns ('#main-category', '#sub-category') are expected to interact with JavaScript
 *   (likely in a separate JS file) to dynamically load products into the '#product-display-area' via AJAX
 *   when a category is selected. The sub-category dropdown might be populated based on the main category selection.
 * - 'Add to Cart' buttons ('.add-to-cart-btn') likely trigger AJAX requests to add items to the cart.
 */

// Use null coalescing operator to ensure variables are defined, preventing errors if not passed from controller.
$categories ??= [];
$products ??= [];
$logged_in ??= false;
$activeFilter ??= null; // Stores the name of the category passed via query string, if any.
?>
<!-- Page Header Section -->
<section class="page-header fixed-page-header">
    <div class="container">
        <h1>Product Categories</h1>
        <p>Browse our wide selection of fresh groceries by category</p>
    </div>
</section>

<!-- Main content wrapper containing sidebar and product grid -->
<div class="products-wrapper">
    <!-- Sidebar for filtering products -->
    <aside class="filter-sidebar">
        <h3>Filter Products</h3>
        <!-- Filter Group: Main Category Selection -->
        <div class="filter-group">
            <label for="main-category">Main Category</label>
            <select id="main-category" name="main_category">
                <option value="all">All Categories</option>
                <?php // Check if the $categories array is not empty 
                ?>
                <?php if (!empty($categories)): ?>
                    <?php // Loop through each category provided 
                    ?>
                    <?php foreach ($categories as $cat): ?>
                        <?php // Determine if this category is the currently active filter 
                        ?>
                        <?php $isSelected = ($activeFilter !== null && $cat['category_name'] === $activeFilter); ?>
                        <option value="<?php echo htmlspecialchars($cat['category_id']); ?>" <?php echo $isSelected ? 'selected' : ''; // Add 'selected' attribute if it matches the active filter 
                                                                                                ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <!-- Filter Group: Sub Category / Product Selection (Likely populated by JS) -->
        <div class="filter-group">
            <label for="sub-category">Sub Category / Products</label>
            <select id="sub-category" name="sub_category" disabled>
                <option value="all">Select Main Category First</option>
                <?php // Options here might be dynamically loaded via JavaScript based on main category selection 
                ?>
            </select>
            <small>Selecting a main category will load products here.</small>
        </div>
    </aside>

    <!-- Product Display Area: Grid where products are shown -->
    <section id="product-display-area" class="products-grid">
        <?php // Check if there are products to display 
        ?>
        <?php if (!empty($products)): ?>
            <?php
            // Calculate layout for a 4-column grid
            $total_products = count($products);
            $rows = ceil($total_products / 4); // Determine number of rows needed

            // Loop through rows
            for ($i = 0; $i < $rows; $i++):
                $start_index = $i * 4; // Starting product index for this row
                $end_index = min($start_index + 4, $total_products); // Ending product index for this row
            ?>
                <!-- Product Row Container -->
                <div class="products-row">
                    <?php // Loop through products for the current row 
                    ?>
                    <?php for ($j = $start_index; $j < $end_index; $j++):
                        $prod = $products[$j];
                        // Basic check to ensure essential product data exists before attempting to display
                        if (!isset($prod['product_id'], $prod['name'], $prod['price'], $prod['image_path']))
                            continue; // Skip this iteration if data is missing
                    ?>
                        <!-- Individual Product Card -->
                        <article class="product-card">
                            <!-- Link to the individual product details page -->
                            <a href="/product/<?php echo htmlspecialchars($prod['product_id']); ?>" class="product-link">
                                <!-- Product Image -->
                                <img src="<?php echo htmlspecialchars($prod['image_path']); ?>"
                                    alt="<?php echo htmlspecialchars($prod['name']); ?>" class="product-image">
                                <!-- Product Name -->
                                <h4 class="product-name"><?php echo htmlspecialchars($prod['name']); ?></h4>
                                <!-- Product Price -->
                                <p class="product-price">$<?php echo number_format($prod['price'], 2); ?></p>
                            </a>
                            <?php // Conditional button display based on login status 
                            ?>
                            <?php if ($logged_in): ?>
                                <!-- Add to Cart Button (for logged-in users, JS interaction) -->
                                <button class="add-to-cart-btn" data-product-id="<?php echo htmlspecialchars($prod['product_id']); ?>">
                                    Add to Cart
                                </button>
                            <?php else: ?>
                                <!-- Login Link (for logged-out users) -->
                                <a href="/login" class="login-to-purchase-btn">
                                    Login to Purchase
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endfor; // End product loop for the row 
                    ?>
                </div> <!-- End products-row -->
            <?php endfor; // End row loop 
            ?>
        <?php else: ?>
            <!-- Message displayed if no products are found (e.g., after filtering) -->
            <p>No products found.</p>
        <?php endif; // End product display check 
        ?>
    </section> <!-- End product-display-area -->
</div> <!-- End products-wrapper -->