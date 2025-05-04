<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Registry;
use App\Models\Order;
use App\Controllers\OrderController;
use PDO;

/**
 * Class APIController
 * Handles incoming API requests, currently focused on order management.
 * Provides endpoints for retrieving and updating order information based on user roles.
 * Authentication is handled via simple Bearer tokens for demonstration purposes.
 *
 * @package App\Controllers
 */
class APIController extends BaseController
{
    /**
     * @var PDO|null Database connection instance.
     */
    private $db;

    /**
     * @var Order Order model instance.
     */
    private $order;

    /**
     * @var array Holds the data to be sent in the JSON response.
     */
    private $response = [];

    /**
     * @var int HTTP status code for the response. Defaults to 200 (OK).
     */
    private $response_code = 200;

    /**
     * @var string The HTTP request method (e.g., 'GET', 'PUT').
     */
    private $request_method;

    /**
     * @var string The primary API endpoint requested (e.g., 'orders').
     */
    private $endpoint;

    /**
     * @var array Parameters extracted from the request URI (e.g., ['id' => 123]).
     */
    private $params = [];

    /**
     * @var bool Flag indicating if the current request is authenticated.
     */
    private $is_authenticated = false;

    /**
     * @var int|null The ID of the authenticated user, if applicable.
     */
    private $user_id = null;

    /**
     * @var bool Flag indicating if the authenticated user has manager privileges.
     */
    private $is_manager = false;

    /**
     * APIController constructor.
     * Initializes database connection, models, request details, and performs authentication.
     *
     * @param PDO $db The database connection instance.
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->order = new Order($db); // Instantiate the Order model
        $this->request_method = $_SERVER['REQUEST_METHOD']; // Get the request method
        $this->endpoint = $this->getEndpoint(); // Determine the requested endpoint
        $this->params = $this->getParams(); // Extract parameters from the URI
        $this->authenticate(); // Attempt to authenticate the request
    }

    /**
     * Processes the incoming API request.
     * Sets the content type to JSON and routes the request based on the determined endpoint.
     * Sends the final response.
     *
     * @return void
     */
    public function processRequest(): void
    {
        header('Content-Type: application/json'); // Set response header

        // Route based on the main endpoint
        switch ($this->endpoint) {
            case 'orders':
                $this->handleOrdersEndpoint();
                break;
            default:
                // Handle unknown endpoints
                $this->setResponse(404, ['error' => 'Endpoint not found']);
                break;
        }

        $this->sendResponse(); // Send the JSON response and terminate
    }

    /**
     * Handles requests specifically for the 'orders' endpoint.
     * Checks authentication and routes based on the HTTP method (GET, PUT).
     * Dispatches to appropriate methods for fetching or updating orders.
     *
     * @return void
     */
    private function handleOrdersEndpoint(): void
    {
        // Check if the user is authenticated
        if (!$this->is_authenticated) {
            $this->setResponse(401, ['error' => 'Authentication required']);
            return;
        }

        // Handle different HTTP methods for the /orders endpoint
        switch ($this->request_method) {
            case 'GET':
                // If an ID is provided in the URI (e.g., /api/orders/123)
                if (isset($this->params['id'])) {
                    $this->getOrderById($this->params['id']);
                } else {
                    // If no ID, fetch multiple orders based on role
                    if ($this->is_manager) {
                        $this->getAllOrders(); // Manager gets all orders (paginated)
                    } else {
                        $this->getUserOrders(); // Regular user gets their own orders
                    }
                }
                break;
            case 'PUT':
                // Allow managers to update order status via PUT /api/orders/{id}
                if ($this->is_manager && isset($this->params['id'])) {
                    $this->updateOrderStatus($this->params['id']);
                } else {
                    // Deny PUT requests for non-managers or without an ID
                    $this->setResponse(403, ['error' => 'Forbidden']);
                }
                break;
            default:
                // Handle unsupported HTTP methods
                $this->setResponse(405, ['error' => 'Method not allowed']);
                break;
        }
    }

    /**
     * Fetches details for a specific order by its ID.
     * Uses OrderController logic to retrieve details and checks authorization.
     * Sets the response based on whether the order is found and accessible.
     *
     * @param int $order_id The ID of the order to retrieve.
     * @return void
     */
    private function getOrderById($order_id): void
    {
        $orderController = new OrderController(); // Use existing controller logic
        $order = $orderController->getOrderDetails($order_id, $this->user_id); // Fetch details

        if ($order) {
            // Check if the user is a manager or the owner of the order
            if ($this->is_manager || $order['user_id'] == $this->user_id) {
                $this->setResponse(200, $order); // Success
            } else {
                // User is not authorized to view this specific order
                $this->setResponse(403, ['error' => 'You are not authorized to view this order']);
            }
        } else {
            // Order not found or user doesn't have access (handled by getOrderDetails)
            $this->setResponse(404, ['error' => 'Order not found']);
        }
    }

