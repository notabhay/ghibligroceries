<?php

namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Session;
use App\Core\Request;
use App\Core\Redirect;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem; // Although not directly used here, it's related to Order details

/**
 * Class AdminOrderController
 *
 * Manages administrative tasks related to customer orders.
 * This includes listing orders with filtering options, viewing detailed order information,
 * and updating order statuses within the admin panel.
 * It interacts primarily with the Order and User models.
 *
 * @package App\Controllers\Admin
 */
class AdminOrderController extends BaseController
{
    /**
     * @var Database Database connection instance.
     */
    private $db;

    /**
     * @var Session Session management instance.
     */
    private $session;

    /**
     * @var Request HTTP request handling instance.
     */
    private $request;

    /**
     * @var Order Order model instance for database interactions related to orders.
     */
    private $orderModel;

    /**
     * @var User User model instance, used here mainly to fetch admin user details.
     */
    private $userModel;

    /**
     * AdminOrderController constructor.
     *
     * Initializes dependencies: Database, Session, Request, and instances of the
     * Order and User models.
     *
     * @param Database $db The database connection instance.
     * @param Session $session The session management instance.
     * @param Request $request The HTTP request handling instance.
     */
    public function __construct(Database $db, Session $session, Request $request)
    {
        $this->db = $db;
        $this->session = $session;
        $this->request = $request;
        $this->orderModel = new Order($db->getConnection());
        $this->userModel = new User($db->getConnection()); // Assuming User model needs connection
    }

