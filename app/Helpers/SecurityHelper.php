<?php

namespace App\Helpers;

/**
 * Class SecurityHelper
 *
 * Provides static utility methods for common security-related tasks such as
 * input sanitization, output encoding, data validation (email, phone, name, password),
 * secure token generation, setting HTTP security headers (CSP, X-Frame-Options, etc.),
 * and logging security events.
 */
class SecurityHelper
{
    /**
     * Sanitizes input data to prevent XSS attacks.
     *
     * Removes leading/trailing whitespace and converts special HTML characters
     * (like <, >, ", ', &) into their corresponding HTML entities.
     * Should be used on any data received from users before storing or processing it,
     * but typically *not* before displaying it (use encodeOutput for display).
     *
     * @param string|null $data The input string to sanitize. Handles null by returning an empty string.
     * @return string The sanitized string.
     */
    public static function sanitizeInput(?string $data): string
    {
        if ($data === null) {
            return ''; // Return empty string for null input
        }
        // Remove leading/trailing whitespace
        $data = trim($data);
        // Convert special characters to HTML entities to prevent XSS
        // ENT_QUOTES converts both double and single quotes.
        // ENT_HTML5 ensures compatibility with HTML5 entities.
        // 'UTF-8' specifies the character encoding.
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $data;
    }