    /**
     * Fetches all orders, intended for manager use.
     * Supports pagination and filtering by status via query parameters (?status=pending&page=2&limit=10).
     * Sets the response with paginated order data or an error.
     *
     * @return void
     */
    private function getAllOrders(): void
    {
        // Get optional query parameters
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : Registry::get('config')['ITEMS_PER_PAGE'];
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Fetch orders using the Order model
        $result = $this->order->getAll($status, $limit, $offset);

        if ($result) {
            $orders = [];
            // Fetch all rows into an array
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = $row;
            }
            // Get total count for pagination
            $total = $this->order->countAll($status);
            // Set successful response with pagination details
            $this->setResponse(200, [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit),
                'orders' => $orders
            ]);
        } else {
            // Handle database error
            $this->setResponse(500, ['error' => 'Failed to retrieve orders']);
        }
    }

    /**
     * Fetches all orders belonging to the currently authenticated non-manager user.
     * Uses OrderController logic to retrieve the orders.
     * Sets the response with the user's orders or an error.
     *
     * @return void
     */
    private function getUserOrders(): void
    {
        $orderController = new OrderController(); // Use existing controller logic
        $orders = $orderController->getUserOrders($this->user_id); // Fetch orders for the user

        if ($orders !== false) { // Check for false explicitly, as an empty array is valid
            $this->setResponse(200, ['orders' => $orders]); // Success
        } else {
            // Handle potential error from getUserOrders
            $this->setResponse(500, ['error' => 'Failed to retrieve orders']);
        }
    }

    /**
     * Updates the status of a specific order, intended for manager use.
     * Reads the new status from the JSON request body (e.g., {"status": "shipped"}).
     * Uses OrderController logic to perform the update.
     * Sets the response indicating success or failure.
     *
     * @param int $order_id The ID of the order to update.
     * @return void
     */
    private function updateOrderStatus($order_id): void
    {
        // Get the raw PUT request body and decode it as JSON
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate that the 'status' field is present in the request body
        if (!isset($data['status'])) {
            $this->setResponse(400, ['error' => 'Status is required']);
            return;
        }

        $orderController = new OrderController(); // Use existing controller logic
        // Attempt to update the order status using the manager-specific method
        if ($orderController->updateOrderStatusAsManager($order_id, $data['status'])) {
            $this->setResponse(200, ['message' => 'Order status updated successfully']); // Success
        } else {
            // Handle failure (e.g., invalid status, DB error, order not found)
            $this->setResponse(500, ['error' => 'Failed to update order status. Check logs for details.']);
        }
    }

    /**
     * Authenticates the request based on the Authorization header.
     * Looks for a 'Bearer' token and validates it against hardcoded tokens.
     * Sets authentication flags ($is_authenticated, $is_manager, $user_id).
     * NOTE: This is a basic placeholder and should be replaced with a robust
     * authentication mechanism (e.g., JWT, OAuth) in a real application.
     *
     * @return void
     */
    private function authenticate(): void
    {
        $headers = getallheaders(); // Get all request headers

        // Check if the Authorization header is set
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            // Check if it starts with 'Bearer '
            if (strpos($auth_header, 'Bearer ') === 0) {
                // Extract the token part
                $token = substr($auth_header, 7);

                // --- Placeholder Token Validation ---
                if ($token == 'manager_token_123') { // Hardcoded manager token
                    $this->is_authenticated = true;
                    $this->is_manager = true;
                    $this->user_id = 1; // Assign a dummy manager user ID
                } elseif ($token == 'user_token_456') { // Hardcoded regular user token
                    $this->is_authenticated = true;
                    $this->user_id = 2; // Assign a dummy regular user ID
                }
                // --- End Placeholder ---
            }
        }
        // If no valid token is found, flags remain false/null.
    }

    /**
     * Extracts the primary API endpoint from the request URI.
     * Assumes the endpoint is the segment immediately following '/api/'.
     * Example: /api/orders/123 -> 'orders'
     *
     * @return string The extracted endpoint name, or an empty string if not found.
     */
    private function getEndpoint(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Get the path part of the URI
        $path_parts = explode('/', trim($path, '/')); // Split the path into segments

        // Find the index of the 'api' segment
        $api_index = array_search('api', $path_parts);

        // If 'api' is found and there's a segment after it, return that segment
        if ($api_index !== false && isset($path_parts[$api_index + 1])) {
            return $path_parts[$api_index + 1];
        }

        return ''; // Return empty string if endpoint structure is not matched
    }

    /**
     * Extracts parameters from the request URI path.
     * Currently assumes the parameter (if any) is the segment immediately
     * following the endpoint. Primarily used to get the 'id'.
     * Example: /api/orders/123 -> ['id' => '123']
     *
     * @return array An associative array of parameters found.
     */
    private function getParams(): array
    {
        $params = [];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Get the path part
        $path_parts = explode('/', trim($path, '/')); // Split into segments

        // Find the index of the previously determined endpoint
        $endpoint_index = array_search($this->endpoint, $path_parts);

        // If the endpoint is found and there's a segment after it, assume it's the ID
        if ($endpoint_index !== false && isset($path_parts[$endpoint_index + 1])) {
            $params['id'] = $path_parts[$endpoint_index + 1];
        }

        // Also consider query parameters (though not explicitly handled here for path params)
        // parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query_params);
        // $params = array_merge($params, $query_params); // Merge if needed

        return $params;
    }

    /**
     * Sets the HTTP response code and the response body data.
     *
     * @param int $code The HTTP status code (e.g., 200, 404, 500).
     * @param array $data The data payload for the JSON response.
     * @return void
     */
    private function setResponse($code, $data): void
    {
        $this->response_code = $code;
        $this->response = $data;
    }

    /**
     * Sends the final JSON response to the client.
     * Sets the HTTP response code, encodes the response data as JSON,
     * outputs it, and terminates the script execution.
     *
     * @return void
     */
    private function sendResponse(): void
    {
        http_response_code($this->response_code); // Set the HTTP status code
        echo json_encode($this->response); // Output the JSON encoded response data
        exit; // Stop script execution
    }
}
