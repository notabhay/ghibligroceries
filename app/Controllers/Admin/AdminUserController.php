<?php

namespace App\Controllers\Admin;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Session;
use App\Core\Request;
use App\Core\Redirect;
use App\Models\User;
// No need to alias User model here as there's no naming conflict within this file

/**
 * Class AdminUserController
 *
 * Handles administrative tasks related to user management.
 * This includes listing users, viewing user details, editing user information
 * (like role and status), and triggering password resets for users within the admin panel.
 * It interacts primarily with the User model.
 *
 * @package App\Controllers\Admin
 */
class AdminUserController extends BaseController
{
    /**
     * @var Database Database connection instance.
     */
    private $db;

    /**
     * @var Session Session management instance.
     */
    private $session;

    /**
     * @var User User model instance for database interactions related to users.
     */
    private $userModel;

    /**
     * @var Request HTTP request handling instance.
     */
    private $request;

    /**
     * AdminUserController constructor.
     *
     * Initializes dependencies: Database, Session, Request, and an instance of the User model.
     *
     * @param Database $db The database connection instance.
     * @param Session $session The session management instance.
     * @param Request $request The HTTP request handling instance.
     */
    public function __construct(Database $db, Session $session, Request $request)
    {
        $this->db = $db;
        $this->session = $session;
        $this->request = $request;
        $this->userModel = new User($db); // Pass the Database object directly
    }

