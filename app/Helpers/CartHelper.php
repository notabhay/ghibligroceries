<?php

namespace App\Helpers;

use App\Core\Session;
use App\Models\Product;
use App\Core\Database;

/**
 * Class CartHelper
 *
 * Manages the shopping cart functionality using the session for storage.
 * Provides methods to add, update, remove items, clear the cart,
 * and retrieve detailed cart data including product information and totals.
 */
class CartHelper
{
    /**
     * @var Session The session management object.
     */
    private $session;

    /**
     * @var Database The database connection object.
     */
    private $db;

    /**
     * @var Product The product model instance for fetching product details.
     */
    private $productModel;

    /**
     * Constructor for CartHelper.
     *
     * Initializes the helper with session and database objects,
     * and creates an instance of the Product model.
     *
     * @param Session $session The session management object.
     * @param Database $db The database connection object.
     */
    public function __construct(Session $session, Database $db)
    {
        $this->session = $session;
        $this->db = $db;
        // Instantiate the Product model to interact with product data.
        $this->productModel = new Product($db);
    }

    /**
     * Retrieves the current state of the shopping cart.
     *
     * Fetches product details from the database for items in the cart (stored in session),
     * calculates the total price and total number of items.
     *
     * @return array An associative array containing:
     *               - 'cart_items' (array): List of items in the cart with details (product_id, name, price, image, quantity, total_price).
     *               - 'total_price' (float): The total price of all items in the cart.
     *               - 'total_items' (int): The total number of individual items in the cart.
     *               - 'is_empty' (bool): True if the cart is empty, false otherwise.
     */
    public function getCartData(): array
    {
        // Retrieve the cart array (productId => quantity) from the session.
        $cart = $this->session->get('cart', []);

        // If the cart is empty, return default empty values.
        if (empty($cart)) {
            return [
                'cart_items' => [],
                'total_price' => 0.0, // Use float for price
                'total_items' => 0,
                'is_empty' => true
            ];
        }

        // Get the product IDs from the cart keys.
        $productIds = array_keys($cart);

        // Fetch product details for all items in the cart in a single query.
        // Assumes findMultipleByIds returns an associative array keyed by product ID.
        $products = $this->productModel->findMultipleByIds($productIds);

        $cartItems = [];
        $totalPrice = 0.0; // Use float for price
        $totalItems = 0;

        // Iterate through the cart items stored in the session.
        foreach ($cart as $productId => $quantity) {
            // Check if the product details were successfully fetched.
            if (isset($products[$productId])) {
                $product = $products[$productId];
                // Calculate the total price for this line item.
                $itemPrice = (float)$product['price'] * (int)$quantity; // Cast for calculation
                // Add to the overall cart total price.
                $totalPrice += $itemPrice;
                // Add to the overall cart total item count.
                $totalItems += (int)$quantity; // Cast quantity to int

                // Add formatted item details to the cartItems array.
                $cartItems[] = [
                    'product_id' => $productId,
                    'name' => $product['name'],
                    'price' => (float)$product['price'], // Cast price to float
                    'image' => $product['image'] ?? 'default_image.png', // Provide default image if missing
                    'quantity' => (int)$quantity, // Cast quantity to int
                    'total_price' => $itemPrice
                ];
            } else {
                // If a product ID in the cart session doesn't exist in the DB,
                // it might be good to log this or potentially remove it from the cart here.
                // For now, we just skip it.
                error_log("CartHelper: Product ID {$productId} found in cart session but not in database.");
            }
        }

        // Return the structured cart data.
        return [
            'cart_items' => $cartItems,
            'total_price' => $totalPrice,
            'total_items' => $totalItems,
            'is_empty' => ($totalItems === 0) // Recalculate emptiness based on actual items processed
        ];
    }

