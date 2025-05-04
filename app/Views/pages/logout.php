<?php

/**
 * Logout Script
 *
 * This script handles the user logout process. It performs the following actions:
 * 1. Starts or resumes the existing session.
 * 2. Clears all session variables by resetting the $_SESSION array.
 * 3. If session cookies are used, it invalidates the session cookie by setting
 *    its expiration time to the past. This helps ensure the cookie is removed
 *    by the browser.
 * 4. Destroys the session data on the server.
 * 5. Redirects the user to the homepage ('index.php').
 * 6. Exits the script immediately to prevent further execution.
 *
 * Note: This is a procedural script and does not render any HTML content.
 * It directly handles the logout logic and redirection.
 */

// Start the session to access session variables and functions.
session_start();

// Unset all session variables.
$_SESSION = array();

// If session cookies are being used, delete the session cookie.
// This is an extra step for security and thoroughness.
if (ini_get("session.use_cookies")) {
    // Get current cookie parameters.
    $params = session_get_cookie_params();
    // Set the session cookie with an expiration time in the past.
    setcookie(
        session_name(), // Get the session name (e.g., PHPSESSID).
        '',             // Set value to empty.
        time() - 42000, // Set expiration time to the past (1 hour ago).
        $params["path"],   // Use the same path as the original cookie.
        $params["domain"], // Use the same domain.
        $params["secure"], // Use the same secure flag.
        $params["httponly"] // Use the same httponly flag.
    );
}

// Finally, destroy the session data on the server.
session_destroy();

// Redirect the user to the homepage after logout.
// Note: Assumes index.php is the intended homepage in the same directory or accessible path.
// Consider using a full URL or a base URL constant for better portability.
header("Location: index.php");

// Terminate script execution immediately after redirection header.
exit();