    /**
     * Displays the main user management page with pagination.
     *
     * Fetches a paginated list of all users using the User model.
     * Retrieves the current admin user's details for the layout and renders
     * the user index view within the admin layout.
     *
     * @return void
     */
    public function index(): void
    {
        // Get current page number from query string, default to 1
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = 15; // Number of users per page

        // Fetch paginated users from the model
        $result = $this->userModel->getAllUsersPaginated($page, $perPage);

        // Get current admin user details for the layout
        $userId = $this->session->getUserId();
        $adminUser = $this->userModel->findById($userId); // Fetch details of the logged-in admin

        // Prepare data for the view
        $data = [
            'page_title' => 'Manage Users',
            'admin_user' => $adminUser, // Pass admin details to the layout/view
            'users' => $result['users'] ?? [], // Users for the current page
            'pagination' => $result['pagination'] ?? [] // Pagination data
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/users/index', $data);
    }

    /**
     * Displays the detailed view for a specific user.
     *
     * Finds the user by ID using the User model. If not found, redirects back
     * with an error. Retrieves the current admin user's details and renders
     * the user show view within the admin layout.
     *
     * @param int $id The ID of the user to display.
     * @return void
     */
    public function show(int $id): void
    {
        // Find the user by ID
        $user = $this->userModel->findById($id);

        // Redirect if user not found
        if (!$user) {
            $this->session->flash('error', 'User not found.');
            Redirect::to('/admin/users');
            exit();
        }

        // Get current admin user details for the layout
        $adminUserId = $this->session->getUserId();
        $adminUser = $this->userModel->findById($adminUserId);

        // Prepare data for the view
        $data = [
            'page_title' => 'User Details',
            'admin_user' => $adminUser, // Pass admin details
            'user' => $user, // The user whose details are being shown
            // CSRF might be needed if actions (like password reset) are added here
            'csrf_token' => $this->session->generateCsrfToken()
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/users/show', $data);
    }

    /**
     * Displays the form for editing an existing user's details.
     *
     * Finds the user by ID. If not found, redirects back with an error.
     * Retrieves the current admin user's details, generates a CSRF token for the form,
     * and renders the user edit view within the admin layout.
     *
     * @param int $id The ID of the user to edit.
     * @return void
     */
    public function edit(int $id): void
    {
        // Find the user to edit
        $user = $this->userModel->findById($id);

        // Redirect if user not found
        if (!$user) {
            $this->session->flash('error', 'User not found.');
            Redirect::to('/admin/users');
            exit();
        }

        // Get current admin user details for the layout
        $adminUserId = $this->session->getUserId();
        $adminUser = $this->userModel->findById($adminUserId);

        // Prepare data for the view
        $data = [
            'page_title' => 'Edit User',
            'admin_user' => $adminUser, // Pass admin details
            'user' => $user, // The user being edited
            'csrf_token' => $this->session->generateCsrfToken() // CSRF token for the edit form
        ];

        // Render the view using the admin layout
        $this->viewWithAdminLayout('admin/users/edit', $data);
    }

    /**
     * Updates an existing user's information based on submitted edit form data.
     *
     * Verifies the CSRF token, finds the user by ID, validates the submitted
     * name, phone, role, and account status. Prevents an admin from removing their
     * own admin role. Attempts to update the user using the User model, sets
     * flash messages, and redirects.
     *
     * @param int $id The ID of the user to update.
     * @return void
     */
    public function update(int $id): void
    {
        // Verify CSRF token
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid form submission. Please try again.');
            Redirect::to('/admin/users/' . $id . '/edit');
            exit();
        }

        // Find the user to update
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->session->flash('error', 'User not found.');
            Redirect::to('/admin/users');
            exit();
        }

        // Get and sanitize input data from POST request
        $name = trim($this->request->post('name'));
        $phone = trim($this->request->post('phone'));
        $role = $this->request->post('role');
        $accountStatus = $this->request->post('account_status');

        // Validate input data
        $errors = [];
        if (empty($name)) {
            $errors[] = 'Name is required.';
        } elseif (strlen($name) > 100) { // Example length limit
            $errors[] = 'Name must be less than 100 characters.';
        }
        if (empty($phone)) {
            $errors[] = 'Phone is required.';
        } elseif (!preg_match('/^[0-9+\-\s()]{5,20}$/', $phone)) { // Basic phone format validation
            $errors[] = 'Phone number format is invalid (allow numbers, +, -, spaces, parentheses, 5-20 chars).';
        }
        // Validate role against allowed values
        if (!in_array($role, ['customer', 'admin'])) {
            $errors[] = 'Invalid role selected. Must be "customer" or "admin".';
        }
        // Validate account status against allowed values
        if (!in_array($accountStatus, ['active', 'inactive'])) {
            $errors[] = 'Invalid account status selected. Must be "active" or "inactive".';
        }

        // Prevent admin from removing their own admin role
        $adminUserId = $this->session->getUserId();
        if ($id == $adminUserId && $role != 'admin') {
            $errors[] = 'You cannot remove your own admin role.';
        }
        // Prevent admin from deactivating their own account
        if ($id == $adminUserId && $accountStatus != 'active') {
            $errors[] = 'You cannot deactivate your own account.';
        }


        // If validation errors exist, redirect back with errors
        if (!empty($errors)) {
            $this->session->flash('errors', $errors);
            Redirect::to('/admin/users/' . $id . '/edit');
            exit();
        }

        // Prepare data for model update (only update fields allowed from this form)
        $data = [
            'name' => $name,
            'phone' => $phone,
            'role' => $role,
            'account_status' => $accountStatus
            // Do NOT update email or password from this form
        ];

        // Attempt to update the user
        $success = $this->userModel->updateUser($id, $data);

        // Handle success or failure
        if ($success) {
            $this->session->flash('success', 'User updated successfully.');
            // Redirect to the user details page after successful update
            Redirect::to('/admin/users/' . $id);
        } else {
            $this->session->flash('error', 'Failed to update user. Please try again.');
            Redirect::to('/admin/users/' . $id . '/edit');
        }
    }

    /**
     * Triggers the password reset process for a specific user.
     *
     * Handles a POST request (likely from a button on the user details/edit page).
     * Verifies CSRF token, finds the user, generates a password reset token using
     * the User model, constructs the reset link, and simulates sending an email
     * (by logging the link and setting a flash message). Redirects back to the
     * user details page.
     *
     * Note: Actual email sending is commented out/logged for development/demo purposes.
     *
     * @param int $id The ID of the user for whom to trigger the password reset.
     * @return void
     */
    public function triggerPasswordReset(int $id): void
    {
        // Verify CSRF token from the POST request
        if (!$this->session->validateCsrfToken($this->request->post('csrf_token'))) {
            $this->session->flash('error', 'Invalid action or token missing. Please try again.');
            Redirect::to('/admin/users/' . $id); // Redirect back to user details
            exit();
        }

        // Find the user
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->session->flash('error', 'User not found.');
            Redirect::to('/admin/users'); // Redirect to list if user doesn't exist
            exit();
        }

        // Generate a password reset token via the User model
        $token = $this->userModel->generatePasswordResetToken($id);
        if (!$token) {
            // Handle error if token generation fails (e.g., database error)
            $this->session->flash('error', 'Failed to generate password reset token. Please try again.');
            Redirect::to('/admin/users/' . $id);
            exit();
        }

