<!--
    View file for the Shopping Cart page.
    Displays the items currently in the user's shopping cart.
    Allows users to modify quantities, remove items, clear the cart, or proceed to checkout.
    Handles both empty and non-empty cart states.

    Expected PHP Variables:
    - $is_empty (bool):      Indicates if the cart has any items.
    - $cart_items (array):   An array of items in the cart. Each item is an associative array
                              containing details like 'product_id', 'name', 'price', 'quantity',
                              'image', 'total_price'.
    - $total_price (float):  The total price of all items in the cart.

    JavaScript Interaction:
    - Buttons with classes like 'decrease-btn', 'increase-btn', 'remove-item-btn', 'clear-cart-btn'
      are expected to have associated JavaScript event listeners (likely in a separate JS file)
      to handle cart modifications via AJAX requests to update the cart state dynamically.
    - The quantity input ('quantity-input') might also trigger updates on change.
    - The total price element ('#cart-total-price') and item totals ('.product-total')
      are expected to be updated dynamically by JavaScript upon quantity changes or item removal.
-->
<div class="cart-container">
    <h1>Your Shopping Cart</h1>

    <?php // Check if the cart is empty using the $is_empty variable passed from the controller 
    ?>
    <?php if ($is_empty): ?>
        <!-- Empty Cart State: Displayed when there are no items in the cart -->
        <div class="empty-state-container">
            <div class="empty-state-illustration">
                <!-- Image indicating an empty cart -->
                <img src="/assets/images/cart/empty_shopping_cart.png" alt="Empty State Illustration">
            </div>
            <h2 class="empty-state-heading">Your cart is looking empty</h2>
            <p class="empty-state-text">Looks like you haven't added anything to your cart yet. Discover our fresh selection
                of products and start filling your basket!</p>
            <!-- Link to browse products -->
            <a href="/categories" class="btn btn-primary empty-state-cta">Browse Products</a>
        </div>
    <?php else: ?>
        <!-- Cart Items Display: Shown when the cart has items -->
        <div class="cart-items">
            <!-- Table structure for displaying cart items -->
            <table class="cart-table" id="cart-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php // Loop through each item in the $cart_items array 
                    ?>
                    <?php foreach ($cart_items as $item): ?>
                        <?php // Each table row represents a single cart item. 'data-product-id' is used by JS. 
                        ?>
                        <tr class="cart-item" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">
                            <!-- Product Information Column -->
                            <td class="product-info">
                                <!-- Product Thumbnail Image -->
                                <img src="/assets/images/products/<?php echo basename(htmlspecialchars($item['image'])); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-thumbnail">
                                <!-- Product Name -->
                                <div class="product-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                </div>
                            </td>
                            <!-- Product Price Column -->
                            <td class="product-price">$<?php echo number_format($item['price'], 2); ?></td>
                            <!-- Product Quantity Column with Controls -->
                            <td class="product-quantity">
                                <div class="quantity-controls">
                                    <!-- Decrease Quantity Button (JS interaction) -->
                                    <button class="quantity-btn decrease-btn"
                                        data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">-</button>
                                    <!-- Quantity Input Field (JS interaction) -->
                                    <input type="number" class="quantity-input"
                                        value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" max="99"
                                        data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>"
                                        aria-label="Item quantity">
                                    <!-- Increase Quantity Button (JS interaction) -->
                                    <button class="quantity-btn increase-btn"
                                        data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">+</button>
                                </div>
                            </td>
                            <!-- Actions Column (Remove Item) -->
                            <td class="product-actions">
                                <!-- Remove Item Button (JS interaction) -->
                                <button class="remove-item-btn"
                                    data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">
                                    <i class="fas fa-trash"></i> <!-- Trash icon -->
                                </button>
                            </td>
                            <!-- Item Total Price Column (Dynamically updated by JS) -->
                            <td class="product-total">$<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; // End of loop through cart items 
                    ?>
                </tbody>
                <tfoot>
                    <!-- Cart Total Row -->
                    <tr class="cart-total">
                        <td colspan="4">Total</td>
                        <!-- Grand Total Price (Dynamically updated by JS) -->
                        <td id="cart-total-price">$<?php echo number_format($total_price, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
            <!-- Cart Actions Section (below the table) -->
            <div class="cart-actions">
                <!-- Link to continue shopping -->
                <a href="/categories" class="continue-shopping-btn">Continue Shopping</a>
                <!-- Right-aligned actions: Clear Cart and Checkout -->
                <div class="cart-actions-right">
                    <!-- Clear Cart Button (JS interaction) -->
                    <button id="clear-cart-btn" class="clear-cart-btn">Clear Cart</button>
                    <!-- Proceed to Checkout Link -->
                    <a href="/order" class="checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
        </div> <!-- End of cart-items -->
    <?php endif; // End of check for empty cart 
    ?>
</div> <!-- End of cart-container -->