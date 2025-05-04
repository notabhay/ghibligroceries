<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Registry;
use App\Core\Request;
use App\Core\Session;
use App\Models\Category;
use App\Models\Product;
use Psr\Log\LoggerInterface;
use PDO; // Added for type hinting in getCategoryIdByName

/**
 * Class ProductController
 * Handles displaying product categories and products, including filtering by category.
 * Provides AJAX endpoints for dynamically loading subcategories and products based on category selection.
 *
 * @package App\Controllers
 */
class ProductController extends BaseController
{
    /**
     * @var Database Database connection wrapper instance.
     */
    private Database $db;

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
     * @var Category Category model instance for database interactions.
     */
    private Category $categoryModel;

    /**
     * @var Product Product model instance for database interactions.
     */
    private Product $productModel;

    /**
     * ProductController constructor.
     * Initializes dependencies (Database, Session, Request, Logger) from the Registry.
     * Initializes Category and Product models.
     * Throws a RuntimeException if the database connection is unavailable.
     */
    public function __construct()
    {
        $this->db = Registry::get('database');
        $this->session = Registry::get('session');
        $this->request = Registry::get('request');
        $this->logger = Registry::get('logger');
        $pdoConnection = $this->db->getConnection(); // Get the actual PDO connection

        // Ensure PDO connection is valid before instantiating models
        if ($pdoConnection) {
            $this->categoryModel = new Category($pdoConnection);
            $this->productModel = new Product($pdoConnection);
        } else {
            // Log critical error and stop if DB connection failed
            $this->logger->critical("Database connection not available for ProductController.");
            throw new \RuntimeException("Database connection not available for ProductController.");
        }
    }

    /**
     * Displays the main categories page.
     * Fetches top-level categories and initial products (either all or filtered by a category name from query param).
     * Handles potential errors during data fetching.
     *
     * @return void Renders the 'pages/categories' view.
     */
    public function showCategories(): void
    {
        $logged_in = $this->session->isAuthenticated(); // Check login status
        $categories = [];
        $initialProducts = [];
        $activeFilterForView = null; // To highlight the active filter in the view

        try {
            // Fetch all top-level categories for display
            $categories = $this->categoryModel->getAllTopLevel();

            // Check if a category filter is applied via query parameter (e.g., /categories?filter=Fruits+%26+Veggies)
            $categoryFilter = $this->request->get('filter'); // Get 'filter' query parameter

            if ($categoryFilter) {
                $this->logger->info("Category filter applied", ['filter' => $categoryFilter]);
                $activeFilterForView = $categoryFilter; // Pass the filter name to the view

                // Find the category ID corresponding to the filter name
                $categoryId = $this->getCategoryIdByName($categoryFilter);

                if ($categoryId) {
                    // Fetch products belonging to the specified category
                    $initialProducts = $this->productModel->findByCategory($categoryId);
                } else {
                    // If category name doesn't match, log a warning and show all products as fallback
                    $this->logger->warning("Category not found for filter, showing all products.", ['filter' => $categoryFilter]);
                    $initialProducts = $this->productModel->getAll();
                    $activeFilterForView = null; // Reset active filter as it was invalid
                }
            } else {
                // If no filter is applied, fetch all products initially
                $initialProducts = $this->productModel->getAll();
            }
        } catch (\Exception $e) {
            // Log error if fetching categories or products fails
            $this->logger->error("Error fetching categories or products for display.", ['exception' => $e]);
            $this->session->flash('error', 'Could not load product categories or products. Please try again later.');
            // Ensure variables are arrays even on error
            $categories = $categories ?: [];
            $initialProducts = $initialProducts ?: [];
        }

        // Prepare data for the view
        $this->view('pages/categories', [
            'categories' => $categories,
            'products' => $initialProducts, // Products to display initially
            'activeFilter' => $activeFilterForView, // Name of the active filter, if any
            'page_title' => 'Browse Products',
            'meta_description' => 'Browse our wide selection of fresh groceries by category.',
            'meta_keywords' => 'products, categories, grocery, online shopping',
            'additional_css_files' => ['/assets/css/categories.css'], // Specific CSS
            'logged_in' => $logged_in // Pass login status
        ]);
    }

