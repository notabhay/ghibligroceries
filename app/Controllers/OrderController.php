<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Core\Database;
use App\Core\BaseController;
use App\Core\Registry;
use App\Core\Session;
use App\Core\Request;
use App\Core\Redirect;
use App\Helpers\SecurityHelper;
use Psr\Log\LoggerInterface;
use PDO;
use Exception;

/**
 * Class OrderController
 * Handles all actions related to customer orders, including placing orders
 * from the cart or directly for a single product, viewing order history,
 * viewing order details, and cancelling orders. Also includes methods used
 * by the APIController and Admin controllers for order management.
 *
 * @package App\Controllers
 */
class OrderController extends BaseController
{
    /**
     * @var Session Session management instance.
     */
    private Session $session;

    /**
     * @var Request HTTP request handling instance.
     */
    private Request $request;

    /**
     * @var LoggerInterface Logger instance for recording events and errors.
     */
    private LoggerInterface $logger;

    /**
     * @var Order Order model instance for database interactions.
     */
    private Order $orderModel;

    /**
     * @var OrderItem OrderItem model instance for database interactions.
     */
    private OrderItem $orderItemModel;

    /**
     * @var Product Product model instance for database interactions.
     */
    private Product $productModel;

    /**
     * OrderController constructor.
     * Initializes dependencies (Session, Request, Logger) from the Registry.
     * Initializes database-dependent models (Order, OrderItem, Product).
     * Throws a RuntimeException if the database connection is unavailable.
     */
    public function __construct()
    {
        $this->session = Registry::get('session');
        $this->request = Registry::get('request');
        $this->logger = Registry::get('logger');
        $db = Registry::get('database'); // Get Database wrapper from Registry
        $pdoConnection = $db->getConnection(); // Get the actual PDO connection

        // Ensure we have a valid PDO connection before proceeding
        if (!$pdoConnection) {
            $this->logger->critical("Database connection not available for OrderController.");
            // Stop execution if DB is down, as this controller heavily relies on it.
            throw new \RuntimeException("Database connection not available for OrderController.");
        }

        // Instantiate models with the PDO connection
        $this->orderModel = new Order($pdoConnection);
        $this->orderItemModel = new OrderItem($pdoConnection);
        $this->productModel = new Product($pdoConnection);
    }

