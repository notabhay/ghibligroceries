<?php

/**
 * Application Configuration File
 *
 * This file defines core application settings, database credentials,
 * security parameters, and other global configurations.
 * It returns an associative array of configuration values.
 */

// Set the default timezone for date/time functions.
date_default_timezone_set('Europe/London');

// --- Debugging and Error Reporting ---

/**
 * @var bool $debugMode Enable or disable debug mode.
 *                     When true, more detailed errors are shown. Set to false in production.
 */
$debugMode = false;

/**
 * @var int $errorLevel The level of error reporting based on debug mode.
 *                    E_ALL shows all errors, 0 suppresses errors (for production).
 */
$errorLevel = $debugMode ? E_ALL : 0;

/**
 * @var int $displayErrors Whether to display errors directly in the output.
 *                       1 displays errors, 0 hides them (for production).
 */
$displayErrors = $debugMode ? 1 : 0;

// Apply the error reporting settings.
error_reporting($errorLevel);
// Original setting based on debug mode
ini_set('display_errors', $displayErrors);

// Return the main configuration array.
return [

    // --- Site Information ---
    'SITE_NAME' => 'GhibliGroceries', // The name of the website.
    'SITE_URL' => 'http://localhost/', // The base URL of the website. Include trailing slash.
    'ADMIN_EMAIL' => 'admin@ghibligroceries.com', // Email address for administrative notifications.

    // --- Database Configuration ---
    'DB_HOST' => 'localhost', // Database host address (e.g., 'localhost' or IP address).
    'DB_NAME' => 'ghibligroceriesdb', // Name of the database to connect to.
    'DB_USER' => 'ghibli_app_user', // Database username for the application.
    'DB_PASS' => 'YOUR_STRONG_PASSWORD_HERE', // Database password for the application user. **CHANGE THIS!**

    // --- Security and Authentication ---
    'AUTH_TIMEOUT' => 3600, // Session timeout in seconds for authenticated users (1 hour).
    'MAX_LOGIN_ATTEMPTS' => 5, // Maximum number of failed login attempts before lockout.
    'LOCKOUT_TIME' => 900, // Lockout duration in seconds after exceeding max login attempts (15 minutes).
    'CSRF_EXPIRY' => 3600, // Expiry time for CSRF tokens in seconds (1 hour).
    'PASSWORD_COST' => 12, // Cost factor for password hashing (higher is more secure but slower).

    // --- API Configuration ---
    'API_TOKEN_EXPIRY' => 86400, // Expiry time for API tokens in seconds (24 hours).
    'API_RATE_LIMIT' => 100, // Maximum number of API requests allowed per minute (example).
    'API_VERSION' => '1.0', // Current version of the API.
    'API_BASE_PATH' => '/api', // Base path for all API routes.

    // --- Application Settings ---
    'ITEMS_PER_PAGE' => 10, // Default number of items to display per page in listings.
    'MAX_FILE_SIZE' => 5 * 1024 * 1024, // Maximum allowed file upload size in bytes (5MB).
    'ALLOWED_EXTENSIONS' => ['jpg', 'jpeg', 'png', 'gif'], // Allowed file extensions for uploads.
    'UPLOAD_DIR' => __DIR__ . '/../public/assets/uploads/products/', // Directory for storing uploaded product images.

    // --- Debug Mode Flag ---
    'DEBUG_MODE' => $debugMode, // Expose the debug mode status to the application.

];