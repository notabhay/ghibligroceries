<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Registry;
use App\Core\Request;
use App\Core\Session;
use App\Core\Redirect;
use App\Models\Category;
use App\Models\Product;
use App\Services\GeminiService;
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
        $activeFilterName = null;
        $activeMainCategoryId = null;
        $activeSubCategoryId = null;

        try {
            // Fetch all top-level categories for display
            $categories = $this->categoryModel->getAllTopLevel();

            // Check if a category filter is applied via query parameter
            $filterQueryParam = $this->request->get('filter');

            if ($filterQueryParam) {
                $this->logger->info("Category filter applied from URL", ['filter' => $filterQueryParam]);
                $activeFilterName = $filterQueryParam; // Store the original filter name

                // Find the category ID corresponding to the filter name
                $filteredCategoryId = $this->getCategoryIdByName($filterQueryParam);

                if ($filteredCategoryId) {
                    $filteredCategory = $this->categoryModel->findById($filteredCategoryId);

                    if ($filteredCategory) {
                        if (!empty($filteredCategory['parent_id'])) { // It's a subcategory
                            $activeSubCategoryId = (int)$filteredCategory['category_id'];
                            $activeMainCategoryId = (int)$filteredCategory['parent_id'];
                            // Fetch products for this subcategory
                            $initialProducts = $this->productModel->findByCategory($activeSubCategoryId);
                            $this->logger->info("Filter is a subcategory.", ['main_category_id' => $activeMainCategoryId, 'sub_category_id' => $activeSubCategoryId, 'filter_name' => $filterQueryParam]);
                        } else { // It's a main category
                            $activeMainCategoryId = (int)$filteredCategory['category_id'];
                            // Fetch products for this main category
                            $initialProducts = $this->productModel->findByCategory($activeMainCategoryId);
                            $this->logger->info("Filter is a main category.", ['main_category_id' => $activeMainCategoryId, 'filter_name' => $filterQueryParam]);
                        }
                    } else {
                        // Category ID found by name, but findById failed (should be rare)
                        $this->logger->warning("Category details not found for ID, showing all products.", ['category_id' => $filteredCategoryId, 'filter_name' => $filterQueryParam]);
                        $initialProducts = $this->productModel->getAll();
                        $activeFilterName = null; // Reset as filter was problematic
                    }
                } else {
                    // If category name doesn't match, log a warning and show all products as fallback
                    $this->logger->warning("Category not found for filter, showing all products.", ['filter' => $filterQueryParam]);
                    $initialProducts = $this->productModel->getAll();
                    $activeFilterName = null; // Reset active filter as it was invalid
                }
            } else {
                // If no filter is applied, fetch all products initially
                $this->logger->info("No category filter applied, showing all products.");
                $initialProducts = $this->productModel->getAll();
            }

            // Add slug to each product
            foreach ($initialProducts as &$product) {
                $product['slug'] = $this->generateSlug($product['name']);
            }
            unset($product);

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
            'activeFilterName' => $activeFilterName, // Name of the active filter from URL, if any
            'activeMainCategoryId' => $activeMainCategoryId,
            'activeSubCategoryId' => $activeSubCategoryId,
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
     * @return void Outputs JSON response.
     */
    public function ajaxGetProductsByCategory(): void
    {
        // Get category ID from GET request
        $categoryId = $this->request->get('categoryId');
        $this->logger->info('AJAX: ajaxGetProductsByCategory called (fetches products).', ['categoryId' => $categoryId]);

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
// Add slug to each product before sending response
            foreach ($products as &$product) { // Use a reference to modify the array directly
                if (isset($product['name'])) { // Ensure name exists before generating slug
                    $product['slug'] = $this->generateSlug($product['name']);
                } else {
                    // Fallback slug if name is missing, and log this occurrence
                    $product['slug'] = 'n-a'; 
                    $this->logger->warning('Product missing name in ajaxGetProductsByCategory, using default slug.', ['product_id' => $product['product_id'] ?? 'unknown']);
                }
            }
            unset($product); // Unset the reference to avoid unintended side effects

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
     * Handles product search functionality with AI enhancement.
     * Retrieves search term from GET parameter, validates it, and uses GeminiService
     * to enhance the search before fetching matching products.
     * Renders the search results view with the products found.
     * 
     * @return void Renders the 'pages/search_results' view or redirects if no search term.
     */
    public function search(): void
    {
        // Get the search term from the GET parameter
        $searchTerm = trim($this->request->get('q', ''));
        $this->logger->info('AI product search initiated', ['searchTerm' => $searchTerm]);
        
        // Check if a search term was provided
        if (empty($searchTerm)) {
            $this->logger->warning('Empty search term, redirecting to categories page');
            Redirect::to('/categories');
            return;
        }
        
        // Get login status
        $logged_in = $this->session->isAuthenticated();
        
        try {
            // Initialize variables
            $products = [];
            $resultCount = 0;
            $correctedTerm = null;
            $aiProcessed = false;
            $fallback = false;
            
            // Get product categories for context
            $categories = $this->productModel->getProductCategories();
            
            // Initialize GeminiService
            $geminiService = new GeminiService();
            
            // Call Gemini API to enhance the search query
            $enhancedParams = $geminiService->callGeminiApi(
                $geminiService->constructPrompt($searchTerm, $categories)
            );
            
            // If Gemini API call succeeds, use enhanced search
            if ($enhancedParams !== null) {
                $products = $this->productModel->searchWithEnhancedTerms($enhancedParams);
                $resultCount = count($products);
                $correctedTerm = $enhancedParams['correctedQuery'] ?? null;
                $aiProcessed = true;
                
                $this->logger->info('AI search successful', [
                    'searchTerm' => $searchTerm,
                    'resultCount' => $resultCount,
                    'correctedTerm' => $correctedTerm
                ]);
            } else {
                // Fall back to traditional search
                $this->logger->warning('AI search failed, falling back to traditional search', ['searchTerm' => $searchTerm]);
                $products = $this->productModel->searchByNameOrDescription($searchTerm);
                $resultCount = count($products);
                $fallback = true;
            }

            // Add slug to each product
            foreach ($products as &$product) {
                $product['slug'] = $this->generateSlug($product['name']);
            }
            unset($product);
            
            // Render the search results view
            $this->view('pages/search_results', [
                'searchTerm' => $searchTerm,
                'correctedTerm' => $correctedTerm,
                'products' => $products,
                'resultCount' => $resultCount,
                'aiProcessed' => $aiProcessed,
                'fallback' => $fallback,
                'page_title' => 'Search Results - GhibliGroceries',
                'meta_description' => "Search results for '{$searchTerm}' at GhibliGroceries.",
                'meta_keywords' => 'search, products, grocery, online shopping',
                'additional_css_files' => [
                    '/assets/css/categories.css',
                    '/assets/css/ai-search.css'
                ],
                'additional_js_files' => [
                    'search-page.js'
                ],
                'logged_in' => $logged_in
            ]);
            
        } catch (\Exception $e) {
            // Log error if search fails
            $this->logger->error("Error during AI product search", ['searchTerm' => $searchTerm, 'exception' => $e]);
            $this->session->flash('error', 'An error occurred while searching. Please try again.');
            
            // Fall back to traditional search
            $products = $this->productModel->searchByNameOrDescription($searchTerm);
            $resultCount = count($products);

            // Add slug to each product
            foreach ($products as &$product) {
                $product['slug'] = $this->generateSlug($product['name']);
            }
            unset($product);
            
            // Render the search results page with fallback results
            $this->view('pages/search_results', [
                'searchTerm' => $searchTerm,
                'products' => $products,
                'resultCount' => $resultCount,
                'fallback' => true,
                'page_title' => 'Search Results - GhibliGroceries',
                'meta_description' => "Search results for '{$searchTerm}' at GhibliGroceries.",
                'meta_keywords' => 'search, products, grocery, online shopping',
                'additional_css_files' => [
                    '/assets/css/categories.css',
                    '/assets/css/ai-search.css'
                ],
                'additional_js_files' => [
                    'search-page.js'
                ],
                'logged_in' => $logged_in
            ]);
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

    /**
     * Generates a URL-friendly slug from a string.
     *
     * @param string $name The string to convert to a slug.
     * @return string The generated slug.
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        // Replace non-letter or digits by -
        $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);
        // Transliterate
        $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
        // Remove unwanted characters
        $slug = preg_replace('~[^-\w]+~', '', $slug);
        // Trim
        $slug = trim($slug, '-');
        // Remove duplicate -
        $slug = preg_replace('~-+~', '-', $slug);
        if (empty($slug)) {
            return 'n-a';
        }
        return $slug;
    }

    /**
     * Displays the product detail page.
     *
     * @param array $params Parameters from the route, expecting 'id' and 'slug'.
     * @return void Renders the 'pages/product_detail' view or an error view.
     */
    public function showProductDetail(array $params): void
    {
        $this->logger->info('Product detail page requested', ['params' => $params]);

        // 1. Sanitize and validate productId
        if (!isset($params['id']) || !filter_var($params['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $this->logger->error('Invalid product ID provided for detail page.', ['id' => $params['id'] ?? null]);
            http_response_code(404); // Set 404 status code
            $this->view('errors.general', [
                'page_title' => 'Invalid Product ID',
                'error_status_code' => 404,
                'error_heading' => 'Invalid Product ID',
                'error_message' => 'The product ID provided is invalid. Please check the URL and try again.',
                'link_text' => 'Browse Products',
                'link_href' => '/categories',
                'additional_css_files' => ['/assets/css/error-page.css']
            ]);
            return;
        }
        $productId = (int) $params['id'];

        // 2. Fetch Product
        // The Product model's findById might need to be modified to fetch category_parent_id
        // For now, we assume it might not have it, or we fetch category details separately.
        $product = $this->productModel->findById($productId);

        if (!$product) {
            $this->logger->warning("Product not found for ID: {$productId}");
            http_response_code(404); // Set 404 status code
            $this->view('errors.general', [
                'page_title' => 'Product Not Found',
                'error_status_code' => 404,
                'error_heading' => 'Product Not Found',
                'error_message' => "Sorry, we couldn't find the product you were looking for. It might have been removed or the link is incorrect.",
                'link_text' => 'Browse Products',
                'link_href' => '/categories',
                'additional_css_files' => ['/assets/css/error-page.css'] // Add this line
            ]);
            return;
        }
        
        // Add slug to product array for consistency, though it's already in $params
        // This also ensures the slug used for display is the one generated by our current logic
        $product['slug'] = $this->generateSlug($product['name']);

        // 3. Fetch Category Breadcrumbs
        // $product['category_id'] is available from $this->productModel->findById($productId)
        $categoryBreadcrumbs = $this->getCategoryBreadcrumbs($product['category_id']);
        
        // 4. Stock Display Logic
        $stock_quantity = (int) $product['stock_quantity'];
        $stock_status_text = '';
        $stock_status_class = '';
        $can_add_to_cart = false;

        if ($stock_quantity <= 0) {
            $stock_status_text = "Out of Stock";
            $stock_status_class = "out-of-stock";
            $can_add_to_cart = false;
        } elseif ($stock_quantity <= 10) { // Configurable threshold, 10 for now
            $stock_status_text = "Low Stock - Only " . $stock_quantity . " left!";
            $stock_status_class = "low-stock";
            $can_add_to_cart = true;
        } else {
            $stock_status_text = "In Stock";
            $stock_status_class = "in-stock";
            $can_add_to_cart = true;
        }

        // 5. Pass Data to View
        $data = [
            'page_title' => htmlspecialchars($product['name']) . ' - GhibliGroceries',
            'meta_description' => 'View details for ' . htmlspecialchars($product['name']) . '. ' . htmlspecialchars(substr($product['description'], 0, 150)) . '...',
            'product' => $product,
            'category_breadcrumbs' => $categoryBreadcrumbs,
            'stock_status_text' => $stock_status_text,
            'stock_status_class' => $stock_status_class,
            'can_add_to_cart' => $can_add_to_cart,
            'csrf_token' => $this->session->getCsrfToken(),
            'additional_css_files' => ['/assets/css/product-detail.css'], // Ensure this path is correct
            'logged_in' => $this->session->isAuthenticated()
        ];

        $this->view('pages/product_detail', $data);
    }

    /**
     * Generates category breadcrumbs for a given category ID.
     *
     * @param int $categoryId The ID of the current category.
     * @return array An array of breadcrumb items.
     */
    private function getCategoryBreadcrumbs(int $categoryId): array
    {
        $breadcrumbs = [];
        $currentCategoryId = $categoryId;

        while ($currentCategoryId !== null && $currentCategoryId > 0) {
            $category = $this->categoryModel->findById($currentCategoryId); // This should fetch parent_id as well
            if ($category) {
                array_unshift($breadcrumbs, [
                    'name' => $category['category_name'],
                    'link' => '/categories?filter=' . urlencode($category['category_name']),
                    'active' => false // All category links are not active; product name is the active part
                ]);
                // Ensure 'parent_id' is part of the $category array from $this->categoryModel->findById()
                $currentCategoryId = isset($category['parent_id']) ? (int)$category['parent_id'] : null;
            } else {
                $this->logger->warning('Category not found while building breadcrumbs.', ['categoryId' => $currentCategoryId]);
                break; 
            }
        }
        
        // The view will append the product name as the last, active breadcrumb item.
        // Example: Home / Category / SubCategory / ProductName (active)
        // This function returns: [ {Home}, {Category}, {SubCategory} ] all with active=false.
        return $breadcrumbs;
    }
}