    /**
     * Encodes output data for safe display in HTML context.
     *
     * Converts all applicable characters to HTML entities, providing stronger
     * protection against XSS than htmlspecialchars when displaying user-generated content.
     * Should be used right before outputting data within HTML tags or attributes.
     *
     * @param string|null $data The string to encode for HTML output. Handles null by returning an empty string.
     * @return string The HTML-encoded string.
     */
    public static function encodeOutput(?string $data): string
    {
        if ($data === null) {
            return ''; // Return empty string for null input
        }
        // Convert all applicable characters to HTML entities.
        // Provides more comprehensive encoding than htmlspecialchars for output.
        return htmlentities($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validates an email address format.
     *
     * Uses PHP's built-in filter_var function for robust email validation.
     *
     * @param string $email The email address to validate.
     * @return bool True if the email format is valid, false otherwise.
     */
    public static function validateEmail(string $email): bool
    {
        // filter_var with FILTER_VALIDATE_EMAIL is the recommended way to validate emails in PHP.
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validates a basic 10-digit phone number format (North American style assumed).
     *
     * Removes common formatting characters (spaces, dashes, parentheses)
     * and checks if the result is exactly 10 digits.
     * Note: This is a very basic validation and might need adjustment for international numbers.
     *
     * @param string $phone The phone number string to validate.
     * @return bool True if the phone number matches the 10-digit format after cleaning, false otherwise.
     */
    public static function validatePhone(string $phone): bool
    {
        // Remove spaces, hyphens, parentheses.
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);
        // Check if the cleaned string consists of exactly 10 digits.
        return preg_match('/^\d{10}$/', $phone) === 1;
    }

    /**
     * Validates a name string.
     *
     * Allows letters (including Unicode letters), spaces, apostrophes, and hyphens.
     * Uses the 'u' modifier for Unicode support.
     *
     * @param string $name The name string to validate.
     * @return bool True if the name contains only allowed characters, false otherwise.
     */
    public static function validateName(string $name): bool
    {
        // Regex breakdown:
        // ^                  - Start of string
        // [\p{L}\s'\-]+     - Match one or more characters that are:
        //   \p{L}            - Any Unicode letter
        //   \s               - Whitespace
        //   '                - Apostrophe
        //   -                - Hyphen
        // +                  - One or more times
        // $                  - End of string
        // u                  - Unicode modifier (essential for \p{L})
        return preg_match('/^[\p{L}\s\'\-]+$/u', $name) === 1;
    }

    /**
     * Validates password complexity (basic length check).
     *
     * Checks if the password meets a minimum length requirement.
     * Note: This is a very basic check. Real-world applications should enforce
     * more complex rules (e.g., requiring uppercase, lowercase, numbers, symbols).
     *
     * @param string $password The password string to validate.
     * @return bool True if the password meets the minimum length requirement (8 characters), false otherwise.
     */
    public static function validatePassword(string $password): bool
    {
        // Check if the password length is at least 8 characters.
        // mb_strlen is used for multi-byte character support.
        if (mb_strlen($password) < 8) {
            return false;
        }
        // Add more checks here for complexity if needed (e.g., regex for character types).
        return true;
    }

    /**
     * Generates a cryptographically secure random token string.
     *
     * Uses random_bytes() for secure random data generation and bin2hex() for conversion
     * to a hexadecimal string representation.
     *
     * @param int $length The desired length of the hexadecimal token string. Must be an even number. Defaults to 64.
     * @return string The generated random token string (hexadecimal).
     * @throws \InvalidArgumentException If the requested length is not an even number.
     * @throws \Exception If random_bytes() fails to gather sufficient randomness.
     */
    public static function generateToken(int $length = 64): string
    {
        // Ensure the length is even because bin2hex doubles the byte length.
        if ($length % 2 !== 0) {
            throw new \InvalidArgumentException("Token length must be an even number.");
        }
        // Calculate the number of raw bytes needed (half the desired hex length).
        $byteLength = $length / 2;
        // Generate cryptographically secure pseudo-random bytes.
        $randomBytes = random_bytes($byteLength);
        // Convert the raw bytes to a hexadecimal string.
        return bin2hex($randomBytes);
    }

    /**
     * Sets the X-Frame-Options header to DENY to prevent clickjacking.
     *
     * Clickjacking is an attack where a user is tricked into clicking something
     * different from what they perceive, often by overlaying a malicious page
     * in an invisible iframe over the legitimate site. DENY prevents the page
     * from being loaded in any frame or iframe.
     *
     * @return void
     */
    public static function preventClickjacking(): void
    {
        // Check if headers have already been sent to avoid errors.
        if (!headers_sent()) {
            // DENY: The page cannot be displayed in a frame, regardless of the site attempting to do so.
            // Other options: SAMEORIGIN (allow framing only by the same site).
            header('X-Frame-Options: DENY');
        }
    }

    /**
     * Sets multiple common HTTP security headers.
     *
     * Includes X-Frame-Options, X-Content-Type-Options, X-XSS-Protection,
     * Content-Security-Policy (CSP), and Referrer-Policy.
     * Starts the session if not already started, as some headers might relate to session security.
     *
     * @return void
     */
    public static function setSecurityHeaders(): void
    {
        // Don't try to set headers if they've already been sent.
        if (headers_sent()) {
            return;
        }

        // Ensure session is started if needed (e.g., for session-related security measures, though none here directly depend on it).
        if (session_status() === PHP_SESSION_NONE) {
            // Suppress errors in case session is already started but status check failed somehow.
            @session_start();
        }

        // --- Set Individual Security Headers ---

        // Prevent Clickjacking (redundant if called separately, but good practice here).
        header('X-Frame-Options: DENY');

        // Prevent browsers from MIME-sniffing the content type away from the declared Content-Type.
        header('X-Content-Type-Options: nosniff');

        // Enable browser's built-in XSS filter (mode=block prevents rendering if attack detected).
        // Note: Modern browsers often rely more on CSP. This header provides an extra layer for older browsers.
        header('X-XSS-Protection: 1; mode=block');

        // Define Content Security Policy (CSP) to control resource loading.
        // This is a restrictive example; adjust based on application needs.
        $csp = "default-src 'self'; "; // Default: Allow only from the same origin.
        // Allow scripts from self, specific CDNs, and inline scripts (use nonce/hash in production instead of 'unsafe-inline').
        $csp .= "script-src 'self' https://cdnjs.cloudflare.com https://unpkg.com 'unsafe-inline'; ";
        // Allow styles from self, specific CDNs, and inline styles (use nonce/hash in production instead of 'unsafe-inline').
        $csp .= "style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; ";
        // Allow images from self and data URIs (e.g., for inline SVGs or base64 images).
        $csp .= "img-src 'self' data:; ";
        // Allow fonts from self and specific CDNs.
        $csp .= "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; ";
        // Allow connections (XHR, WebSockets) only to self.
        $csp .= "connect-src 'self'; ";
        // Allow form submissions only to self.
        $csp .= "form-action 'self'; ";
        // Disallow framing of the page by any other page (stronger than X-Frame-Options).
        $csp .= "frame-ancestors 'none'; ";
        // Specify base URI to prevent attacks that change document URIs.
        $csp .= "base-uri 'self'; ";
        // Disallow embedding plugins (<object>, <embed>).
        $csp .= "object-src 'none';";
        header("Content-Security-Policy: " . $csp);

        // Control how much referrer information is sent with requests.
        // 'strict-origin-when-cross-origin': Send full URL for same-origin, only origin for cross-origin HTTPS->HTTPS, no referrer for HTTPS->HTTP.
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Logs a security-related event to a dedicated security log file.
     *
     * Records the timestamp, event type, IP address, a descriptive message,
     * and optional context data (as JSON).
     *
     * @param string $eventType A category for the event (e.g., 'AUTH_FAIL', 'CSRF_FAIL', 'INPUT_VALIDATION').
     * @param string $message A description of the security event.
     * @param array $context Optional additional data related to the event (e.g., user ID, submitted data).
     * @return void
     */
    public static function logSecurityEvent(string $eventType, string $message, array $context = []): void
    {
        // Define the log directory path relative to the project base path.
        // Assumes BASE_PATH constant is defined (e.g., in a bootstrap file).
        $logDirectory = defined('BASE_PATH') ? BASE_PATH . '/logs' : __DIR__ . '/../../logs'; // Fallback path
        $logFile = $logDirectory . '/security.log';

        // Create the log directory if it doesn't exist.
        if (!is_dir($logDirectory)) {
            // Attempt to create directory recursively with appropriate permissions.
            // Use error suppression (@) in case of race conditions or permission issues.
            @mkdir($logDirectory, 0755, true);
        }

        // Get current timestamp and user's IP address.
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'; // Use 'UNKNOWN' if IP is not available.

        // Format the main log message.
        $log_message = "[{$timestamp}] [{$eventType}] [IP: {$ip}] {$message}";

        // Append context data if provided.
        if (!empty($context)) {
            // Encode context array as JSON for structured logging.
            // JSON_UNESCAPED_SLASHES prevents escaping forward slashes.
            $log_message .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        // Append the log message to the security log file.
        // Use error_log with type 3 (append to file).
        // Use error suppression (@) to prevent warnings/errors if logging fails (e.g., file permissions).
        @error_log($log_message . PHP_EOL, 3, $logFile);
    }

    /**
     * Generates a simple random string of a specified length.
     *
     * Uses alphanumeric characters (0-9, a-z, A-Z).
     * Note: This is generally NOT suitable for security-sensitive purposes like passwords or tokens.
     * Use `generateToken()` for secure random strings. This might be used for non-critical
     * identifiers like temporary filenames or simple unique IDs where collision resistance
     * and unpredictability are less critical.
     *
     * @param int $length The desired length of the random string. Defaults to 10.
     * @return string The generated random string.
     * @throws \Exception If random_int() fails to gather sufficient randomness.
     */
    public static function generateRandomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            // Use random_int for better randomness than rand().
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