    /**
     * Updates the quantity of a specific item in the cart.
     *
     * Adds the specified quantity to the existing quantity. If the resulting quantity
     * is zero or less, the item is removed from the cart.
     * Returns the updated cart state.
     *
     * @param int $productId The ID of the product to update.
     * @param int $quantity The quantity to add (can be negative to decrease).
     * @return array An associative array indicating success or failure, along with the updated cart data:
     *               - 'success' (bool): True on success, false if product not found.
     *               - 'message' (string): Status message.
     *               - 'cart' (array): Updated list of cart items.
     *               - 'total_items' (int): Updated total item count.
     *               - 'total_price' (float): Updated total price.
     *               - 'is_empty' (bool): Updated cart empty status.
     *               - 'updated_product' (array|null): Details of the updated product line item, or null if removed.
     */
    public function updateCartItem(int $productId, int $quantity): array
    {
        // Find the product to ensure it exists and get its details.
        $product = $this->productModel->findById($productId);
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found.'
            ];
        }

        // Get the current cart from the session.
        $cart = $this->session->get('cart', []);

        // Get the current quantity of the item in the cart, default to 0 if not present.
        $currentQuantity = isset($cart[$productId]) ? (int)$cart[$productId] : 0;

        // Calculate the new quantity.
        $newQuantity = $currentQuantity + $quantity;

        // If the new quantity is 0 or less, remove the item from the cart.
        if ($newQuantity <= 0) {
            if (isset($cart[$productId])) {
                unset($cart[$productId]);
            }
            $newQuantity = 0; // Ensure new quantity is reported as 0
        } else {
            // Otherwise, update the cart with the new quantity.
            $cart[$productId] = $newQuantity;
        }

        // Save the updated cart back to the session.
        $this->session->set('cart', $cart);

        // Get the latest cart data after the update.
        $cartData = $this->getCartData();

        // Prepare details about the specific item that was updated.
        $updatedProductData = null;
        if ($newQuantity > 0) {
            $updatedProductData = [
                'product_id' => $productId,
                'name' => $product['name'],
                'new_quantity' => $newQuantity,
                'price' => (float)$product['price'],
                'new_total' => (float)$product['price'] * $newQuantity
            ];
        } elseif ($currentQuantity > 0) { // If item was removed (newQuantity is 0 but was > 0 before)
            $updatedProductData = [
                'product_id' => $productId,
                'name' => $product['name'],
                'new_quantity' => 0,
                'price' => (float)$product['price'],
                'new_total' => 0.0
            ];
        }


        // Return success response with updated cart state.
        return [
            'success' => true,
            'message' => 'Cart updated successfully.',
            'cart' => $cartData['cart_items'],
            'total_items' => $cartData['total_items'],
            'total_price' => $cartData['total_price'],
            'is_empty' => $cartData['is_empty'],
            'updated_product' => $updatedProductData
        ];
    }

    /**
     * Removes an item completely from the shopping cart.
     *
     * @param int $productId The ID of the product to remove.
     * @return array An associative array with the updated cart state:
     *               - 'success' (bool): Always true in this implementation.
     *               - 'message' (string): Status message.
     *               - 'cart' (array): Updated list of cart items.
     *               - 'total_items' (int): Updated total item count.
     *               - 'total_price' (float): Updated total price.
     *               - 'is_empty' (bool): Updated cart empty status.
     */
    public function removeCartItem(int $productId): array
    {
        // Get the current cart.
        $cart = $this->session->get('cart', []);

        // If the item exists in the cart, remove it.
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            // Save the modified cart back to the session.
            $this->session->set('cart', $cart);
        }

        // Get the latest cart data after removal.
        $cartData = $this->getCartData();

        // Return success response with updated cart state.
        return [
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart' => $cartData['cart_items'],
            'total_items' => $cartData['total_items'],
            'total_price' => $cartData['total_price'],
            'is_empty' => $cartData['is_empty']
        ];
    }

    /**
     * Clears all items from the shopping cart.
     *
     * Removes the 'cart' key from the session.
     *
     * @return array An associative array confirming the cart is cleared:
     *               - 'success' (bool): Always true.
     *               - 'message' (string): Status message.
     *               - 'cart_items' (array): Empty array.
     *               - 'total_price' (float): 0.0.
     *               - 'total_items' (int): 0.
     *               - 'is_empty' (bool): True.
     */
    public function clearCart(): array
    {
        // Set the cart in the session to an empty array.
        $this->session->set('cart', []);

        // Return a response indicating the cart is now empty.
        return [
            'success' => true,
            'message' => 'Cart cleared.',
            'cart_items' => [],
            'total_price' => 0.0,
            'total_items' => 0,
            'is_empty' => true
        ];
    }
}
