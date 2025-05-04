<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Registry;
use App\Core\Request;
use App\Core\Session;
use App\Core\Redirect;
use App\Models\User;
use App\Helpers\SecurityHelper;
use App\Helpers\CaptchaHelper;
use Psr\Log\LoggerInterface;

/**
 * Class UserController
 * Handles user authentication (login, logout) and registration processes.
 * Includes methods for displaying login/registration forms, processing submissions,
 * validating input (including CAPTCHA), and managing user sessions.
 * Also provides an AJAX endpoint for checking email availability.
 *
 * @package App\Controllers
 */
class UserController extends BaseController
{
    /**
     * @var Database Database connection wrapper instance.
     */
    private Database $db;

    /**
     * @var Session Session management instance.
     */
    private Session $session;

    /**
     * @var Request HTTP request handling instance.
     */
    private Request $request;

    /**
     * @var User User model instance for database interactions.
     */
    private User $userModel;

    /**
     * @var LoggerInterface Logger instance for recording events and errors.
     */
    private LoggerInterface $logger;

    /**
     * @var CaptchaHelper Helper for generating and validating CAPTCHA images/text.
     */
    private CaptchaHelper $captchaHelper;

    /**
     * UserController constructor.
     * Initializes dependencies (Database, Session, Request, Logger) from the Registry.
     * Instantiates User model and CaptchaHelper.
     * Throws a RuntimeException if the database connection is unavailable.
     */
    public function __construct()
    {
        $this->db = Registry::get('database');
        $this->session = Registry::get('session');
        $this->request = Registry::get('request');
        $this->logger = Registry::get('logger');
        // Instantiate CaptchaHelper, passing the session dependency
        $this->captchaHelper = new CaptchaHelper($this->session);
        $pdoConnection = $this->db->getConnection(); // Get the actual PDO connection

        // Ensure PDO connection is valid before instantiating User model
        if ($pdoConnection) {
            $this->userModel = new User($pdoConnection);
        } else {
            // Log critical error and stop if DB connection failed
            $this->logger->critical("Database connection not available for UserController.");
            throw new \RuntimeException("Database connection not available for UserController.");
        }
    }

    /**
     * Displays the login form page.
     * Redirects to the homepage if the user is already authenticated.
     * Generates and stores a new CAPTCHA text.
     * Retrieves flash messages (errors, success, previous input) to display.
     * Generates a CSRF token for the form.
     *
     * @return void Renders the 'pages/login' view or redirects.
     */
    public function showLogin(): void
    {
        // If user is already logged in, redirect them away from the login page
        if ($this->session->isAuthenticated()) {
            Redirect::to('/');
            return;
        }

        // Generate a new CAPTCHA text and store it in the session
        $captchaText = $this->captchaHelper->generateText();
        $this->captchaHelper->storeText($captchaText);

        // Retrieve flash messages from the session (if any) from previous attempts
        $login_error = $this->session->getFlash('login_error');
        $captcha_error = $this->session->getFlash('captcha_error');
        $email = $this->session->getFlash('input_email'); // Repopulate email field on error
        $success_message = $this->session->getFlash('success'); // e.g., after successful registration

        // Generate CSRF token for form security
        $csrf_token = $this->session->getCsrfToken();

        // Prepare data for the view
        $data = [
            'page_title' => 'Login - GhibliGroceries',
            'meta_description' => 'Login to GhibliGroceries - Access your account to place orders.',
            'meta_keywords' => 'login, grocery, online shopping, account access',
            'additional_css_files' => ['/assets/css/login.css'], // Specific CSS
            'email' => $email ?? '', // Email input value (for repopulation)
            'login_error' => $login_error ?? '', // Login error message
            'captcha_error' => $captcha_error ?? '', // CAPTCHA error message
            'success_message' => $success_message ?? '', // Success message (e.g., from registration)
            'csrf_token' => $csrf_token // CSRF token
        ];

        // Render the login view
        $this->view('pages/login', $data);
    }

