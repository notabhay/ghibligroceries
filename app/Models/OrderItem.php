<?php

namespace App\Models;

use PDO;
use App\Core\Database; // Not used directly, but contextually relevant
use App\Core\Registry;

/**
 * Represents an individual item within a customer order.
 *
 * Links an order to a specific product, storing the quantity and price
 * at the time the order was placed. Provides methods for creating (single and bulk),
 * retrieving, updating, and deleting order items.
 */
class OrderItem
{
    /**
     * The database connection instance (PDO).
     * @var PDO
     */
    private $db;

    /**
     * Stores the last error message encountered during database operations.
     * @var string
     */
    private $error_message = '';

    /**
     * The unique identifier for the order item.
     * @var int|null
     */
    public $item_id;

    /**
     * The ID of the order this item belongs to.
     * @var int|null
     */
    public $order_id;

    /**
     * The ID of the product associated with this item.
     * @var int|null
     */
    public $product_id;

    /**
     * The quantity of the product ordered in this item.
     * @var int|null
     */
    public $quantity;

    /**
     * The price per unit of the product at the time the order was placed.
     * @var float|null
     */
    public $price;

    /**
     * The name of the product.
     * Populated when fetching item details with product info (e.g., via readByOrder).
     * @var string|null
     */
    public $product_name;

    /**
     * The image path of the product.
     * Populated when fetching item details with product info (e.g., via readByOrder).
     * @var string|null
     */
    public $product_image;

    /**
     * Constructor for the OrderItem model.
     *
     * @param PDO $db The database connection instance.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retrieves the last error message.
     *
     * @return string The last recorded error message, or an empty string if no error occurred.
     */
    public function getErrorMessage(): string
    {
        return $this->error_message;
    }

    /**
     * Creates a single order item record in the database.
     *
     * @param array $data Associative array containing order item data.
     *                    Required keys: 'order_id', 'product_id', 'quantity', 'price'.
     * @return int|false The ID of the newly created order item on success, false on failure (error message will be set).
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (:order_id, :product_id, :quantity, :price)";
        $stmt = $this->db->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':order_id', $data['order_id'], PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $data['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
        $stmt->bindParam(':price', $data['price']); // Let PDO handle type (decimal/float)

        try {
            if ($stmt->execute()) {
                return (int) $this->db->lastInsertId(); // Cast to int
            } else {
                $this->error_message = "Failed to create order item: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error creating order item: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e]);
            return false;
        }
    }

    /**
     * Creates multiple order items in a single query (bulk insert).
     *
     * More efficient than calling `create` multiple times within a loop, especially inside a transaction.
     * Builds a single INSERT statement with multiple value sets.
     *
     * @param int $orderId The ID of the order these items belong to.
     * @param array $items An array of associative arrays, each representing an item.
     *                     Each inner array must contain 'product_id', 'quantity', and 'price'.
     * @return bool True on success, false on failure (e.g., empty items array, invalid item data, DB error). Error message will be set on failure.
     */
    public function createBulk(int $orderId, array $items): bool
    {
        if (empty($items)) {
            $this->error_message = "No items provided for bulk insert.";
            return false;
        }

        // Base SQL statement
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES ";
        $valuePlaceholders = [];
        $params = [];
        $paramIndex = 0; // Index for unique parameter names

        // Prepare placeholders and parameters for each item
        foreach ($items as $item) {
            // Basic validation for required keys
            if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                $this->error_message = "Invalid item data provided for bulk insert. Missing required keys.";
                Registry::get('logger')->warning($this->error_message, ['item_data' => $item, 'order_id' => $orderId]);
                return false; // Stop processing if any item is invalid
            }

            // Create unique placeholders for this item's values
            $orderIdPlaceholder = ":order_id_" . $paramIndex;
            $productIdPlaceholder = ":product_id_" . $paramIndex;
            $quantityPlaceholder = ":quantity_" . $paramIndex;
            $pricePlaceholder = ":price_" . $paramIndex;

            // Add the placeholder group for this item
            $valuePlaceholders[] = "($orderIdPlaceholder, $productIdPlaceholder, $quantityPlaceholder, $pricePlaceholder)";

            // Add parameters for this item
            $params[$orderIdPlaceholder] = $orderId;
            $params[$productIdPlaceholder] = $item['product_id'];
            $params[$quantityPlaceholder] = $item['quantity'];
            $params[$pricePlaceholder] = $item['price'];

            $paramIndex++;
        }

        // Combine placeholders into the final SQL
        $sql .= implode(', ', $valuePlaceholders);

        // Prepare and bind parameters
        $stmt = $this->db->prepare($sql);
        foreach ($params as $placeholder => $value) {
            // Determine parameter type based on placeholder name convention
            if (strpos($placeholder, ':order_id_') === 0 || strpos($placeholder, ':product_id_') === 0 || strpos($placeholder, ':quantity_') === 0) {
                $stmt->bindValue($placeholder, $value, PDO::PARAM_INT);
            } elseif (strpos($placeholder, ':price_') === 0) {
                $stmt->bindValue($placeholder, $value); // Let PDO handle float/decimal type
            }
            // Note: No other types expected based on the loop logic
        }

