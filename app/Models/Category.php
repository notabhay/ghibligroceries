<?php

namespace App\Models;

use PDO;
use App\Core\Database;

/**
 * Represents a product category in the application.
 *
 * Provides methods for interacting with the categories table in the database,
 * including retrieving, creating, updating, and deleting categories,
 * as well as handling hierarchical relationships (parent/subcategories).
 */
class Category
{
    /**
     * The database connection instance (PDO).
     * @var PDO
     */
    private $db;

    /**
     * Constructor for the Category model.
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
     * Retrieves all categories from the database, ordered by name.
     *
     * @return array An array of all categories, each represented as an associative array.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY category_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a specific category by its ID.
     *
     * @param int $id The ID of the category to find.
     * @return array|false An associative array representing the category if found, otherwise false.
     */
    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE category_id = :category_id");
        $stmt->bindParam(':category_id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all top-level categories (those without a parent).
     *
     * @return array An array of top-level categories, ordered by name.
     */
    public function getAllTopLevel(): array
    {
        $stmt = $this->db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY category_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all subcategories for a given parent category ID.
     *
     * @param int $parentId The ID of the parent category.
     * @return array An array of subcategories belonging to the specified parent, ordered by name.
     */
    public function getSubcategoriesByParentId(int $parentId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id = :parentId ORDER BY category_name ASC");
        $stmt->bindParam(':parentId', $parentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves categories with pagination, including parent category names.
     *
     * @param int $page The current page number (defaults to 1).
     * @param int $perPage The number of categories per page (defaults to 10).
     * @return array An array containing the categories for the current page and pagination details.
     *               Structure: ['categories' => [...], 'pagination' => [...]]
     */
    public function getAllCategoriesPaginated(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count for pagination calculation
        $countStmt = $this->db->query("SELECT COUNT(*) FROM categories");
        $totalCount = (int) $countStmt->fetchColumn();

        // Prepare statement to fetch paginated categories with parent names
        $stmt = $this->db->prepare("
            SELECT c.*, p.category_name as parent_name
            FROM categories c
            LEFT JOIN categories p ON c.parent_id = p.category_id
            ORDER BY c.category_name ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total pages
        $totalPages = ceil($totalCount / $perPage);

        // Return data and pagination info
        return [
            'categories' => $categories,
            'pagination' => [
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $perPage,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }

    /**
     * Creates a new category in the database.
     *
     * @param array $data An associative array containing category data.
     *                    Expected keys: 'name' (string, required), 'parent_id' (int|null, optional).
     * @return int|bool The ID of the newly created category on success, or false on failure.
     */
    public function createCategory(array $data): int|bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO categories (category_name, parent_id)
            VALUES (:name, :parent_id)
        ");
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);

        // Handle optional parent_id
        if (!empty($data['parent_id'])) {
            $stmt->bindParam(':parent_id', $data['parent_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
        }

        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Updates an existing category.
     *
     * Prevents a category from being its own parent.
     *
     * @param int $id The ID of the category to update.
     * @param array $data An associative array containing the updated data.
     *                    Expected keys: 'name' (string, required), 'parent_id' (int|null, optional).
     * @return bool True on success, false on failure.
     */
    public function updateCategory(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE categories
            SET category_name = :name, parent_id = :parent_id
            WHERE category_id = :category_id
        ");
        $stmt->bindParam(':category_id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);

        // Handle parent_id, ensuring it's not the category itself
        if (!empty($data['parent_id']) && $data['parent_id'] != $id) {
            $stmt->bindParam(':parent_id', $data['parent_id'], PDO::PARAM_INT);
        } else {
            // Set parent_id to NULL if empty or if it matches the category's own ID
            $stmt->bindValue(':parent_id', null, PDO::PARAM_NULL);
        }

        return $stmt->execute();
    }

    /**
     * Checks if a category has any associated products.
     *
     * Used to prevent deletion of categories that contain products.
     *
     * @param int $id The ID of the category to check.
     * @return bool True if the category has products, false otherwise.
     */
    public function hasProducts(int $id): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM products WHERE category_id = :category_id
        ");
        $stmt->bindParam(':category_id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Deletes a category from the database.
     *
     * Deletion is prevented if the category has associated products.
     *
     * @param int $id The ID of the category to delete.
     * @return bool True on successful deletion, false if deletion fails or is prevented.
     */
    public function deleteCategory(int $id): bool
    {
        // Prevent deletion if the category contains products
        if ($this->hasProducts($id)) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM categories WHERE category_id = :category_id");
        $stmt->bindParam(':category_id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Gets the total count of all categories in the database.
     *
     * @return int The total number of categories, or 0 on error.
     */
    public function getTotalCategoryCount(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM categories");
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            // Log error if logger is available
            if (class_exists('\\App\\Core\\Registry') && \App\Core\Registry::has('logger')) {
                \App\Core\Registry::get('logger')->error("Error counting total categories", ['exception' => $e]);
            }
            return 0; // Return 0 on error
        }
    }
}
