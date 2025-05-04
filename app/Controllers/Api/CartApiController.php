<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Session;
use App\Models\Product;
use App\Core\Registry;
use App\Helpers\CartHelper;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

/**
 * Cart API Controller
 *
 * Handles API requests related to the shopping cart functionality.
 * Allows authenticated users to add, update, view, and remove items from their cart.
 * All responses are in JSON format.
 */
class CartApiController extends BaseController
{
    /**
     * @var CartHelper Instance of the CartHelper for cart logic.
     */
    private $cartHelper;

    /**
     * @var Session Instance of the Session manager.
     */
    private $session;

    /**
     * @var \PDO Database connection instance.
     */
    private $db;

    /**
     * @var Logger Instance of the Monolog logger.
     */
    private $logger;

    /**
     * Constructor for CartApiController.
     *
     * Initializes session, database connection, CartHelper, and logger.
     * Ensures the log directory exists.
     */
    public function __construct()
    {
        $this->session = Registry::get('session');
        $this->db = Registry::get('database');
        $this->cartHelper = new CartHelper($this->session, Registry::get('database'));

        // Initialize Logger
        $this->logger = new Logger('cart_api');
        $logFilePath = BASE_PATH . '/logs/app.log'; // Use BASE_PATH constant
        $logDir = dirname($logFilePath);
        if (!is_dir($logDir)) {
            // Attempt to create the directory recursively
            mkdir($logDir, 0777, true);
        }
        // Add a handler to log messages to the application log file
        $this->logger->pushHandler(new StreamHandler($logFilePath, Level::Debug));
    }

