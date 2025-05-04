<?php

namespace App\Core;

// Import Monolog classes for logging functionality.
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel; // Import PSR LogLevel constants.
use Exception; // Import base Exception class.

/**
 * Class Registry
 *
 * A simple static service container or registry implementation.
 * Allows binding (registering) and retrieving shared application services
 * (like database connections, loggers, configuration) using string keys.
 * This avoids the need for global variables and provides a central place
 * to manage shared instances.
 *
 * @package App\Core
 */
class Registry
{
    /**
     * @var array Holds the registered services, keyed by their string identifiers.
     */
    private static $services = [];

    /**
     * Binds (registers) a service instance or value with a specific key.
     *
     * By default, it prevents overwriting an existing key unless explicitly allowed.
     *
     * @param string $key The unique string identifier for the service.
     * @param mixed $value The service instance or value to store (e.g., an object, array, scalar).
     * @param bool $overwrite If true, allows overwriting an existing service bound to the same key. Defaults to false.
     * @throws Exception If the key is already bound and overwrite is false.
     * @return void
     */
    public static function bind(string $key, $value, bool $overwrite = false): void
    {
        // Check if the key exists and overwriting is not allowed.
        if (!$overwrite && static::has($key)) {
            throw new Exception("Key '{$key}' is already bound in the registry.");
        }
        // Store the service in the static array.
        static::$services[$key] = $value;
    }

    /**
     * Retrieves a service instance bound to the specified key.
     *
     * @param string $key The unique string identifier of the service to retrieve.
     * @throws Exception If no service is bound to the given key.
     * @return mixed The service instance or value associated with the key.
     */
    public static function get(string $key)
    {
        // Check if the service exists in the registry.
        if (!static::has($key)) {
            throw new Exception("No service bound for key '{$key}' in the registry.");
        }
        // Return the stored service.
        return static::$services[$key];
    }

    /**
     * Checks if a service is bound to the specified key.
     *
     * @param string $key The unique string identifier to check.
     * @return bool True if a service is bound to the key, false otherwise.
     */
    public static function has(string $key): bool
    {
        // Use isset for efficient checking.
        return isset(static::$services[$key]);
    }

    /**
     * Removes (unbinds) a service from the registry.
     *
     * @param string $key The unique string identifier of the service to remove.
     * @return void
     */
    public static function remove(string $key): void
    {
        // Use unset to remove the key and its associated value.
        unset(static::$services[$key]);
    }

    /**
     * Removes all services from the registry.
     *
     * Useful for resetting state, particularly during testing.
     *
     * @return void
     */
    public static function flush(): void
    {
        // Reset the services array to an empty array.
        static::$services = [];
    }

    /**
     * Initializes and binds a Monolog logger instance to the registry.
     *
     * Creates a logger that writes to a specified file path with a configured format.
     * Automatically creates the log directory if it doesn't exist.
     * Binds the logger instance with the key 'logger'.
     *
     * @param string $logFilePath The absolute path to the log file.
     * @param string $loggerName The name of the logger channel (e.g., 'app', 'database'). Defaults to 'app'.
     * @param int $logLevel The minimum log level to record (e.g., LogLevel::DEBUG, LogLevel::WARNING). Defaults to DEBUG.
     * @throws Exception If the log directory cannot be created or logger initialization fails.
     * @return void
     */
    public static function initializeLogger(string $logFilePath, string $loggerName = 'app', int $logLevel = LogLevel::DEBUG): void
    {
        try {
            // Get the directory path from the log file path.
            $logDir = dirname($logFilePath);
            // Check if the directory exists, and attempt to create it recursively if not.
            if (!is_dir($logDir) && !mkdir($logDir, 0775, true)) {
                // Throw an exception if directory creation fails.
                throw new Exception("Failed to create log directory: {$logDir}");
            }

            // Create a new Monolog Logger instance.
            $logger = new Logger($loggerName);

            // Create a stream handler to write logs to the specified file.
            $handler = new StreamHandler($logFilePath, $logLevel);

            // Define the output format for log entries.
            $outputFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
            // Define the date format for log timestamps.
            $dateFormat = "Y-m-d H:i:s";

            // Create a line formatter with the defined formats.
            // allowInlineLineBreaks = true, ignoreEmptyContextAndExtra = true
            $formatter = new LineFormatter($outputFormat, $dateFormat, true, true);

            // Set the formatter for the handler.
            $handler->setFormatter($formatter);

            // Push the configured handler to the logger instance.
            $logger->pushHandler($handler);

            // Bind the created logger instance to the registry with the key 'logger', allowing overwrite.
            static::bind('logger', $logger, true);
        } catch (Exception $e) {
            // Wrap any exception during logger setup in a more specific exception.
            throw new Exception("Failed to initialize logger: " . $e->getMessage(), 0, $e);
        }
    }
}
