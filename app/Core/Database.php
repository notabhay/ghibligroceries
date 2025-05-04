<?php

namespace App\Core;

use PDO; // Import the PDO class for database interactions.
use PDOException; // Import the PDOException class for handling database errors.
use App\Core\Registry; // Import the Registry for accessing shared services like the logger.

/**
 * Class Database
 *
 * Provides a wrapper around PDO for database connections and operations.
 * Handles connection management, query execution (SELECT, INSERT/UPDATE/DELETE),
 * transaction management, and error handling/logging.
 *
 * @package App\Core
 */
class Database
{
    /** @var string|null Database host address. */
    private $host;
    /** @var string|null Database name. */
    private $db_name;
    /** @var string|null Database username. */
    private $username;
    /** @var string|null Database password. */
    private $password;
    /** @var string Character set for the database connection. */
    private $charset = 'utf8';
    /** @var PDO|null The active PDO connection instance. Null if not connected. */
    private $conn;
    /** @var string Stores the last error message encountered. */
    private $error = '';

    /**
     * Database constructor.
     *
     * Initializes the database connection parameters and attempts to establish
     * the initial connection.
     *
     * @param string $host Database host address.
     * @param string $db_name Database name.
     * @param string $username Database username.
     * @param string $password Database password.
     * @param string $charset Character set for the connection (defaults to 'utf8').
     */
    public function __construct($host, $db_name, $username, $password, $charset = 'utf8')
    {
        $this->host = $host;
        $this->db_name = $db_name;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
        $this->connect(); // Attempt initial connection upon instantiation.
    }

    /**
     * Gets the active PDO connection instance.
     *
     * If a connection doesn't exist, it attempts to establish one.
     *
     * @return PDO|null The PDO connection instance, or null if connection fails.
     */
    public function getConnection(): ?PDO
    {
        if ($this->conn) {
            return $this->conn;
        }
        // If not connected, attempt to connect again.
        return $this->connect();
    }