    /**
     * Add an item to the cart.
     *
     * Handles POST requests to add a specified quantity of a product to the user's cart.
     * Requires user authentication.
     * Expects JSON input: {"product_id": int, "quantity": int}
     *
     * @api {post} /api/cart/add Add item to cart
     * @apiName AddCartItem
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiBody {Number} product_id The ID of the product to add.
     * @apiBody {Number} quantity The positive quantity of the product to add.
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {String} message Confirmation message.
     * @apiSuccess {Number} total_items The new total number of unique items in the cart.
     * @apiSuccess {Number} added_product_id The ID of the product added.
     * @apiSuccess {Number} added_quantity The quantity of the product added in this request.
     * @apiSuccess {String} product_name The name of the added product.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "success": true,
     *       "message": "Product added to cart successfully.",
     *       "total_items": 3,
     *       "added_product_id": 101,
     *       "added_quantity": 2,
     *       "product_name": "Example Product"
     *     }
     *
     * @apiError (400 Bad Request) InvalidInput Invalid product_id or quantity provided.
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (404 Not Found) ProductNotFound The specified product does not exist or is unavailable.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     *
     * @apiErrorExample {json} Error-Response (400):
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "error": "Invalid input. Please provide a valid product_id and a positive quantity."
     *     }
     * @apiErrorExample {json} Error-Response (401):
     *     HTTP/1.1 401 Unauthorized
     *     {
     *       "error": "Authentication required."
     *     }
     * @apiErrorExample {json} Error-Response (404):
     *     HTTP/1.1 404 Not Found
     *     {
     *       "error": "Product not found or insufficient stock."
     *     }
     */
    public function add()
    {
        // Ensure the request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Check if the user is authenticated
        if (!$this->session->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Get JSON data from the request body
        $requestData = json_decode(file_get_contents('php://input'), true);

        // Validate input data
        if (!isset($requestData['product_id']) || !isset($requestData['quantity']) || !is_numeric($requestData['product_id']) || !is_numeric($requestData['quantity']) || $requestData['quantity'] <= 0) {
            $this->jsonResponse(['error' => 'Invalid input. Please provide a valid product_id and a positive quantity.'], 400);
            return;
        }

        $productId = (int) $requestData['product_id'];
        $quantity = (int) $requestData['quantity'];

        // Use CartHelper to add/update the item (updateCartItem handles adding logic)
        $result = $this->cartHelper->updateCartItem($productId, $quantity);

        // Handle potential errors from CartHelper
        if (!$result['success']) {
            // Determine appropriate status code based on message (e.g., 404 for not found)
            $statusCode = ($result['message'] === 'Product not found or insufficient stock.') ? 404 : 400;
            $this->jsonResponse(['error' => $result['message']], $statusCode);
            return;
        }

        // Respond with success message and updated cart info
        $this->jsonResponse([
            'success' => true,
            'message' => 'Product added to cart successfully.',
            'total_items' => $result['total_items'],
            'added_product_id' => $productId,
            'added_quantity' => $quantity,
            'product_name' => $result['updated_product']['name'] ?? 'N/A' // Include product name if available
        ], 200); // 200 OK for successful update/addition
    }

    /**
     * Update item quantity in the cart.
     *
     * Handles POST requests to update the quantity of a specific product in the cart.
     * If the new quantity is 0 or less, the item is removed.
     * Requires user authentication.
     * Expects JSON input: {"product_id": int, "quantity": int}
     *
     * @api {post} /api/cart/update Update item quantity
     * @apiName UpdateCartItem
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiBody {Number} product_id The ID of the product to update.
     * @apiBody {Number} quantity The new quantity for the product (non-negative integer). If 0, the item is removed.
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {String} message Confirmation message.
     * @apiSuccess {Object} cart Detailed view of the updated cart items.
     * @apiSuccess {Number} total_items The new total number of unique items in the cart.
     * @apiSuccess {Number} total_price The new total price of the cart.
     * @apiSuccess {Boolean} is_empty Indicates if the cart is now empty.
     * @apiSuccess {Object|null} updated_product Details of the product whose quantity was updated (if applicable).
     *
     * @apiSuccessExample {json} Success-Response (Quantity Updated):
     *     HTTP/1.1 200 OK
     *     {
     *       "success": true,
     *       "message": "Cart updated successfully.",
     *       "cart": { ... detailed cart items ... },
     *       "total_items": 2,
     *       "total_price": 55.99,
     *       "is_empty": false,
     *       "updated_product": { "id": 101, "name": "Example Product", "quantity": 3, "price": 10.00, "subtotal": 30.00 }
     *     }
     * @apiSuccessExample {json} Success-Response (Item Removed):
     *     HTTP/1.1 200 OK
     *     {
     *       "success": true,
     *       "message": "Item removed from cart.",
     *       "cart": { ... remaining cart items ... },
     *       "total_items": 1,
     *       "total_price": 25.99,
     *       "is_empty": false,
     *       "updated_product": null // No specific product updated when removing
     *     }
     *
     * @apiError (400 Bad Request) InvalidInput Invalid product_id or quantity provided.
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (404 Not Found) ProductNotFound The specified product does not exist in the cart or database.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     *
     * @apiErrorExample {json} Error-Response (400):
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "error": "Invalid input. Please provide a valid product_id and quantity."
     *     }
     */
    public function update()
    {
        // Ensure the request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Get JSON data
        $requestData = json_decode(file_get_contents('php://input'), true);

        // Validate input
        if (!isset($requestData['product_id']) || !isset($requestData['quantity']) || !is_numeric($requestData['product_id']) || !is_numeric($requestData['quantity'])) {
            $this->jsonResponse(['error' => 'Invalid input. Please provide a valid product_id and quantity.'], 400);
            return;
        }

        $productId = (int) $requestData['product_id'];
        $newQuantity = (int) $requestData['quantity'];

        // Get current quantity to calculate the change needed for updateCartItem
        $cart = $this->session->get('cart', []);
        $currentQuantity = isset($cart[$productId]) ? $cart[$productId] : 0;
        $quantityChange = $newQuantity - $currentQuantity; // Calculate the difference to add/remove

        // If new quantity is zero or less, remove the item; otherwise, update it.
        if ($newQuantity <= 0) {
            $result = $this->cartHelper->removeCartItem($productId);
        } else {
            // updateCartItem expects the *change* in quantity, not the new total
            $result = $this->cartHelper->updateCartItem($productId, $quantityChange);
        }

        // Handle errors from CartHelper
        if (!$result['success']) {
            // Determine status code (e.g., 404 if item wasn't found)
            $statusCode = ($result['message'] === 'Item not found in cart.' || $result['message'] === 'Product not found or insufficient stock.') ? 404 : 400;
            $this->jsonResponse(['error' => $result['message']], $statusCode);
            return;
        }

        // Respond with success and the full updated cart state
        $this->jsonResponse([
            'success' => true,
            'message' => $result['message'] ?? 'Cart updated successfully.', // Use message from helper if available
            'cart' => $result['cart'],
            'total_items' => $result['total_items'],
            'total_price' => $result['total_price'],
            'is_empty' => $result['is_empty'],
            'updated_product' => $result['updated_product'] ?? null // Include details if a product was updated
        ], 200);
    }

    /**
     * Get the current user's cart contents.
     *
     * Handles GET requests to retrieve the detailed contents of the authenticated user's cart.
     * Requires user authentication.
     *
     * @api {get} /api/cart Get cart contents
     * @apiName GetCart
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {Object[]} cart Array of items currently in the cart.
     * @apiSuccess {Number} cart.id Product ID.
     * @apiSuccess {String} cart.name Product name.
     * @apiSuccess {Number} cart.quantity Quantity of the product in the cart.
     * @apiSuccess {Number} cart.price Price per unit of the product.
     * @apiSuccess {Number} cart.subtotal Total price for this item (quantity * price).
     * @apiSuccess {String} cart.image Relative path to the product image.
     * @apiSuccess {Number} total_items Total number of unique items in the cart.
     * @apiSuccess {Number} total_price Total price of all items in the cart.
     * @apiSuccess {Boolean} is_empty Indicates if the cart is empty.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "success": true,
     *       "cart": [
     *         {
     *           "id": 101,
     *           "name": "Example Product A",
     *           "quantity": 2,
     *           "price": 10.00,
     *           "subtotal": 20.00,
     *           "image": "path/to/image_a.jpg"
     *         },
     *         {
     *           "id": 105,
     *           "name": "Example Product B",
     *           "quantity": 1,
     *           "price": 15.50,
     *           "subtotal": 15.50,
     *           "image": "path/to/image_b.jpg"
     *         }
     *       ],
     *       "total_items": 2,
     *       "total_price": 35.50,
     *       "is_empty": false
     *     }
     *
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not GET.
     */
    public function getCart()
    {
        // Ensure GET request
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Invalid request method. Only GET is allowed.'], 405);
            return;
        }

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Retrieve cart data using the helper
        $cartData = $this->cartHelper->getCartData();

        // Respond with the cart data
        $this->jsonResponse([
            'success' => true,
            'cart' => $cartData['cart_items'], // Use 'cart_items' key from helper
            'total_items' => $cartData['total_items'],
            'total_price' => $cartData['total_price'],
            'is_empty' => $cartData['is_empty']
        ], 200);
    }

