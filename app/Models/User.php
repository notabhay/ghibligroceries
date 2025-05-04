<?php

namespace App\Models;

use PDO;
use App\Core\Database;

/**
 * Represents a user in the application.
 *
 * Provides methods for interacting with the users table, including user retrieval
 * (by ID, by email, all, paginated), creation, update, deletion, password verification,
 * role checking, and password reset token generation.
 */
class User
{
    /**
     * The database connection instance (PDO).
     * @var PDO
     */
    private $db;

    /**
     * Constructor for the User model.
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
     * Finds a specific user by their ID.
     *
     * Retrieves essential user details excluding sensitive information like password reset tokens.
     *
     * @param int $id The ID of the user to find.
     * @return array|null An associative array representing the user if found, otherwise null.
     *                    Returns null also if fetch returns false.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT user_id, name, phone, email, password, role, registration_date FROM users WHERE user_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result; // Return null if fetch fails
    }

    /**
     * Finds a specific user by their email address.
     *
     * Retrieves essential user details. Used for login and checking email existence.
     *
     * @param string $email The email address of the user to find.
     * @return array|null An associative array representing the user if found, otherwise null.
     *                    Returns null also if fetch returns false.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT user_id, name, phone, email, password, role, registration_date, account_status, failed_login_attempts FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result; // Return null if fetch fails
    }

    /**
     * Checks if an email address already exists in the users table.
     *
     * Useful for registration validation.
     *
     * @param string $email The email address to check.
     * @return bool True if the email exists, false otherwise.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        // fetchColumn returns 0 if no rows match, which evaluates to false. > 0 ensures boolean true only if count is 1 or more.
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Creates a new user record in the database.
     *
     * Assumes the password provided is already hashed.
     *
     * @param string $name The user's full name.
     * @param string $phone The user's phone number.
     * @param string $email The user's email address (should be unique).
     * @param string $password The hashed password for the user.
     * @return int|false The ID of the newly created user on success, false on failure.
     */
    public function create(string $name, string $phone, string $email, string $password): int|false
    {
        // Consider adding checks for email uniqueness before attempting insert, or handle potential PDOException for unique constraint violation.
        $stmt = $this->db->prepare("INSERT INTO users (name, phone, email, password) VALUES (:name, :phone, :email, :password)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR); // Store the hashed password

        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId(); // Cast to int
        }
        // Log error details from $stmt->errorInfo() if needed
        return false;
    }

    /**
     * Updates specific fields of an existing user.
     *
     * Only allows updating 'name', 'phone', 'email', 'password', and 'role'.
     * Dynamically builds the SET clause based on the provided data.
     * Note: If updating the password, ensure the provided value in $data['password'] is already hashed.
     *
     * @param int $id The ID of the user to update.
     * @param array $data An associative array where keys are the column names to update
     *                    (from allowed fields) and values are the new values.
     * @return bool True if the update query executed successfully, false otherwise.
     *              Note: Returns true even if no rows were affected (e.g., data was the same).
     *              Consider checking `$stmt->rowCount() > 0` if needed.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        $allowedFields = ['name', 'phone', 'email', 'password', 'role']; // Fields allowed for general update

        // Build SET clause and parameters dynamically
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "`$key` = :$key"; // Use backticks for field names
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false; // Nothing to update
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);

        // Bind parameters (all are strings except ID)
        foreach ($params as $key => &$value) { // Use reference for bindParam/bindValue
            if ($key === ':id') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        unset($value); // Break the reference

        // Execute returns true on success, false on failure.
        // It does not indicate if rows were actually changed.
        return $stmt->execute();
    }

    /**
     * Deletes a user from the database.
     *
     * Note: This permanently removes the user record. Consider implications for related data (e.g., orders).
     * Foreign key constraints or application logic should handle related data cleanup if necessary.
     *
     * @param int $id The ID of the user to delete.
     * @return bool True if the deletion query executed successfully, false otherwise.
     *              Note: Returns true even if no user with that ID existed.
     *              Consider checking `$stmt->rowCount() > 0` if needed.
     */
    public function delete(int $id): bool
    {
        // Consider adding checks or cascading deletes for related data (orders, etc.)
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute(); // Returns true on successful execution, false on error
    }

    /**
     * Verifies a user's password against the stored hash.
     *
     * Fetches the user by email and uses `password_verify`.
     *
     * @param string $email The user's email address.
     * @param string $password The plain-text password to verify.
     * @return bool True if the email exists and the password matches the stored hash, false otherwise.
     */
    public function verifyPassword(string $email, string $password): bool
    {
        $user = $this->findByEmail($email);
        // Check if user exists and has a password set
        if ($user && isset($user['password'])) {
            // Log password verification attempt details (for debugging)
            if (\App\Core\Registry::has('logger')) {
                $logger = \App\Core\Registry::get('logger');
                $logger->debug("Password verification attempt", [
                    'email' => $email,
                    'password_length' => strlen($password),
                    'stored_hash' => $user['password'],
                    'hash_length' => strlen($user['password']),
                    'hash_starts_with' => substr($user['password'], 0, 7)
                ]);

                // Verify the provided plain password against the stored hash
                $result = password_verify($password, $user['password']);

                $logger->debug("Password verification result", [
                    'email' => $email,
                    'result' => $result ? 'success' : 'failure'
                ]);

                return $result;
            }

            // Original verification if logger not available
            return password_verify($password, $user['password']);
        }
        return false; // User not found or password not set
    }

