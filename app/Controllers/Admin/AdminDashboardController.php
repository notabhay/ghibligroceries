<?php

namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;

/**
 * Class AdminDashboardController
 *
 * Handles the display of the main administrative dashboard.
 * It gathers various statistics and recent data from different models
 * (Users, Orders, Products, Categories) and presents them in the admin dashboard view.
 *
 * @package App\Controllers\Admin
 */
class AdminDashboardController extends BaseController
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
     * @var User User model instance for fetching user data.
     */
    private $userModel;

    /**
     * @var Order Order model instance for fetching order statistics and recent orders.
     */
    private $orderModel;

    /**
     * @var Product Product model instance for fetching product statistics.
     */
    private $productModel;

    /**
     * @var Category Category model instance for fetching category statistics.
     */
    private $categoryModel;

    /**
     * AdminDashboardController constructor.
     *
     * Initializes dependencies: Database, Session, and instances of User, Order,
     * Product, and Category models required for gathering dashboard data.
     *
     * @param Database $db The database connection instance.
     * @param Session $session The session management instance.
     */
    public function __construct(Database $db, Session $session)
    {
        $this->db = $db;
        $this->session = $session;
        // Instantiate models needed for dashboard statistics
        $this->userModel = new User($db);
        $this->orderModel = new Order($db->getConnection());
        $this->productModel = new Product($db); // Assuming Product model takes DB connection directly
        $this->categoryModel = new Category($db->getConnection());
    }

    /**
     * Displays the main admin dashboard page.
     *
     * Fetches various statistics like total users, order counts by status,
     * total products, total categories, low stock product count, and recent orders.
     * It also retrieves the currently logged-in admin user's details.
     * Finally, it renders the dashboard view within the admin layout, passing all collected data.
     *
     * @return void
     */
    public function index(): void
    {
        // Get current admin user details for the layout/header
        $userId = $this->session->getUserId();
        $adminUser = $this->userModel->findById($userId);

        // Fetch various statistics from different models
        $totalUsers = $this->userModel->getTotalUserCount();
        $totalOrders = $this->orderModel->getTotalOrderCount();
        $pendingOrders = $this->orderModel->getOrderCountByStatus('pending');
        $processingOrders = $this->orderModel->getOrderCountByStatus('processing');
        $completedOrders = $this->orderModel->getOrderCountByStatus('completed');
        $cancelledOrders = $this->orderModel->getOrderCountByStatus('cancelled');
        $recentOrders = $this->orderModel->getRecentOrders(5); // Get the 5 most recent orders
        $lowStockProducts = $this->productModel->getLowStockProductCount(); // Count products with low stock
        $totalProducts = $this->productModel->getTotalProductCount(); // Get total product count by counting all products from getAll()
        $totalCategories = $this->categoryModel->getTotalCategoryCount(); // Get total category count using getTotalCategoryCount()

        // Prepare data array for the view
        $data = [
            'page_title' => 'Admin Dashboard',
            'admin_user' => $adminUser, // Pass admin user details to the view
            'stats' => [ // Group statistics for easier access in the view
                'total_users' => $totalUsers,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'processing_orders' => $processingOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_products' => $totalProducts,
                'total_categories' => $totalCategories,
                'low_stock_products' => $lowStockProducts
            ],
            'recent_orders' => $recentOrders // Pass recent orders data to the view
        ];

        // Render the dashboard view using the admin layout
        $this->viewWithAdminLayout('admin/dashboard', $data);
    }

    /**
     * Renders a view file within the standard admin layout.
     *
     * This is a protected helper method used by other actions in this controller
     * (and potentially others inheriting from a common base or trait)
     * to ensure consistent layout across the admin panel.
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
