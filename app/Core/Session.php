<?php

namespace App\Core;

use App\Core\Registry; // Import Registry for accessing shared services like the logger.

/**
 * Class Session
 *
 * Manages PHP sessions with enhanced security features and convenience methods.
 * Handles session starting, data storage (get/set/has/remove), session ID regeneration,
 * secure destruction, flash messages, CSRF token generation/validation,
 * user authentication state (login/logout/isAuthenticated), and activity validation
 * (timeout, IP address check, periodic regeneration).
 *
 * Configuration options allow customizing cookie parameters, timeouts, and security checks.
 *
 * @package App\Core
 */
class Session
{
    /**
     * @var array Default configuration settings for the session handler.
     *            These can be overridden during instantiation.
     */
    private $config = [
        // Standard PHP session cookie parameters:
        'cookie_lifetime' => 0, // 0 = until browser closes. Set to a positive value for persistent cookies (in seconds).
        'cookie_path' => '/', // Path on the server where the cookie will be available. '/' means the entire domain.
        'cookie_domain' => '', // Cookie domain. Empty means the host name of the server. Set to '.yourdomain.com' for subdomains.
        'cookie_secure' => false, // Send cookie only over HTTPS? Automatically set based on current request.
        'cookie_httponly' => true, // Make cookie inaccessible to JavaScript? (Helps prevent XSS). Highly recommended.
        'cookie_samesite' => 'Lax', // SameSite attribute ('Lax', 'Strict', 'None'). Helps prevent CSRF. 'Lax' is a good default.

        // Custom session management settings:
        'session_timeout' => 1800, // Inactivity timeout in seconds (30 minutes). User is logged out after this period of inactivity.
        'regenerate_interval' => 300, // How often to regenerate the session ID in seconds (5 minutes). Helps prevent session fixation.
        'check_ip_address' => true, // Validate user's IP address against the one stored in session? Helps prevent session hijacking.

        // Keys used for storing specific session data:
        'csrf_token_key' => '_csrf_token', // Session key for the CSRF token.
        'flash_message_key' => '_flash', // Session key for storing flash messages.
        'user_id_key' => 'user_id', // Session key for storing the logged-in user's ID.
        'login_time_key' => 'login_time', // Session key for storing the last activity timestamp.
        'user_ip_key' => 'user_ip', // Session key for storing the user's IP address (if check_ip_address is true).
    ];