    /**
     * Retrieves all users from the database.
     *
     * Selects a subset of non-sensitive user fields.
     *
     * @return array An array of all users, each represented as an associative array.
     */
    public function getAll(): array
    {
        // Select only necessary, non-sensitive fields for a general listing
        $stmt = $this->db->query("SELECT user_id, name, phone, email, role, account_status, registration_date FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves users with pagination.
     *
     * Fetches a specific page of users, ordered by ID descending.
     * Returns an array containing the users for the current page and pagination details.
     *
     * @param int $page The current page number (defaults to 1, minimum 1).
     * @param int $perPage The number of users per page (defaults to 15).
     * @return array An array containing 'users' and 'pagination' data.
     *               Pagination structure: ['total', 'per_page', 'current_page', 'total_pages', 'has_more_pages']
     */
    public function getAllUsersPaginated(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page); // Ensure page is at least 1
        $offset = ($page - 1) * $perPage;

        // Get total user count for pagination calculation
        $countStmt = $this->db->query("SELECT COUNT(*) FROM users");
        $totalUsers = (int) $countStmt->fetchColumn();
        $totalPages = $totalUsers > 0 ? ceil($totalUsers / $perPage) : 0;

        // Prepare statement to fetch paginated users (non-sensitive fields)
        $stmt = $this->db->prepare("
            SELECT user_id, name, phone, email, role, account_status, registration_date
            FROM users
            ORDER BY user_id DESC -- Or order by name, registration_date etc.
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return data and pagination info
        return [
            'users' => $users,
            'pagination' => [
                'total' => $totalUsers,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more_pages' => ($page < $totalPages)
            ]
        ];
    }

    /**
     * Updates specific fields for a user, typically used in admin contexts.
     *
     * Allows updating 'name', 'phone', 'role', and 'account_status'.
     * Dynamically builds the SET clause. This is distinct from the general `update` method
     * which might allow different fields (like email or password).
     *
     * @param int $id The ID of the user to update.
     * @param array $data An associative array containing the data to update.
     *                    Allowed keys: 'name', 'phone', 'role', 'account_status'.
     * @return bool True if the update query executed successfully, false otherwise.
     *              Note: Returns true even if no rows were affected.
     */
    public function updateUser(int $id, array $data): bool
    {
        // Fields specifically allowed for this admin-like update function
        $allowedFields = ['name', 'phone', 'role', 'account_status'];
        $fields = [];
        $params = [':id' => $id];

        // Build SET clause and parameters
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false; // Nothing to update
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);

        // Bind parameters
        foreach ($params as $key => &$value) {
            if ($key === ':id') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                // All other allowed fields are expected to be strings
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        unset($value); // Break reference

        return $stmt->execute(); // Returns true on success, false on failure
    }

    /**
     * Generates and stores a password reset token for a user.
     *
     * Creates a cryptographically secure token and sets an expiration time (e.g., 1 hour).
     * Updates the user record with the token and its expiry timestamp.
     *
     * @param int $userId The ID of the user requesting the password reset.
     * @return string|bool The generated reset token string on success, false on failure.
     */
    public function generatePasswordResetToken(int $userId): string|bool
    {
        try {
            $token = bin2hex(random_bytes(32)); // Generate a secure random token
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // Set expiry (e.g., 1 hour from now)

            $stmt = $this->db->prepare("
                UPDATE users
                SET reset_token = :token, reset_token_expires = :expires_at
                WHERE user_id = :user_id
            ");
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Check if a row was actually updated to ensure user exists
                if ($stmt->rowCount() > 0) {
                    return $token;
                } else {
                    // User ID might not exist
                    return false;
                }
            }
            return false; // Execution failed
        } catch (\Exception $e) {
            // Catch potential errors from random_bytes or PDO
            \App\Core\Registry::get('logger')->error("Error generating password reset token", ['exception' => $e, 'user_id' => $userId]);
            // error_log("Error generating password reset token for user {$userId}: " . $e->getMessage()); // Keep if needed
            return false;
        }
    }

    /**
     * Checks if a user has a specific role.
     *
     * Fetches the user by ID and compares their role field.
     *
     * @param int $userId The ID of the user to check.
     * @param string $role The role name to check against (e.g., 'admin', 'customer').
     * @return bool True if the user exists and has the specified role, false otherwise.
     */
    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->findById($userId);
        // Check if user exists, has a role set, and the role matches
        return $user && isset($user['role']) && $user['role'] === $role;
    }

    /**
     * Checks if a user has the 'admin' role.
     *
     * Convenience method using `hasRole`.
     *
     * @param int $userId The ID of the user to check.
     * @return bool True if the user exists and has the 'admin' role, false otherwise.
     */
    public function isAdmin(int $userId): bool
    {
        return $this->hasRole($userId, 'admin');
    }

    /**
     * Gets the total count of all registered users.
     *
     * @return int The total number of users, or 0 on error.
     */
    public function getTotalUserCount(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM users");
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            \App\Core\Registry::get('logger')->error("Error counting users", ['exception' => $e]);
            // error_log("Error counting users: " . $e->getMessage()); // Keep if needed
            return 0; // Return 0 on error
        }
    }
}
