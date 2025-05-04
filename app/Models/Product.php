<?php

namespace App\Models;

use PDO;
use App\Core\Database;
use App\Core\Registry;

/**
 * Represents a product in the application.
 *
 * Provides methods for interacting with the products table, including retrieving products
 * (all, by ID, by category, featured, paginated), managing stock, creating, updating,
 * and managing the active status of products. Also includes methods for fetching
 * related category information.
 */
class Product
{
    /**
     * The database connection instance (PDO).
     * @var PDO
     */
    private $db;

    /**
     * Constructor for the Product model.
     *
     * Accepts either a Database wrapper object or a direct PDO connection.
     *
     * @param Database|PDO $db The database connection or wrapper.
     * @throws \InvalidArgumentException If an invalid database connection type is provided.
     */
    public function __construct($db)
    {
        if ($db instanceof Database) {
            $this->db = $db->getConnection();
        } elseif ($db instanceof PDO) {
            $this->db = $db;
        } else {
            throw new \InvalidArgumentException("Invalid database connection provided.");
        }
    }

    /**
     * Retrieves all products from the database, including their category names.
     *
     * Orders products alphabetically by name.
     *
     * @return array An array of all products, each represented as an associative array with category name included.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT p.*, c.category_name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a specific product by its ID, including its category name.
     *
     * @param int $id The ID of the product to find.
     * @return array|false An associative array representing the product if found (including category name), otherwise false.
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT p.*, c.category_name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Finds all products belonging to a specific category ID, including its subcategories.
     *
     * This method first finds all direct child categories of the given parent category ID,
     * then queries for products belonging to the parent category OR any of its direct children.
     * Logs information and errors during the process.
     *
     * @param int $categoryId The ID of the parent category.
     * @return array An array of products belonging to the specified category and its direct subcategories,
     *               ordered by product name. Returns an empty array on error or if no products are found.
     */
    public function findByCategory(int $categoryId): array
    {
        $logger = Registry::get('logger');
        $logger->info('Product::findByCategory called.', ['categoryId' => $categoryId]);

        $childCategoryIds = [];
        try {
            // Fetch IDs of direct subcategories
            $stmtChild = $this->db->prepare("SELECT category_id FROM categories WHERE parent_id = :parent_id");
            $stmtChild->bindParam(':parent_id', $categoryId, PDO::PARAM_INT);
            $stmtChild->execute();
            $childCategoryIds = $stmtChild->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch only the category_id column
            $logger->info('Fetched child category IDs.', ['parent_id' => $categoryId, 'child_ids' => $childCategoryIds]);
        } catch (\PDOException $e) {
            $logger->error('Failed to fetch child categories.', ['error' => $e->getMessage(), 'categoryId' => $categoryId]);
            // Decide whether to proceed without child categories or return empty
            // Proceeding without children might be acceptable depending on requirements.
        }

        // Combine parent and child IDs, ensuring uniqueness and integer type
        $allCategoryIds = array_merge([$categoryId], $childCategoryIds);
        $allCategoryIds = array_unique(array_map('intval', $allCategoryIds)); // Ensure unique integers
        $logger->info('Combined category IDs for query.', ['allCategoryIds' => $allCategoryIds]);

        // If no valid IDs (e.g., initial ID was invalid and no children found), return empty array
        if (empty($allCategoryIds)) {
            $logger->warning('No valid category IDs found after combining parent and children.', ['original_categoryId' => $categoryId]);
            return [];
        }

        // Create placeholders for the IN clause (e.g., ?,?,?)
        $placeholders = implode(',', array_fill(0, count($allCategoryIds), '?'));

        // Prepare the main query to fetch products in the combined category list
        $sql = "SELECT p.*, c.category_name as category_name
                FROM products p
                JOIN categories c ON p.category_id = c.category_id
                WHERE p.category_id IN ($placeholders)
                ORDER BY p.name ASC";
        $logger->info('Preparing SQL query for findByCategory.', ['sql' => $sql]);

        $stmt = $this->db->prepare($sql);

        // Bind each category ID to the prepared statement placeholders
        $paramIndex = 1; // PDO placeholders are 1-indexed when using ?
        foreach ($allCategoryIds as $id) {
            $stmt->bindValue($paramIndex++, $id, PDO::PARAM_INT);
        }
        $logger->info('Binding parameters for findByCategory.', ['bound_category_ids' => $allCategoryIds]);

        // Execute the query and fetch results
        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $logger->info('Fetched products by category.', ['results_count' => count($results), 'categoryIds' => $allCategoryIds]);
            return $results;
        } catch (\PDOException $e) {
            $logger->error('Database error during findByCategory execution.', ['error' => $e->getMessage(), 'sql' => $sql, 'params' => $allCategoryIds]);
            return []; // Return empty array on error
        }
    }


    /**
     * Retrieves a small number of randomly selected featured products.
     *
     * Includes basic product details (ID, name, price, image) and category name.
     * Useful for homepage displays or promotional sections.
     *
     * @return array An array of 2 randomly selected products.
     */
    public function getFeaturedProducts(): array
    {
        // Using ORDER BY RAND() can be inefficient on large tables. Consider alternative strategies
        // like fetching a random offset or having a dedicated 'is_featured' flag if performance becomes an issue.
        $sql = "SELECT p.product_id, p.name, p.price, p.image_path, c.category_name as category_name FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                ORDER BY RAND()
                LIMIT 2"; // Limit to 2 featured products
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a list of distinct categories that have associated products.
     *
     * Useful for generating category filters where only relevant categories are shown.
     *
     * @return array An array of distinct categories (id and name) that contain products, ordered by name.
     */
    public function getProductCategories(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT c.category_id, c.category_name FROM categories c JOIN products p ON c.category_id = p.category_id ORDER BY c.category_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds multiple products by their IDs.
     *
     * Efficiently fetches details for a list of product IDs (e.g., for a shopping cart).
     * Returns an associative array keyed by product ID for easy lookup.
     *
     * @param array $ids An array of product IDs to retrieve.
     * @return array An associative array where keys are product IDs and values are associative arrays
     *               containing product details ('id', 'name', 'price', 'image'). Returns empty array if input is empty or on error.
     */
    public function findMultipleByIds(array $ids): array
    {
        if (empty($ids)) {
            return []; // Return early if no IDs provided
        }

        // Ensure all IDs are integers
        $ids = array_map('intval', $ids);

        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Select specific fields needed (e.g., for cart display)
        $sql = "SELECT product_id as id, name, price, image_path as image, stock_quantity FROM products WHERE product_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);

        try {
            // Execute with the array of IDs directly
            $stmt->execute($ids);
            // Fetch results as an associative array keyed by the first column (product_id aliased as id)
            return $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
        } catch (\PDOException $e) {
            // Log the error appropriately
            Registry::get('logger')->error("Error fetching multiple products by ID", ['exception' => $e, 'ids' => $ids]);
            // error_log("Error fetching multiple products by ID: " . $e->getMessage()); // Keep original logging if desired
            return []; // Return empty array on error
        }
    }

    /**
     * Checks the current stock quantity for a specific product.
     *
     * @param int $id The ID of the product to check.
     * @return int|false The stock quantity as an integer if the product is found, otherwise false.
     */
    public function checkStock(int $id)
    {
        $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE product_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        // Return the quantity as int if found, otherwise false
        return $result !== false ? (int) $result['stock_quantity'] : false;
    }

    /**
     * Retrieves products with pagination and optional filtering.
     *
     * Filters can include category ID and active status.
     * Returns an array containing the products for the current page and pagination details.
     *
     * @param int $page The current page number (defaults to 1).
     * @param int $perPage The number of products per page (defaults to 15).
     * @param array $filters Associative array of filters. Keys: 'category_id', 'is_active' (0 or 1).
     * @return array An array containing 'products' and 'pagination' data.
     *               Pagination structure: ['current_page', 'per_page', 'total_items', 'total_pages', 'has_previous', 'has_next']
     */
    public function getAllProductsPaginated(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;

        // Base SQL query
        $sql = "SELECT p.*, c.category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id";
        $countSql = "SELECT COUNT(*) as total FROM products p"; // Base count query

        $whereConditions = [];
        $params = []; // Parameters for the main query execution
        $countParams = []; // Parameters for the count query execution

        // Apply filters
        if (!empty($filters['category_id'])) {
            $whereConditions[] = "p.category_id = ?"; // Use positional placeholders for simplicity here
            $params[] = $filters['category_id'];
            $countParams[] = $filters['category_id'];
        }
        if (isset($filters['is_active']) && ($filters['is_active'] === 0 || $filters['is_active'] === 1)) {
            $whereConditions[] = "p.is_active = ?";
            $params[] = $filters['is_active'];
            $countParams[] = $filters['is_active'];
        }

        // Append WHERE clause if filters are applied
        if (!empty($whereConditions)) {
            $whereClause = " WHERE " . implode(" AND ", $whereConditions);
            $sql .= $whereClause;
            $countSql .= $whereClause;
        }

        // Add ordering and pagination to the main query
        $sql .= " ORDER BY p.name ASC LIMIT ? OFFSET ?";
        $params[] = $perPage; // Add limit and offset to main query params
        $params[] = $offset;

        try {
            // Execute main query
            $stmt = $this->db->prepare($sql);
            // Execute with parameters (types inferred by PDO for positional placeholders)
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Execute count query
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams); // Execute with filter parameters only
            $totalCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total']; // Fetch the count

            // Calculate pagination details
            $totalPages = $totalCount > 0 ? ceil($totalCount / $perPage) : 0;

            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ];

            return [
                'products' => $products,
                'pagination' => $pagination
            ];
        } catch (\PDOException $e) {
            Registry::get('logger')->error("Error fetching paginated products", [
                'exception' => $e,
                'page' => $page,
                'perPage' => $perPage,
                'filters' => $filters
            ]);
            // Return empty structure on error
            return [
                'products' => [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_items' => 0,
                    'total_pages' => 0,
                    'has_previous' => false,
                    'has_next' => false
                ]
            ];
        }
    }


    /**
     * Creates a new product in the database.
     *
     * Uses positional placeholders for data insertion.
     *
     * @param array $data Associative array containing product data.
     *                    Expected keys: 'name', 'description', 'price', 'category_id',
     *                                   'image_path', 'stock_quantity', 'is_active'.
     * @return int|false The ID of the newly created product on success, false on failure.
     */
    public function createProduct(array $data)
    {
        try {
            $sql = "INSERT INTO products (name, description, price, category_id, image_path, stock_quantity, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"; // Positional placeholders
            $stmt = $this->db->prepare($sql);
            // Execute with an array of values in the correct order
            $stmt->execute([
                $data['name'] ?? null,
                $data['description'] ?? null,
                $data['price'] ?? 0.0,
                $data['category_id'] ?? null,
                $data['image_path'] ?? null,
                $data['stock_quantity'] ?? 0,
                $data['is_active'] ?? 1 // Default to active
            ]);
            return (int) $this->db->lastInsertId(); // Return the new product ID
        } catch (\PDOException $e) {
            Registry::get('logger')->error("Error creating product", ['exception' => $e, 'data' => $data]);
            // error_log("Error creating product: " . $e->getMessage()); // Keep if needed
            return false;
        }
    }

    /**
     * Updates an existing product.
     *
     * Uses positional placeholders for updating data.
     *
     * @param int $id The ID of the product to update.
     * @param array $data Associative array containing the updated product data.
     *                    Expected keys match the columns being updated.
     * @return bool True if the update was successful and affected at least one row, false otherwise.
     */
    public function updateProduct(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE products
                    SET name = ?, description = ?, price = ?, category_id = ?,
                        image_path = ?, stock_quantity = ?, is_active = ?
                    WHERE product_id = ?"; // Positional placeholders
            $stmt = $this->db->prepare($sql);
            // Execute with an array of values in the correct order, including the ID for the WHERE clause
            $result = $stmt->execute([
                $data['name'] ?? null,
                $data['description'] ?? null,
                $data['price'] ?? 0.0,
                $data['category_id'] ?? null,
                $data['image_path'] ?? null,
                $data['stock_quantity'] ?? 0,
                $data['is_active'] ?? 1,
                $id // ID for the WHERE clause
            ]);
            // Return true only if execute succeeded AND at least one row was changed
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            Registry::get('logger')->error("Error updating product", ['exception' => $e, 'product_id' => $id, 'data' => $data]);
            // error_log("Error updating product: " . $e->getMessage()); // Keep if needed
            return false;
        }
    }

    /**
     * Toggles the active status (is_active field) of a product.
     *
     * Sets is_active to its opposite boolean value (0 becomes 1, 1 becomes 0).
     *
     * @param int $id The ID of the product whose status needs to be toggled.
     * @return bool True if the toggle was successful and affected one row, false otherwise.
     */
    public function toggleProductActiveStatus(int $id): bool
    {
        try {
            // Use NOT operator to toggle the boolean/tinyint value
            $sql = "UPDATE products SET is_active = NOT is_active WHERE product_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);
            // Ensure the query executed and exactly one row was affected
            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            Registry::get('logger')->error("Error toggling product status", ['exception' => $e, 'product_id' => $id]);
            // error_log("Error toggling product status: " . $e->getMessage()); // Keep if needed
            return false;
        }
    }

    /**
     * Counts the number of active products with stock quantity at or below a given threshold.
     *
     * Useful for dashboard warnings or low stock reports.
     *
     * @param int $threshold The stock quantity threshold (defaults to 5).
     * @return int The count of low-stock active products, or 0 on error.
     */
    public function getLowStockProductCount(int $threshold = 5): int
    {
        try {
            // Count only active products (is_active = 1)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity <= :threshold AND is_active = 1");
            $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            Registry::get('logger')->error("Error counting low stock products", ['exception' => $e, 'threshold' => $threshold]);
            // error_log("Error counting low stock products: " . $e->getMessage()); // Keep if needed
            return 0; // Return 0 on error
        }
    }

    /**
     * Gets the total count of all products in the database.
     *
     * @return int The total number of products, or 0 on error.
     */
    public function getTotalProductCount(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM products");
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            Registry::get('logger')->error("Error counting total products", ['exception' => $e]);
            return 0; // Return 0 on error
        }
    }
}