    /**
     * Establishes a new PDO database connection.
     *
     * Uses the configured credentials and sets common PDO attributes for error handling
     * and fetch mode. Logs critical errors if the connection fails.
     *
     * @return PDO|null The PDO connection instance, or null if connection fails.
     */
    private function connect(): ?PDO
    {
        // If already connected, return the existing connection.
        if ($this->conn) {
            return $this->conn;
        }

        // Ensure the connection is null before attempting a new one.
        $this->conn = null;
        // Construct the Data Source Name (DSN) string.
        $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;

        try {
            // Attempt to create a new PDO instance.
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    // Set PDO attributes:
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays.
                    PDO::ATTR_EMULATE_PREPARES => false, // Disable emulation of prepared statements for security and performance.
                ]
            );
            $this->error = ''; // Clear any previous error on successful connection.
        } catch (PDOException $e) {
            // Handle connection errors.
            $this->error = "Connection Error: " . $e->getMessage();
            // Log the critical error using the application logger.
            if (Registry::has('logger')) {
                Registry::get('logger')->critical("Database connection error: " . $e->getMessage(), ['exception' => $e]);
            }
            $this->conn = null; // Ensure connection is null on failure.
        }

        return $this->conn;
    }

    /**
     * Gets the last error message recorded.
     *
     * @return string The last error message, or an empty string if no error occurred.
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Executes a SELECT query and returns the PDOStatement object.
     *
     * Prepares and executes a query intended to fetch data (e.g., SELECT).
     * Handles potential errors and logs them.
     *
     * @param string $query The SQL query string with optional placeholders (e.g., :id).
     * @param array $params An associative array of parameters to bind to the query placeholders.
     * @return \PDOStatement|false The PDOStatement object on success, allowing iteration over results,
     *                             or false on failure (connection issue or query error).
     */
    public function select($query, $params = [])
    {
        if (!$this->conn) {
            $this->error = "Database not connected.";
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Attempted query on non-existent DB connection.");
            }
            return false;
        }

        try {
            $stmt = $this->conn->prepare($query); // Prepare the statement.
            $stmt->execute($params); // Execute with bound parameters.
            $this->error = ''; // Clear error on success.
            return $stmt; // Return the statement object.
        } catch (PDOException $e) {
            $this->error = "Query Error: " . $e->getMessage();
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Database query error: " . $e->getMessage(), [
                    'query' => $query,
                    'params' => $params, // Log parameters (be cautious with sensitive data in production logs)
                    'exception' => $e
                ]);
            }
            return false; // Return false on error.
        }
    }

    /**
     * Executes a non-SELECT query (INSERT, UPDATE, DELETE) and returns the number of affected rows.
     *
     * Prepares and executes a query that modifies data.
     * Handles potential errors and logs them.
     *
     * @param string $query The SQL query string with optional placeholders.
     * @param array $params An associative array of parameters to bind to the query placeholders.
     * @return int|false The number of rows affected by the query on success,
     *                   or false on failure (connection issue or query error).
     */
    public function execute($query, $params = [])
    {
        if (!$this->conn) {
            $this->error = "Database not connected.";
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Attempted execute on non-existent DB connection.");
            }
            return false;
        }

        try {
            $stmt = $this->conn->prepare($query); // Prepare the statement.
            $stmt->execute($params); // Execute with bound parameters.
            $this->error = ''; // Clear error on success.
            return $stmt->rowCount(); // Return the number of affected rows.
        } catch (PDOException $e) {
            $this->error = "Query Error: " . $e->getMessage();
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Database execute error: " . $e->getMessage(), [
                    'query' => $query,
                    'params' => $params, // Log parameters (be cautious with sensitive data)
                    'exception' => $e
                ]);
            }
            return false; // Return false on error.
        }
    }

    /**
     * Begins a database transaction.
     *
     * Turns off autocommit mode. Changes are not saved until commit() is called.
     *
     * @return bool True on success, false on failure (connection issue or error).
     */
    public function beginTransaction(): bool
    {
        if (!$this->conn) {
            $this->error = "Database not connected.";
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Attempted beginTransaction on non-existent DB connection.");
            }
            return false;
        }

        try {
            $this->error = '';
            return $this->conn->beginTransaction();
        } catch (PDOException $e) {
            $this->error = "Transaction Error: " . $e->getMessage();
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Transaction begin error: " . $e->getMessage(), ['exception' => $e]);
            }
            return false;
        }
    }

    /**
     * Commits the current database transaction.
     *
     * Makes all changes since beginTransaction() permanent.
     *
     * @return bool True on success, false on failure (no active transaction, connection issue, or error).
     */
    public function commit(): bool
    {
        if (!$this->conn || !$this->conn->inTransaction()) {
            $this->error = "Cannot commit: No active transaction or connection.";
            if (Registry::has('logger')) {
                Registry::get('logger')->error($this->error);
            }
            return false;
        }

        try {
            $this->error = '';
            return $this->conn->commit();
        } catch (PDOException $e) {
            $this->error = "Commit Error: " . $e->getMessage();
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Transaction commit error: " . $e->getMessage(), ['exception' => $e]);
            }
            // Attempt to rollback if commit fails to leave the DB in a consistent state.
            $this->rollback();
            return false;
        }
    }

    /**
     * Rolls back the current database transaction.
     *
     * Discards all changes made since beginTransaction().
     *
     * @return bool True on success, false on failure (no active transaction, connection issue, or error).
     */
    public function rollback(): bool
    {
        if (!$this->conn || !$this->conn->inTransaction()) {
            $this->error = "Cannot rollback: No active transaction or connection.";
            if (Registry::has('logger')) {
                Registry::get('logger')->error($this->error);
            }
            return false;
        }

        try {
            $this->error = '';
            return $this->conn->rollBack();
        } catch (PDOException $e) {
            $this->error = "Rollback Error: " . $e->getMessage();
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Transaction rollback error: " . $e->getMessage(), ['exception' => $e]);
            }
            return false;
        }
    }

    /**
     * Gets the ID of the last inserted row.
     *
     * Note: Behavior might vary depending on the database driver and table structure.
     *
     * @param string|null $name Name of the sequence object (if applicable, e.g., PostgreSQL).
     * @return string|false The ID of the last inserted row as a string, or false on failure.
     */
    public function lastInsertId($name = null)
    {
        if (!$this->conn) {
            $this->error = "Database not connected.";
            if (Registry::has('logger')) {
                Registry::get('logger')->error("Attempted lastInsertId on non-existent DB connection.");
            }
            return false;
        }

        try {
            $this->error = '';
            // Pass the optional sequence name if provided.
            return $this->conn->lastInsertId($name);
        } catch (PDOException $e) {
            $this->error = "LastInsertId Error: " . $e->getMessage();
            if (Registry::has('logger')) {
                Registry::get('logger')->error("LastInsertId error: " . $e->getMessage(), ['exception' => $e]);
            }
            return false;
        }
    }

    /**
     * Closes the database connection by setting the PDO instance to null.
     *
     * @return void
     */
    public function closeConnection(): void
    {
        $this->conn = null;
    }

    /**
     * Destructor.
     *
     * Ensures the database connection is closed when the Database object is destroyed.
     */
    public function __destruct()
    {
        $this->closeConnection();
    }
}
