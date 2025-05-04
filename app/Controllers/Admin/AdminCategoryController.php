<?php

namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Session;
use App\Core\Request;
use App\Core\Redirect;
use App\Models\Category;
use App\Models\User as UserModel; // Alias User model to avoid naming conflict
use App\Helpers\SecurityHelper;

/**
 * Class AdminCategoryController
 *
 * Handles administrative tasks related to product categories, such as listing,
 * creating, editing, and deleting categories within the admin panel.
 * It interacts with the Category model for data operations and renders views
 * using the admin layout.
 *
 * @package App\Controllers\Admin
 */
class AdminCategoryController extends BaseController
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
     * @var Category Category model instance for database interactions.
     */
    private $categoryModel;

    /**
     * AdminCategoryController constructor.
     *
     * Initializes dependencies like Database, Session, Request, and the Category model.
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
        $this->categoryModel = new Category($db->getConnection());
    }

    /**
     * Displays the main category management page.
     *
     * Fetches a paginated list of categories from the Category model,
     * retrieves the current admin user's details, generates a CSRF token,
     * and renders the category index view within the admin layout.
     *
     * @return void
     */
    public function index(): void
    {
        // Get current page number from query string, default to 1
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = 15; // Number of categories per page

        // Fetch paginated categories from the model
        $result = $this->categoryModel->getAllCategoriesPaginated($page, $perPage);

        // Get current admin user details for the layout
        $userId = $this->session->getUserId();
        $adminUserModel = new UserModel($this->db); // Use aliased UserModel
        $adminUser = $adminUserModel->findById($userId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Manage Categories',
            'admin_user' => $adminUser,
            'categories' => $result['categories'] ?? [], // Categories for the current page
            'pagination' => $result['pagination'] ?? [], // Pagination data
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token for forms
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/categories/index', $data);
    }

    /**
     * Displays the form for creating a new category.
     *
     * Fetches all existing categories (for parent selection dropdown),
     * retrieves the current admin user's details, generates a CSRF token,
     * and renders the category creation view within the admin layout.
     *
     * @return void
     */
    public function create(): void
    {
        // Fetch all categories for the parent dropdown
        $categories = $this->categoryModel->getAll();

        // Get current admin user details
        $userId = $this->session->getUserId();
        $adminUserModel = new UserModel($this->db); // Use aliased UserModel
        $adminUser = $adminUserModel->findById($userId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Add New Category',
            'admin_user' => $adminUser,
            'categories' => $categories, // List of potential parent categories
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token for the form
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/categories/create', $data);
    }

    /**
     * Stores a new category submitted via the creation form.
     *
     * Verifies the CSRF token, validates the submitted category name and parent ID,
     * attempts to create the category using the Category model, sets appropriate
     * flash messages (success or error), and redirects back to the category list
     * or the creation form on failure.
     *
     * @return void
     */
    public function store(): void
    {
        // Verify CSRF token
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid form submission. Please try again.');
            Redirect::to('/admin/categories/create');
            exit();
        }

        // Get and sanitize input data
        $name = trim($this->request->post('name'));
        $parentId = (int) $this->request->post('parent_id');
        // Treat 0 as null for parent_id (top-level category)
        if ($parentId === 0) {
            $parentId = null;
        }

        // Validate input
        $errors = [];
        if (empty($name)) {
            $errors[] = 'Category name is required.';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Category name must be less than 100 characters.';
        }

        // If validation errors exist, redirect back with errors
        if (!empty($errors)) {
            $this->session->flash('errors', $errors);
            Redirect::to('/admin/categories/create');
            exit();
        }

        // Prepare data for model insertion
        $categoryData = [
            'name' => $name,
            'parent_id' => $parentId
        ];

        // Attempt to create the category
        $categoryId = $this->categoryModel->createCategory($categoryData);

        // Handle success or failure
        if ($categoryId) {
            $this->session->flash('success', 'Category created successfully.');
            Redirect::to('/admin/categories');
        } else {
            $this->session->flash('error', 'Failed to create category. Please try again.');
            Redirect::to('/admin/categories/create');
        }
    }

    /**
     * Displays the form for editing an existing category.
     *
     * Finds the category by its ID using the Category model. If not found,
     * redirects back with an error. Fetches all categories (for parent selection),
     * checks if the category has associated products, retrieves admin user details,
     * generates a CSRF token, and renders the category edit view.
     *
     * @param int $id The ID of the category to edit.
     * @return void
     */
    public function edit(int $id): void
    {
        // Find the category to edit
        $category = $this->categoryModel->findById($id);

        // Redirect if category not found
        if (!$category) {
            $this->session->flash('error', 'Category not found.');
            Redirect::to('/admin/categories');
            exit();
        }

        // Fetch all categories for parent dropdown
        $categories = $this->categoryModel->getAll();

        // Get current admin user details
        $userId = $this->session->getUserId();
        $adminUserModel = new UserModel($this->db); // Use aliased UserModel
        $adminUser = $adminUserModel->findById($userId);

        // Check if the category has associated products (affects deletion possibility)
        $hasProducts = $this->categoryModel->hasProducts($id);

        // Prepare data for the view
        $data = [
            'page_title' => 'Edit Category',
            'admin_user' => $adminUser,
            'category' => $category, // The category being edited
            'categories' => $categories, // List of potential parent categories
            'has_products' => $hasProducts, // Flag indicating if products are linked
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token for the form
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/categories/edit', $data);
    }

    /**
     * Updates an existing category based on submitted edit form data.
     *
     * Verifies the CSRF token, finds the category by ID, validates the submitted
     * name and parent ID (preventing self-parenting), attempts to update the
     * category using the Category model, sets flash messages, and redirects.
     *
     * @param int $id The ID of the category to update.
     * @return void
     */
    public function update(int $id): void
    {
        // Verify CSRF token
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid form submission. Please try again.');
            Redirect::to('/admin/categories/' . $id . '/edit');
            exit();
        }

        // Find the category to update
        $category = $this->categoryModel->findById($id);
        if (!$category) {
            $this->session->flash('error', 'Category not found.');
            Redirect::to('/admin/categories');
            exit();
        }

        // Get and sanitize input data
        $name = trim($this->request->post('name'));
        $parentId = (int) $this->request->post('parent_id');
        // Prevent setting category as its own parent or treat 0 as null
        if ($parentId === 0 || $parentId === $id) {
            $parentId = null;
        }

        // Validate input
        $errors = [];
        if (empty($name)) {
            $errors[] = 'Category name is required.';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Category name must be less than 100 characters.';
        }

        // If validation errors exist, redirect back with errors
        if (!empty($errors)) {
            $this->session->flash('errors', $errors);
            Redirect::to('/admin/categories/' . $id . '/edit');
            exit();
        }

        // Prepare data for model update
        $categoryData = [
            'name' => $name,
            'parent_id' => $parentId
        ];

        // Attempt to update the category
        $success = $this->categoryModel->updateCategory($id, $categoryData);

        // Handle success or failure
        if ($success) {
            $this->session->flash('success', 'Category updated successfully.');
            Redirect::to('/admin/categories');
        } else {
            $this->session->flash('error', 'Failed to update category. Please try again.');
            Redirect::to('/admin/categories/' . $id . '/edit');
        }
    }

    /**
     * Deletes a category.
     *
     * Verifies the CSRF token, finds the category by ID. Checks if the category
     * has associated products; if so, prevents deletion. Otherwise, attempts to
     * delete the category using the Category model, sets flash messages, and
     * redirects back to the category list.
     *
     * @param int $id The ID of the category to delete.
     * @return void
     */
    public function destroy(int $id): void
    {
        // Verify CSRF token from the POST request (usually from a form)
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid form submission or token missing. Please try again.');
            Redirect::to('/admin/categories');
            exit();
        }

        // Find the category to delete
        $category = $this->categoryModel->findById($id);
        if (!$category) {
            $this->session->flash('error', 'Category not found.');
            Redirect::to('/admin/categories');
            exit();
        }

        // Prevent deletion if the category has associated products
        if ($this->categoryModel->hasProducts($id)) {
            $this->session->flash('error', 'Cannot delete category because it has associated products.');
            Redirect::to('/admin/categories');
            exit();
        }

        // Attempt to delete the category
        $success = $this->categoryModel->deleteCategory($id);

        // Set flash message based on success or failure
        if ($success) {
            $this->session->flash('success', 'Category deleted successfully.');
        } else {
            $this->session->flash('error', 'Failed to delete category. Please try again.');
        }

        // Redirect back to the category list
        Redirect::to('/admin/categories');
    }

    /**
     * Renders a view file within the standard admin layout.
     *
     * This is a protected helper method used by other actions in this controller
     * to ensure consistent layout across the admin category management section.
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