        // Construct the password reset link
        // Ensure scheme (http/https) and host are correct for the environment
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $resetLink = $scheme . '://' . $host . '/reset-password?token=' . urlencode($token);

        // Prepare email content (replace with actual email sending logic)
        $emailSubject = 'GhibliGroceries Password Reset Request';
        $emailBody = "Hello {$user['name']},\n\n";
        $emailBody .= "A password reset was requested for your account by an administrator.\n";
        $emailBody .= "Please click the link below to set a new password:\n\n";
        $emailBody .= $resetLink . "\n\n";
        $emailBody .= "This link will expire in 1 hour.\n\n";
        $emailBody .= "If you did not expect this, please contact support or ignore this email.\n\n";
        $emailBody .= "Regards,\nThe GhibliGroceries Team";

        // --- Email Sending Simulation ---
        // In a real application, use a mail library (PHPMailer, SwiftMailer, etc.) here
        // mail($user['email'], $emailSubject, $emailBody, "From: no-reply@yourdomain.com");

        // Log the email content/link for development/debugging purposes
        error_log("Password reset triggered for user ID {$id} ({$user['email']}). Reset Link: " . $resetLink);

        // Set a success flash message (including the link for easy testing in dev)
        $this->session->flash(
            'success',
            "Password reset process initiated for {$user['email']}. " .
                "An email simulation has been logged. <br><br>" .
                "<strong>Reset Link (for testing):</strong> <a href='{$resetLink}' target='_blank'>{$resetLink}</a>"
        );

        // Redirect back to the user details page
        Redirect::to('/admin/users/' . $id);
    }


    /**
     * Renders a view file within the standard admin layout.
     *
     * This is a protected helper method used by other actions in this controller
     * to ensure consistent layout across the admin user management section.
     * It handles path construction, file existence checks, data extraction,
     * and output buffering to inject the view's content into the layout.
     * It also attempts to determine the current request path for navigation highlighting.
     *
     * @param string $view The path to the view file (relative to the Views directory, using dot notation).
     * @param array $data An associative array of data to be extracted and made available to the view and layout.
     * @return void
     */
    protected function viewWithAdminLayout(string $view, array $data = []): void
    {
        // Construct full paths to the view and layout files
        $viewPath = __DIR__ . '/../../Views/' . str_replace('.', '/', $view) . '.php';
        $layoutPath = __DIR__ . '/../../Views/layouts/admin.php';

        // Check if the view file exists
        if (!file_exists($viewPath)) {
            trigger_error("View file not found: {$viewPath}", E_USER_WARNING);
            echo "Error: View file '{$view}' not found.";
            exit; // Stop execution if view is missing
        }

        // Check if the admin layout file exists
        if (!file_exists($layoutPath)) {
            trigger_error("Layout file not found: {$layoutPath}", E_USER_WARNING);
            echo "Error: Admin layout file not found.";
            exit; // Stop execution if layout is missing
        }

        // Attempt to get the current request path for navigation highlighting
        try {
            // Retrieve the request object from the registry (if available)
            $request = \App\Core\Registry::get('request');
            $uri = $request->uri();
            // Prepend slash for consistency
            $currentPath = '/' . ($uri ?: '');
            $data['currentPath'] = $currentPath;
        } catch (\Exception $e) {
            // Fallback if request object is not available or URI fails
            $data['currentPath'] = '/';
        }

        // Extract the data array into individual variables accessible by the view and layout
        extract($data);

        // Start output buffering to capture the view's content
        ob_start();
        try {
            // Include the specific view file
            include $viewPath;
        } catch (\Throwable $e) {
            // Clean buffer and display error if view rendering fails
            ob_end_clean();
            error_log("Error rendering view '{$view}': " . $e->getMessage()); // Log the actual error
            echo "Error rendering view '{$view}'. Please check the logs.";
            exit;
        }
        // Get the captured content from the buffer
        $content = ob_get_clean();

        // Make the captured view content available to the layout file
        $data['content'] = $content;

        // Extract data again to ensure $content is available in the layout's scope
        extract($data);

        // Include the main admin layout file, which will render the overall structure
        // and incorporate the $content variable.
        include $layoutPath;
    }
}