    /**
     * Session constructor.
     *
     * Merges provided configuration with defaults and sets the secure cookie flag
     * based on whether the current request is HTTPS.
     *
     * @param array $config Optional configuration array to override default settings.
     */
    public function __construct(array $config = [])
    {
        // Merge provided config with defaults. Provided values overwrite defaults.
        $this->config = array_merge($this->config, $config);
        // Automatically set 'cookie_secure' based on HTTPS status.
        $this->config['cookie_secure'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        // Set cookie domain based on current host if not explicitly provided.
        if (empty($this->config['cookie_domain'])) {
            $this->config['cookie_domain'] = $_SERVER['HTTP_HOST'] ?? '';
        }
    }

    /**
     * Starts or resumes a session with configured settings.
     *
     * Sets session cookie parameters before starting the session.
     * Validates session activity after starting.
     *
     * @return bool True if the session was successfully started or was already active, false on failure.
     */
    public function start(): bool
    {
        // Don't start if already active.
        if ($this->isActive()) {
            return true;
        }

        // Set cookie parameters before starting the session.
        session_set_cookie_params([
            'lifetime' => $this->config['cookie_lifetime'],
            'path' => $this->config['cookie_path'],
            'domain' => $this->config['cookie_domain'],
            'secure' => $this->config['cookie_secure'],
            'httponly' => $this->config['cookie_httponly'],
            'samesite' => $this->config['cookie_samesite']
        ]);

        // Attempt to start the session.
        if (session_start()) {
            // Validate session activity (timeout, IP, regeneration) after starting.
            $this->validateActivity();
            return true;
        }

        // Log error if session start fails?
        // if (Registry::has('logger')) { Registry::get('logger')->error("Session failed to start."); }
        return false;
    }

    /**
     * Checks if a session is currently active.
     *
     * @return bool True if session_status() is PHP_SESSION_ACTIVE, false otherwise.
     */
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Sets a value in the session.
     *
     * @param string $key The key to store the value under.
     * @param mixed $value The value to store. Can be any serializable type.
     * @return void
     */
    public function set(string $key, $value): void
    {
        // Requires session to be started. Consider adding an isActive() check or ensuring start() is called.
        $_SESSION[$key] = $value;
    }

    /**
     * Gets a value from the session by key.
     *
     * @param string $key The key of the value to retrieve.
     * @param mixed $default The default value to return if the key is not found. Defaults to null.
     * @return mixed The stored value or the default value.
     */
    public function get(string $key, $default = null)
    {
        // Requires session to be started.
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a key exists in the session.
     *
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool
    {
        // Requires session to be started.
        return isset($_SESSION[$key]);
    }

    /**
     * Removes a key and its value from the session.
     *
     * @param string $key The key to remove.
     * @return void
     */
    public function remove(string $key): void
    {
        // Requires session to be started.
        unset($_SESSION[$key]);
    }

    /**
     * Regenerates the session ID.
     *
     * Helps prevent session fixation attacks. Updates the last regeneration timestamp.
     *
     * @param bool $deleteOldSession Whether to delete the old session file. Defaults to true.
     * @return void
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        if ($this->isActive()) {
            // Store the regeneration time before regenerating.
            $this->set('_last_regenerate', time());
            // Regenerate the session ID.
            session_regenerate_id($deleteOldSession);
        }
    }

    /**
     * Destroys the current session completely.
     *
     * Clears the $_SESSION array, deletes the session cookie, and destroys the session data on the server.
     *
     * @return void
     */
    public function destroy(): void
    {
        if ($this->isActive()) {
            // Clear all session variables.
            $_SESSION = [];

            // Delete the session cookie if used.
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), // Get the session name (e.g., PHPSESSID).
                    '', // Empty value.
                    time() - 42000, // Set expiration date in the past.
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Destroy the session data on the server.
            session_destroy();
        }
    }

    /**
     * Sets a flash message that persists only for the next request.
     *
     * Useful for displaying status messages after redirects (e.g., "Item saved successfully").
     *
     * @param string $key The key for the flash message (e.g., 'success', 'error').
     * @param mixed $value The message or data to store.
     * @return void
     */
    public function flash(string $key, $value): void
    {
        $flashKey = $this->config['flash_message_key'];
        // Initialize the flash message array if it doesn't exist.
        if (!isset($_SESSION[$flashKey])) {
            $_SESSION[$flashKey] = [];
        }
        // Store the flash message.
        $_SESSION[$flashKey][$key] = $value;
    }

    /**
     * Retrieves a flash message by key and removes it from the session.
     *
     * @param string $key The key of the flash message to retrieve.
     * @param mixed $default The default value if the flash message doesn't exist. Defaults to null.
     * @return mixed The flash message value or the default value.
     */
    public function getFlash(string $key, $default = null)
    {
        $flashKey = $this->config['flash_message_key'];
        // Get the value, using default if not set.
        $value = $_SESSION[$flashKey][$key] ?? $default;
        // If the flash message existed, remove it.
        if (isset($_SESSION[$flashKey][$key])) {
            unset($_SESSION[$flashKey][$key]);
            // Remove the parent flash array if it's now empty.
            if (empty($_SESSION[$flashKey])) {
                unset($_SESSION[$flashKey]);
            }
        }
        return $value;
    }

    /**
     * Checks if a flash message exists for the given key.
     *
     * Does not remove the message.
     *
     * @param string $key The key to check.
     * @return bool True if a flash message exists for the key, false otherwise.
     */
    public function hasFlash(string $key): bool
    {
        $flashKey = $this->config['flash_message_key'];
        return isset($_SESSION[$flashKey][$key]);
    }

    /**
     * Generates a new CSRF (Cross-Site Request Forgery) token and stores it in the session.
     *
     * Uses cryptographically secure random bytes.
     *
     * @return string The generated CSRF token (hexadecimal representation).
     */
    public function generateCsrfToken(): string
    {
        // Generate 32 random bytes and convert to hex (64 characters).
        $token = bin2hex(random_bytes(32));
        // Store the token in the session using the configured key.
        $this->set($this->config['csrf_token_key'], $token);
        return $token;
    }

    /**
     * Retrieves the current CSRF token from the session.
     *
     * If no token exists, it generates a new one.
     *
     * @return string The CSRF token.
     */
    public function getCsrfToken(): string
    {
        $key = $this->config['csrf_token_key'];
        // Generate a new token if one doesn't exist in the session.
        if (!$this->has($key)) {
            return $this->generateCsrfToken();
        }
        // Return the existing token.
        return $this->get($key);
    }

    /**
     * Validates a submitted CSRF token against the one stored in the session.
     *
     * Uses hash_equals() for timing-attack-safe comparison.
     *
     * @param string|null $submittedToken The CSRF token received from the user's request (e.g., from a form).
     * @return bool True if the submitted token is valid and matches the session token, false otherwise.
     */
    public function validateCsrfToken(?string $submittedToken): bool
    {
        $sessionToken = $this->get($this->config['csrf_token_key']);

        // Fail if either the submitted token or the session token is missing.
        if (!$submittedToken || !$sessionToken) {
            return false;
        }

        // Use hash_equals for secure comparison to prevent timing attacks.
        return hash_equals($sessionToken, $submittedToken);
    }

    /**
     * Alias for validateCsrfToken.
     *
     * @param string|null $submittedToken The submitted CSRF token.
     * @return bool True if valid, false otherwise.
     * @deprecated Prefer using validateCsrfToken for clarity.
     */
    public function verifyCsrfToken(?string $submittedToken): bool
    {
        return $this->validateCsrfToken($submittedToken);
    }


    /**
     * Logs in a user by storing their ID and relevant metadata in the session.
     *
     * Regenerates the session ID for security, stores user ID, login time,
     * and optionally the user's IP address. Also generates a fresh CSRF token.
     *
     * @param mixed $userId The unique identifier of the user being logged in.
     * @return void
     */
    public function loginUser($userId): void
    {
        // Regenerate session ID to prevent session fixation after login.
        $this->regenerate(true);
        // Store the user's ID.
        $this->set($this->config['user_id_key'], $userId);
        // Store the current time as the login/last activity time.
        $this->set($this->config['login_time_key'], time());
        // Store the user's IP address if configured.
        if ($this->config['check_ip_address']) {
            $this->set($this->config['user_ip_key'], $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        }
        // Remove any old CSRF token and generate a new one for the authenticated session.
        $this->remove($this->config['csrf_token_key']);
        $this->generateCsrfToken();
    }

    /**
     * Logs out the current user by destroying the session.
     *
     * Preserves flash messages across logout by temporarily storing them,
     * destroying the session, starting a new anonymous session, and restoring the messages.
     *
     * @return void
     */
    public function logoutUser(): void
    {
        // Temporarily store any existing flash messages.
        $flashData = $_SESSION[$this->config['flash_message_key']] ?? [];
        // Destroy the current authenticated session.
        $this->destroy();
        // Start a new, anonymous session.
        $this->start(); // Important to start a new session for the flash messages.
        // Restore flash messages into the new session if any were stored.
        if (!empty($flashData)) {
            $_SESSION[$this->config['flash_message_key']] = $flashData;
        }
        // Generate a CSRF token for the new anonymous session.
        $this->generateCsrfToken();
    }

    /**
     * Checks if a user is currently authenticated (logged in).
     *
     * Verifies the presence of the user ID key in the session.
     *
     * @return bool True if the user ID key exists in the session, false otherwise.
     */
    public function isAuthenticated(): bool
    {
        // Assumes session is started. Relies on validateActivity to handle invalid sessions.
        return $this->has($this->config['user_id_key']);
    }

    /**
     * Gets the ID of the currently authenticated user.
     *
     * @return int|string|null The user ID stored in the session, or null if not authenticated.
     *                         The type depends on what was stored during loginUser().
     */
    public function getUserId() // Return type depends on stored ID type
    {
        return $this->get($this->config['user_id_key']);
    }

    /**
     * Static method to check authentication status.
     *
     * Useful in contexts where a Session object instance might not be available,
     * but use with caution as it bypasses the instance's configuration and validation logic.
     * Attempts to start the session if not already started.
     *
     * @return bool True if the 'user_id' key exists in the session, false otherwise.
     * @deprecated Prefer using an injected Session instance and `isAuthenticated()` for better consistency and security checks.
     */
    public static function isAuthenticatedStatic(): bool
    {
        // Attempt to start session if not active. Suppress errors if headers already sent.
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        // Directly check the $_SESSION array. Bypasses timeout/IP checks.
        return isset($_SESSION['user_id']); // Assumes default 'user_id' key.
    }

    /**
     * Static method to get a session value.
     *
     * @param string $key Session key.
     * @param mixed $default Default value.
     * @return mixed Session value or default.
     * @deprecated Prefer using an injected Session instance and `get()`.
     */
    public static function getStatic(string $key, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Static method to set a session value.
     *
     * @param string $key Session key.
     * @param mixed $value Value to set.
     * @return void
     * @deprecated Prefer using an injected Session instance and `set()`.
     */
    public static function setStatic(string $key, $value): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION[$key] = $value;
    }


    /**
     * Validates the current session's activity and security constraints.
     *
     * Checks for:
     * 1. Session timeout based on inactivity.
     * 2. IP address mismatch (if configured).
     * 3. Need for session ID regeneration based on interval.
     *
     * If validation fails (timeout or IP mismatch), the user is logged out,
     * a flash message is set, and false is returned.
     * If regeneration is needed, it's performed.
     * Updates the last activity timestamp on successful validation.
     *
     * @return bool True if the session is valid and active, false if validation failed (user logged out).
     */
    public function validateActivity(): bool
    {
        // Skip validation if user is not logged in.
        if (!$this->isAuthenticated()) {
            return true; // Anonymous sessions are considered valid in this context.
        }

        // 1. Check for session timeout.
        $loginTime = $this->get($this->config['login_time_key']);
        if ($loginTime && (time() - $loginTime > $this->config['session_timeout'])) {
            // Session expired due to inactivity.
            $this->flash('error', 'Your session has expired due to inactivity. Please login again.');
            $this->logoutUser(); // Destroy session, preserve flash message.
            return false; // Indicate validation failure.
        }

        // 2. Check IP address consistency if enabled.
        if ($this->config['check_ip_address']) {
            $storedIp = $this->get($this->config['user_ip_key']);
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // Check if stored IP exists and differs from the current IP.
            if ($storedIp && $storedIp !== $currentIp) {
                // IP mismatch detected - potential hijacking attempt.
                if (Registry::has('logger')) {
                    Registry::get('logger')->warning("Session IP mismatch detected.", [
                        'stored_ip' => $storedIp,
                        'current_ip' => $currentIp,
                        'user_id' => $this->getUserId()
                    ]);
                }
                $this->flash('error', 'Your session is invalid due to a security check. Please login again.');
                $this->logoutUser(); // Destroy session, preserve flash message.
                return false; // Indicate validation failure.
            }
            // If IP wasn't stored previously (e.g., check enabled after login), store it now.
            if (!$storedIp && $currentIp !== 'unknown') {
                $this->set($this->config['user_ip_key'], $currentIp);
            }
        }

        // 3. Check if session ID needs regeneration.
        $lastRegenerate = $this->get('_last_regenerate', 0); // Get last regeneration time, default to 0.
        if (time() - $lastRegenerate > $this->config['regenerate_interval']) {
            // Regenerate ID, but don't delete the old session immediately
            // to avoid race conditions if requests overlap slightly.
            $this->regenerate(false);
        }

        // If all checks passed, update the last activity time.
        $this->set($this->config['login_time_key'], time());
        return true; // Indicate session is valid.
    }

    /**
     * Requires the user to be logged in to access a resource.
     *
     * If the user is not authenticated or their session fails validation,
     * it sets a flash message and redirects them to the specified login URL.
     *
     * @param string $redirectUrl The URL to redirect unauthenticated users to. Defaults to '/login'.
     * @return void This method may terminate script execution via Redirect::to().
     */
    public function requireLogin(string $redirectUrl = '/login'): void
    {
        // Check if user is authenticated.
        if (!$this->isAuthenticated()) {
            $this->flash('error', 'Please login to access this page.');
            \App\Core\Redirect::to($redirectUrl); // Redirect::to() includes exit()
        }
        // Also validate the session activity (timeout, IP, etc.).
        if (!$this->validateActivity()) {
            // If validation fails, user is logged out by validateActivity,
            // and we redirect them (flash message is already set).
            \App\Core\Redirect::to($redirectUrl); // Redirect::to() includes exit()
        }
        // If execution reaches here, the user is authenticated and the session is valid.
    }
}
