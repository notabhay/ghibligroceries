<?php

/**
 * Front Controller / Application Entry Point
 *
 * This script serves as the single entry point for all HTTP requests
 * to the application. It initializes the core components, sets up
 * the environment, handles the request, and dispatches it to the
 * appropriate controller action via the router.
 */

// Define the base path of the application directory.
// dirname(__DIR__) gets the parent directory of the current file's directory (public -> project root).
define('BASE_PATH', dirname(__DIR__));

// Include the Composer autoloader.
// This makes all Composer-managed libraries and application classes (following PSR-4) available.
require_once BASE_PATH . '/vendor/autoload.php';

// --- Environment Variable Loading ---
// Load environment variables from .env file using phpdotenv
// This should be done before loading the application configuration.
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

// Load the application configuration.
// Contains settings like database credentials, debug mode, etc.
// Now it can access environment variables loaded by phpdotenv.
$config = require_once BASE_PATH . '/app/config.php';

// --- Dependency Imports ---
// Import necessary classes from the application's core, helpers, and external libraries.
use App\Core\Request;       // Handles incoming HTTP requests.
use App\Core\Router;        // Manages routing and dispatches requests.
use App\Core\Database;      // Handles database connections and queries.
use App\Core\Registry;      // A simple service container for dependency injection.
use App\Core\Session;       // Manages user sessions.
use App\Helpers\CaptchaHelper; // Helper for generating and validating CAPTCHAs.
use Monolog\Logger;         // PSR-3 compliant logging library.
use Monolog\Handler\StreamHandler; // Monolog handler to write logs to files.
use Monolog\Level;          // Defines logging levels (e.g., Debug, Warning, Error).
use App\Helpers\SecurityHelper; // Helper for setting security-related HTTP headers.

// --- Initial Setup & Security ---

// Apply essential security headers to HTTP responses.
// Helps mitigate common web vulnerabilities like XSS, clickjacking, etc.
SecurityHelper::setSecurityHeaders();

// Bind the loaded configuration array to the Registry.
// Makes configuration accessible throughout the application via Registry::get('config').
Registry::bind('config', $config);

