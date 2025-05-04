<?php

namespace App\Core;

use App\Core\Registry; // Import Registry for accessing shared services like the logger.

/**
 * Class Redirect
 *
 * Provides static utility methods for handling HTTP redirects.
 * Simplifies redirecting to specific URLs or back to the previous page.
 *
 * @package App\Core
 */
class Redirect
{
    /**
     * Redirects the user to a specified URL.
     *
     * Sends a 'Location' header to the browser to initiate the redirect.
     * It validates the URL format to ensure it's an absolute path, a protocol-relative URL,
     * or a full HTTP/HTTPS URL before redirecting. If the format is invalid, it logs a warning
     * and displays an error message. It also cleans any existing output buffers before sending headers.
     *
     * @param string $url The URL to redirect to. Should start with '/', '//', 'http:', or 'https:'.
     * @param int $statusCode The HTTP status code to use for the redirect (default: 302 Found).
     *                        Common alternatives include 301 (Moved Permanently).
     * @return void This method terminates script execution using exit() after sending the header.
     */
    public static function to(string $url, int $statusCode = 302): void
    {
        // Validate URL format: must be absolute path, protocol-relative, or full URL.
        if (strpos($url, '//') === 0 || strpos($url, 'http:') === 0 || strpos($url, 'https:') === 0 || strpos($url, '/') === 0) {
            // Clean any active output buffers to prevent header errors.
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            // Send the Location header with the specified URL and status code.
            header('Location: ' . $url, true, $statusCode);
            // Terminate script execution immediately after sending the header.
            exit();
        } else {
            // Log a warning if the URL format is invalid.
            if (Registry::has('logger')) {
                Registry::get('logger')->warning("Invalid redirect URL format provided.", ['url' => $url]);
            }
            // Display an error message and terminate execution.
            echo "Error: Invalid redirect URL specified.";
            exit();
        }
    }

    /**
     * Redirects the user back to the referring page or a fallback URL.
     *
     * Uses the 'HTTP_REFERER' server variable to determine the previous page.
     * If the 'HTTP_REFERER' is not set (e.g., direct access, privacy settings),
     * it redirects to the provided fallback URL.
     *
     * @param string $fallbackUrl The URL to redirect to if the HTTP_REFERER is not available (default: '/').
     * @param int $statusCode The HTTP status code for the redirect (default: 302 Found).
     * @return void This method terminates script execution via the `to()` method.
     */
    public static function back(string $fallbackUrl = '/', int $statusCode = 302): void
    {
        // Determine the referring URL, using the fallback if HTTP_REFERER is not set.
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallbackUrl;
        // Use the 'to' method to perform the actual redirect.
        self::to($referer, $statusCode);
    }
}
