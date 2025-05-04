<?php

namespace App\Helpers;

use PDO;
use PDOException;
use PDOStatement; // Added for type hinting

/**
 * Database Helper Functions
 *
 * This file provides a collection of procedural helper functions for common database
 * operations using PDO (PHP Data Objects). These functions simplify tasks like
 * selecting, inserting, updating, deleting records, executing raw queries,
 * managing transactions, and establishing a database connection.
 * They include basic error handling by logging PDOExceptions.
 */

/**
 * Executes a SELECT query and fetches all results.
 *
 * Prepares and executes a given SQL SELECT query with optional parameters.
 * Uses prepared statements to prevent SQL injection.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @param string $query The SQL SELECT query string. Can contain placeholders (e.g., ?, :name).
 * @param array $params An array of parameters to bind to the query placeholders.
 *                      For unnamed placeholders (?), use a numerically indexed array.
 *                      For named placeholders (:name), use an associative array.
 * @return array|false An array containing all result rows as associative arrays,
 *                     or false if the query fails.
 */
function db_select(PDO $conn, string $query, array $params = []): array|false
{
    try {
        $stmt = $conn->prepare($query);
        // Bind parameters if any are provided.
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                // Check if the parameter key is an integer (for unnamed placeholders like ?)
                // or a string (for named placeholders like :name).
                if (is_int($param)) {
                    // PDO uses 1-based indexing for unnamed placeholders.
                    $stmt->bindValue($param + 1, $value, getParamType($value));
                } else {
                    // Bind named placeholder.
                    $stmt->bindValue($param, $value, getParamType($value));
                }
            }
        }
        $stmt->execute();
        // Fetch all results as an associative array.
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error for debugging.
        error_log("Database error (SELECT): " . $e->getMessage() . " Query: " . $query);
        return false; // Indicate failure.
    }
}

/**
 * Inserts a new record into a specified table.
 *
 * Constructs and executes an INSERT statement based on the provided data array.
 * Uses prepared statements to prevent SQL injection.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @param string $table The name of the table to insert data into.
 * @param array $data An associative array where keys are column names and values are the data to insert.
 * @return string|false The ID of the last inserted row (if the table has an auto-incrementing ID),
 *                      or false if the insertion fails. Note: Return type depends on the driver and table setup.
 */