// --- Core Services Initialization (Dependency Injection Setup) ---
// This block sets up essential services and binds them to the Registry.
// It's wrapped in a try-catch to handle critical initialization errors.
try {
    // --- Logger Setup ---
    // Define the path for the application log file.
    $logFilePath = BASE_PATH . '/logs/app.log';
    // Get the directory path for the log file.
    $logDir = dirname($logFilePath);
    // Create the log directory if it doesn't exist.
    if (!is_dir($logDir)) {
        // Recursively create the directory with appropriate permissions (0775).
        mkdir($logDir, 0775, true);
    }
    // Instantiate the Logger. 'app' is the channel name.
    $logger = new Logger('app');
    // Determine the minimum logging level based on the debug mode setting in config.
    // Log everything (Debug level) if DEBUG_MODE is true, otherwise log Warnings and above.
    $logLevel = ($config['DEBUG_MODE'] ?? false) ? Level::Debug : Level::Warning;
    // Add a handler to write log records to the specified file with the determined level.
    $logger->pushHandler(new StreamHandler($logFilePath, $logLevel));
    // Bind the logger instance to the Registry.
    Registry::bind('logger', $logger);

    // --- Database Setup ---
    // Instantiate the Database connection handler using credentials from the config.
    $db = new Database(
        $config['DB_HOST'],
        $config['DB_NAME'],
        $config['DB_USER'],
        $config['DB_PASS']
    );
    // Check if the database connection was successful.
    if ($db->getConnection()) {
        // Bind the database instance to the Registry if connection is successful.
        Registry::bind('database', $db);
    } else {
        // Handle database connection failure gracefully.
        $errorMessage = "Database Connection Error: Sorry, we couldn't connect to the database. Please try again later.";
        // Log the critical error using the logger if available.
        if (Registry::has('logger')) {
            Registry::get('logger')->critical("Database connection failed.", ['error' => $db->getError()]);
        } else {
            // Fallback to PHP's built-in error log if logger isn't ready.
            error_log("CRITICAL: Database connection failed. Error: " . $db->getError());
        }
        // Set a 503 Service Unavailable HTTP status code.
        http_response_code(503);
        // Display a user-friendly error message.
        echo "<h1>Database Connection Error</h1>";
        echo "<p>Sorry, we couldn't connect to the database. Please try again later.</p>";
        // Display detailed error information if debug mode is enabled.
        if ($config['DEBUG_MODE'] ?? false) {
            echo "<p>Error details: " . htmlspecialchars($db->getError()) . "</p>";
        }
        // Terminate script execution as the application cannot proceed without a database.
        exit;
    }

    // --- Session Setup ---
    // Prepare session configuration options.
    $sessionConfig = [
        // Set session inactivity timeout from config (default: 1800 seconds / 30 minutes).
        'session_timeout' => $config['AUTH_TIMEOUT'] ?? 1800,
    ];
    // Instantiate the Session manager with the configuration.
    $session = new Session($sessionConfig);
    // Bind the session instance to the Registry.
    Registry::bind('session', $session);

    // --- CAPTCHA Helper Setup ---
    // Instantiate the CaptchaHelper, passing the session instance for storing CAPTCHA codes.
    $captchaHelper = new CaptchaHelper($session);
    // Bind the CaptchaHelper instance to the Registry.
    Registry::bind('captchaHelper', $captchaHelper);

    // --- Request Object Setup ---
    // Instantiate the Request object, which encapsulates the current HTTP request data (URI, method, POST/GET data).
    $request = new Request();
    // Bind the Request instance to the Registry.
    Registry::bind('request', $request);
} catch (\Exception $e) {
    // --- Bootstrap Error Handling ---
    // Catch any exceptions that occur during the core service initialization phase.
    $errorMessage = "Bootstrap Error (DI Setup): " . $e->getMessage();
    // Log the critical error using the logger if available.
    if (Registry::has('logger')) {
        Registry::get('logger')->critical($errorMessage, ['exception' => $e]);
    } else {
        // Fallback to PHP's built-in error log.
        error_log("CRITICAL: " . $errorMessage);
    }
    // Set a 500 Internal Server Error HTTP status code.
    http_response_code(500);
    // Display a generic error message to the user.
    echo "<h1>Application Initialization Error</h1>";
    echo "<p>An error occurred during application setup. Please try again later.</p>";
    // Display detailed error information if debug mode is enabled.
    if ($config['DEBUG_MODE'] ?? false) {
        echo "<p>Error details: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    // Terminate script execution as a critical component failed to initialize.
    exit;
}

// --- Session Start & Validation ---

// Explicitly start or resume the session.
// This must happen after core services (like the logger) are potentially available.
if (!$session->start()) {
    // Log a critical error if the session fails to start.
    Registry::get('logger')->critical("Failed to start session.");
    // Set a 500 Internal Server Error HTTP status code.
    http_response_code(500);
    // Display an error message.
    echo "<h1>Session Error</h1><p>Could not start session. Please check server configuration.</p>";
    // Terminate script execution.
    exit;
}

// Validate the user's session activity (e.g., check for timeout).
// This might regenerate the session ID or log the user out if inactive for too long.
$session->validateActivity();

// --- Routing and Request Dispatching ---
// This block handles the incoming request by routing it to the correct controller action.
try {
    // Load the defined application routes from the routes file.
    // Then, direct the router to find a match for the current request URI and method.
    // The router will instantiate the controller and call the appropriate method.
    Router::load(BASE_PATH . '/app/routes.php')
        ->direct($request->uri(), $request->method());
} catch (\Exception $e) {
    // --- General Exception Handling ---
    // Catch any unhandled exceptions that occur during routing or controller execution.
    $logger = Registry::get('logger'); // Get the logger instance.
    // Log the error with exception details.
    $logger->error("Unhandled Exception: " . $e->getMessage(), ['exception' => $e]);

    // Determine the appropriate HTTP status code.
    // Use 404 for routing exceptions (often thrown by the router itself), otherwise default to 500.
    $statusCode = ($e->getCode() == 404) ? 404 : 500;
    // Set the HTTP response status code.
    http_response_code($statusCode);

    // Display error details based on debug mode.
    if ($config['DEBUG_MODE'] ?? false) {
        // Show detailed error information including stack trace in debug mode.
        echo '<h1>Error (' . $statusCode . ')</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Show a generic error page in production mode.
        $displayMessage = '';
        // Check if there's a specific error flash message set in the session.
        if (Registry::has('session')) {
            $session = Registry::get('session');
            if ($session->hasFlash('error')) {
                // Retrieve and clear the flash message.
                $displayMessage = $session->getFlash('error');
            }
        }

        // Display appropriate message based on the status code.
        if ($statusCode == 404) {
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>Sorry, the page you are looking for could not be found.</p>';
        } else {
            echo '<h1>An Error Occurred</h1>';
            echo '<p>We are sorry, something went wrong. Please try again later.</p>';
            // If a specific flash message exists, display it.
            if (!empty($displayMessage)) {
                echo '<p>Details: ' . htmlspecialchars($displayMessage) . '</p>';
            }
        }
    }
    // Note: Script execution implicitly ends here after handling the exception.
}