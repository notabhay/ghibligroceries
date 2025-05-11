<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Session;
use App\Models\Product;
use App\Core\Registry;
use App\Helpers\CartHelper;
use Monolog\Logger; // Add this line


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
     * @var \Monolog\Logger Instance of the Monolog logger.
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
        $this->logger = Registry::get('logger');
    }

    /**
     * Add an item to the cart.
     *
     * Handles POST requests to add a specified quantity of a product to the user's cart.
     * Requires user authentication.
     * Expects JSON input: {"product_id": int, "quantity": int, "csrf_token": string}
     *
     * @api {post} /api/cart/add Add item to cart
     * @apiName AddCartItem
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiBody {Number} product_id The ID of the product to add.
     * @apiBody {Number} quantity The positive quantity of the product to add.
     * @apiBody {String} csrf_token The CSRF protection token.
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
     * @apiError (403 Forbidden) InvalidCsrfToken The provided CSRF token was invalid.
     * @apiError (404 Not Found) ProductNotFound The specified product does not exist or is unavailable.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     *
     * @apiErrorExample {json} Error-Response (403):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "Invalid security token."
     *     }
     */
    public function add()
    {
        // Ensure the request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->logger->warning('Cart add: Invalid request method.', ['method' => $_SERVER['REQUEST_METHOD']]);
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Get JSON data from the request body
        $requestData = json_decode(file_get_contents('php://input'), true);
        $this->logger->info('Cart add request received.', [
            'product_id' => $requestData['product_id'] ?? 'N/A',
            'quantity' => $requestData['quantity'] ?? 'N/A',
            'csrf_token_present' => isset($requestData['csrf_token'])
        ]);

        // Check if the user is authenticated
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Cart add: Authentication required.', ['product_id' => $requestData['product_id'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Validate CSRF token
        if (!isset($requestData['csrf_token']) || !$this->session->validateCsrfToken($requestData['csrf_token'])) {
            $this->logger->warning('Cart add: Invalid CSRF token.', ['submitted_token' => $requestData['csrf_token'] ?? 'N/A', 'session_token_hash' => $this->session->getCsrfToken() ? hash('sha256', $this->session->getCsrfToken()) : 'N/A']);
            $this->jsonResponse(['error' => 'Invalid security token.'], 403);
            return;
        }

        // Validate input data
        if (!isset($requestData['product_id']) || !isset($requestData['quantity']) || !is_numeric($requestData['product_id']) || !is_numeric($requestData['quantity']) || $requestData['quantity'] <= 0) {
            $this->logger->warning('Cart add: Invalid input.', ['product_id' => $requestData['product_id'] ?? 'N/A', 'quantity' => $requestData['quantity'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Invalid input. Please provide a valid product_id and a positive quantity.'], 400);
            return;
        }

        $productId = (int) $requestData['product_id'];
        $quantity = (int) $requestData['quantity'];
        $this->logger->info('Cart add: Input validated.', ['product_id' => $productId, 'quantity' => $quantity]);

        // Use CartHelper to add/update the item
        $this->logger->info('Cart add: Calling CartHelper::setCartItemQuantity.', ['product_id' => $productId, 'quantity' => $quantity]);
        $result = $this->cartHelper->setCartItemQuantity($productId, $quantity);

        // Handle potential errors from CartHelper
        if (!$result['success']) {
            $statusCode = ($result['message'] === 'Product not found or insufficient stock.') ? 404 : 400;
            $this->logger->warning('Cart add: CartHelper failed to set item quantity.', ['product_id' => $productId, 'quantity' => $quantity, 'error' => $result['message'], 'status_code' => $statusCode]);
            $this->jsonResponse(['error' => $result['message']], $statusCode);
            return;
        }

        // Respond with success message and updated cart info
        $responseData = [
            'success' => true,
            'message' => $result['message'] ?? 'Product processing complete.',
            'total_items' => $result['total_items'],
            'added_product_id' => $productId,
            'added_quantity' => $quantity,
            'product_name' => $result['updated_product']['name'] ?? 'N/A'
        ];
        $this->logger->info('Cart add: Successfully processed item.', ['product_id' => $productId, 'new_total_items' => $result['total_items']]);
        $this->logger->info('Cart add: Sending success response.', ['status_code' => 200, 'product_id' => $productId, 'response_summary' => ['total_items' => $result['total_items']]]);
        $this->jsonResponse($responseData, 200);
    }

    /**
     * Update item quantity in the cart.
     *
     * Handles POST requests to update the quantity of a specific product in the cart.
     * If the new quantity is 0 or less, the item is removed.
     * Requires user authentication.
     * Expects JSON input: {"product_id": int, "quantity": int, "csrf_token": string}
     *
     * @api {post} /api/cart/update Update item quantity
     * @apiName UpdateCartItem
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiBody {Number} product_id The ID of the product to update.
     * @apiBody {Number} quantity The new quantity for the product (non-negative integer). If 0, the item is removed.
     * @apiBody {String} csrf_token The CSRF protection token.
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
     * @apiError (403 Forbidden) InvalidCsrfToken The provided CSRF token was invalid.
     * @apiError (404 Not Found) ProductNotFound The specified product does not exist in the cart or database.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     *
     * @apiErrorExample {json} Error-Response (403):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "Invalid security token."
     *     }
     */
    public function update()
    {
        // Ensure the request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->logger->warning('Cart update: Invalid request method.', ['method' => $_SERVER['REQUEST_METHOD']]);
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Get JSON data
        $requestData = json_decode(file_get_contents('php://input'), true);
        $this->logger->info('Cart update request received.', [
            'product_id' => $requestData['product_id'] ?? 'N/A',
            'quantity' => $requestData['quantity'] ?? 'N/A',
            'csrf_token_present' => isset($requestData['csrf_token'])
        ]);

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Cart update: Authentication required.', ['product_id' => $requestData['product_id'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Validate CSRF token
        if (!isset($requestData['csrf_token']) || !$this->session->validateCsrfToken($requestData['csrf_token'])) {
            $this->logger->warning('Cart update: Invalid CSRF token.', ['submitted_token' => $requestData['csrf_token'] ?? 'N/A', 'session_token_hash' => $this->session->getCsrfToken() ? hash('sha256', $this->session->getCsrfToken()) : 'N/A']);
            $this->jsonResponse(['error' => 'Invalid security token.'], 403);
            return;
        }

        // Validate input
        if (!isset($requestData['product_id']) || !isset($requestData['quantity']) || !is_numeric($requestData['product_id']) || !is_numeric($requestData['quantity'])) {
            $this->logger->warning('Cart update: Invalid input.', ['product_id' => $requestData['product_id'] ?? 'N/A', 'quantity' => $requestData['quantity'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Invalid input. Please provide a valid product_id and quantity.'], 400);
            return;
        }

        $productId = (int) $requestData['product_id'];
        $newQuantity = (int) $requestData['quantity'];
        $this->logger->info('Cart update: Input validated.', ['product_id' => $productId, 'new_quantity' => $newQuantity]);

        // Get current quantity to calculate the change needed for updateCartItem
        // This logic for quantityChange is specific to how updateCartItem was designed.
        // If CartHelper::setCartItemQuantity is preferred, this calculation might change.
        // For now, sticking to the existing logic of CartHelper::updateCartItem expecting a delta.
        $cart = $this->session->get('cart', []);
        $currentQuantity = isset($cart[$productId]) ? $cart[$productId] : 0;
        $quantityChange = $newQuantity - $currentQuantity;

        // If new quantity is zero or less, remove the item; otherwise, update it.
        if ($newQuantity <= 0) {
            $this->logger->info('Cart update: Removing item due to zero or negative quantity.', ['product_id' => $productId, 'new_quantity' => $newQuantity]);
            $this->logger->info('Cart update: Calling CartHelper::removeCartItem.', ['product_id' => $productId]);
            $result = $this->cartHelper->removeCartItem($productId);
        } else {
            $this->logger->info('Cart update: Updating item quantity.', ['product_id' => $productId, 'new_quantity' => $newQuantity, 'quantity_change' => $quantityChange]);
            // Assuming updateCartItem expects the *change* in quantity. If it expects the absolute new quantity, this call needs adjustment.
            // The original code used updateCartItem with quantityChange, so we maintain that.
            // If CartHelper::setCartItemQuantity is the standard, this could be:
            // $result = $this->cartHelper->setCartItemQuantity($productId, $newQuantity);
            $this->logger->info('Cart update: Calling CartHelper::updateCartItem.', ['product_id' => $productId, 'quantity_change' => $quantityChange]);
            $result = $this->cartHelper->updateCartItem($productId, $quantityChange);
        }

        // Handle errors from CartHelper
        if (!$result['success']) {
            $statusCode = ($result['message'] === 'Item not found in cart.' || $result['message'] === 'Product not found or insufficient stock.') ? 404 : 400;
            $this->logger->warning('Cart update: CartHelper failed.', ['product_id' => $productId, 'new_quantity' => $newQuantity, 'error' => $result['message'], 'status_code' => $statusCode]);
            $this->jsonResponse(['error' => $result['message']], $statusCode);
            return;
        }

        // Respond with success and the full updated cart state
        $responseData = [
            'success' => true,
            'message' => $result['message'] ?? 'Cart updated successfully.',
            'cart' => $result['cart'],
            'total_items' => $result['total_items'],
            'total_price' => $result['total_price'],
            'is_empty' => $result['is_empty'],
            'updated_product' => $result['updated_product'] ?? null
        ];
        $this->logger->info('Cart update: Successfully processed update.', ['product_id' => $productId, 'new_total_items' => $result['total_items']]);
        $this->logger->info('Cart update: Sending success response.', ['status_code' => 200, 'product_id' => $productId, 'response_summary' => ['total_items' => $result['total_items']]]);
        $this->jsonResponse($responseData, 200);
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
        $this->logger->info('Get cart request received.');
        // Ensure GET request
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->logger->warning('Get cart: Invalid request method.', ['method' => $_SERVER['REQUEST_METHOD']]);
            $this->jsonResponse(['error' => 'Invalid request method. Only GET is allowed.'], 405);
            return;
        }

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Get cart: Authentication required.');
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Retrieve cart data using the helper
        $this->logger->info('Get cart: Calling CartHelper::getCartData.');
        $cartData = $this->cartHelper->getCartData();
        $this->logger->info('Get cart: Cart data retrieved.', ['total_items' => $cartData['total_items'], 'is_empty' => $cartData['is_empty']]);

        // Respond with the cart data
        $responseData = [
            'success' => true,
            'cart' => $cartData['cart_items'],
            'total_items' => $cartData['total_items'],
            'total_price' => $cartData['total_price'],
            'is_empty' => $cartData['is_empty']
        ];
        $this->logger->info('Get cart: Sending success response.', ['status_code' => 200, 'response_summary' => ['total_items' => $cartData['total_items']]]);
        $this->jsonResponse($responseData, 200);
    }

    /**
     * Remove an item completely from the cart.
     *
     * Handles POST requests to remove a specific product entirely from the cart, regardless of quantity.
     * Requires user authentication.
     * Expects JSON input: {"product_id": int, "csrf_token": string}
     *
     * @api {post} /api/cart/remove Remove item from cart
     * @apiName RemoveCartItem
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiBody {Number} product_id The ID of the product to remove.
     * @apiBody {String} csrf_token The CSRF protection token.
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
     * @apiError (403 Forbidden) InvalidCsrfToken The provided CSRF token was invalid.
     * @apiError (404 Not Found) ItemNotFound The specified item was not found in the cart.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     *
     * @apiErrorExample {json} Error-Response (403):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "Invalid security token."
     *     }
     */
    public function remove()
    {
        // Ensure POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->logger->warning('Cart remove: Invalid request method.', ['method' => $_SERVER['REQUEST_METHOD']]);
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Get JSON data
        $requestData = json_decode(file_get_contents('php://input'), true);
        $this->logger->info('Cart remove request received.', [
            'product_id' => $requestData['product_id'] ?? 'N/A',
            'csrf_token_present' => isset($requestData['csrf_token'])
        ]);

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Cart remove: Authentication required.', ['product_id' => $requestData['product_id'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Validate CSRF token
        if (!isset($requestData['csrf_token']) || !$this->session->validateCsrfToken($requestData['csrf_token'])) {
            $this->logger->warning('Cart remove: Invalid CSRF token.', ['submitted_token' => $requestData['csrf_token'] ?? 'N/A', 'session_token_hash' => $this->session->getCsrfToken() ? hash('sha256', $this->session->getCsrfToken()) : 'N/A']);
            $this->jsonResponse(['error' => 'Invalid security token.'], 403);
            return;
        }

        // Validate input
        if (!isset($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            $this->logger->warning('Cart remove: Invalid input.', ['product_id' => $requestData['product_id'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Invalid input. Please provide a valid product_id.'], 400);
            return;
        }

        $productId = (int) $requestData['product_id'];
        $this->logger->info('Cart remove: Input validated.', ['product_id' => $productId]);

        // Use CartHelper to remove the item
        $this->logger->info('Cart remove: Calling CartHelper::removeCartItem.', ['product_id' => $productId]);
        $result = $this->cartHelper->removeCartItem($productId);

        // Determine status code based on success/failure
        if ($result['success']) {
            $statusCode = 200;
            $this->logger->info('Cart remove: Successfully removed item.', ['product_id' => $productId, 'new_total_items' => $result['total_items']]);
            $this->logger->info('Cart remove: Sending success response.', ['status_code' => $statusCode, 'product_id' => $productId, 'response_summary' => ['total_items' => $result['total_items']]]);
        } else {
            $statusCode = ($result['message'] === 'Item not found in cart.') ? 404 : 400;
            $this->logger->warning('Cart remove: CartHelper failed to remove item.', ['product_id' => $productId, 'error' => $result['message'], 'status_code' => $statusCode]);
            $this->logger->warning('Cart remove: Sending error response.', ['status_code' => $statusCode, 'product_id' => $productId, 'error_message' => $result['message']]);
        }
        $this->jsonResponse($result, $statusCode);
    }

    /**
     * Remove an item from the cart (alternative route).
     *
     * Handles POST requests (often via DELETE method override in forms/JS)
     * to remove a specific product from the cart using a route parameter.
     * Requires user authentication.
     * Expects JSON input: {"csrf_token": string}
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
     * @apiBody {String} csrf_token The CSRF protection token.
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
     * @apiError (403 Forbidden) InvalidCsrfToken The provided CSRF token was invalid.
     * @apiError (404 Not Found) ItemNotFound The specified item was not found in the cart.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST (or simulated DELETE via POST).
     *
     * @apiErrorExample {json} Error-Response (403):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "Invalid security token."
     *     }
     */
    public function removeItem($params)
    {
        $this->logger->info('Cart removeItem (URL param) request received.', ['params_received' => $params, 'method' => $_SERVER['REQUEST_METHOD']]);

        // Although the route might imply DELETE, web forms often use POST for this.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->logger->warning('Cart removeItem: Invalid request method.', ['method' => $_SERVER['REQUEST_METHOD'], 'product_id_param' => $params['product_id'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed for this action.'], 405);
            return;
        }

        // Get JSON data (even if it's just for the CSRF token)
        $requestData = json_decode(file_get_contents('php://input'), true);
        $this->logger->info('Cart removeItem: Request details.', [
            'product_id_param' => $params['product_id'] ?? 'N/A',
            'csrf_token_present' => isset($requestData['csrf_token'])
        ]);

        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Cart removeItem: Authentication required.', ['product_id_param' => $params['product_id'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Validate CSRF token
        if (!isset($requestData['csrf_token']) || !$this->session->validateCsrfToken($requestData['csrf_token'])) {
            $this->logger->warning('Cart removeItem: Invalid CSRF token.', ['submitted_token' => $requestData['csrf_token'] ?? 'N/A', 'session_token_hash' => $this->session->getCsrfToken() ? hash('sha256', $this->session->getCsrfToken()) : 'N/A', 'product_id_param' => $params['product_id'] ?? 'N/A']);
            $this->jsonResponse(['error' => 'Invalid security token.'], 403);
            return;
        }

        // Validate the product ID from the route parameters
        if (!isset($params['product_id']) || !is_numeric($params['product_id']) || (int)$params['product_id'] <= 0) {
            $this->logger->warning('Cart removeItem: Invalid or missing product ID in URL.', ['params_received' => $params]);
            $this->jsonResponse(['success' => false, 'error' => 'Invalid product ID.'], 400);
            return;
        }

        $actualProductId = (int) $params['product_id'];
        $this->logger->info('Cart removeItem: Input validated.', ['product_id' => $actualProductId]);

        // Attempt to remove the item using CartHelper
        $this->logger->info('Cart removeItem: Calling CartHelper::removeCartItem.', ['product_id' => $actualProductId]);
        $result = $this->cartHelper->removeCartItem($actualProductId);

        // Prepare response based on the result
        if ($result['success']) {
            $statusCode = 200;
            $responseData = [
                'success' => true,
                'message' => $result['message'] ?? 'Item removed from cart.',
                'cart' => $result['cart'],
                'total_items' => $result['total_items'],
                'total_price' => $result['total_price'],
                'is_empty' => $result['is_empty']
            ];
            $this->logger->info('Cart removeItem: Successfully removed item.', ['product_id' => $actualProductId, 'new_total_items' => $result['total_items']]);
            $this->logger->info('Cart removeItem: Sending success response.', ['status_code' => $statusCode, 'product_id' => $actualProductId, 'response_summary' => ['total_items' => $result['total_items']]]);
        } else {
            $statusCode = ($result['message'] === 'Item not found in cart.') ? 404 : 400;
            $responseData = [
                'success' => false,
                'error' => $result['message'] ?? 'Could not remove item from cart.'
            ];
            $this->logger->warning('Cart removeItem: CartHelper failed to remove item.', ['product_id' => $actualProductId, 'error' => $result['message'], 'status_code' => $statusCode]);
            $this->logger->warning('Cart removeItem: Sending error response.', ['status_code' => $statusCode, 'product_id' => $actualProductId, 'error_message' => $result['message']]);
        }
        $this->jsonResponse($responseData, $statusCode);
    }


    /**
     * Clear all items from the cart.
     *
     * Handles POST requests to remove all items from the authenticated user's cart.
     * Requires user authentication.
     * Expects JSON input: {"csrf_token": string}
     *
     * @api {post} /api/cart/clear Clear entire cart
     * @apiName ClearCart
     * @apiGroup Cart
     * @apiPermission user
     *
     * @apiBody {String} csrf_token The CSRF protection token.
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
     * @apiError (403 Forbidden) InvalidCsrfToken The provided CSRF token was invalid.
     * @apiError (405 Method Not Allowed) InvalidMethod Request method was not POST.
     *
     * @apiErrorExample {json} Error-Response (403):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "Invalid security token."
     *     }
     */
    public function clearCart()
    {
        // Ensure POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->logger->warning('Cart clear: Invalid request method.', ['method' => $_SERVER['REQUEST_METHOD']]);
            $this->jsonResponse(['error' => 'Invalid request method. Only POST is allowed.'], 405);
            return;
        }

        // Get JSON data (even if it's just for the CSRF token)
        $requestData = json_decode(file_get_contents('php://input'), true);
        $this->logger->info('Cart clear request received.', ['csrf_token_present' => isset($requestData['csrf_token'])]);


        // Check authentication
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Cart clear: Authentication required.');
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Validate CSRF token
        if (!isset($requestData['csrf_token']) || !$this->session->validateCsrfToken($requestData['csrf_token'])) {
            $this->logger->warning('Cart clear: Invalid CSRF token.', ['submitted_token' => $requestData['csrf_token'] ?? 'N/A', 'session_token_hash' => $this->session->getCsrfToken() ? hash('sha256', $this->session->getCsrfToken()) : 'N/A']);
            $this->jsonResponse(['error' => 'Invalid security token.'], 403);
            return;
        }

        // Use CartHelper to clear the cart
        $this->logger->info('Cart clear: Calling CartHelper::clearCart.');
        $result = $this->cartHelper->clearCart();
        $this->logger->info('Cart clear: Cart successfully cleared.', ['new_total_items' => $result['total_items']]);

        // Respond with confirmation and the now empty cart state
        $responseData = [
            'success' => true,
            'message' => 'Cart cleared.',
            'total_items' => $result['total_items'],
            'total_price' => $result['total_price'],
            'is_empty' => $result['is_empty']
        ];
        $this->logger->info('Cart clear: Sending success response.', ['status_code' => 200, 'response_summary' => ['total_items' => $result['total_items']]]);
        $this->jsonResponse($responseData, 200);
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
        $this->logger->info('Get cart count request received.');
        // Ensure GET request
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->logger->warning('Get cart count: Invalid request method.', ['method' => $_SERVER['REQUEST_METHOD']]);
            $this->jsonResponse(['error' => 'Invalid request method. Only GET is allowed.'], 405);
            return;
        }

        // Authentication check: The original code commented this out.
        // For consistency with other cart actions and general API security, it's often good to require authentication
        // unless there's a specific reason for unauthenticated access to cart count (e.g. for guest carts).
        // Re-enabling it for now, assuming consistency is preferred.
        if (!$this->session->isAuthenticated()) {
            $this->logger->warning('Get cart count: Authentication required.');
            $this->jsonResponse(['error' => 'Authentication required.'], 401);
            return;
        }

        // Retrieve cart data using the helper
        $this->logger->info('Get cart count: Calling CartHelper::getCartData.');
        $cartData = $this->cartHelper->getCartData();
        $this->logger->info('Get cart count: Cart data retrieved for count.', ['total_items_from_helper' => $cartData['total_items'] ?? 'N/A']);

        $count = $cartData['total_items'] ?? 0;
        // Respond with just the count
        $responseData = ['count' => $count];
        $this->logger->info('Get cart count: Sending success response.', ['status_code' => 200, 'count' => $count]);
        $this->jsonResponse($responseData, 200);
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