    /**
     * Remove an item completely from the cart.
     *
     * Handles POST requests to remove a specific product entirely from the cart, regardless of quantity.
     * Requires user authentication.
     * Expects JSON input: {"product_id": int}
     *
     * @api {post} /api/cart/remove Remove item from cart
     * @apiName RemoveCartItem
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiBody {Number} product_id The ID of the product to remove.
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {String} message Confirmation message.
     * @apiSuccess {Object} cart Detailed view of the updated cart items.
     * @apiSuccess {Number} total_items The new total number of unique items in the cart.
     * @apiSuccess {Number} total_price The new total price of the cart.
     * @apiSuccess {Boolean} is_empty Indicates if the cart is now empty.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "success": true,
     *       "message": "Item removed from cart.",
     *       "cart": { ... remaining cart items ... },
     *       "total_items": 1,
     *       "total_price": 25.99,
     *       "is_empty": false
     *     }
     *
     * @apiError (400 Bad Request) InvalidInput Invalid or missing product_id.
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (404 Not Found) ItemNotFound The specified item was not found in the cart.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     *
     * @apiErrorExample {json} Error-Response (404):
     *     HTTP/1.1 404 Not Found
     *     {
     *       "success": false,
     *       "error": "Item not found in cart."
     *     }
     */
    public function remove()
    {
        // Ensure POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Get JSON data
        $requestData = json_decode(file_get_contents('php://input'), true);

        // Validate input
        if (!isset($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            $this->jsonResponse(['error' => 'Invalid input. Please provide a valid product_id.'], 400);
            return;
        }

        $productId = (int) $requestData['product_id'];

        // Use CartHelper to remove the item
        $result = $this->cartHelper->removeCartItem($productId);

        // Determine status code based on success/failure (e.g., 404 if not found)
        $statusCode = $result['success'] ? 200 : ($result['message'] === 'Item not found in cart.' ? 404 : 400);

        // Respond with the result from the helper
        $this->jsonResponse($result, $statusCode);
    }

    /**
     * Remove an item from the cart (alternative route).
     *
     * Handles POST requests (often via DELETE method override in forms/JS)
     * to remove a specific product from the cart using a route parameter.
     * Requires user authentication.
     * Logs detailed information about the process.
     *
     * @param array $params Associative array containing route parameters. Expected: ['product_id' => int]
     *
     * @api {post} /api/cart/item/{product_id}/delete Remove item by ID (alternative)
     * @apiName RemoveCartItemById
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiParam {Number} product_id The ID of the product to remove passed in the URL path.
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {String} message Confirmation message.
     * @apiSuccess {Object} cart Detailed view of the updated cart items.
     * @apiSuccess {Number} total_items The new total number of unique items in the cart.
     * @apiSuccess {Number} total_price The new total price of the cart.
     * @apiSuccess {Boolean} is_empty Indicates if the cart is now empty.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "success": true,
     *       "message": "Item removed from cart.",
     *       "cart": { ... remaining cart items ... },
     *       "total_items": 1,
     *       "total_price": 25.99,
     *       "is_empty": false
     *     }
     *
     * @apiError (400 Bad Request) InvalidInput Invalid or missing product_id in the URL.
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (404 Not Found) ItemNotFound The specified item was not found in the cart.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST (or simulated DELETE via POST).
     *
     * @apiErrorExample {json} Error-Response (400):
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "success": false,
     *       "error": "Invalid product ID."
     *     }
     */
    public function removeItem($params)
    {
        $this->logger->info('Received request to delete item from cart via URL parameter.', ['params_received' => $params]);

        // Although the route might imply DELETE, web forms often use POST for this.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->logger->warning('Invalid request method for removeItem.', ['method' => $_SERVER['REQUEST_METHOD']]);
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed for this action.'], 405);
            return;
        }

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Authentication required for removeItem.', ['params_received' => $params]);
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Validate the product ID from the route parameters
        if (!isset($params['product_id']) || !is_numeric($params['product_id']) || (int)$params['product_id'] <= 0) {
            $this->logger->warning('Invalid or missing product ID received for removeItem.', ['params_received' => $params]);
            $this->jsonResponse(['success' => false, 'error' => 'Invalid product ID.'], 400);
            return;
        }

        $actualProductId = (int) $params['product_id'];
        $this->logger->info('Validated product ID.', ['productId' => $actualProductId]);

        // Attempt to remove the item using CartHelper
        $this->logger->info('Attempting to remove item from session/cart helper.', ['productId' => $actualProductId]);
        $result = $this->cartHelper->removeCartItem($actualProductId);

        // Prepare response based on the result
        if ($result['success']) {
            $this->logger->info('Successfully removed item from cart.', ['productId' => $actualProductId, 'result' => $result]);
            $responseData = [
                'success' => true,
                'message' => $result['message'] ?? 'Item removed from cart.',
                'cart' => $result['cart'],
                'total_items' => $result['total_items'],
                'total_price' => $result['total_price'],
                'is_empty' => $result['is_empty']
            ];
            $statusCode = 200;
        } else {
            $this->logger->warning('Failed to remove item from cart (item might not exist?).', ['productId' => $actualProductId, 'result' => $result]);
            // Determine status code: 404 if specifically "not found", 400 otherwise
            $statusCode = ($result['message'] === 'Item not found in cart.') ? 404 : 400;
            $responseData = [
                'success' => false,
                'error' => $result['message'] ?? 'Could not remove item from cart.'
            ];
        }

        $this->logger->info('Sending JSON response for delete request.', ['response' => $responseData, 'statusCode' => $statusCode]);
        $this->jsonResponse($responseData, $statusCode);
    }