    /**
     * Displays the main order management page with filtering and pagination.
     *
     * Fetches a paginated list of orders, applying filters based on GET parameters
     * (status, start_date, end_date). Retrieves the current admin user's details,
     * generates a CSRF token, and renders the order index view within the admin layout.
     *
     * @return void
     */
    public function index(): void
    {
        // Get current page number from query string, default to 1
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = 15; // Number of orders per page

        // Collect filter parameters from the GET request
        $filters = [];
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            // Basic validation/sanitization could be added here
            $filters['status'] = $_GET['status'];
        }
        if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
            // Basic validation/sanitization could be added here
            $filters['start_date'] = $_GET['start_date'];
        }
        if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
            // Basic validation/sanitization could be added here
            $filters['end_date'] = $_GET['end_date'];
        }

        // Fetch paginated and filtered orders from the model
        $result = $this->orderModel->getAllOrdersPaginated($page, $perPage, $filters);

        // Get current admin user details for the layout
        $userId = $this->session->getUserId();
        $adminUser = $this->userModel->findById($userId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Manage Orders',
            'admin_user' => $adminUser,
            'orders' => $result['orders'] ?? [], // Orders for the current page
            'pagination' => $result['pagination'] ?? [], // Pagination data
            'filters' => $filters, // Pass filters back to the view for display/persistence
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token (might be used for batch actions later)
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/orders/index', $data);
    }

    /**
     * Displays the detailed view for a specific order.
     *
     * Fetches the order details (including associated items and user info) using
     * the Order model's `findOrderWithDetails` method. If the order is not found,
     * redirects back with an error. Retrieves admin user details, generates a CSRF token
     * (for the status update form), and renders the order show view.
     *
     * @param int $id The ID of the order to display.
     * @return void
     */
    public function show(int $id): void
    {
        // Fetch detailed order information (includes items, user, etc.)
        $order = $this->orderModel->findOrderWithDetails($id);

        // Redirect if order not found
        if (!$order) {
            $this->session->flash('error', 'Order not found.');
            Redirect::to('/admin/orders');
            exit();
        }

        // Get current admin user details
        $adminUserId = $this->session->getUserId();
        $adminUser = $this->userModel->findById($adminUserId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Order Details',
            'admin_user' => $adminUser,
            'order' => $order, // The detailed order object/array
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token for the status update form
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/orders/show', $data);
    }

    /**
     * Updates the status of a specific order.
     *
     * Handles the POST request from the order details page to change the order status.
     * Verifies the CSRF token, finds the order, validates the new status against
     * allowed values ('pending', 'processing', 'completed', 'cancelled'), attempts
     * the update using the Order model, sets flash messages, and redirects back
     * to the order details page.
     *
     * @param int $id The ID of the order whose status is to be updated.
     * @return void
     */
    public function updateStatus(int $id): void
    {
        // Verify CSRF token from the POST request
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid form submission. Please try again.');
            Redirect::to('/admin/orders/' . $id); // Redirect back to the specific order page
            exit();
        }

        // Find the order to update (basic info is sufficient here)
        $order = $this->orderModel->readOne($id); // Assuming readOne fetches basic order data
        if (!$order) {
            $this->session->flash('error', 'Order not found.');
            Redirect::to('/admin/orders'); // Redirect to the orders list if not found
            exit();
        }

        // Get the new status from the POST request
        $newStatus = $this->request->post('status');

        // Validate the new status against a predefined list of valid statuses
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->session->flash('error', 'Invalid order status provided.');
            Redirect::to('/admin/orders/' . $id); // Redirect back to the specific order page
            exit();
        }

        // Attempt to update the order status in the database via the model
        $success = $this->orderModel->updateOrderStatus($id, $newStatus);

        // Set flash message based on success or failure
        if ($success) {
            $this->session->flash('success', "Order #{$id} status updated to '{$newStatus}'.");
        } else {
            // Include potential error message from the model if available
            $errorMessage = $this->orderModel->getErrorMessage() ?: 'Failed to update order status.';
            $this->session->flash('error', $errorMessage . ' Please try again.');
        }

        // Redirect back to the order details page regardless of success/failure
        Redirect::to('/admin/orders/' . $id);
    }

    /**
     * Renders a view file within the standard admin layout.
     *
     * This is a protected helper method used by other actions in this controller
     * to ensure consistent layout across the admin order management section.
     * It handles path construction, file existence checks, data extraction,
     * and output buffering to inject the view's content into the layout.
     * It also attempts to determine the current request path for navigation highlighting.
     *
     * @param string $view The path to the view file (relative to the Views directory, using dot notation).
     * @param array $data An associative array of data to be extracted and made available to the view and layout.
     * @return void
     */
    protected function viewWithAdminLayout(string $view, array $data = []): void
    {
        // Construct full paths to the view and layout files
        $viewPath = __DIR__ . '/../../Views/' . str_replace('.', '/', $view) . '.php';
        $layoutPath = __DIR__ . '/../../Views/layouts/admin.php';

        // Check if the view file exists
        if (!file_exists($viewPath)) {
            trigger_error("View file not found: {$viewPath}", E_USER_WARNING);
            echo "Error: View file '{$view}' not found.";
            exit; // Stop execution if view is missing
        }

        // Check if the admin layout file exists
        if (!file_exists($layoutPath)) {
            trigger_error("Layout file not found: {$layoutPath}", E_USER_WARNING);
            echo "Error: Admin layout file not found.";
            exit; // Stop execution if layout is missing
        }

        // Attempt to get the current request path for navigation highlighting
        try {
            // Retrieve the request object from the registry (if available)
            $request = \App\Core\Registry::get('request');
            $uri = $request->uri();
            // Prepend slash for consistency
            $currentPath = '/' . ($uri ?: '');
            $data['currentPath'] = $currentPath;
        } catch (\Exception $e) {
            // Fallback if request object is not available or URI fails
            $data['currentPath'] = '/';
        }

        // Extract the data array into individual variables accessible by the view and layout
        extract($data);

        // Start output buffering to capture the view's content
        ob_start();
        try {
            // Include the specific view file
            include $viewPath;
        } catch (\Throwable $e) {
            // Clean buffer and display error if view rendering fails
            ob_end_clean();
            error_log("Error rendering view '{$view}': " . $e->getMessage()); // Log the actual error
            echo "Error rendering view '{$view}'. Please check the logs.";
            exit;
        }
        // Get the captured content from the buffer
        $content = ob_get_clean();

        // Make the captured view content available to the layout file
        $data['content'] = $content;

        // Extract data again to ensure $content is available in the layout's scope
        extract($data);

        // Include the main admin layout file, which will render the overall structure
        // and incorporate the $content variable.
        include $layoutPath;
    }
}