        // Execute the bulk insert
        try {
            if ($stmt->execute()) {
                return true; // Success
            } else {
                $this->error_message = "Bulk order item insert failed: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error during bulk order item creation: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        }
    }


    /**
     * Retrieves all order items associated with a specific order ID.
     *
     * Joins with the products table to include product name and image path.
     *
     * @param int $orderId The ID of the order whose items are to be retrieved.
     * @return \PDOStatement|false A PDOStatement object containing the results on success, false on failure (error message will be set).
     */
    public function readByOrder(int $orderId)
    {
        $sql = "SELECT oi.item_id, oi.order_id, oi.product_id, oi.quantity, oi.price,
                       p.name as product_name, p.image_path as product_image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt; // Return the statement for fetching
            } else {
                $this->error_message = "Failed to execute query for order items: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching order items: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        }
    }

    /**
     * Finds a single order item by its unique ID.
     *
     * Includes additional product details (name, description, image).
     * Note: The SQL joins on `products.id` which might be incorrect if the product primary key is `product_id`.
     * Assuming `products.product_id` is the correct key based on `readByOrder`.
     * **Correction:** Changed `p.id` to `p.product_id` and `oi.id` to `oi.item_id`.
     *
     * @param int $orderItemId The ID of the order item to find.
     * @return array|false An associative array representing the order item and product details if found, otherwise false (error message set on DB error).
     */
    public function findById(int $orderItemId)
    {
        // Corrected SQL to use item_id and product_id
        $sql = "SELECT oi.*, p.name as product_name, p.description as product_description, p.image_path as product_image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.item_id = :order_item_id"; // Use item_id
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_item_id', $orderItemId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC); // Returns false if not found
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching single order item: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'item_id' => $orderItemId]);
            return false;
        }
    }

    /**
     * Updates specific fields of an existing order item.
     *
     * Only allows updating 'quantity' and 'price'.
     * Dynamically builds the SET clause.
     * Note: The SQL uses `id` which might be incorrect if the primary key is `item_id`.
     * **Correction:** Changed `id` to `item_id`.
     *
     * @param int $orderItemId The ID of the order item to update.
     * @param array $data An associative array where keys are 'quantity' or 'price' and values are the new values.
     * @return bool True if the update was successful (at least one row affected), false otherwise (error message will be set).
     */
    public function update(int $orderItemId, array $data): bool
    {
        $fields = [];
        $params = [':order_item_id' => $orderItemId]; // Use correct key name
        $allowedFields = ['quantity', 'price']; // Fields allowed for update

        // Build SET clause and parameters
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            $this->error_message = "No valid fields provided for item update.";
            return false; // Nothing to update
        }

        // Corrected SQL to use item_id
        $sql = "UPDATE order_items SET " . implode(', ', $fields) . " WHERE item_id = :order_item_id"; // Use item_id
        $stmt = $this->db->prepare($sql);

        // Bind parameters with appropriate types
        foreach ($params as $key => &$value) { // Use reference
            if ($key === ':order_item_id' || $key === ':quantity') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } elseif ($key === ':price') {
                $stmt->bindValue($key, $value); // Let PDO handle float/decimal
            }
            // No other fields allowed based on $allowedFields
        }
        unset($value); // Break reference

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Check if any row was actually updated
            } else {
                $this->error_message = "Failed to execute item update: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error updating order item: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'item_id' => $orderItemId]);
            return false;
        }
    }

    /**
     * Deletes a single order item from the database by its ID.
     *
     * Note: The SQL uses `id` which might be incorrect if the primary key is `item_id`.
     * **Correction:** Changed `id` to `item_id`.
     *
     * @param int $orderItemId The ID of the order item to delete.
     * @return bool True if the deletion was successful (at least one row affected), false otherwise (error message will be set).
     */
    public function delete(int $orderItemId): bool
    {
        // Corrected SQL to use item_id
        $sql = "DELETE FROM order_items WHERE item_id = :order_item_id"; // Use item_id
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_item_id', $orderItemId, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Check if a row was actually deleted
            } else {
                $this->error_message = "Failed to execute item delete: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error deleting order item: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'item_id' => $orderItemId]);
            return false;
        }
    }

    /**
     * Deletes all order items associated with a specific order ID.
     *
     * Useful when deleting an entire order to clean up related items.
     * Should typically be called within a transaction when deleting the parent order.
     *
     * @param int $orderId The ID of the order whose items should be deleted.
     * @return bool True if the deletion query executed successfully (regardless of rows affected), false on failure (error message will be set).
     */
    public function deleteByOrderId(int $orderId): bool
    {
        $sql = "DELETE FROM order_items WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);

        try {
            // For mass delete, success is often defined by execution without error,
            // as 0 rows affected is valid if the order had no items.
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->error_message = "Database error deleting items by order ID: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        }
    }
}
