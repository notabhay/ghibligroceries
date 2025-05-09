<?php
/**
 * View file for the Search Results page.
 * Displays products matching a search query with AI-powered enhancements.
 *
 * Expected PHP Variables:
 * - $searchTerm (string):     The search term used to find products. Required.
 * - $products (array):        An array of products matching the search term. Each product should have 
 *                             'product_id', 'name', 'price', 'image_path', 'category_name', 'slug'. Defaults to empty array.
 * - $resultCount (int):       The number of products found. Defaults to 0.
 * - $correctedTerm (string):  AI-suggested corrected search term, if available. Defaults to null.
 * - $aiProcessed (bool):      Whether the search was processed by AI. Defaults to false.
 * - $fallback (bool):         Whether traditional search was used as fallback. Defaults to false.
 * - $logged_in (bool):        Indicates if the user is currently logged in. Used to show 'Add to Cart' or 'Login' buttons. Defaults to false.
 * - $page_title (string):     The title for the page (used in <title> tag). Defaults to 'Search Results - GhibliGroceries'.
 * - $meta_description (string): Meta description for SEO. Defaults to 'Search results for products at GhibliGroceries'.
 * - $meta_keywords (string):  Meta keywords for SEO. Defaults to 'search, products, grocery, online shopping'.
 */

// Initialize variables with default values using null coalescing operator
$searchTerm = $searchTerm ?? '';
$products = $products ?? [];
$resultCount = $resultCount ?? 0;
$correctedTerm = $correctedTerm ?? null;
$aiProcessed = $aiProcessed ?? false;
$fallback = $fallback ?? false;
$logged_in = $logged_in ?? false;
$page_title = $page_title ?? 'Search Results - GhibliGroceries';
$meta_description = $meta_description ?? 'Search results for products at GhibliGroceries';
$meta_keywords = $meta_keywords ?? 'search, products, grocery, online shopping';
?>

<!-- Page Header Section -->
<section class="page-header fixed-page-header">
    <div class="container">
        <h1>Search Results</h1>
        <p>
            <?php if ($resultCount > 0): ?>
            Found <?php echo $resultCount; ?> result<?php echo $resultCount !== 1 ? 's' : ''; ?> for
            '<?php echo htmlspecialchars($searchTerm); ?>'
            <?php else: ?>
            No products found matching '<?php echo htmlspecialchars($searchTerm); ?>'
            <?php endif; ?>
        </p>

        <?php if ($fallback): ?>
        <div class="search-fallback-notice">
            <p>Showing standard search results. AI-powered enhancements are temporarily unavailable.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Main content wrapper containing product grid -->
<div class="products-wrapper search-results-page-wrapper">

    <!-- AI Search Refinement Form -->
    <div class="search-refinement">
        <form action="/search" method="GET" class="search-bar" id="ai-search-form">
            <input type="text" name="q" id="ai-search-input" class="search-input"
                placeholder="Refine your search or start a new one..."
                value="<?php echo htmlspecialchars($searchTerm); ?>" required>
            <button type="submit" id="ai-search-button" class="search-button" aria-label="Search">
                <i class="fas fa-search"></i> <!-- Search Icon -->
            </button>
        </form>
    </div>

    <!-- "Did you mean" suggestion (if available) -->
    <div id="ai-suggestion-area">
        <?php if (isset($correctedTerm) && $correctedTerm !== $searchTerm): ?>
        <div class="ai-suggestion">
            Did you mean: <a href="/search?q=<?php echo urlencode($correctedTerm); ?>"
                class="ai-suggestion-link"><?php echo htmlspecialchars($correctedTerm); ?></a>?
        </div>
        <?php endif; ?>
    </div>

    <!-- AI Search Results Container -->
    <div id="ai-search-results">
        <!-- Product Display Area: Grid where products are shown -->
        <section id="product-display-area" class="products-grid" aria-live="polite">
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
                            if (!isset($prod['product_id'], $prod['name'], $prod['price'], $prod['image_path'], $prod['slug']))
                                continue; // Skip this iteration if data is missing
                        ?>
                <!-- Individual Product Card -->
                <article class="product-card">
                    <!-- Link to the individual product details page -->
                    <a href="/product/<?php echo htmlspecialchars($prod['product_id']); ?>/<?php echo htmlspecialchars($prod['slug']); ?>"
                        class="product-link">
                        <!-- Product Image -->
                        <img src="<?php echo htmlspecialchars($prod['image_path']); ?>"
                            alt="<?php echo htmlspecialchars($prod['name']); ?>" class="product-image">
                        <!-- Product Name -->
                        <h4 class="product-name"><?php echo htmlspecialchars($prod['name']); ?></h4>
                        <!-- Category Name (if available) -->
                        <?php if (isset($prod['category_name'])): ?>
                        <p class="product-category"><?php echo htmlspecialchars($prod['category_name']); ?></p>
                        <?php endif; ?>
                        <!-- Product Price -->
                        <p class="product-price">$<?php echo number_format($prod['price'], 2); ?></p>
                    </a>
                    <?php // Conditional button display based on login status 
                                ?>
                    <?php if ($logged_in): ?>
                    <!-- Add to Cart Button (for logged-in users, JS interaction) -->
                    <button class="add-to-cart-btn"
                        data-product-id="<?php echo htmlspecialchars($prod['product_id']); ?>">
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
            <!-- Message displayed if no products are found -->
            <div class="no-results">
                <p>No products found matching '<?php echo htmlspecialchars($searchTerm); ?>'. Please try a different
                    search
                    term.</p>
                <a href="/categories" class="browse-categories-btn">Browse Categories</a>
            </div>
            <?php endif; // End product display check 
            ?>
        </section> <!-- End product-display-area -->
    </div> <!-- End ai-search-results -->
</div> <!-- End products-wrapper -->

<!-- Hidden status message for screen readers -->
<div id="search-status-message" class="sr-only" aria-live="polite"></div>