    /**
     * Displays the order form for a single specific product.
     * Requires the user to be logged in. Fetches product details based on the ID
     * provided in the route parameters.
     *
     * @param array $params Associative array containing route parameters, expecting 'productId'.
     * @return void Renders the 'pages/order' view or redirects on error/invalid input.
     */
    public function showSingleProductOrderForm($params): void
    {
        $this->session->requireLogin('/login'); // Redirect to login if not authenticated

        // Validate and sanitize the product ID from the route parameters
        $productId = filter_var($params['productId'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId) {
            $this->logger->warning("Invalid or missing product ID for single product order form.", ['params' => $params]);
            $this->session->flash('error', 'Invalid product specified.');
            Redirect::to('/'); // Redirect to homepage if product ID is invalid
            return;
        }

        try {
            // Fetch product details from the database
            $product = $this->productModel->findById($productId);

            // Check if the product exists
            if (!$product) {
                $this->logger->warning("Product not found for single product order form.", ['product_id' => $productId]);
                $this->session->flash('error', 'Product not found.');
                Redirect::to('/'); // Redirect if product doesn't exist
                return;
            }

            // Prepare product data for the view, handling potential missing fields
            $productData = [
                'product_id' => $product['product_id'] ?? $productId,
                'name' => $product['name'] ?? '',
                'description' => $product['description'] ?? '',
                'price' => $product['price'] ?? 0,
                'stock_quantity' => $product['stock_quantity'] ?? 0,
                'image_path' => $product['image_path'] ?? $product['image'] ?? 'default.png', // Handle different image field names
            ];
        } catch (Exception $e) {
            // Log database errors and inform the user
            $this->logger->error("Error fetching product details for single order form.", ['product_id' => $productId, 'exception' => $e]);
            $this->session->flash('error', 'Could not load product details. Please try again.');
            Redirect::to('/');
            return;
        }

        // Generate CSRF token for form security
        $csrfToken = $this->session->getCsrfToken();

        // Prepare data for the view template
        $data = [
            'product' => $productData,
            'csrfToken' => $csrfToken,
            'page_title' => 'Place Order - ' . htmlspecialchars($productData['name']),
            'meta_description' => 'Place your order for ' . htmlspecialchars($productData['name']) . ' at GhibliGroceries',
            'meta_keywords' => 'order, grocery, purchase, ' . htmlspecialchars($productData['name']),
            'additional_css_files' => ['/assets/css/order.css'], // Specific CSS for this page
        ];

        // Render the view
        $this->view('pages/order', $data);
    }

    /**
     * Processes the submission of the single product order form.
     * Requires login, validates CSRF token, quantity, and product availability/stock.
     * Creates the order and redirects to the confirmation page on success.
     *
     * @param array $params Associative array containing route parameters, expecting 'productId'.
     * @return void Redirects to confirmation, back to form on error, or login page.
     */
    public function processSingleProductOrder($params): void
    {
        $this->session->requireLogin(); // Ensure user is logged in

        // Only allow POST requests
        if (!$this->request->isPost()) {
            $this->session->flash('error', 'Invalid request method.');
            Redirect::back(); // Redirect back to the previous page
            return;
        }

        // Validate product ID from route parameters
        $productId = filter_var($params['productId'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId) {
            $this->logger->warning("Invalid or missing product ID for single product order processing.", ['params' => $params]);
            $this->session->flash('error', 'Invalid product specified.');
            Redirect::to('/'); // Redirect to homepage if invalid
            return;
        }

        // Validate CSRF token
        $submittedToken = $this->request->post('csrf_token');
        if (!$this->session->validateCsrfToken($submittedToken)) {
            $this->logger->warning("CSRF token mismatch for single product order.", ['product_id' => $productId]);
            $this->session->flash('error', 'Invalid security token. Please try submitting the form again.');
            Redirect::to('/order/product/' . $productId); // Redirect back to the form
            return;
        }

        // Validate quantity input
        $quantity = filter_var($this->request->post('quantity'), FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity <= 0) {
            $this->session->flash('error', 'Please enter a valid quantity.');
            Redirect::to('/order/product/' . $productId);
            return;
        }

        // Sanitize optional notes
        $notes = SecurityHelper::sanitizeInput($this->request->post('notes', ''));

        try {
            // Re-fetch product details to ensure price and stock are current
            $product = $this->productModel->findById($productId);

            // Check product existence and stock level
            if (!$product || $product['stock_quantity'] < $quantity) {
                $this->logger->warning("Product not found or insufficient stock during single order processing.", [
                    'product_id' => $productId,
                    'requested_qty' => $quantity,
                    'available_qty' => $product['stock_quantity'] ?? 'N/A' // Log available stock
                ]);
                $this->session->flash('error', 'Product not found or insufficient stock (' . ($product['stock_quantity'] ?? 0) . ' available).');
                Redirect::to('/order/product/' . $productId);
                return;
            }

            // Use the current price from the database
            $currentPrice = $product['price'];
            $productName = $product['name']; // For success message

        } catch (Exception $e) {
            // Handle database errors during product re-fetch
            $this->logger->error("Error re-fetching product details for single order processing.", ['product_id' => $productId, 'exception' => $e]);
            $this->session->flash('error', 'Could not verify product details. Please try again.');
            Redirect::to('/order/product/' . $productId);
            return;
        }

        // Prepare item data for the createOrder method
        $items = [
            [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $currentPrice // Use the fetched current price
            ]
        ];
        $totalAmount = $currentPrice * $quantity;

        // Get user ID from session
        $userId = $this->session->get('user_id');
        if (!$userId) {
            // This should ideally not happen if requireLogin worked, but check anyway
            $this->logger->error("User ID not found in session during single order processing.", ['product_id' => $productId]);
            $this->session->flash('error', 'User session not found. Please log in again.');
            Redirect::to('/login');
            return;
        }

        // Attempt to create the order
        $orderId = $this->createOrder($userId, $items, $totalAmount, $notes);

        if ($orderId) {
            // Order created successfully
            $this->logger->info("Single product order placed successfully.", ['order_id' => $orderId, 'user_id' => $userId, 'product_id' => $productId, 'quantity' => $quantity]);
            $this->session->flash('success', 'Your order for ' . htmlspecialchars($productName) . ' has been placed successfully!');
            Redirect::to('/order/confirmation/' . $orderId); // Redirect to confirmation page
            return;
        } else {
            // Order creation failed (handled within createOrder)
            $this->session->flash('error', 'Failed to place your order. Please try again.');
            Redirect::to('/order/product/' . $productId); // Redirect back to form
            return;
        }
    }

    /**
     * Displays the order form populated with items from the user's shopping cart.
     * Requires login. Fetches details for all products in the cart.
     * Calculates the total amount and prepares data for the view.
     * Handles cases where cart items are no longer available.
     *
     * @return void Renders the 'pages/order_form' view or redirects if cart is empty or on error.
     */
    public function showOrderForm(): void
    {
        $this->session->requireLogin(); // Ensure user is logged in

        // Retrieve cart items from session
        $cart = $this->session->get('cart', []);
        $totalAmount = 0;
        $cartItemsDetails = []; // Array to hold detailed product info for the view

        if (!empty($cart)) {
            $productIds = array_keys($cart); // Get all product IDs from the cart

            if (!empty($productIds)) {
                try {
                    // Fetch details for all products in the cart in one query
                    $productsById = $this->productModel->findMultipleByIds($productIds);

                    // Iterate through the cart items
                    foreach ($cart as $productId => $quantity) {
                        // Check if the product details were successfully fetched
                        if (isset($productsById[$productId])) {
                            $product = $productsById[$productId];
                            $subtotal = $product['price'] * $quantity;
                            $totalAmount += $subtotal;

                            // Add detailed product info to the array for the view
                            $cartItemsDetails[] = [
                                'product_id' => $productId,
                                'name' => $product['name'],
                                'price' => $product['price'],
                                // Construct image URL safely
                                'image_url' => '/assets/images/products/' . basename($product['image'] ?? 'default.png'),
                                'quantity' => $quantity,
                                'subtotal' => $subtotal
                            ];
                        } else {
                            // Product from cart not found in DB (maybe deleted?)
                            $this->logger->warning("Product ID {$productId} from cart not found in database during order form display.");
                            // Remove the invalid item from the session cart
                            $currentCart = $this->session->get('cart', []);
                            unset($currentCart[$productId]);
                            $this->session->set('cart', $currentCart);
                            // Inform user and redirect back to the (now updated) order form
                            $this->session->flash('warning', "Some items were removed from your cart as they are no longer available.");
                            Redirect::to('/order'); // Redirect to reload the order form
                            return;
                        }
                    }
                } catch (Exception $e) {
                    // Handle database errors during product fetching
                    $this->logger->error("Error fetching product details for order form.", ['product_ids' => $productIds, 'exception' => $e]);
                    $this->session->flash('error', 'Could not load product details. Please try again.');
                    Redirect::to('/cart'); // Redirect to cart page on error
                    return;
                }
            }
        }

        // If the cart is empty or all items were invalid
        if (empty($cartItemsDetails)) {
            $this->session->flash('info', 'Your cart is empty. Add some products before placing an order.');
            Redirect::to('/categories'); // Redirect to categories page
            return;
        }

        // Generate CSRF token
        $csrfToken = $this->session->getCsrfToken();

        // Prepare data for the view
        $data = [
            'cartItems' => $cartItemsDetails,
            'totalAmount' => $totalAmount,
            'csrfToken' => $csrfToken,
            'page_title' => 'Place Your Order',
            'meta_description' => 'Review your cart and place your order.',
            'meta_keywords' => 'order, checkout, cart, grocery',
            'additional_css_files' => ['/assets/css/order.css'],
        ];

        // Render the order form view
        $this->view('pages/order_form', $data);
    }

    /**
     * Processes the submission of the main order form (from the cart).
     * Requires login, validates CSRF token, re-validates cart items and stock.
     * Creates the order, clears the cart, and redirects to confirmation on success.
     *
     * @return void Redirects to confirmation, back to form on error, or login page.
     */
    public function processOrder(): void
    {
        // Only allow POST requests
        if (!$this->request->isPost()) {
            $this->session->flash('error', 'Invalid request method.');
            Redirect::to('/order');
            return;
        }

        $this->session->requireLogin(); // Ensure user is logged in

        // Validate CSRF token
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid security token. Please try again.');
            Redirect::to('/order');
            return;
        }

        // Get user ID from session
        $userId = $this->session->get('user_id');
        if (!$userId) {
            $this->logger->error("User ID not found in session during order processing.");
            $this->session->flash('error', 'User session not found. Please log in again.');
            Redirect::to('/login');
            return;
        }

        // Retrieve cart from session
        $cart = $this->session->get('cart', []);
        if (empty($cart)) {
            $this->session->flash('error', 'Your cart is empty.');
            Redirect::to('/categories'); // Redirect if cart is empty
            return;
        }

        // Sanitize optional order notes
        $notes = SecurityHelper::sanitizeInput($this->request->post('order_notes', ''));

        // Prepare data for order items and calculate total amount
        $orderItemsData = [];
        $totalAmount = 0;
        $productIds = array_keys($cart);

        if (!empty($productIds)) {
            try {
                // Re-fetch product details to verify price and stock just before ordering
                $productsById = $this->productModel->findMultipleByIds($productIds);

                foreach ($cart as $productId => $quantity) {
                    // Debug logging to check the structure of the product data
                    $this->logger->debug("Product data structure check:", [
                        'product_id' => $productId,
                        'product_data' => $productsById[$productId] ?? 'Not found',
                        'has_stock_quantity' => isset($productsById[$productId]['stock_quantity']),
                        'stock_quantity_value' => $productsById[$productId]['stock_quantity'] ?? 'N/A',
                        'requested_quantity' => $quantity
                    ]);

                    // Check if product exists and has sufficient stock
                    if (isset($productsById[$productId]) && isset($productsById[$productId]['stock_quantity']) && $productsById[$productId]['stock_quantity'] >= $quantity) {
                        $price = $productsById[$productId]['price']; // Use current price
                        $orderItemsData[] = [
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'price' => $price
                        ];
                        $totalAmount += $price * $quantity;
                    } else {
                        // Product not found, insufficient stock, or other issue
                        $reason = isset($productsById[$productId]) ?
                            (isset($productsById[$productId]['stock_quantity']) ? 'insufficient stock' : 'stock quantity not available') :
                            'no longer available';
                        $this->logger->warning("Product ID {$productId} from cart invalid during order processing ({$reason}).", [
                            'requested_qty' => $quantity,
                            'available_qty' => $productsById[$productId]['stock_quantity'] ?? 'N/A',
                            'product_data_keys' => isset($productsById[$productId]) ? array_keys($productsById[$productId]) : []
                        ]);
                        $this->session->flash('error', 'Some items in your cart (' . ($productsById[$productId]['name'] ?? 'ID:' . $productId) . ') are no longer available in the requested quantity. Please review your order.');
                        // Don't remove from cart here, let user adjust on the order form
                        Redirect::to('/order'); // Redirect back to the order form
                        return;
                    }
                }
            } catch (Exception $e) {
                // Handle database errors during product re-fetch
                $this->logger->error("Error fetching product details for order processing.", ['product_ids' => $productIds, 'exception' => $e]);
                $this->session->flash('error', 'Could not verify product details. Please try again.');
                Redirect::to('/order');
                return;
            }
        } else {
            // This case should be rare if the initial cart check passed
            $this->logger->error("Cart product IDs array was empty during order processing.");
            $this->session->flash('error', 'Error processing cart items.');
            Redirect::to('/order');
            return;
        }

        // Attempt to create the order with the validated items and total
        $orderId = $this->createOrder($userId, $orderItemsData, $totalAmount, $notes);

        if ($orderId) {
            // Order created successfully
            $this->session->set('cart', []); // Clear the cart from the session
            $this->session->flash('success', 'Your order has been placed successfully!');
            Redirect::to('/order/confirmation/' . $orderId); // Redirect to confirmation
            return;
        } else {
            // Order creation failed (error logged within createOrder)
            $this->session->flash('error', 'Failed to place order. Please check the details and try again.');
            Redirect::to('/order'); // Redirect back to the order form
            return;
        }
    }

    /**
     * Displays the user's order history page.
     * Requires login. Fetches all orders for the current user.
     * Formats order data for display.
     *
     * @return void Renders the 'pages/my_orders' view.
     */
    public function myOrders(): void
    {
        $this->session->requireLogin(); // Ensure user is logged in
        $userId = $this->session->get('user_id');

        // Fetch orders for the logged-in user
        $orders = $this->getUserOrders($userId);

        // Handle potential errors during order fetching
        if ($orders === false) {
            $this->session->flash('error', 'Could not retrieve your orders at this time.');
            $orders = []; // Ensure $orders is an array for the loop
        }

        // Format each order for display (e.g., date formatting, status text)
        $formattedOrders = [];
        foreach ($orders as $order) {
            $formattedOrders[] = $this->formatOrderForDisplay($order);
        }

        // Prepare data for the view
        $data = [
            'orders' => $formattedOrders,
            'page_title' => 'My Orders',
            'meta_description' => 'View your past orders with GhibliGroceries.',
            'meta_keywords' => 'orders, history, purchase history, grocery',
            'additional_css_files' => ['/assets/css/order.css'],
        ];

        // Render the 'my orders' view
        $this->view('pages/my_orders', $data);
    }

    /**
     * Displays the order confirmation page after a successful order placement.
     * Requires login. Fetches details for the specified order ID, ensuring the
     * current user owns the order.
     *
     * @param array $params Associative array containing route parameters, expecting 'id' (order ID).
     * @return void Renders the 'pages/order_confirmation' view or redirects on error/invalid ID.
     */
    public function orderConfirmation($params): void
    {
        $this->session->requireLogin(); // Ensure user is logged in
        $userId = $this->session->get('user_id');

        // Validate order ID from route parameters
        $orderId = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$orderId) {
            $this->logger->warning("Order ID missing or invalid in confirmation request.", ['params' => $params]);
            $this->session->flash('error', 'Order ID not provided or invalid.');
            Redirect::to('/orders'); // Redirect to order history
            return;
        }

        // Fetch order details, ensuring the user owns this order
        $orderDetails = $this->getOrderDetails($orderId, $userId);

        // Handle case where order is not found or doesn't belong to the user
        if (!$orderDetails) {
            // Error message set within getOrderDetails if not found/accessible
            $this->session->flash('error', 'Could not retrieve order confirmation details or order not found.');
            Redirect::to('/orders');
            return;
        }

        // Prepare data for the view
        $data = [
            'order' => $this->formatOrderForDisplay($orderDetails), // Format data for display
            'page_title' => 'Order Confirmation #' . $orderId,
            'meta_description' => 'Your GhibliGroceries order #' . $orderId . ' has been placed successfully.',
            'meta_keywords' => 'order confirmation, grocery, purchase',
            'additional_css_files' => ['/assets/css/order.css'],
        ];

        // Render the confirmation view
        $this->view('pages/order_confirmation', $data);
    }