    /**
     * Finds a category ID by its exact name.
     * Includes debug logging for names containing '&' to help diagnose potential encoding issues.
     *
     * @param string $categoryName The exact name of the category to find.
     * @return int|null The category ID if found, otherwise null.
     */
    private function getCategoryIdByName($categoryName): ?int
    {
        try {
            // --- Debugging for names with ampersands ---
            // This helps check if the name received matches what's in the DB,
            // especially if URL encoding/decoding issues occur with '&'.
            if (strpos($categoryName, '&') !== false) {
                $likeName = str_replace('&', '%', $categoryName); // Create a LIKE pattern
                $debugStmt = $this->db->getConnection()->prepare("SELECT category_name FROM categories WHERE category_name LIKE :likeName LIMIT 5");
                $debugStmt->bindParam(':likeName', $likeName, PDO::PARAM_STR);
                $debugStmt->execute();
                $potentialMatches = $debugStmt->fetchAll(PDO::FETCH_COLUMN);
                if ($potentialMatches) {
                    $this->logger->info("Potential category name matches in DB (debug)", ['search_term' => $categoryName, 'like_pattern' => $likeName, 'matches' => $potentialMatches]);
                } else {
                    $this->logger->info("No similar category names found in DB (debug)", ['search_term' => $categoryName, 'like_pattern' => $likeName]);
                }
            }
            // --- End Debugging ---

            // Prepare and execute query to find category ID by exact name match
            $stmt = $this->db->getConnection()->prepare("SELECT category_id FROM categories WHERE category_name = :name");
            $stmt->bindParam(':name', $categoryName, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Return the category ID as an integer if found, otherwise null
            return $result ? (int) $result['category_id'] : null;
        } catch (\Exception $e) {
            // Log any errors during the database query
            $this->logger->error("Error finding category by name", ['name' => $categoryName, 'error' => $e->getMessage()]);
            return null; // Return null on error
        }
    }

    /**
     * AJAX endpoint to fetch products belonging to a specific category ID.
     * Used by JavaScript to update the product list when a category is selected.
     * Expects 'categoryId' as a GET parameter.
     * Returns JSON response with products or an error.
     *
     * @deprecated This method seems redundant or possibly incorrectly named given ajaxGetSubcategories.
     *             It fetches *products* by category ID, not subcategories.
     *             Consider renaming or clarifying its purpose if kept.
     * @return void Outputs JSON response.
     */
    public function getSubcategoriesAjax(): void
    {
        // Get category ID from GET request
        $categoryId = $this->request->get('categoryId');
        $this->logger->info('AJAX: getSubcategoriesAjax called (fetches products).', ['categoryId' => $categoryId]);

        // Validate the category ID
        if (!filter_var($categoryId, FILTER_VALIDATE_INT)) {
            $this->jsonResponse(['error' => 'Invalid Category ID provided.'], 400); // Bad Request
            return;
        }
        $categoryId = (int) $categoryId; // Cast to integer

        try {
            // Fetch products using the Product model
            $this->logger->info('AJAX: Calling productModel->findByCategory.', ['categoryId' => $categoryId]);
            $products = $this->productModel->findByCategory($categoryId);
            $this->logger->info('AJAX: Received products from model.', ['products_count' => count($products)]); // Avoid logging full data unless debugging

            // Send successful JSON response with the products
            $this->jsonResponse(['products' => $products]);
        } catch (\Exception $e) {
            // Log error and send error response
            $this->logger->error("AJAX: Error fetching products for category.", ['categoryId' => $categoryId, 'exception' => $e]);
            $this->jsonResponse(['error' => 'Could not load products for this category.'], 500); // Internal Server Error
        }
    }

    /**
     * AJAX endpoint to fetch subcategories based on a parent category ID.
     * Used by JavaScript to dynamically populate subcategory lists.
     * Expects 'parentId' as a GET parameter.
     * Returns JSON response with subcategories or an error.
     *
     * @return void Outputs JSON response.
     */
    public function ajaxGetSubcategories(): void
    {
        // Get parent category ID from GET request
        $parentId = $this->request->get('parentId');
        $this->logger->info('AJAX: ajaxGetSubcategories called.', ['parentId' => $parentId]);

        // Validate the parent ID (must be a positive integer)
        if (!filter_var($parentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $this->logger->warning('AJAX: Invalid parentId received.', ['parentId' => $parentId]);
            $this->jsonResponse(['error' => 'Invalid Parent Category ID provided.'], 400); // Bad Request
            return;
        }
        $parentId = (int) $parentId; // Cast to integer

        try {
            // Fetch subcategories using the Category model
            $this->logger->info('AJAX: Calling categoryModel->getSubcategoriesByParentId.', ['parentId' => $parentId]);
            $subcategories = $this->categoryModel->getSubcategoriesByParentId($parentId);
            $this->logger->info('AJAX: Received subcategories from model.', ['subcategories_count' => count($subcategories)]);

            // Send successful JSON response with the subcategories
            $this->jsonResponse(['subcategories' => $subcategories]);
        } catch (\Exception $e) {
            // Log error and send error response (return empty array as per original logic)
            $this->logger->error("AJAX: Error fetching subcategories.", ['parentId' => $parentId, 'exception' => $e]);
            // Consider sending a 500 error instead of empty array for clearer error handling client-side
            $this->jsonResponse(['subcategories' => []]); // Original logic returned empty array on error
        }
    }

    /**
     * Helper method to send a JSON response.
     * Sets the Content-Type header, HTTP status code, encodes data to JSON, and echoes it.
     * Logs an error if headers have already been sent.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code for the response (default: 200).
     * @return void
     */
    protected function jsonResponse($data, int $statusCode = 200): void
    {
        // Check if headers have already been sent to prevent warnings/errors
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($statusCode); // Set the HTTP status code
        } else {
            // Log an error if headers are already sent, as we can't set them again
            $this->logger->error("Headers already sent, cannot set JSON response headers.", ['status_code' => $statusCode]);
        }
        // Encode the data to JSON and output it
        echo json_encode($data);
        // Note: exit() is not called here, allowing potential further script execution if needed,
        // though typically AJAX handlers terminate after sending the response.
    }

    /**
     * Default action for the ProductController (e.g., when accessing /products).
     * Simply calls the showCategories method to display the main product browsing page.
     *
     * @return void
     */
    public function index(): void
    {
        // The main entry point for this controller shows the categories page
        $this->showCategories();
    }
}