    /**
     * Processes the login form submission.
     * Validates request method, CSRF token, CAPTCHA, email format, and password presence.
     * Verifies user credentials against the database.
     * On success, establishes the user session and redirects to the homepage.
     * On failure, sets flash messages and redirects back to the login form.
     * Regenerates CAPTCHA on any failure.
     *
     * @return void Redirects to homepage on success or back to login on failure.
     */
    public function login(): void
    {
        // Ensure the request method is POST
        if (!$this->request->isPost()) {
            Redirect::to('/login');
            return;
        }

        // Validate CSRF token
        $csrf_input = $this->request->post('csrf_token', '');
        if (!$this->session->validateCsrfToken($csrf_input)) {
            $this->session->flash('login_error', 'Invalid security token. Please try again.');
            $this->logger->warning('CSRF token validation failed during login attempt.');
            $this->regenerateCaptchaAndRedirect('/login'); // Helper to reduce repetition
            return;
        }

        // Validate CAPTCHA input
        $captcha_input = $this->request->post('captcha', '');
        if (!$this->captchaHelper->validate($captcha_input)) {
            $this->session->flash('captcha_error', "The verification code is incorrect.");
            $this->session->flash('input_email', $this->request->post('email', '')); // Repopulate email
            $this->logger->warning('CAPTCHA validation failed during login attempt.');
            $this->regenerateCaptchaAndRedirect('/login');
            return;
        }

        // Sanitize and validate email and password presence
        $email = SecurityHelper::sanitizeInput($this->request->post('email', ''));
        $password = $this->request->post('password', ''); // Password is not sanitized here, used directly for verification
        if (!SecurityHelper::validateEmail($email) || empty($password)) {
            $this->session->flash('login_error', "Invalid email format or missing password.");
            $this->session->flash('input_email', $email); // Repopulate email
            $this->regenerateCaptchaAndRedirect('/login');
            return;
        }

        // Attempt to verify the password using the User model
        if ($this->userModel->verifyPassword($email, $password)) {
            // Password is correct, fetch user details
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                // Check if the account is locked
                if (isset($user['account_status']) && $user['account_status'] === 'locked') {
                    $this->session->flash('login_error', "Your account has been locked due to too many failed login attempts. Please contact support.");
                    $this->logger->warning('Login attempt on locked account.', ['email' => $email]);
                    $this->regenerateCaptchaAndRedirect('/login');
                    return;
                }

                // User found, establish session
                $this->session->loginUser($user['user_id']); // Sets user ID and authenticated flag
                $this->session->set('user_name', $user['name']); // Store user name in session
                $this->session->set('user_email', $user['email']); // Store user email
                $this->session->remove('captcha'); // Remove used CAPTCHA text from session
                $this->logger->info('User logged in successfully.', ['user_id' => $user['user_id'], 'email' => $email]);
                Redirect::to('/'); // Redirect to homepage
                return;
            } else {
                // Should not happen if verifyPassword succeeded, but handle defensively
                $this->logger->error('User data not found after successful password verification.', ['email' => $email]);
                $this->session->flash('login_error', "An unexpected error occurred during login.");
                $this->regenerateCaptchaAndRedirect('/login');
                return;
            }
        } else {
            // Invalid email or password
            $this->session->flash('login_error', "Invalid email or password.");
            $this->session->flash('input_email', $email); // Repopulate email
            $this->logger->warning('Invalid login attempt (wrong credentials).', ['email' => $email]);
            $this->regenerateCaptchaAndRedirect('/login');
            return;
        }
    }

    /**
     * Displays the registration form page.
     * Redirects to the homepage if the user is already authenticated.
     * Retrieves flash messages (errors, success, previous input) to display.
     * Generates a CSRF token for the form.
     *
     * @return void Renders the 'pages/register' view or redirects.
     */
    public function showRegister(): void
    {
        // If user is already logged in, redirect them away from the registration page
        if ($this->session->isAuthenticated()) {
            Redirect::to('/');
            return;
        }

        // Retrieve flash messages from session (if any) from previous attempts
        $registration_error = $this->session->getFlash('registration_error');
        $registration_success = $this->session->getFlash('registration_success'); // Not currently used, but could be
        $input_data = $this->session->getFlash('input_data', []); // Repopulate form on error

        // Prepare data for the view
        $data = [
            'page_title' => 'Register - GhibliGroceries',
            'meta_description' => 'Create an account with GhibliGroceries to start ordering fresh groceries online.',
            'meta_keywords' => 'register, grocery, create account, sign up',
            'additional_css_files' => ['/assets/css/register.css'], // Specific CSS
            'csrf_token' => $this->session->getCsrfToken(), // CSRF token for form
            'registration_error' => $registration_error ?? '', // Registration error message(s)
            'registration_success' => $registration_success ?? false, // Success flag (optional)
            'input' => $input_data // Array of previous input values
        ];

        // Render the registration view
        $this->view('pages/register', $data);
    }

    /**
     * Processes the registration form submission (supports both standard POST and AJAX).
     * Redirects or returns JSON error if user is already logged in or request method is invalid.
     * Validates CSRF token, sanitizes inputs, performs validation (name, phone, email, password, email uniqueness).
     * Hashes the password and creates the new user record in the database.
     * Handles responses differently for AJAX vs standard POST requests (JSON vs. Redirect with flash messages).
     *
     * @return void Redirects or outputs JSON response.
     */
    public function register(): void
    {
        $isAjax = $this->request->isAjax(); // Check if it's an AJAX request

        // Prevent registration if already logged in
        if ($this->session->isAuthenticated()) {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Already logged in.'], 403); // Forbidden
                return;
            } else {
                Redirect::to('/');
                return;
            }
        }

        // Ensure the request method is POST
        if (!$this->request->isPost()) {
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405); // Method Not Allowed
                return;
            } else {
                Redirect::to('/register');
                return;
            }
        }

        // Validate CSRF token
        $csrf_input = $this->request->input('csrf_token', ''); // Use input() for AJAX compatibility
        if (!$this->session->validateCsrfToken($csrf_input)) {
            $this->logger->warning('CSRF token validation failed during registration attempt.', ['isAjax' => $isAjax]);
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.'], 403);
                return;
            } else {
                $this->session->flash('registration_error', 'Invalid security token. Please try again.');
                Redirect::to('/register');
                return;
            }
        }

        // Sanitize inputs
        $name = SecurityHelper::sanitizeInput($this->request->input('name', ''));
        $phone = SecurityHelper::sanitizeInput($this->request->input('phone', ''));
        $email = SecurityHelper::sanitizeInput($this->request->input('email', ''));
        $password = $this->request->input('password', ''); // Password validated, not sanitized before hashing
        $input_data = ['name' => $name, 'phone' => $phone, 'email' => $email]; // For repopulation on error

        // Perform validation using SecurityHelper methods
        $errors = [];
        if (!SecurityHelper::validateName($name)) {
            $errors['name'] = 'Please enter a valid name (letters and spaces only).';
        }
        if (!SecurityHelper::validatePhone($phone)) {
            $errors['phone'] = 'Please enter a valid 10-digit phone number.';
        }
        if (!SecurityHelper::validateEmail($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (!SecurityHelper::validatePassword($password)) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }
        // Check email uniqueness only if the email format is valid
        if (empty($errors['email']) && $this->userModel->emailExists($email)) {
            $errors['email'] = 'This email address is already registered.';
        }

        // Handle validation errors
        if (!empty($errors)) {
            $this->logger->warning('Registration validation failed.', ['errors' => $errors, 'email' => $email, 'isAjax' => $isAjax]);
            if ($isAjax) {
                // Return JSON response with specific errors
                $this->jsonResponse(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422); // Unprocessable Entity
                return;
            } else {
                // Set flash messages for standard POST request
                $this->session->flash('registration_error', implode('<br>', $errors)); // Combine errors
                $this->session->flash('input_data', $input_data); // Repopulate form
                Redirect::to('/register');
                return;
            }
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            // Handle password hashing failure
            $this->logger->error("Password hashing failed during registration.", ['email' => $email, 'isAjax' => $isAjax]);
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'Could not process registration due to a server error. Please try again.'], 500);
                return;
            } else {
                $this->session->flash('registration_error', 'Could not process registration due to a server error. Please try again.');
                Redirect::to('/register');
                return;
            }
        }

        // Attempt to create the user in the database
        try {
            $userId = $this->userModel->create($name, $phone, $email, $hashedPassword);
            if ($userId) {
                // User created successfully
                $this->logger->info('New user registered successfully.', ['user_id' => $userId, 'email' => $email, 'isAjax' => $isAjax]);
                if ($isAjax) {
                    $this->jsonResponse(['success' => true, 'message' => 'Registration successful!']);
                    return;
                } else {
                    // Redirect to login page with success message for standard POST
                    $this->session->flash('success', 'Registration successful! You can now login.');
                    Redirect::to('/login');
                    return;
                }
            } else {
                // Database operation failed (e.g., constraint violation not caught earlier)
                $this->logger->error("User creation database operation failed.", ['email' => $email, 'isAjax' => $isAjax]);
                if ($isAjax) {
                    $this->jsonResponse(['success' => false, 'message' => 'Registration failed due to a database error. Please try again later.'], 500);
                    return;
                } else {
                    $this->session->flash('registration_error', 'Registration failed due to a database error. Please try again later.');
                    $this->session->flash('input_data', $input_data); // Repopulate form
                    Redirect::to('/register');
                    return;
                }
            }
        } catch (\Exception $e) {
            // Catch any unexpected exceptions during user creation
            $this->logger->error("Exception during user creation.", ['email' => $email, 'error' => $e->getMessage(), 'isAjax' => $isAjax]);
            if ($isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'An unexpected error occurred during registration. Please try again.'], 500);
                return;
            } else {
                $this->session->flash('registration_error', 'An unexpected error occurred during registration. Please try again.');
                $this->session->flash('input_data', $input_data); // Repopulate form
                Redirect::to('/register');
                return;
            }
        }
    }

    /**
     * AJAX endpoint to check if an email address already exists in the database.
     * Used for real-time validation in the registration form.
     * Expects 'email' as a GET or POST parameter.
     * Returns JSON response indicating whether the email exists or an error.
     *
     * @return void Outputs JSON response.
     */
    public function checkEmail(): void
    {
        // Get email from request (works for GET or POST)
        $email = $this->request->input('email');
        $exists = false;
        $error = null;

        // Basic validation
        if (empty($email)) {
            $error = 'Email parameter is missing.';
        } elseif (!SecurityHelper::validateEmail($email)) {
            $error = 'Invalid email format.';
        } else {
            // Check email existence using the User model
            try {
                $exists = $this->userModel->emailExists($email);
            } catch (\Exception $e) {
                // Handle potential database errors
                $this->logger->error("Error checking email existence via AJAX.", ['email' => $email, 'error' => $e->getMessage()]);
                $error = 'Server error checking email.';
            }
        }

        // Send JSON response
        if ($error) {
            $this->jsonResponse(['error' => $error], 400); // Bad Request
        } else {
            $this->jsonResponse(['exists' => $exists]); // OK
        }
    }

    /**
     * Logs the user out by destroying their session data.
     * Sets a flash message and redirects to the login page.
     *
     * @return void Redirects to the '/login' page.
     */
    public function logout(): void
    {
        $userId = $this->session->get('user_id'); // Get user ID before destroying session for logging
        $this->session->logoutUser(); // Destroys session and removes auth flags
        $this->session->flash('success', 'You have been logged out successfully.'); // Set flash message
        $this->logger->info('User logged out.', ['user_id' => $userId ?? 'N/A']); // Log logout event
        Redirect::to('/login'); // Redirect to login page
    }

    /**
     * Helper method to send a JSON response.
     * Sets the Content-Type header, HTTP status code, encodes data to JSON, and echoes it.
     * Logs an error if headers have already been sent.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code for the response (default: 200).
     * @return void
     */
    protected function jsonResponse($data, int $statusCode = 200): void
    {
        // Check if headers have already been sent to prevent warnings/errors
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($statusCode); // Set the HTTP status code
        } else {
            // Log an error if headers are already sent
            $this->logger->error("Headers already sent, cannot set JSON response headers.", ['status_code' => $statusCode]);
        }
        // Encode the data to JSON and output it
        echo json_encode($data);
        // Note: exit() is not called here by default. Consider adding if termination is always desired.
    }

    /**
     * Helper method to regenerate CAPTCHA text, store it, and redirect.
     * Reduces code duplication in login/register methods on validation failures.
     *
     * @param string $redirectTo The path to redirect to (e.g., '/login').
     * @return void
     */
    private function regenerateCaptchaAndRedirect(string $redirectTo): void
    {
        $captchaText = $this->captchaHelper->generateText();
        $this->captchaHelper->storeText($captchaText);
        Redirect::to($redirectTo);
    }
}