function db_insert(PDO $conn, string $table, array $data): string|false
{
    // Basic sanitation for table name (prevent injection in table name itself)
    $table = sanitize_identifier($table);
    if (empty($data)) {
        error_log("Database error (INSERT): No data provided for table {$table}.");
        return false;
    }

    try {
        // Build the column list and placeholder list for the prepared statement.
        $columns = implode(', ', array_map('App\\Helpers\\sanitize_identifier', array_keys($data))); // Sanitize column names
        $placeholders = ':' . implode(', :', array_keys($data)); // Named placeholders based on keys
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $conn->prepare($query);
        // Bind values from the data array to the named placeholders.
        foreach ($data as $column => $value) {
            // Note: Parameter name in bindValue must match the placeholder including the colon.
            $stmt->bindValue(":{$column}", $value, getParamType($value));
        }
        $stmt->execute();
        // Return the ID of the newly inserted row.
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        error_log("Database error (INSERT) into {$table}: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates existing records in a specified table based on a condition.
 *
 * Constructs and executes an UPDATE statement.
 * Uses prepared statements for both the SET values and the WHERE clause parameters.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @param string $table The name of the table to update.
 * @param array $data An associative array where keys are column names and values are the new data.
 * @param string $condition The WHERE clause condition (e.g., "id = :id", "status = ?"). Placeholders should be used.
 * @param array $params An associative or indexed array containing parameters for the WHERE clause condition.
 *                      These parameters are bound *after* the parameters from the $data array.
 * @return int|false The number of affected rows, or false if the update fails.
 */
function db_update(PDO $conn, string $table, array $data, string $condition, array $params = []): int|false
{
    // Basic sanitation for table name
    $table = sanitize_identifier($table);
    if (empty($data)) {
        error_log("Database error (UPDATE): No data provided for table {$table}.");
        return false;
    }

    try {
        // Build the SET part of the query dynamically.
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $sanitizedColumn = sanitize_identifier($column); // Sanitize column name
            // Use distinct placeholder names for SET clause to avoid conflicts with WHERE clause params if names overlap
            $setClauses[] = "{$sanitizedColumn} = :set_{$sanitizedColumn}";
        }
        $set = implode(', ', $setClauses);

        $query = "UPDATE {$table} SET {$set} WHERE {$condition}";
        $stmt = $conn->prepare($query);

        // Bind values for the SET clause.
        foreach ($data as $column => $value) {
            $sanitizedColumn = sanitize_identifier($column);
            $stmt->bindValue(":set_{$sanitizedColumn}", $value, getParamType($value));
        }

        // Bind values for the WHERE clause.
        $paramIndex = 1; // For unnamed placeholders in WHERE clause
        foreach ($params as $param => $value) {
            if (is_int($param)) {
                // Bind unnamed placeholder (?). Use an index that doesn't conflict with named placeholders.
                // It's generally safer to use named placeholders exclusively in WHERE when also using named in SET.
                // If using mixed, ensure placeholder names/indices are unique across SET and WHERE.
                // This implementation assumes WHERE uses standard placeholders (? or :name).
                $stmt->bindValue($paramIndex++, $value, getParamType($value)); // Or use $param + 1 if 0-indexed array passed
            } else {
                // Bind named placeholder (:name) for WHERE clause.
                $stmt->bindValue($param, $value, getParamType($value));
            }
        }

        $stmt->execute();
        // Return the number of rows affected by the update.
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Database error (UPDATE) on {$table}: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes records from a specified table based on a condition.
 *
 * Constructs and executes a DELETE statement.
 * Uses prepared statements to prevent SQL injection in the WHERE clause.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @param string $table The name of the table to delete from.
 * @param string $condition The WHERE clause condition (e.g., "id = :id", "status = ?").
 * @param array $params An array of parameters to bind to the WHERE clause placeholders.
 * @return int|false The number of affected rows, or false if the deletion fails.
 */
function db_delete(PDO $conn, string $table, string $condition, array $params = []): int|false
{
    // Basic sanitation for table name
    $table = sanitize_identifier($table);
    try {
        $query = "DELETE FROM {$table} WHERE {$condition}";
        $stmt = $conn->prepare($query);

        // Bind parameters for the WHERE clause.
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                if (is_int($param)) {
                    $stmt->bindValue($param + 1, $value, getParamType($value)); // 1-based index
                } else {
                    $stmt->bindValue($param, $value, getParamType($value)); // Named placeholder
                }
            }
        }
        $stmt->execute();
        // Return the number of rows deleted.
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Database error (DELETE) from {$table}: " . $e->getMessage());
        return false;
    }
}

/**
 * Executes a generic SQL query (e.g., CREATE, ALTER, complex queries).
 *
 * Prepares and executes a given SQL query with optional parameters.
 * Useful for queries that don't fit neatly into SELECT, INSERT, UPDATE, DELETE helpers,
 * or when you need direct access to the PDOStatement object (e.g., for rowCount on non-SELECT).
 * Use with caution, especially for DDL statements.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @param string $query The SQL query string. Can contain placeholders.
 * @param array $params An array of parameters to bind to the query placeholders.
 * @return PDOStatement|false The PDOStatement object on successful execution,
 *                            or false if the query preparation or execution fails.
 */
function db_query(PDO $conn, string $query, array $params = []): PDOStatement|false
{
    try {
        $stmt = $conn->prepare($query);
        // Bind parameters if provided.
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                if (is_int($param)) {
                    $stmt->bindValue($param + 1, $value, getParamType($value)); // 1-based index
                } else {
                    $stmt->bindValue($param, $value, getParamType($value)); // Named placeholder
                }
            }
        }
        $stmt->execute();
        // Return the statement object for potential further processing (e.g., fetching results if it was a SELECT).
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error (QUERY): " . $e->getMessage() . " Query: " . $query);
        return false;
    }
}

/**
 * Determines the PDO::PARAM_* constant type for a given value.
 *
 * Used internally by the helper functions to correctly bind parameters in prepared statements.
 *
 * @param mixed $value The value whose PDO parameter type needs to be determined.
 * @return int The corresponding PDO::PARAM_* constant (PARAM_INT, PARAM_BOOL, PARAM_NULL, PARAM_STR).
 */
