<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Session;
use App\Core\Registry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Helpers\SecurityHelper;

/**
 * Order API Controller
 *
 * Handles API requests related to viewing and managing customer orders.
 * Primarily intended for administrative access (managers).
 * Provides endpoints to list all orders, view specific order details, and update order status.
 * Requires 'admin' role authentication for most actions.
 * All responses are in JSON format.
 */
class OrderApiController extends BaseController
{
    /**
     * @var Order Instance of the Order model for database interactions.
     */
    private Order $orderModel;

    /**
     * @var OrderItem Instance of the OrderItem model for retrieving order items.
     */
    private OrderItem $orderItemModel;

    /**
     * @var \App\Controllers\OrderController Instance of the standard OrderController
     *                                       (potentially for reusing business logic like status updates).
     */
    private \App\Controllers\OrderController $orderController;

    /**
     * Constructor for OrderApiController.
     *
     * Initializes Order and OrderItem models, and the standard OrderController.
     * Sets common HTTP headers for JSON API responses, including CORS headers
     * and handling for preflight OPTIONS requests.
     */
    public function __construct()
    {
        $this->orderModel = new Order(Registry::get('db'));
        $this->orderItemModel = new OrderItem(Registry::get('db'));
        $this->orderController = new \App\Controllers\OrderController(); // Instantiate the web controller

        // Set headers common to all responses from this API controller
        header('Content-Type: application/json'); // Indicate JSON response
        header('Access-Control-Allow-Origin: *'); // Allow requests from any origin (adjust for production)
        header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS'); // Allowed HTTP methods
        header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Allowed headers

        // Handle CORS preflight requests (OPTIONS method)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200); // Respond OK to preflight checks
            exit(); // Stop script execution for OPTIONS requests
        }
    }

    /**
     * Check API Authentication and Authorization (Admin Role).
     *
     * Verifies if the user is authenticated and has the 'admin' role.
     * Sends appropriate JSON error responses (401 or 403) and returns false if checks fail.
     *
     * @return bool True if authenticated and authorized as admin, false otherwise.
     */
    private function checkApiAuth(): bool
    {
        // Check if a user session exists and is authenticated
        if (!Registry::get('session')->isAuthenticated()) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Authentication required.']);
            return false;
        }

        // Check if the authenticated user has the 'admin' role
        $userRole = Registry::get('session')->get('user_role');
        if ($userRole !== 'admin') {
            http_response_code(403); // Forbidden
            echo json_encode(['error' => 'Permission denied. Requires administrator privileges.']);
            return false;
        }

        // If both checks pass
        return true;
    }

    /**
     * Get all orders (Admin only).
     *
     * Handles GET requests to retrieve a list of all orders in the system.
     * Requires admin authentication.
     *
     * @api {get} /api/orders List all orders
     * @apiName GetAllOrders
     * @apiGroup Order
     * @apiPermission admin
     *
     * @apiSuccess {Object[]} orders Array of order objects. Structure might vary based on `Order::getAll()` implementation.
     * @apiSuccess {Number} orders.order_id Order ID.
     * @apiSuccess {Number} orders.user_id User ID associated with the order.
     * @apiSuccess {String} orders.order_date Date and time the order was placed.
     * @apiSuccess {Number} orders.total_amount Total amount of the order.
     * @apiSuccess {String} orders.status Current status of the order.
     * @apiSuccess {String} [orders.user_name] User's full name (if joined in model).
     * @apiSuccess {String} [orders.user_email] User's email (if joined in model).
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     [
     *       {
     *         "order_id": 1,
     *         "user_id": 2,
     *         "order_date": "2023-05-10 14:35:12",
     *         "total_amount": 45.75,
     *         "status": "completed",
     *         "user_name": "John Doe",
     *         "user_email": "john@example.com"
     *       },
     *       { ... more orders ... }
     *     ]
     *
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (403 Forbidden) PermissionDenied User is not an administrator.
     * @apiError (500 Internal Server Error) ServerError Failed to retrieve orders from the database.
     */
    public function index()
    {
        // Check authentication and authorization
        if (!$this->checkApiAuth()) {
            return; // Error response already sent by checkApiAuth()
        }

        try {
            // Retrieve all orders using the Order model
            $orders = $this->orderModel->getAll();

            // Check if retrieval was successful
            if ($orders === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to retrieve orders.']);
                return;
            }

            // Send successful response with order data
            http_response_code(200);
            echo json_encode($orders);
        } catch (\Exception $e) {
            // Log the exception $e->getMessage() here if needed
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred while fetching orders.']);
        }
    }

    /**
     * Get a specific order by ID (Admin only).
     *
     * Handles GET requests to retrieve detailed information for a single order, including its items.
     * Requires admin authentication.
     *
     * @param int $id The ID of the order to retrieve, passed as a route parameter.
     *
     * @api {get} /api/orders/{id} Get order details
     * @apiName GetOrderById
     * @apiGroup Order
     * @apiPermission admin
     *
     * @apiParam {Number} id Order's unique ID in the URL path.
     *
     * @apiSuccess {Number} order_id Order ID.
     * @apiSuccess {Number} user_id User ID associated with the order.
     * @apiSuccess {String} [user_name] User's full name (if available).
     * @apiSuccess {String} [user_email] User's email (if available).
     * @apiSuccess {String} [user_phone] User's phone number (if available).
     * @apiSuccess {String} order_date Date and time the order was placed.
     * @apiSuccess {Number} total_amount Total amount of the order.
     * @apiSuccess {String} status Current status of the order.
     * @apiSuccess {String|null} notes Any notes associated with the order.
     * @apiSuccess {Object[]} items Array of items included in the order.
     * @apiSuccess {Number} items.item_id Order item ID.
     * @apiSuccess {Number} items.product_id Product ID.
     * @apiSuccess {String} items.product_name Name of the product.
     * @apiSuccess {String} items.product_image Image path for the product.
     * @apiSuccess {Number} items.quantity Quantity ordered.
     * @apiSuccess {Number} items.price Price per unit at the time of order.
     * @apiSuccess {Number} items.subtotal Total for this item (quantity * price).
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "order_id": 1,
     *       "user_id": 2,
     *       "user_name": "John Doe",
     *       "user_email": "john@example.com",
     *       "user_phone": "1234567890",
     *       "order_date": "2023-05-10 14:35:12",
     *       "total_amount": 45.75,
     *       "status": "completed",
     *       "notes": "Please leave at the front door",
     *       "items": [ { ... item details ... }, { ... } ]
     *     }
     *
     * @apiError (400 Bad Request) InvalidID Invalid order ID format provided.
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (403 Forbidden) PermissionDenied User is not an administrator.
     * @apiError (404 Not Found) OrderNotFound No order found with the specified ID.
     * @apiError (500 Internal Server Error) ServerError Failed to retrieve order details.
     */
    public function show(int $id)
    {
        // Check authentication and authorization
        if (!$this->checkApiAuth()) {
            return;
        }

        // Validate the incoming order ID
        $orderId = filter_var($id, FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid order ID provided.']);
            return;
        }

        try {
            // Retrieve the main order details
            $order = $this->orderModel->readOne($orderId);

            // Check if the order exists
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found.']);
                return;
            }

            // Retrieve the associated order items
            $order['items'] = $this->orderItemModel->readByOrder($orderId);

            // Send successful response with complete order data
            http_response_code(200);
            echo json_encode($order);
        } catch (\Exception $e) {
            // Log the exception $e->getMessage() here if needed
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred while fetching order details.']);
        }
    }

    /**
     * Update the status of a specific order (Admin only).
     *
     * Handles PUT requests to update the status of an existing order.
     * Requires admin authentication.
     * Expects JSON input: {"status": "new_status"}
     * Valid statuses are defined in $allowedStatuses.
     *
     * @param int $id The ID of the order to update, passed as a route parameter.
     *
     * @api {put} /api/orders/{id} Update order status
     * @apiName UpdateOrderStatus
     * @apiGroup Order
     * @apiPermission admin
     *
     * @apiParam {Number} id Order's unique ID in the URL path.
     * @apiBody {String} status Required. The new status for the order (e.g., "Processing", "Shipped").
     *
     * @apiSuccess {String} message Confirmation message.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "Order status updated successfully."
     *     }
     *
     * @apiError (400 Bad Request) InvalidID Invalid order ID format provided.
     * @apiError (400 Bad Request) InvalidPayload Malformed JSON or missing 'status' field.
     * @apiError (400 Bad Request) InvalidStatus Provided status value is not allowed.
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (403 Forbidden) PermissionDenied User is not an administrator.
     * @apiError (404 Not Found) OrderNotFound No order found with the specified ID.
     * @apiError (500 Internal Server Error) ServerError Failed to update the order status in the database.
     *
     * @apiErrorExample {json} Error-Response (400 - Invalid Status):
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "error": "Invalid status value provided."
     *     }
     * @apiErrorExample {json} Error-Response (404):
     *     HTTP/1.1 404 Not Found
     *     {
     *       "error": "Order not found."
     *     }
     */
    public function update(int $id)
    {
        // Check authentication and authorization
        if (!$this->checkApiAuth()) {
            return;
        }

        // Validate the order ID
        $orderId = filter_var($id, FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid order ID provided.']);
            return;
        }

        // Get JSON data from the request body
        $requestData = json_decode(file_get_contents('php://input'), true);

        // Validate JSON payload and presence of 'status' field
        if (json_last_error() !== JSON_ERROR_NONE || !isset($requestData['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload. Expecting {"status": "new_status"}.']);
            return;
        }

        // Sanitize and validate the new status value
        $newStatus = SecurityHelper::sanitizeInput($requestData['status']);
        $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled']; // Define valid statuses
        if (!in_array($newStatus, $allowedStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status value provided. Allowed values: ' . implode(', ', $allowedStatuses)]);
            return;
        }

        try {
            // Verify the order exists before attempting update
            $order = $this->orderModel->readOne($orderId);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found.']);
                return;
            }

            // Attempt to update the status using the Order model
            $success = $this->orderModel->updateStatus($orderId, $newStatus);

            // Respond based on the success of the update operation
            if ($success) {
                http_response_code(200);
                echo json_encode(['message' => 'Order status updated successfully.']);
            } else {
                http_response_code(500); // Or 400 if it's a validation issue within updateStatus
                echo json_encode(['error' => 'Failed to update order status.']);
            }
        } catch (\Exception $e) {
            // Log the exception $e->getMessage() here if needed
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred while updating order status.']);
        }
    }

    /**
     * Update order status using OrderController logic (Admin only).
     *
     * Handles POST requests (potentially intended for PUT but using POST)
     * to update an order's status, leveraging the logic within the main OrderController.
     * Requires admin authentication.
     * Expects JSON input: {"status": "new_status"}
     *
     * Note: This method seems redundant with `update()`. Consider consolidating
     * or clarifying the distinction if both are needed. It uses the web OrderController's
     * `updateOrderStatusAsManager` method.
     *
     * @param int $id The ID of the order to update, passed as a route parameter.
     *
     * @api {post} /api/orders/{id}/status Update order status (alternative)
     * @apiName UpdateOrderStatusAlt
     * @apiGroup Order
     * @apiPermission admin
     *
     * @apiParam {Number} id Order's unique ID in the URL path.
     * @apiBody {String} status Required. The new status for the order.
     *
     * @apiSuccess {String} message Confirmation message.
     *
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "message": "Order status updated successfully."
     *     }
     *
     * @apiError (400 Bad Request) InvalidID Invalid order ID format provided.
     * @apiError (400 Bad Request) InvalidPayload Malformed JSON or missing 'status' field.
     * @apiError (400 Bad Request) UpdateFailed Failed to update status (e.g., invalid status via controller logic).
     * @apiError (401 Unauthorized) AuthenticationRequired User is not authenticated.
     * @apiError (403 Forbidden) PermissionDenied User is not an administrator.
     * @apiError (404 Not Found) OrderNotFound No order found with the specified ID.
     * @apiError (500 Internal Server Error) ServerError An internal error occurred.
     */
    public function updateStatus(int $id)
    {
        // Check authentication and authorization
        if (!$this->checkApiAuth()) {
            return;
        }

        // Validate the order ID
        $orderId = filter_var($id, FILTER_VALIDATE_INT);
        if ($orderId === false || $orderId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid order ID provided.']);
            return;
        }

        // Get JSON data
        $requestData = json_decode(file_get_contents('php://input'), true);

        // Validate JSON and 'status' field
        if (json_last_error() !== JSON_ERROR_NONE || !isset($requestData['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload. Expecting {"status": "new_status"}.']);
            return;
        }

        // Sanitize the status input
        $newStatus = SecurityHelper::sanitizeInput($requestData['status']);

        try {
            // Verify the order exists (optional but good practice)
            $order = $this->orderModel->readOne($orderId);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found.']);
                return;
            }

            // Delegate the update logic to the standard OrderController's method
            // This assumes updateOrderStatusAsManager handles validation and database update.
            $success = $this->orderController->updateOrderStatusAsManager($orderId, $newStatus);

            // Respond based on the result from the controller method
            if ($success) {
                http_response_code(200);
                echo json_encode(['message' => 'Order status updated successfully.']);
            } else {
                // Assume failure in controller logic implies bad request (e.g., invalid status)
                http_response_code(400);
                echo json_encode(['error' => 'Failed to update order status. Status may be invalid or order cannot be updated.']);
            }
        } catch (\Exception $e) {
            // Log the exception $e->getMessage() here if needed
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred while updating order status.']);
        }
    }
}
