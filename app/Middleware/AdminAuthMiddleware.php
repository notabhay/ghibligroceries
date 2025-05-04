<?php

namespace App\Middleware;

use App\Core\Session;
use App\Core\Redirect;
use App\Core\Registry;
use App\Models\User;

/**
 * Class AdminAuthMiddleware
 *
 * Middleware to protect routes that require administrator privileges.
 * It checks if a user is authenticated and if their role is 'admin'.
 * If checks fail, it redirects the user to the login page or the homepage
 * with an appropriate flash message.
 */
class AdminAuthMiddleware
{
    /**
     * @var Session The session management object, retrieved from the registry.
     */
    private $session;

    /**
     * @var \App\Core\Database The database connection object, retrieved from the registry.
     */
    private $db;

    /**
     * Constructor for AdminAuthMiddleware.
     *
     * Retrieves necessary dependencies (Session, Database) from the application Registry.
     */
    public function __construct()
    {
        // Get shared instances from the central registry.
        $this->session = Registry::get('session');
        $this->db = Registry::get('database');
    }

    /**
     * Handles the incoming request for admin-protected routes.
     *
     * Checks for user authentication and admin role. If authorized, it calls the next
     * middleware or controller in the stack. Otherwise, it redirects the user.
     *
     * @param callable $next The next middleware or controller action to be executed if authorization succeeds.
     * @return mixed The result of the next middleware/controller, or void if redirected.
     */
    public function handle(callable $next): mixed // Changed return type hint
    {
        // 1. Check if the user is authenticated at all.
        if (!$this->session->isAuthenticated()) {
            // If not authenticated, store an error message in flash session data.
            $this->session->flash('error', 'Please log in to access the admin area.');
            // Redirect the user to the login page.
            Redirect::to('/login');
            // Stop further script execution after redirect.
            exit();
        }

        // 2. Get the authenticated user's ID from the session.
        $userId = $this->session->getUserId();
        if (!$userId) {
            // This case should ideally not happen if isAuthenticated is true, but added as a safeguard.
            $this->session->flash('error', 'Authentication error. Please log in again.');
            $this->session->destroy(); // Destroy potentially corrupted session
            Redirect::to('/login');
            exit();
        }


        // 3. Fetch the user's details from the database using their ID.
        $userModel = new User($this->db);
        $user = $userModel->findById($userId);

        // 4. Check if the user exists and has the 'admin' role.
        // It also checks if the 'role' key exists in the fetched user data.
        if (!$user || !isset($user['role']) || $user['role'] !== 'admin') {
            // If user not found, role is missing, or role is not 'admin', deny access.
            $this->session->flash('error', 'You do not have permission to access the admin area.');
            // Redirect non-admin users to the homepage.
            Redirect::to('/');
            // Stop further script execution.
            exit();
        }

        // 5. If all checks pass, call the next middleware/controller in the chain.
        return $next();
    }
}