function getParamType(mixed $value): int
{
    if (is_int($value)) {
        return PDO::PARAM_INT;
    } elseif (is_bool($value)) {
        return PDO::PARAM_BOOL;
    } elseif (is_null($value)) {
        return PDO::PARAM_NULL;
    } else {
        // Default to string for everything else (including floats, resources etc.)
        // PDO handles string conversion appropriately in most cases.
        return PDO::PARAM_STR;
    }
}

/**
 * Establishes a new PDO database connection.
 *
 * Configures the connection with error mode set to exceptions, default fetch mode
 * to associative arrays, and disables emulated prepares for better security and performance.
 *
 * @param string $host The database host address (e.g., 'localhost', '127.0.0.1').
 * @param string $db_name The name of the database.
 * @param string $username The database username.
 * @param string $password The database password.
 * @return PDO|false A PDO connection object on success, or false on failure.
 */
function db_connect(string $host, string $db_name, string $username, string $password): PDO|false
{
    try {
        $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4"; // Use utf8mb4 for full unicode support
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,         // Throw exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,    // Fetch results as associative arrays
            PDO::ATTR_EMULATE_PREPARES => false,                 // Use native prepared statements
            PDO::MYSQL_ATTR_FOUND_ROWS => true                   // Make rowCount() reliable for UPDATEs
        ];
        $conn = new PDO($dsn, $username, $password, $options);
        return $conn;
    } catch (PDOException $e) {
        // Log connection errors, avoid exposing details in production environments.
        error_log("Database connection error: " . $e->getMessage());
        // Depending on the application context, you might want to throw an exception
        // or display a user-friendly error message instead of returning false.
        return false;
    }
}

/**
 * Sanitizes a potential database identifier (table or column name).
 *
 * Removes characters that are not alphanumeric or underscore.
 * WARNING: This is a basic measure and might not be sufficient for all cases.
 * It's generally better to avoid dynamic table/column names or use a strict whitelist.
 * This function should NOT be used to sanitize user input intended as data values.
 *
 * @param string $identifier The potential identifier string.
 * @return string The sanitized identifier.
 */
function sanitize_identifier(string $identifier): string
{
    // Remove any character that is not a letter, number, or underscore.
    // Allows identifiers like `my_table`, `column1`, etc.
    // Does NOT handle backticks or more complex quoting needed for reserved words.
    return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
}

/**
 * Begins a database transaction.
 *
 * Turns off autocommit mode. Changes made after this call will not be permanent
 * until db_commit() is called.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @return bool True on success, false on failure.
 */
function db_begin_transaction(PDO $conn): bool
{
    try {
        return $conn->beginTransaction();
    } catch (PDOException $e) {
        error_log("Transaction error (BEGIN): " . $e->getMessage());
        return false;
    }
}

/**
 * Commits the current database transaction.
 *
 * Makes all changes made since the corresponding db_begin_transaction() call permanent.
 * Restores autocommit mode.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @return bool True on success, false on failure.
 */
function db_commit(PDO $conn): bool
{
    try {
        // Check if a transaction is actually active before committing
        if ($conn->inTransaction()) {
            return $conn->commit();
        }
        // Optionally log or return false if commit is called without active transaction
        error_log("Transaction warning (COMMIT): No active transaction to commit.");
        return false; // Or true, depending on desired behavior
    } catch (PDOException $e) {
        error_log("Transaction error (COMMIT): " . $e->getMessage());
        // Attempt to rollback if commit failed within a transaction
        if ($conn->inTransaction()) {
            try {
                $conn->rollBack();
                error_log("Transaction error (COMMIT): Rollback attempted after commit failure.");
            } catch (PDOException $re) {
                error_log("Transaction error (COMMIT): Rollback failed after commit failure: " . $re->getMessage());
            }
        }
        return false;
    }
}

/**
 * Rolls back the current database transaction.
 *
 * Discards all changes made since the corresponding db_begin_transaction() call.
 * Restores autocommit mode.
 *
 * @param PDO $conn The active PDO database connection instance.
 * @return bool True on success, false on failure or if no transaction is active.
 */
function db_rollback(PDO $conn): bool
{
    try {
        // Check if a transaction is actually active before rolling back
        if ($conn->inTransaction()) {
            return $conn->rollBack();
        }
        error_log("Transaction warning (ROLLBACK): No active transaction to roll back.");
        return false; // Cannot rollback if not in transaction
    } catch (PDOException $e) {
        error_log("Transaction error (ROLLBACK): " . $e->getMessage());
        return false;
    }
}