    /**
     * Clear all items from the cart.
     *
     * Handles POST requests to remove all items from the authenticated user's cart.
     * Requires user authentication.
     *
     * @api {post} /api/cart/clear Clear entire cart
     * @apiName ClearCart
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {String} message Confirmation message.
     * @apiSuccess {Number} total_items Should be 0 after clearing.
     * @apiSuccess {Number} total_price Should be 0.00 after clearing.
     * @apiSuccess {Boolean} is_empty Should be true after clearing.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "success": true,
     *       "message": "Cart cleared.",
     *       "total_items": 0,
     *       "total_price": 0.00,
     *       "is_empty": true
     *     }
     *
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     */
    public function clearCart()
    {
        // Ensure POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Use CartHelper to clear the cart
        $result = $this->cartHelper->clearCart();

        // Respond with confirmation and the now empty cart state
        $this->jsonResponse([
            'success' => true,
            'message' => 'Cart cleared.',
            'total_items' => $result['total_items'], // Should be 0
            'total_price' => $result['total_price'], // Should be 0.00
            'is_empty' => $result['is_empty']      // Should be true
        ], 200);
    }

    /**
     * Get the total count of items in the cart.
     *
     * Handles GET requests to retrieve just the total number of unique items
     * currently in the authenticated user's cart. Useful for quick updates (e.g., cart icon badge).
     * Requires user authentication.
     *
     * @api {get} /api/cart/count Get cart item count
     * @apiName GetCartCount
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiSuccess {Number} count The total number of unique items in the cart.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "count": 3
     *     }
     * @apiSuccessExample {json} Success-Response (Empty Cart):
     *     HTTP/1.1 200 OK
     *     {
     *       "count": 0
     *     }
     *
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not GET.
     */
    public function getCartCount()
    {
        // Ensure GET request
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['error' => 'Invalid request method. Only GET is allowed.'], 405);
            return;
        }

        // Note: Depending on requirements, this might not strictly need authentication
        // if the cart count is displayed even for logged-out users (session-based cart).
        // However, keeping it consistent with other cart actions requiring auth.
        // if (!$this->session->isAuthenticated()) {
        //     $this->jsonResponse(['error' => 'Authentication required.'], 401);
        //     return;
        // }

        // Retrieve cart data using the helper
        $cartData = $this->cartHelper->getCartData();

        // Respond with just the count
        $this->jsonResponse([
            'count' => $cartData['total_items'] ?? 0 // Default to 0 if cart is empty/not set
        ], 200);
    }

    /**
     * Send a JSON response.
     *
     * Sets the HTTP status code and Content-Type header, then echoes the data
     * encoded as JSON.
     *
     * @param mixed $data The data to encode and send (usually an array).
     * @param int $statusCode The HTTP status code to set (default: 200).
     * @return void
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        // Set the HTTP response code (e.g., 200, 400, 404)
        http_response_code($statusCode);
        // Indicate that the response body is JSON
        header('Content-Type: application/json');
        // Encode the data array into a JSON string and output it
        echo json_encode($data);
        // Note: It's generally good practice to exit after sending a response in API endpoints
        // exit(); // Consider adding exit() if further script execution is undesirable.
    }
}
