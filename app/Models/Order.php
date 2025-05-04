<?php

namespace App\Models;

use PDO;
use App\Core\Database; // Although not directly used in constructor, it might be relevant contextually.
use App\Core\Registry;

/**
 * Represents a customer order in the application.
 *
 * Provides methods for managing orders, including creation, retrieval (by user, by ID),
 * status updates, deletion, pagination, and transaction management.
 * It also handles fetching associated user details and order items.
 */
class Order
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
     * The unique identifier for the order.
     * Populated after successful creation or retrieval.
     * @var int|null
     */
    public $order_id;

    /**
     * The ID of the user who placed the order.
     * @var int|null
     */
    public $user_id;

    /**
     * The total monetary amount of the order.
     * @var float|null
     */
    public $total_amount;

    /**
     * The current status of the order (e.g., 'pending', 'processing', 'completed', 'cancelled').
     * @var string|null
     */
    public $status;

    /**
     * Optional notes provided by the customer or admin regarding the order.
     * @var string|null
     */
    public $notes;

    /**
     * The shipping address for the order.
     * @var string|null
     */
    public $shipping_address;

    /**
     * The timestamp when the order was placed.
     * @var string|null (Typically in 'YYYY-MM-DD HH:MM:SS' format)
     */
    public $order_date;

    /**
     * The name of the user associated with the order.
     * Populated when fetching order details with user info.
     * @var string|null
     */
    public $user_name;

    /**
     * The email address of the user associated with the order.
     * Populated when fetching order details with user info.
     * @var string|null
     */
    public $user_email;

    /**
     * The phone number of the user associated with the order.
     * Populated when fetching order details with user info.
     * @var string|null
     */
    public $user_phone;

    /**
     * Constructor for the Order model.
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
     * Begins a database transaction.
     *
     * @return bool True on success, false on failure (error message will be set).
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->db->beginTransaction();
        } catch (\PDOException $e) {
            $this->error_message = "Failed to begin transaction: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e]);
            return false;
        }
    }

    /**
     * Commits the current database transaction.
     *
     * @return bool True on success, false on failure (error message will be set).
     */
    public function commit(): bool
    {
        try {
            return $this->db->commit();
        } catch (\PDOException $e) {
            $this->error_message = "Failed to commit transaction: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e]);
            return false;
        }
    }

    /**
     * Rolls back the current database transaction.
     *
     * @return bool True on success, false on failure (error message will be set).
     */
    public function rollback(): bool
    {
        try {
            return $this->db->rollBack();
        } catch (\PDOException $e) {
            $this->error_message = "Failed to rollback transaction: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e]);
            return false;
        }
    }

    /**
     * Creates a new order record in the database.
     *
     * Sets default values for status ('pending'), notes (null), and shipping_address (null) if not provided.
     * Populates the `order_id` property on success.
     *
     * @param array $data Associative array containing order data.
     *                    Required keys: 'user_id', 'total_amount'.
     *                    Optional keys: 'status', 'notes', 'shipping_address'.
     * @return int|false The ID of the newly created order on success, false on failure (error message will be set).
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO orders (user_id, total_amount, status, notes, shipping_address, order_date)
                VALUES (:user_id, :total_amount, :status, :notes, :shipping_address, NOW())";
        $stmt = $this->db->prepare($sql);

        // Set defaults if not provided
        $data['status'] = $data['status'] ?? 'pending';
        $data['notes'] = $data['notes'] ?? null;
        $data['shipping_address'] = $data['shipping_address'] ?? null;

        // Bind parameters
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':total_amount', $data['total_amount']); // PDO determines type
        $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        $stmt->bindParam(':notes', $data['notes'], PDO::PARAM_STR);
        $stmt->bindParam(':shipping_address', $data['shipping_address'], PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                $this->order_id = (int) $this->db->lastInsertId(); // Cast to int
                return $this->order_id;
            } else {
                $this->error_message = "Order execution failed: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error during order creation: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e]);
            return false;
        }
    }

    /**
     * Retrieves a specific order by its ID and the user ID it belongs to.
     *
     * Ensures a user can only access their own orders.
     * Populates the public properties of the Order object with the fetched data on success.
     *
     * @param int $orderId The ID of the order to retrieve.
     * @param int $userId The ID of the user who owns the order.
     * @return array|false An associative array containing the order and user details on success, false if not found or access denied (error message will be set).
     */
    public function readOneByIdAndUser(int $orderId, int $userId): array|false
    {
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = :order_id AND o.user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($orderData) {
                // Populate object properties
                $this->order_id = (int) $orderData['order_id'];
                $this->user_id = (int) $orderData['user_id'];
                $this->total_amount = (float) $orderData['total_amount'];
                $this->status = $orderData['status'];
                $this->notes = $orderData['notes'];
                $this->shipping_address = $orderData['shipping_address'];
                $this->order_date = $orderData['order_date'];
                $this->user_name = $orderData['user_name'];
                $this->user_email = $orderData['user_email'];
                $this->user_phone = $orderData['user_phone'];
                return $orderData;
            } else {
                $this->error_message = "Order not found or access denied.";
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching order: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId, 'user_id' => $userId]);
            return false;
        }
    }

    /**
     * Retrieves all orders placed by a specific user.
     *
     * Returns only essential order details (ID, date, total, status), ordered by date descending.
     *
     * @param int $userId The ID of the user whose orders are to be retrieved.
     * @return \PDOStatement|false A PDOStatement object containing the results on success, false on failure (error message will be set). The statement can be iterated over or fetched from.
     */
    public function readByUser(int $userId): \PDOStatement|false
    {
        $sql = "SELECT o.order_id, o.order_date, o.total_amount, o.status
                FROM orders o
                WHERE o.user_id = :user_id
                ORDER BY o.order_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt;
            } else {
                $this->error_message = "Failed to execute query for user orders: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching user orders: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'user_id' => $userId]);
            return false;
        }
    }

    /**
     * Updates the status of a specific order.
     *
     * Note: Consider using `updateOrderStatus` for validation of status values.
     *
     * @param int $orderId The ID of the order to update.
     * @param string $status The new status for the order.
     * @return bool True if the status was updated successfully (at least one row affected), false otherwise (error message will be set).
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $sql = "UPDATE orders SET status = :status WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            } else {
                $this->error_message = "Failed to execute status update: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error updating order status: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId, 'status' => $status]);
            return false;
        }
    }

    /**
     * Retrieves a single order by its ID, including associated user details.
     *
     * This method does not check user ownership, suitable for admin contexts.
     *
     * @param int $orderId The ID of the order to retrieve.
     * @return array|false An associative array containing the order and user details on success, false if not found (error message will be set on DB error).
     */
    public function readOne(int $orderId): array|false
    {
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC); // Returns false if no row found
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching order: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        }
    }

    /**
     * Updates specific fields of an existing order.
     *
     * Only allows updating 'total_amount', 'status', 'notes', and 'shipping_address'.
     * Dynamically builds the SET clause based on the provided data.
     *
     * @param int $orderId The ID of the order to update.
     * @param array $data An associative array where keys are the column names to update
     *                    and values are the new values.
     * @return bool True if the update was successful (at least one row affected), false otherwise (error message will be set).
     */
    public function update(int $orderId, array $data): bool
    {
        $fields = [];
        $params = [':order_id' => $orderId];
        $allowedFields = ['total_amount', 'status', 'notes', 'shipping_address']; // Fields allowed for update

        // Build SET clause and parameters dynamically
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "`$key` = :$key"; // Use backticks for field names
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            $this->error_message = "No valid fields provided for update.";
            return false; // Nothing to update
        }

        $sql = "UPDATE orders SET " . implode(', ', $fields) . " WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);

        // Bind parameters with appropriate types
        foreach ($params as $key => &$value) { // Use reference for bindParam/bindValue
            if ($key === ':order_id') { // user_id is not updated here
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } elseif ($key === ':total_amount') {
                $stmt->bindValue($key, $value); // Let PDO infer type (usually string for decimal/float)
            } else { // status, notes, shipping_address
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        unset($value); // Break the reference

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            } else {
                $this->error_message = "Failed to execute generic update: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error during generic order update: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        }
    }

    /**
     * Deletes an order from the database.
     *
     * Note: This permanently removes the order record. Consider soft deletes or status changes for reversibility.
     * It does NOT automatically delete associated order items. Use OrderItem::deleteByOrderId for that.
     *
     * @param int $orderId The ID of the order to delete.
     * @return bool True if the deletion was successful (at least one row affected), false otherwise (error message will be set).
     */
    public function delete(int $orderId): bool
    {
        // Consider adding a check for related order items or handling them within a transaction
        $sql = "DELETE FROM orders WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            } else {
                $this->error_message = "Failed to execute delete: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error deleting order: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        }
    }

    /**
     * Retrieves all orders, optionally filtered by status and with pagination limits.
     *
     * Includes associated user details. Orders are sorted by date descending.
     *
     * @param string|null $status Optional status to filter orders by (e.g., 'pending').
     * @param int|null $limit Optional maximum number of orders to return.
     * @param int|null $offset Optional starting offset for pagination.
     * @return \PDOStatement|false A PDOStatement object containing the results on success, false on failure (error message will be set).
     */
    public function getAll(?string $status = null, ?int $limit = null, ?int $offset = null): \PDOStatement|false
    {
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                 FROM orders o
                 JOIN users u ON o.user_id = u.user_id";
        $params = [];

        // Add status filter if provided
        if ($status !== null) {
            $sql .= " WHERE o.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY o.order_date DESC";

        // Add LIMIT and OFFSET if provided
        // Note: PDO requires LIMIT/OFFSET values to be bound as integers.
        $limitParam = ':limit';
        $offsetParam = ':offset';

        if ($limit !== null) {
            $sql .= " LIMIT $limitParam"; // Use placeholder name
            $params[$limitParam] = $limit;
        }
        if ($offset !== null) {
            // OFFSET requires LIMIT
            if ($limit === null) {
                // Set a large default limit if only offset is provided (or handle as error)
                $sql .= " LIMIT :default_limit";
                $params[':default_limit'] = 1000000; // Or some very large number
            }
            $sql .= " OFFSET $offsetParam"; // Use placeholder name
            $params[$offsetParam] = $offset;
        }


        try {
            $stmt = $this->db->prepare($sql);

            // Bind parameters with correct types
            foreach ($params as $key => &$value) {
                if ($key === $limitParam || $key === $offsetParam || $key === ':default_limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else { // status
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            unset($value); // Break reference

            if ($stmt->execute()) {
                return $stmt;
            } else {
                $this->error_message = "Failed to execute query for all orders: " . implode(", ", $stmt->errorInfo());
                return false;
            }
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching all orders: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'status' => $status, 'limit' => $limit, 'offset' => $offset]);
            return false;
        }
    }

    /**
     * Retrieves orders with pagination and optional filtering.
     *
     * Filters can include status, start date, and end date.
     * Returns an array containing the orders for the current page and pagination details.
     *
     * @param int $page The current page number (defaults to 1).
     * @param int $perPage The number of orders per page (defaults to 15).
     * @param array $filters Associative array of filters. Keys: 'status', 'start_date', 'end_date'.
     * @return array|false An array containing 'orders' and 'pagination' data on success, false on failure (error message will be set).
     *                     Pagination structure: ['total', 'per_page', 'current_page', 'total_pages', 'has_more_pages']
     */
    public function getAllOrdersPaginated(int $page = 1, int $perPage = 15, array $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        // Base SQL for fetching orders with user details
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE 1=1"; // Start WHERE clause for easier appending

        // Base SQL for counting total matching orders
        $countSql = "SELECT COUNT(*) FROM orders o WHERE 1=1";

        $params = []; // Parameters for binding

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $countSql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND o.order_date >= :start_date";
            $countSql .= " AND o.order_date >= :start_date";
            // Assume date format YYYY-MM-DD, append time for inclusive range
            $params[':start_date'] = $filters['start_date'] . ' 00:00:00';
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND o.order_date <= :end_date";
            $countSql .= " AND o.order_date <= :end_date";
            // Assume date format YYYY-MM-DD, append time for inclusive range
            $params[':end_date'] = $filters['end_date'] . ' 23:59:59';
        }

        // Add ordering and pagination to the main query
        $sql .= " ORDER BY o.order_date DESC";
        $sql .= " LIMIT :limit OFFSET :offset";

        // Parameters for the main query (including limit and offset)
        $queryParams = $params; // Copy filter params
        $queryParams[':limit'] = $perPage;
        $queryParams[':offset'] = $offset;

        // Parameters for the count query (excluding limit and offset)
        $countParams = $params;

        try {
            // Execute count query
            $countStmt = $this->db->prepare($countSql);
            // Bind count parameters (all are strings except potentially IDs if added later)
            foreach ($countParams as $key => &$value) {
                $countStmt->bindValue($key, $value); // PDO usually handles type correctly here
            }
            unset($value);
            $countStmt->execute();
            $totalOrders = (int) $countStmt->fetchColumn();
            $totalPages = ceil($totalOrders / $perPage);

            // Execute main query
            $stmt = $this->db->prepare($sql);
            // Bind main query parameters (including limit/offset as INT)
            foreach ($queryParams as $key => &$value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value); // Let PDO handle others (likely string)
                }
            }
            unset($value);

            if (!$stmt->execute()) {
                $this->error_message = "Failed to execute paginated orders query: " . implode(", ", $stmt->errorInfo());
                return false;
            }

            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Return results and pagination info
            return [
                'orders' => $orders,
                'pagination' => [
                    'total' => $totalOrders,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'has_more_pages' => ($page < $totalPages)
                ]
            ];
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching paginated orders: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, [
                'exception' => $e,
                'page' => $page,
                'per_page' => $perPage,
                'filters' => $filters
            ]);
            return false;
        }
    }


    /**
     * Counts all orders, optionally filtered by status.
     *
     * @param string|null $status Optional status to filter the count by.
     * @return int The total number of orders matching the criteria, or 0 on error.
     */
    public function countAll(?string $status = null): int
    {
        $sql = "SELECT COUNT(*) FROM orders";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params); // execute() can take params directly
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->error_message = "Database error counting orders: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'status' => $status]);
            return 0; // Return 0 on error
        }
    }

    /**
     * Retrieves a single order along with all its associated order items and user details.
     *
     * Calculates the total amount from items and logs a warning if it differs significantly
     * from the stored `total_amount` in the order record.
     *
     * @param int $orderId The ID of the order to retrieve.
     * @return array|false An associative array containing the order details, user details, and an 'items' array,
     *                     or false if the order is not found or an error occurs (error message will be set).
     */
    public function findOrderWithDetails(int $orderId): array|false
    {
        try {
            // 1. Fetch the basic order details (including user info)
            $order = $this->readOne($orderId);
            if (!$order) {
                // error_message should be set by readOne if it failed due to DB error
                if (empty($this->error_message)) {
                    $this->error_message = "Order not found."; // Set specific message if readOne returned false (not found)
                }
                return false;
            }

            // 2. Fetch associated order items
            $orderItemModel = new OrderItem($this->db); // Assuming OrderItem model exists
            $itemsStmt = $orderItemModel->readByOrder($orderId);

            if (!$itemsStmt) {
                // If fetching items failed, use the error message from OrderItem model
                $this->error_message = "Failed to fetch order items: " . $orderItemModel->getErrorMessage();
                return false;
            }

            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Calculate total amount from items for verification
            $calculatedTotalAmount = 0.0;
            foreach ($items as $item) {
                // Ensure price and quantity are numeric before calculation
                if (isset($item['price']) && is_numeric($item['price']) && isset($item['quantity']) && is_numeric($item['quantity'])) {
                    $calculatedTotalAmount += (float) $item['price'] * (int) $item['quantity'];
                } else {
                    Registry::get('logger')->warning("Invalid price or quantity for item in order.", [
                        'order_id' => $orderId,
                        'item_id' => $item['item_id'] ?? 'N/A',
                        'item_price' => $item['price'] ?? 'N/A',
                        'item_quantity' => $item['quantity'] ?? 'N/A'
                    ]);
                }
            }

            // 4. Add items to the order array
            $order['items'] = $items;

            // 5. Compare stored total with calculated total (optional check)
            if (isset($order['total_amount']) && abs((float)$order['total_amount'] - $calculatedTotalAmount) > 0.01) { // Use a small tolerance for float comparison
                Registry::get('logger')->warning("Order total discrepancy detected.", [
                    'order_id' => $orderId,
                    'stored_total' => $order['total_amount'],
                    'calculated_total' => $calculatedTotalAmount
                ]);
                // Decide if this should be an error or just a warning
            }

            return $order;
        } catch (\PDOException $e) {
            // Catch potential exceptions from readOne or OrderItem instantiation/methods if they throw
            $this->error_message = "Database error fetching order with details: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        } catch (\Exception $e) {
            // Catch other potential errors (e.g., OrderItem class not found)
            $this->error_message = "Application error fetching order details: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'order_id' => $orderId]);
            return false;
        }
    }

    /**
     * Updates the status of an order, ensuring the provided status is valid.
     *
     * Valid statuses: 'pending', 'processing', 'completed', 'cancelled'.
     * Uses the `updateStatus` method internally after validation.
     *
     * @param int $orderId The ID of the order to update.
     * @param string $status The new status ('pending', 'processing', 'completed', 'cancelled').
     * @return bool True on successful update, false if the status is invalid or the update fails (error message will be set).
     */
    public function updateOrderStatus(int $orderId, string $status): bool
    {
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            $this->error_message = "Invalid order status provided: {$status}";
            return false;
        }
        // Call the actual update method after validation
        return $this->updateStatus($orderId, $status);
    }

    /**
     * Gets the total count of all orders in the system.
     *
     * @return int The total number of orders, or 0 on error.
     */
    public function getTotalOrderCount(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM orders");
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->error_message = "Database error counting orders: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e]);
            return 0; // Return 0 on error
        }
    }

    /**
     * Gets the count of orders for a specific status.
     *
     * @param string $status The status to count orders for (e.g., 'pending', 'completed').
     * @return int The number of orders with the specified status, or 0 on error.
     */
    public function getOrderCountByStatus(string $status): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE status = :status");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->error_message = "Database error counting orders by status: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'status' => $status]);
            return 0; // Return 0 on error
        }
    }

    /**
     * Retrieves a specified number of the most recent orders.
     *
     * Includes associated user details. Orders are sorted by date descending.
     *
     * @param int $limit The maximum number of recent orders to retrieve (defaults to 5).
     * @return array|false An array of the most recent orders (each as an associative array) on success,
     *                     or false on failure (error message will be set).
     */
    public function getRecentOrders(int $limit = 5): array|false
    {
        try {
            $sql = "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
                    FROM orders o
                    JOIN users u ON o.user_id = u.user_id
                    ORDER BY o.order_date DESC
                    LIMIT :limit"; // Use named placeholder
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT); // Bind as integer
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->error_message = "Database error fetching recent orders: " . $e->getMessage();
            Registry::get('logger')->error($this->error_message, ['exception' => $e, 'limit' => $limit]);
            return false;
        }
    }
}
