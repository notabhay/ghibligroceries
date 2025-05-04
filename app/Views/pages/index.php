<?php

/**
 * View file for the Homepage (index page).
 * Displays the main landing page content including a hero section,
 * featured products, and links to main categories.
 *
 * Expected PHP Variables:
 * - $page_title (string):       The title for the page (used in <title> tag). Defaults to 'GhibliGroceries'.
 * - $meta_description (string): Meta description for SEO. Defaults to 'Fresh groceries delivered.'.
 * - $meta_keywords (string):    Meta keywords for SEO. Defaults to 'grocery, online shopping'.
 * - $random_products (array):   An array of randomly selected products to feature on the homepage.
 *                                Each product should have 'product_id', 'name', 'price', 'image_path'. Defaults to empty array.
 * - $logged_in (bool):          Indicates if the user is currently logged in. Used to show 'Add to Cart' or 'Login' buttons. Defaults to false.
 *
 * JavaScript Interaction:
 * - The search bar ('.search-bar') might have associated JS for live search suggestions or redirection.
 * - 'Add to Cart' buttons ('.add-to-cart-btn') likely trigger AJAX requests to add items to the cart.
 */

// Initialize variables with default values using null coalescing operator
$page_title = $page_title ?? 'GhibliGroceries';
$meta_description = $meta_description ?? 'Fresh groceries delivered.';
$meta_keywords = $meta_keywords ?? 'grocery, online shopping';
$random_products = $random_products ?? []; // Array for featured products
$logged_in = $logged_in ?? false; // User login status
?>

<!-- Hero Section: Main banner area -->
<section class="hero">
    <!-- Left side content: Headline, description, search, features -->
    <div class="hero-copy">
        <h1>Let Your <span>Groceries</span> Come To You</h1>
        <p>Get fresh groceries online without stepping out to make delicious food with the freshest ingredients.</p>
        <!-- Search Bar (functionality likely requires JS) -->
        <search class="search-bar">
            <input type="text" class="search-input" placeholder="Search products...">
            <button class="search-button" aria-label="Search">
                <i class="fas fa-search"></i> <!-- Search Icon -->
            </button>
        </search>
        <!-- List of key features/selling points -->
        <div class="feature-list">
            <div class="feature-item">
                <i class="fas fa-carrot feature-icon"></i> Fresh Vegetables
            </div>
            <div class="feature-item">
                <i class="fas fa-check-circle feature-icon"></i> 100% Guarantee
            </div>
            <div class="feature-item">
                <i class="fas fa-money-bill-wave feature-icon"></i> Cash on Delivery
            </div>
            <div class="feature-item">
                <i class="fas fa-shipping-fast feature-icon"></i> Fast Delivery
            </div>
        </div>
    </div>
    <!-- Right side visual element: Hero image -->
    <div class="hero-visual">
        <img src="/assets/images/hero_image.png" alt="Delivery person with groceries" class="hero-image">
    </div>

    <!-- Featured Product Showcase Section (within the hero section structure) -->
    <section class="product-showcase">
        <h2 class="featured-items-title">Today's Featured Items</h2>
        <?php // Check if there are random products to display 
        ?>
        <?php if (!empty($random_products)): ?>
            <?php // Loop through each featured product 
            ?>
            <?php foreach ($random_products as $product): ?>
                <!-- Individual Product Card -->
                <article class="product-card">
                    <!-- Product Image (uses placeholder if path is missing) -->
                    <img src="/<?php echo htmlspecialchars($product['image_path'] ?? 'assets/images/placeholder.png'); ?>"
                        alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>">
                    <!-- Product Name (uses 'N/A' if missing) -->
                    <div class="product-name"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></div>
                    <!-- Product Price (formats to 2 decimal places, defaults to 0) -->
                    <div class="product-price">$<?php echo number_format($product['price'] ?? 0, 2); ?></div>
                    <?php // Display button based on login status 
                    ?>
                    <?php if ($logged_in): ?>
                        <!-- Add to Cart Button (for logged-in users, JS interaction) -->
                        <button class="add-to-cart-btn" data-product-id="<?php echo htmlspecialchars($product['product_id'] ?? ''); // Ensure product_id exists 
                                                                            ?>">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <!-- Login Link (for logged-out users) -->
                        <a href="/login" class="login-to-purchase-btn">
                            Login to Purchase
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; // End product loop 
            ?>
        <?php else: ?>
            <!-- Message displayed if no featured products are available -->
            <p>No products to display currently.</p>
        <?php endif; // End check for random products 
        ?>
    </section> <!-- End product-showcase -->
</section> <!-- End hero section -->

<!-- Category Links Section -->
<section class="category-section">
    <!-- Each category is presented as a clickable article linking to the filtered categories page -->
    <!-- Link to Dairy Products category -->
    <a href="/categories?filter=Dairy%20Products" class="category-link">
        <article class="category-item">
            <div class="category-icon">
                <img src="/assets/images/categories/dairy_products_icon.png" alt="Dairy Products">
            </div>
            <div class="category-title">Dairy Products</div>
            <div class="category-description">Fresh milk, cheese, yogurt, and more.</div>
        </article>
    </a>
    <!-- Link to Fruits & Veggies category -->
    <a href="/categories?filter=Fruits%20%26%20Veggies" class="category-link">
        <article class="category-item">
            <div class="category-icon">
                <img src="/assets/images/categories/fruits_and_veggies_icon.png" alt="Fruits & Veggies">
            </div>
            <div class="category-title">Fruits & Veggies</div>
            <div class="category-description">Farm-fresh seasonal produce.</div>
        </article>
    </a>
    <!-- Link to Spices & Seasonings category -->
    <a href="/categories?filter=Spices%20%26%20Seasonings" class="category-link">
        <article class="category-item">
            <div class="category-icon">
                <img src="/assets/images/categories/spices_and_seasonings_icon.png" alt="Spices & Seasonings">
            </div>
            <div class="category-title">Spices & Seasonings</div>
            <div class="category-description">Flavorful additions for your cooking.</div>
        </article>
    </a>
    <!-- Link to Meat category -->
    <a href="/categories?filter=Meat" class="category-link">
        <article class="category-item">
            <div class="category-icon">
                <img src="/assets/images/categories/meat_icon.png" alt="Meat">
            </div>
            <div class="category-title">Meat</div>
            <div class="category-description">Quality cuts of chicken, beef, and pork.</div>
        </article>
    </a>
    <!-- Link to Baked Goods category -->
    <a href="/categories?filter=Baked%20Goods" class="category-link">
        <article class="category-item">
            <div class="category-icon">
                <img src="/assets/images/categories/baked_goods_icon.png" alt="Baked Goods">
            </div>
            <div class="category-title">Baked Goods</div>
            <div class="category-description">Delicious bread, pastries, and cookies.</div>
        </article>
    </a>
</section> <!-- End category-section -->