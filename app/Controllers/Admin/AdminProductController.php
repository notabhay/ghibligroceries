<?php

namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Session;
use App\Core\Request;
use App\Core\Redirect;
use App\Models\Product;
use App\Models\Category;
use App\Models\User as UserModel; // Alias User model
use App\Helpers\SecurityHelper;

/**
 * Class AdminProductController
 *
 * Handles administrative tasks related to products, including listing, creating,
 * editing, updating status, and managing product images within the admin panel.
 * It interacts with the Product and Category models for data operations and
 * uses the admin layout for rendering views.
 *
 * @package App\Controllers\Admin
 */
class AdminProductController extends BaseController
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
     * @var Product Product model instance for database interactions related to products.
     */
    private $productModel;

    /**
     * @var Category Category model instance, used for fetching categories for selection.
     */
    private $categoryModel;

    /**
     * AdminProductController constructor.
     *
     * Initializes dependencies: Database, Session, Request, and instances of the
     * Product and Category models.
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
        $this->productModel = new Product($db->getConnection()); // Assuming Product model needs connection
        $this->categoryModel = new Category($db->getConnection());
    }

    /**
     * Displays the main product management page with filtering and pagination.
     *
     * Fetches a paginated list of products, applying filters based on GET parameters
     * (category_id, is_active). Retrieves all categories (for the filter dropdown),
     * gets the current admin user's details, generates a CSRF token, and renders
     * the product index view within the admin layout.
     *
     * @return void
     */
    public function index(): void
    {
        // Get current page number from query string, default to 1
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = 15; // Number of products per page

        // Collect filter parameters from the GET request
        $filters = [];
        if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
            $filters['category_id'] = (int) $_GET['category_id'];
        }
        // Check specifically for '0' or '1' for active status filter
        if (isset($_GET['is_active']) && ($_GET['is_active'] === '0' || $_GET['is_active'] === '1')) {
            $filters['is_active'] = (int) $_GET['is_active'];
        }

        // Fetch paginated and filtered products from the model
        $result = $this->productModel->getAllProductsPaginated($page, $perPage, $filters);

        // Fetch all categories for the filter dropdown in the view
        $categories = $this->categoryModel->getAll();

        // Get current admin user details for the layout
        $userId = $this->session->getUserId();
        $adminUserModel = new UserModel($this->db); // Use aliased UserModel
        $adminUser = $adminUserModel->findById($userId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Manage Products',
            'admin_user' => $adminUser,
            'products' => $result['products'] ?? [], // Products for the current page
            'pagination' => $result['pagination'] ?? [], // Pagination data
            'filters' => $filters, // Pass filters back to the view
            'categories' => $categories, // Pass categories for the filter dropdown
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token (e.g., for toggle status forms)
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/products/index', $data);
    }

    /**
     * Displays the form for creating a new product.
     *
     * Fetches all categories (for the category selection dropdown), retrieves the
     * current admin user's details, generates a CSRF token, and renders the
     * product creation view within the admin layout.
     *
     * @return void
     */
    public function create(): void
    {
        // Fetch all categories for the selection dropdown
        $categories = $this->categoryModel->getAll();

        // Get current admin user details
        $userId = $this->session->getUserId();
        $adminUserModel = new UserModel($this->db); // Use aliased UserModel
        $adminUser = $adminUserModel->findById($userId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Add New Product',
            'admin_user' => $adminUser,
            'categories' => $categories, // List of categories for selection
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token for the form
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/products/create', $data);
    }

    /**
     * Stores a new product submitted via the creation form.
     *
     * Verifies the CSRF token, validates submitted data (name, description, price,
     * category, stock, active status), handles the product image upload, attempts
     * to create the product using the Product model, sets flash messages, and redirects.
     *
     * @return void
     */
    public function store(): void
    {
        // Verify CSRF token
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid form submission. Please try again.');
            Redirect::to('/admin/products/create');
            exit();
        }

        // Get and sanitize input data from POST request
        $name = trim($this->request->post('name'));
        $description = trim($this->request->post('description'));
        $price = $this->request->post('price'); // Validation will check numeric
        $categoryId = (int) $this->request->post('category_id');
        $stockQuantity = (int) $this->request->post('stock_quantity');
        $isActive = $this->request->post('is_active') ? 1 : 0; // Convert checkbox value to 1 or 0

        // Validate input data
        $errors = [];
        if (empty($name)) {
            $errors[] = 'Product name is required.';
        } elseif (strlen($name) > 100) { // Example length limit
            $errors[] = 'Product name must be less than 100 characters.';
        }
        if (empty($description)) {
            $errors[] = 'Product description is required.';
        }
        if (empty($price) || !is_numeric($price) || $price <= 0) {
            $errors[] = 'Valid positive product price is required.';
        }
        if (empty($categoryId) || $categoryId <= 0 || !$this->categoryModel->findById($categoryId)) { // Check if category exists
            $errors[] = 'Valid category is required.';
        }
        if ($stockQuantity < 0) {
            $errors[] = 'Stock quantity cannot be negative.';
        }

        // Handle image upload
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleImageUpload($_FILES['image']);
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['path']; // Relative path from public dir
            } else {
                $errors[] = $uploadResult['error']; // Add upload error to validation errors
            }
        } else {
            // Check for other upload errors or if file is missing
            $uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Error uploading image. Code: ' . $uploadError;
            } else {
                $errors[] = 'Product image is required.';
            }
        }

        // If validation or upload errors exist, redirect back with errors
        if (!empty($errors)) {
            $this->session->flash('errors', $errors);
            // Persist input data? Consider adding old input flashing
            Redirect::to('/admin/products/create');
            exit();
        }

        // Prepare data for model insertion
        $productData = [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'category_id' => $categoryId,
            'image_path' => $imagePath, // Store the relative path
            'stock_quantity' => $stockQuantity,
            'is_active' => $isActive
        ];

        // Attempt to create the product
        $productId = $this->productModel->createProduct($productData);

        // Handle success or failure
        if ($productId) {
            $this->session->flash('success', 'Product created successfully.');
            Redirect::to('/admin/products');
        } else {
            $this->session->flash('error', 'Failed to create product. Please try again.');
            // Optionally delete uploaded image if db insert fails
            if (!empty($imagePath) && file_exists(__DIR__ . '/../../../public/' . $imagePath)) {
                unlink(__DIR__ . '/../../../public/' . $imagePath);
            }
            Redirect::to('/admin/products/create');
        }
    }

    /**
     * Displays the form for editing an existing product.
     *
     * Finds the product by its ID using the Product model. If not found, redirects
     * back with an error. Fetches all categories (for selection), retrieves admin
     * user details, generates a CSRF token, and renders the product edit view.
     *
     * @param int $id The ID of the product to edit.
     * @return void
     */
    public function edit(int $id): void
    {
        // Find the product to edit
        $product = $this->productModel->findById($id);

        // Redirect if product not found
        if (!$product) {
            $this->session->flash('error', 'Product not found.');
            Redirect::to('/admin/products');
            exit();
        }

        // Fetch all categories for the selection dropdown
        $categories = $this->categoryModel->getAll();

        // Get current admin user details
        $userId = $this->session->getUserId();
        $adminUserModel = new UserModel($this->db); // Use aliased UserModel
        $adminUser = $adminUserModel->findById($userId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Edit Product',
            'admin_user' => $adminUser,
            'product' => $product, // The product being edited
            'categories' => $categories, // List of categories for selection
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token for the form
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/products/edit', $data);
    }

    /**
     * Updates an existing product based on submitted edit form data.
     *
     * Verifies CSRF token, finds the product, validates submitted data, handles
     * potential new image upload (replacing old one if necessary), attempts to
     * update the product via the Product model, sets flash messages, and redirects.
     *
     * @param int $id The ID of the product to update.
     * @return void
     */
    public function update(int $id): void
    {
        // Verify CSRF token
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid form submission. Please try again.');
            Redirect::to('/admin/products/' . $id . '/edit');
            exit();
        }

        // Find the existing product
        $product = $this->productModel->findById($id);
        if (!$product) {
            $this->session->flash('error', 'Product not found.');
            Redirect::to('/admin/products');
            exit();
        }

        // Get and sanitize input data
        $name = trim($this->request->post('name'));
        $description = trim($this->request->post('description'));
        $price = $this->request->post('price');
        $categoryId = (int) $this->request->post('category_id');
        $stockQuantity = (int) $this->request->post('stock_quantity');
        $isActive = $this->request->post('is_active') ? 1 : 0;

        // Validate input data
        $errors = [];
        // (Validation logic is similar to store(), add checks here)
        if (empty($name)) $errors[] = 'Product name is required.';
        elseif (strlen($name) > 100) $errors[] = 'Product name must be less than 100 characters.';
        if (empty($description)) $errors[] = 'Product description is required.';
        if (empty($price) || !is_numeric($price) || $price <= 0) $errors[] = 'Valid positive product price is required.';
        if (empty($categoryId) || $categoryId <= 0 || !$this->categoryModel->findById($categoryId)) $errors[] = 'Valid category is required.';
        if ($stockQuantity < 0) $errors[] = 'Stock quantity cannot be negative.';

        // Handle potential new image upload
        $imagePath = $product['image_path']; // Keep old image path by default
        $oldImagePath = $product['image_path']; // Store old path for potential deletion
        $newImageUploaded = false;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleImageUpload($_FILES['image']);
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['path']; // Set new image path
                $newImageUploaded = true;
            } else {
                $errors[] = $uploadResult['error']; // Add upload error
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle other upload errors if a file was attempted but failed
            $errors[] = 'Error uploading new image. Code: ' . $_FILES['image']['error'];
        }

        // If validation or upload errors exist, redirect back
        if (!empty($errors)) {
            $this->session->flash('errors', $errors);
            // If a new image upload failed, don't delete the temporary file yet
            Redirect::to('/admin/products/' . $id . '/edit');
            exit();
        }

        // Prepare data for model update
        $productData = [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'category_id' => $categoryId,
            'image_path' => $imagePath, // Use new or existing path
            'stock_quantity' => $stockQuantity,
            'is_active' => $isActive
        ];

        // Attempt to update the product
        $success = $this->productModel->updateProduct($id, $productData);

        // Handle success or failure
        if ($success) {
            // If update succeeded and a new image was uploaded, delete the old one
            if ($newImageUploaded && !empty($oldImagePath) && $oldImagePath !== $imagePath) {
                $fullOldPath = __DIR__ . '/../../../public/' . $oldImagePath;
                if (file_exists($fullOldPath)) {
                    unlink($fullOldPath);
                }
            }
            $this->session->flash('success', 'Product updated successfully.');
            Redirect::to('/admin/products');
        } else {
            // If update failed but a new image was uploaded, delete the newly uploaded file
            if ($newImageUploaded) {
                $fullNewPath = __DIR__ . '/../../../public/' . $imagePath;
                if (file_exists($fullNewPath)) {
                    unlink($fullNewPath);
                }
            }
            $this->session->flash('error', 'Failed to update product. Please try again.');
            Redirect::to('/admin/products/' . $id . '/edit');
        }
    }

    /**
     * Toggles the active/inactive status of a product.
     *
     * Handles a POST request (usually from a button/link in the product list).
     * Verifies CSRF token, finds the product, calls the model method to toggle
     * the status, sets appropriate flash messages, and redirects back to the
     * product list.
     *
     * @param int $id The ID of the product whose status is to be toggled.
     * @return void
     */
    public function toggleActive(int $id): void
    {
        // Verify CSRF token from the POST request
        // Assumes the toggle action is submitted via a form with a CSRF token
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid action or token missing. Please try again.');
            Redirect::to('/admin/products');
            exit();
        }

        // Find the product
        $product = $this->productModel->findById($id);
        if (!$product) {
            $this->session->flash('error', 'Product not found.');
            Redirect::to('/admin/products');
            exit();
        }

        // Attempt to toggle the active status via the model
        $success = $this->productModel->toggleProductActiveStatus($id);

        // Set flash message based on success/failure and the new status
        if ($success) {
            // Determine the new status based on the *original* status before toggling
            $newStatus = $product['is_active'] ? 'inactive' : 'active';
            $this->session->flash('success', "Product '{$product['name']}' is now {$newStatus}.");
        } else {
            $this->session->flash('error', 'Failed to update product status. Please try again.');
        }

        // Redirect back to the product list page
        Redirect::to('/admin/products');
    }

    /**
     * Handles the upload process for product images.
     *
     * Validates the uploaded file's type and size. Generates a unique filename,
     * moves the uploaded file to the designated public directory, and returns
     * an array indicating success status and the relative path to the stored image
     * or an error message.
     *
     * @param array $file The file array from $_FILES (e.g., $_FILES['image']).
     * @return array An associative array with 'success' (bool) and either 'path' (string) or 'error' (string).
     */
    private function handleImageUpload(array $file): array
    {
        // Define allowed MIME types and maximum file size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            return [
                'success' => false,
                'error' => 'Invalid file type. Allowed types: JPG, PNG, GIF, WEBP.'
            ];
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds the maximum allowed (5MB).'
            ];
        }

        // Define the target directory for uploads (relative to the public folder)
        // Ensure this path is correct based on your project structure
        $uploadDirRelative = 'assets/images/products/uploads/';
        $uploadDirAbsolute = __DIR__ . '/../../../public/' . $uploadDirRelative;


        // Create the directory if it doesn't exist
        if (!is_dir($uploadDirAbsolute)) {
            if (!mkdir($uploadDirAbsolute, 0755, true)) {
                // Error creating directory
                error_log("Failed to create upload directory: " . $uploadDirAbsolute);
                return [
                    'success' => false,
                    'error' => 'Server error: Could not create image directory.'
                ];
            }
        }

        // Generate a unique filename to prevent overwrites and improve security
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Use a secure random string + timestamp for uniqueness
        $uniqueFilename = SecurityHelper::generateRandomString(16) . '_' . time() . '.' . $fileExtension;
        $uploadPathAbsolute = $uploadDirAbsolute . $uniqueFilename;
        $uploadPathRelative = $uploadDirRelative . $uniqueFilename; // Path to store in DB

        // Attempt to move the uploaded file from the temporary location
        if (move_uploaded_file($file['tmp_name'], $uploadPathAbsolute)) {
            // Success: return the relative path for database storage
            return [
                'success' => true,
                'path' => $uploadPathRelative
            ];
        } else {
            // Error moving file
            error_log("Failed to move uploaded file to: " . $uploadPathAbsolute);
            return [
                'success' => false,
                'error' => 'Failed to save uploaded file. Check permissions or server logs.'
            ];
        }
    }

    /**
     * Renders a view file within the standard admin layout.
     *
     * This is a protected helper method used by other actions in this controller
     * to ensure consistent layout across the admin product management section.
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