    /**
     * Displays the detailed view for a specific order.
     * Requires login. Fetches details for the specified order ID, ensuring the
     * current user owns the order. Includes order items.
     *
     * @param array $params Associative array containing route parameters, expecting 'id' (order ID).
     * @return void Renders the 'pages/order_details' view or redirects on error/invalid ID.
     */
    public function orderDetails($params): void
    {
        $this->session->requireLogin(); // Ensure user is logged in
        $userId = $this->session->get('user_id');

        // Validate order ID from route parameters
        $orderId = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$orderId) {
            $this->logger->warning("Order ID missing or invalid in details request.", ['params' => $params]);
            $this->session->flash('error', 'Order ID not provided or invalid.');
            Redirect::to('/orders'); // Redirect to order history
            return;
        }

        // Fetch order details, including items, ensuring user ownership
        $orderDetails = $this->getOrderDetails($orderId, $userId);

        // Handle case where order is not found or doesn't belong to the user
        if (!$orderDetails) {
            $this->session->flash('error', 'Could not retrieve order details or order not found.');
            Redirect::to('/orders');
            return;
        }

        // Prepare data for the view
        $data = [
            'order' => $this->formatOrderForDisplay($orderDetails), // Format data
            'page_title' => 'Order Details #' . $orderId,
            'meta_description' => 'Details for your GhibliGroceries order #' . $orderId . '.',
            'meta_keywords' => 'order details, grocery, purchase',
            'additional_css_files' => ['/assets/css/order.css'],
            'csrfToken' => $this->session->getCsrfToken() // For the cancel button form
        ];

