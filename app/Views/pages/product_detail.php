<?php
// The BaseController's view() method handles the layout.
// Variables like $page_title, $meta_description, etc., are passed
// from the controller to the view() method, which then makes them
// available to the layout. So, this $this->layout() call is not needed
// and was causing the "Call to undefined method" error.

// $this->layout('layouts/default', [
//     'page_title' => $page_title ?? 'Product Details',
//     'meta_description' => $meta_description ?? 'View details about our product.',
//     'meta_keywords' => $meta_keywords ?? 'product, details, ghibligroceries',
//     'additional_css_files' => $additional_css_files ?? [],
//     'additional_js_files' => $additional_js_files ?? [],
//     'logged_in' => $logged_in ?? false,
//     'user_name' => $user_name ?? '',
//     'cart_item_count' => $cart_item_count ?? 0
// ]);
?>

<div class="product-detail-container content-spacing">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="product-breadcrumbs">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <?php foreach ($category_breadcrumbs as $breadcrumb): ?>
            <li class="breadcrumb-item">
                <a
                    href="<?php echo htmlspecialchars($breadcrumb['link']); ?>"><?php echo htmlspecialchars($breadcrumb['name']); ?></a>
            </li>
            <?php endforeach; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?>
            </li>
        </ol>
    </nav>

    <div class="product-detail-main-content">
        <!-- Left Column: Product Image -->
        <div class="product-image-column">
            <div class="product-image-main-wrapper">
                <img src="/<?php echo htmlspecialchars($product['image_path']); ?>"
                    alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-detail-image">
            </div>
        </div>

        <!-- Right Column: Product Info -->
        <div class="product-info-column">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="product-detail-price">$<?php echo number_format($product['price'], 2); ?></p>
            <p class="product-detail-category">
                Category: <a
                    href="/categories?filter=<?php echo urlencode($product['category_name']); ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
            </p>
            <div class="product-stock <?php echo htmlspecialchars($stock_status_class); ?>">
                <?php echo htmlspecialchars($stock_status_text); ?>
            </div>

            <div class="product-description-full">
                <h2>Description</h2>
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>

            <!-- Add to Cart Form -->
            <form class="add-to-cart-form-detail"
                data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="quantity-controls product-detail-quantity">
                    <label for="quantity_<?php echo $product['product_id']; ?>">Quantity:</label>
                    <button type="button" class="quantity-btn decrease-btn"
                        data-product-id="<?php echo $product['product_id']; ?>">-</button>
                    <input type="number" id="quantity_<?php echo $product['product_id']; ?>" name="quantity"
                        class="quantity-input" value="1" min="1"
                        max="<?php echo $product['stock_quantity'] > 0 ? $product['stock_quantity'] : 1; ?>"
                        <?php echo !$can_add_to_cart ? 'disabled' : ''; ?>>
                    <button type="button" class="quantity-btn increase-btn"
                        data-product-id="<?php echo $product['product_id']; ?>">+</button>
                </div>
                <?php if ($logged_in): ?>
                <button type="submit" class="add-to-cart-btn"
                    data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>"
                    <?php echo !$can_add_to_cart ? 'disabled' : ''; ?>>
                    Add to Cart
                </button>
                <?php else: ?>
                <a href="/login" class="login-to-purchase-btn">
                    Login to Purchase
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>