        // Render the order details view
        $this->view('pages/order_details', $data);
    }

    /**
     * Handles the request to cancel an order.
     * Requires login and POST method. Validates CSRF token and order ID.
     * Attempts to update the order status to 'cancelled'. Only works if the
     * order is currently 'pending'.
     *
     * @param array $params Associative array containing route parameters, expecting 'id' (order ID).
     * @return void Redirects to order history on success/failure, or details page on error.
     */
    public function cancelOrder($params): void
    {
        // Only allow POST requests for cancellation
        if (!$this->request->isPost()) {
            $this->session->flash('error', 'Invalid request method.');
            Redirect::to('/orders'); // Redirect to order history
            return;
        }

        $this->session->requireLogin(); // Ensure user is logged in
        $userId = $this->session->get('user_id');

        // Validate order ID from route parameters
        $orderId = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$orderId) {
            $this->logger->warning("Order ID missing or invalid in cancel request.", ['params' => $params]);
            $this->session->flash('error', 'Order ID not provided for cancellation.');
            Redirect::to('/orders');
            return;
        }

        // Validate CSRF token from the form submission
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid security token. Please try again.');
            Redirect::to('/order/details/' . $orderId); // Redirect back to details page
            return;
        }

        // Attempt to update the order status to 'cancelled'
        if ($this->updateOrderStatus($orderId, 'cancelled', $userId)) {
            // Cancellation successful
            $this->session->flash('success', 'Order #' . $orderId . ' has been cancelled.');
            Redirect::to('/orders'); // Redirect to order history
        } else {
            // Cancellation failed (e.g., order not pending, not found, DB error)
            // Specific reason logged within updateOrderStatus
            $this->session->flash('error', 'Could not cancel order. It might have already been processed or cancelled.');
            Redirect::to('/order/details/' . $orderId); // Redirect back to details page
        }
    }

    /**
     * Creates a new order and its associated items in the database within a transaction.
     * Updates product stock levels.
     *
     * @param int $user_id The ID of the user placing the order.
     * @param array $items An array of items, each containing 'product_id', 'quantity', 'price'.
     * @param float $total_amount The total calculated amount for the order.
     * @param string|null $notes Optional notes provided by the user.
     * @return int|false The ID of the newly created order on success, or false on failure.
     */
    private function createOrder(int $user_id, array $items, float $total_amount, ?string $notes = null): int|false
    {
        try {
            // Start a database transaction
            $this->orderModel->beginTransaction();

            // Prepare order data
            $orderData = [
                'user_id' => $user_id,
                'total_amount' => $total_amount,
                'status' => 'pending', // Initial status
                'notes' => $notes
            ];
            // Create the main order record
            $orderId = $this->orderModel->create($orderData);

            // Check if order creation was successful
            if (!$orderId) {
                throw new Exception("Failed to create order record in database.");
            }

            // Process each item in the order
            foreach ($items as $item) {
                // Prepare order item data
                $orderItemData = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] // Price at the time of order
                ];
                // Create the order item record
                if (!$this->orderItemModel->create($orderItemData)) {
                    throw new Exception("Failed to create order item record for product ID " . $item['product_id']);
                }

                // --- Stock Update ---
                // Check current stock level *within the transaction* for consistency
                $stockLevel = $this->productModel->checkStock($item['product_id']);

                // Ensure stock is sufficient before updating
                if ($stockLevel !== false && $stockLevel >= $item['quantity']) {
                    // Get raw PDO connection for direct update within transaction
                    $db = Registry::get('database')->getConnection();
                    $newStockLevel = $stockLevel - $item['quantity'];
                    // Prepare and execute stock update query
                    $updateStmt = $db->prepare("UPDATE products SET stock_quantity = :new_qty WHERE product_id = :product_id");
                    $updateStmt->bindParam(':new_qty', $newStockLevel, PDO::PARAM_INT);
                    $updateStmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
                    if (!$updateStmt->execute()) {
                        // If stock update fails, the transaction will be rolled back
                        throw new Exception("Failed to update product stock for product ID " . $item['product_id']);
                    }
                } else {
                    // If stock check fails (product gone or insufficient stock discovered mid-transaction)
                    throw new Exception("Insufficient stock detected during transaction for product ID " . $item['product_id']);
                }
                // --- End Stock Update ---
            }

            // If all steps succeeded, commit the transaction
            $this->orderModel->commit();
            return $orderId; // Return the new order ID

        } catch (Exception $e) {
            // If any exception occurred, roll back the transaction
            $this->orderModel->rollback();
            // Log the error with details
            $this->logger->error("Error creating order: " . $e->getMessage(), [
                'user_id' => $user_id,
                'items_count' => count($items), // Avoid logging potentially large item array directly
                'total_amount' => $total_amount,
                'exception' => $e // Include exception details if possible/safe
            ]);
            return false; // Indicate failure
        }
    }

    /**
     * Retrieves detailed information for a specific order, including its items.
     * Ensures the specified user ID matches the order's user ID.
     * Used for displaying order details, confirmation, and by the API.
     *
     * @param int $order_id The ID of the order to retrieve.
     * @param int $user_id The ID of the user requesting the details (for authorization).
     * @return array|false An associative array with order details and items on success, false on failure or if not found/authorized.
     */
    public function getOrderDetails(int $order_id, int $user_id): array|false
    {
        try {
            // Fetch the main order data, checking ownership
            $orderData = $this->orderModel->readOneByIdAndUser($order_id, $user_id);

            // If order not found or user doesn't own it
            if (!$orderData) {
                $errorMessage = $this->orderModel->getErrorMessage() ?: "Order not found or access denied.";
                // Log only if it's genuinely not found, not just access denied
                if (strpos($errorMessage, 'denied') === false) {
                    $this->logger->warning($errorMessage, ['order_id' => $order_id, 'user_id' => $user_id]);
                }
                return false; // Indicate failure/not found/unauthorized
            }

            // Fetch the associated order items
            $items_stmt = $this->orderItemModel->readByOrder($order_id);
            if (!$items_stmt) {
                // Log error if fetching items fails
                $errorMessage = "Failed to get order items: " . $this->orderItemModel->getErrorMessage();
                $this->logger->error($errorMessage, ['order_id' => $order_id]);
                // Return false as the order details are incomplete without items
                return false;
            }

            // Fetch all items into an array
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            // Add the items array to the order data
            $orderData['items'] = $items;

            return $orderData; // Return the complete order details

        } catch (Exception $e) {
            // Log any unexpected exceptions
            $this->logger->error("Error getting order details: " . $e->getMessage(), ['exception' => $e, 'order_id' => $order_id, 'user_id' => $user_id]);
            return false; // Indicate failure
        }
    }

    /**
     * Retrieves all orders placed by a specific user.
     * Used for the 'My Orders' page and by the APIController.
     *
     * @param int $user_id The ID of the user whose orders are to be retrieved.
     * @return array|false An array of orders (each as an associative array) on success, false on failure.
     */
    public function getUserOrders(int $user_id): array|false
    {
        try {
            // Fetch orders using the Order model
            $orders_stmt = $this->orderModel->readByUser($user_id);

            // Check if the statement was prepared successfully
            if (!$orders_stmt) {
                $errorMessage = "Failed to get user orders statement: " . $this->orderModel->getErrorMessage();
                $this->logger->error($errorMessage, ['user_id' => $user_id]);
                return false; // Indicate failure
            }

            // Fetch all orders into an array
            $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
            return $orders; // Return the array of orders (can be empty)

        } catch (Exception $e) {
            // Log any unexpected exceptions
            $this->logger->error("Error getting user orders: " . $e->getMessage(), ['exception' => $e, 'user_id' => $user_id]);
            return false; // Indicate failure
        }
    }

    /**
     * Updates the status of an order, specifically for user actions (like cancellation).
     * Performs checks: valid status, order ownership, and current order state (e.g., cannot cancel completed orders).
     * Uses a transaction to ensure atomicity if multiple checks/updates were needed (though currently simple).
     *
     * @param int $order_id The ID of the order to update.
     * @param string $status The new status to set (e.g., 'cancelled').
     * @param int $user_id The ID of the user attempting the update (for authorization).
     * @return bool True on successful update, false otherwise.
     */
    private function updateOrderStatus(int $order_id, string $status, int $user_id): bool
    {
        // Define valid statuses a user can potentially set (currently only 'cancelled')
        $valid_statuses = ['cancelled']; // Extend if users can perform other status changes
        if (!in_array($status, $valid_statuses)) {
            $this->logger->warning("Invalid status value provided for user update.", ['order_id' => $order_id, 'user_id' => $user_id, 'status' => $status]);
            return false;
        }

        // Get PDO connection for transaction management
        $db = Registry::get('database');
        $pdoConnection = $db->getConnection();
        if (!$pdoConnection) {
            $this->logger->critical("Failed to get PDO connection for order status update transaction.");
            return false;
        }

        try {
            // Begin transaction
            $pdoConnection->beginTransaction();

            // Fetch current order data, verifying ownership
            $currentOrderData = $this->orderModel->readOneByIdAndUser($order_id, $user_id);

            // Check if order exists and belongs to the user
            if (!$currentOrderData) {
                $errorMessage = $this->orderModel->getErrorMessage() ?: "Order not found or access denied for status update.";
                if (strpos($errorMessage, 'denied') === false) { // Log if not found, not just denied
                    $this->logger->warning($errorMessage, ['order_id' => $order_id, 'user_id' => $user_id]);
                }
                $pdoConnection->rollBack(); // Rollback transaction
                return false;
            }

            // Check current status to prevent invalid transitions
            $currentStatus = $currentOrderData['status'];
            if ($currentStatus === 'completed' || $currentStatus === 'cancelled') {
                // Cannot change status of already completed or cancelled orders
                $this->logger->info("User attempted to change status of an already {$currentStatus} order.", ['order_id' => $order_id, 'user_id' => $user_id, 'new_status' => $status]);
                $pdoConnection->rollBack();
                return false;
            }

            // Specific logic for cancellation: only allow if status is 'pending'
            if ($status === 'cancelled' && $currentStatus !== 'pending') {
                $this->logger->info("User attempted to cancel a non-pending order.", ['order_id' => $order_id, 'user_id' => $user_id, 'current_status' => $currentStatus]);
                $pdoConnection->rollBack();
                return false;
            }

            // Perform the status update via the model
            if (!$this->orderModel->updateStatus($order_id, $status)) {
                $errorMessage = "Failed to update order status in database: " . $this->orderModel->getErrorMessage();
                $pdoConnection->rollBack(); // Rollback on update failure
                $this->logger->error($errorMessage, ['order_id' => $order_id, 'status' => $status]);
                return false;
            }

            // If all checks and the update passed, commit the transaction
            $pdoConnection->commit();
            $this->logger->info("Order status updated by user.", ['order_id' => $order_id, 'user_id' => $user_id, 'new_status' => $status]);
            return true; // Indicate success

        } catch (Exception $e) {
            // Catch any exceptions during the process
            // Ensure rollback if transaction is still active
            if ($pdoConnection->inTransaction()) {
                $pdoConnection->rollBack();
            }
            $this->logger->error("Error updating order status: " . $e->getMessage(), ['exception' => $e, 'order_id' => $order_id, 'user_id' => $user_id]);
            return false; // Indicate failure
        }
    }

    /**
     * Updates the status of an order, intended for manager/admin actions.
     * Performs checks: valid status, order existence, and current order state.
     * Does NOT check user ownership, assuming the caller (Admin/API) has authorization.
     *
     * @param int $order_id The ID of the order to update.
     * @param string $status The new status to set (e.g., 'processing', 'shipped', 'completed').
     * @return bool True on successful update, false otherwise.
     */
    public function updateOrderStatusAsManager(int $order_id, string $status): bool
    {
        // Define valid statuses a manager can set
        $valid_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            $this->logger->warning("Invalid status value provided for manager update.", ['order_id' => $order_id, 'status' => $status]);
            return false;
        }

        try {
            // Check if the order exists (doesn't check user ownership)
            $orderExists = $this->orderModel->readOne($order_id);
            if (!$orderExists) {
                $this->logger->warning("Order not found for manager status update.", ['order_id' => $order_id]);
                return false;
            }

            // Check current status to prevent invalid transitions (e.g., changing a completed order)
            $currentStatus = $orderExists['status'];
            if ($currentStatus === 'completed' || $currentStatus === 'cancelled') {
                $this->logger->info("Manager attempted to change status of an already {$currentStatus} order.", ['order_id' => $order_id, 'new_status' => $status]);
                // Optionally allow changing from 'cancelled' back to 'pending'? Depends on business logic.
                return false; // Currently disallow changing completed/cancelled orders
            }

            // Perform the status update via the model
            if (!$this->orderModel->updateStatus($order_id, $status)) {
                $errorMessage = "Failed to update order status via manager action: " . $this->orderModel->getErrorMessage();
                $this->logger->error($errorMessage, ['order_id' => $order_id, 'status' => $status]);
                return false;
            }

            // Log successful update by manager
            $this->logger->info("Order status updated by manager.", ['order_id' => $order_id, 'new_status' => $status]);
            return true; // Indicate success

        } catch (Exception $e) {
            // Log any exceptions during the process
            $this->logger->error("Error during manager order status update: " . $e->getMessage(), ['exception' => $e, 'order_id' => $order_id, 'status' => $status]);
            return false; // Indicate failure
        }
    }

    /**
     * Formats raw order data (including items) for display in views.
     * Adds formatted dates, amounts, status text/classes, and image URLs.
     * Sanitizes user-provided data like notes.
     *
     * @param array $order_details Raw order data fetched from the database, potentially including an 'items' array.
     * @return array The formatted order data ready for the view. Returns empty array if input is empty.
     */
    private function formatOrderForDisplay(array $order_details): array
    {
        // Return early if input is empty
        if (empty($order_details)) {
            return [];
        }

        $formatted = $order_details; // Start with the original data

        // Format order date
        if (!empty($formatted['order_date'])) {
            try {
                $date = new \DateTime($formatted['order_date']);
                // Example format: Mon, 01 Jan 2024 15:30
                $formatted['order_date_formatted'] = $date->format('D, d M Y H:i');
            } catch (Exception $e) {
                // Handle invalid date format from DB
                $formatted['order_date_formatted'] = 'Invalid Date';
                $this->logger->warning("Invalid date format encountered for order ID {$formatted['order_id']}", ['date' => $formatted['order_date']]);
            }
        } else {
            $formatted['order_date_formatted'] = 'N/A'; // Fallback if date is missing
        }

        // Map status codes to human-readable text and CSS classes
        $status_map = [
            'pending' => ['text' => 'Pending Confirmation', 'class' => 'status-pending'],
            'processing' => ['text' => 'Processing', 'class' => 'status-processing'],
            'shipped' => ['text' => 'Shipped', 'class' => 'status-shipped'],
            'completed' => ['text' => 'Completed', 'class' => 'status-completed'],
            'cancelled' => ['text' => 'Cancelled', 'class' => 'status-cancelled']
        ];
        // Get status info from map, or provide a default for unknown statuses
        $status_info = $status_map[$formatted['status']] ?? ['text' => ucfirst($formatted['status'] ?? 'Unknown'), 'class' => 'status-unknown'];
        $formatted['status_text'] = $status_info['text'];
        $formatted['status_class'] = $status_info['class'];

        // Format total amount as currency
        $formatted['total_amount_formatted'] = '$' . number_format($formatted['total_amount'] ?? 0, 2);

        // Format details for each item if present
        if (isset($formatted['items']) && is_array($formatted['items'])) {
            foreach ($formatted['items'] as &$item) { // Use reference to modify array directly
                if (is_array($item)) {
                    // Format item price
                    $item['price_formatted'] = '$' . number_format($item['price'] ?? 0, 2);
                    // Calculate and format subtotal if not already present
                    if (!isset($item['subtotal'])) {
                        $item['subtotal'] = ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                    }
                    $item['subtotal_formatted'] = '$' . number_format($item['subtotal'] ?? 0, 2);
                    // Construct image URL safely using basename
                    $image_filename = $item['product_image'] ?? ($item['image'] ?? 'default.png'); // Check multiple possible field names
                    $item['image_url'] = '/assets/images/products/' . basename($image_filename);
                    // Sanitize product name
                    $item['product_name'] = htmlspecialchars($item['product_name'] ?? 'N/A');
                }
            }
            unset($item); // Unset reference after loop
        } elseif (!isset($formatted['items'])) {
            // Ensure 'items' key exists even if empty
            $formatted['items'] = [];
        }

        // Sanitize other potentially user-provided or dynamic fields
        $formatted['notes'] = isset($formatted['notes']) ? htmlspecialchars($formatted['notes']) : '';
        $formatted['user_name'] = isset($formatted['user_name']) ? htmlspecialchars($formatted['user_name']) : 'N/A'; // If joined with user table
        $formatted['user_email'] = isset($formatted['user_email']) ? htmlspecialchars($formatted['user_email']) : 'N/A'; // If joined
        $formatted['order_id'] = isset($formatted['order_id']) ? htmlspecialchars($formatted['order_id']) : 'N/A'; // Sanitize just in case

        return $formatted; // Return the fully formatted array
    }
}
