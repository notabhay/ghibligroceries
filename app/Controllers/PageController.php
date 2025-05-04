<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\BaseController;
use App\Core\Request;
use App\Core\Redirect;
use App\Helpers\SecurityHelper;
use App\Helpers\CartHelper;
use App\Core\Registry;
use Psr\Log\LoggerInterface;
use App\Models\Product; // Added for type hinting

/**
 * Class PageController
 * Handles requests for standard informational pages like the homepage, about page,
 * contact page, and the shopping cart view. It also processes the contact form submission.
 *
 * @package App\Controllers
 */
class PageController extends BaseController
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
     * @var SecurityHelper Helper for input sanitization and validation.
     */
    private SecurityHelper $securityHelper;

    /**
     * @var LoggerInterface Logger instance for recording events and errors.
     */
    private LoggerInterface $logger;

    /**
     * @var CartHelper Helper for managing shopping cart data and interactions.
     */
    private CartHelper $cartHelper;

    /**
     * PageController constructor.
     * Initializes dependencies (Database, Session, Request, Logger) from the Registry.
     * Instantiates helper classes (SecurityHelper, CartHelper).
     * Throws a RuntimeException if the database connection is unavailable.
     */
    public function __construct()
    {
        $this->db = Registry::get('database');
        $this->session = Registry::get('session');
        $this->request = Registry::get('request');
        $this->logger = Registry::get('logger');
        $this->securityHelper = new SecurityHelper(); // Instantiate security helper
        // Instantiate cart helper, passing dependencies
        $this->cartHelper = new CartHelper($this->session, $this->db);

        // Verify database connection is available
        $pdoConnection = $this->db->getConnection();
        if (!$pdoConnection) {
            $this->logger->critical("Database connection not available for PageController.");
            throw new \RuntimeException("Database connection not available for PageController.");
        }
        // Note: Product model is instantiated within methods where needed, not in constructor
    }

    /**
     * Displays the homepage.
     * Fetches a selection of featured/random products to display.
     * Sets standard page metadata.
     *
     * @return void Renders the 'pages/index' view.
     */
    public function index(): void
    {
        $random_products = []; // Initialize empty array for products
        try {
            // Instantiate Product model here as it's specific to this method
            $productModel = new Product($this->db->getConnection()); // Pass PDO connection
            // Fetch featured products (implementation details in Product model)
            $random_products = $productModel->getFeaturedProducts();

            // Add image URL to each product for easier access in the view
            foreach ($random_products as &$product) { // Use reference to modify directly
                $image_filename = isset($product['image']) ? basename($product['image']) : 'default.png';
                $product['image_url'] = '/assets/images/products/' . $image_filename;
            }
            unset($product); // Unset reference after loop

        } catch (\Exception $e) {
            // Log error if fetching products fails
            $this->logger->error("Error fetching products for homepage.", ['exception' => $e]);
            // Continue rendering the page, but without products
        }

        // Prepare data for the view
        $data = [
            'page_title' => 'GhibliGroceries - Fresh Food Delivered',
            'meta_description' => 'Fresh groceries delivered to your door. Shop vegetables, meat, and more at our online grocery store.',
            'meta_keywords' => 'grocery, online shopping, vegetables, meat, fresh produce',
            'random_products' => $random_products, // Pass fetched products to the view
            'logged_in' => $this->session->isAuthenticated(), // Pass login status
        ];

        // Render the homepage view
        $this->view('pages/index', $data);
    }

    /**
     * Displays the 'About Us' page.
     * Sets standard page metadata.
     *
     * @return void Renders the 'pages/about' view.
     */
    public function about(): void
    {
        // Prepare data for the view
        $data = [
            'page_title' => 'About Us - GhibliGroceries',
            'meta_description' => 'Learn more about GhibliGroceries, your source for fresh, quality groceries delivered fast.',
            'meta_keywords' => 'about us, grocery store, online groceries, GhibliGroceries',
            'logged_in' => $this->session->isAuthenticated(), // Pass login status
            'additional_css_files' => ['/assets/css/about.css'], // Specific CSS for this page
        ];

        // Render the 'about' view
        $this->view('pages/about', $data);
    }

    /**
     * Displays the 'Contact Us' page with a contact form.
     * Includes CSRF token, flash messages for success/error, and previous input data if validation failed.
     * Sets standard page metadata.
     *
     * @return void Renders the 'pages/contact' view.
     */
    public function contact(): void
    {
        // Prepare data for the view, including form-related state from session
        $data = [
            'page_title' => 'Contact Us - GhibliGroceries',
            'meta_description' => 'Get in touch with GhibliGroceries. Contact us for support, inquiries, or feedback.',
            'meta_keywords' => 'contact, support, help, grocery store, GhibliGroceries',
            'csrf_token' => $this->session->getCsrfToken(), // CSRF token for form security
            'flash_message' => $this->session->getFlash('contact_message'), // Success/error message from previous submission
            'form_errors' => $this->session->getFlash('form_errors', []), // Validation errors from previous submission
            'old_input' => $this->session->getFlash('old_input', []), // User's previous input on validation failure
            'logged_in' => $this->session->isAuthenticated(), // Pass login status
            'additional_css_files' => ['/assets/css/contact.css'], // Specific CSS
        ];

        // Render the 'contact' view
        $this->view('pages/contact', $data);
    }

    /**
     * Processes the submission of the contact form.
     * Validates CSRF token, sanitizes inputs, performs validation (name, email, message length).
     * Logs the submission (simulated success) and sets flash messages before redirecting back to the contact page.
     *
     * @return void Redirects back to the '/contact' page with flash messages.
     */
    public function submitContact(): void
    {
        // Ensure the request method is POST
        if (!$this->request->isPost()) {
            Redirect::to('/contact'); // Redirect if not POST
            return;
        }

        // Get form inputs
        $name = $this->request->post('name', '');
        $email = $this->request->post('email', '');
        $message = $this->request->post('message', '');
        $submitted_token = $this->request->post('csrf_token', '');

        // Validate CSRF token
        if (!$this->session->validateCsrfToken($submitted_token)) {
            $this->session->flash('contact_message', ['type' => 'error', 'text' => 'Invalid security token. Please try again.']);
            Redirect::to('/contact');
            return;
        }

        // Sanitize inputs
        $sanitized_name = $this->securityHelper->sanitizeInput($name);
        $sanitized_email = filter_var($email, FILTER_SANITIZE_EMAIL); // Use PHP's email sanitizer
        $sanitized_message = $this->securityHelper->sanitizeInput($message);

        // Perform validation
        $errors = [];
        if (empty($sanitized_name)) {
            $errors['name'] = 'Name is required.';
        }
        if (!filter_var($sanitized_email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }
        if (empty($sanitized_message)) {
            $errors['message'] = 'Message is required.';
        }
        // Example: Basic length validation for message
        if (strlen($sanitized_message) < 10 || strlen($sanitized_message) > 1000) {
            $errors['message'] = 'Message must be between 10 and 1000 characters.';
        }

        // If validation errors exist
        if (!empty($errors)) {
            $this->session->flash('contact_message', ['type' => 'error', 'text' => 'Please correct the errors below.']);
            $this->session->flash('form_errors', $errors); // Store specific errors
            // Store sanitized old input to repopulate the form
            $this->session->flash('old_input', ['name' => $sanitized_name, 'email' => $sanitized_email, 'message' => $sanitized_message]);
            Redirect::to('/contact'); // Redirect back to the form
            return;
        }

        // --- Simulate Submission Processing ---
        // In a real application, this would involve sending an email or saving to a database.
        $this->logger->info("Contact form submitted successfully (simulation).", ['name' => $sanitized_name, 'email' => $sanitized_email]);
        $submission_successful = true; // Assume success for this example
        // --- End Simulation ---

        // Set flash message based on submission outcome
        if ($submission_successful) {
            $this->session->flash('contact_message', ['type' => 'success', 'text' => 'Thank you for your message! We will get back to you soon.']);
            // Do not flash old input on success
        } else {
            // Handle potential submission failure (e.g., email sending error)
            $this->session->flash('contact_message', ['type' => 'error', 'text' => 'Sorry, there was an error sending your message. Please try again later.']);
            // Flash old input on failure so user doesn't have to retype everything
            $this->session->flash('old_input', ['name' => $sanitized_name, 'email' => $sanitized_email, 'message' => $sanitized_message]);
        }

        // Redirect back to the contact page (which will display the flash message)
        Redirect::to('/contact');
    }

    /**
     * Displays the shopping cart page.
     * Requires the user to be logged in.
     * Uses CartHelper to retrieve detailed cart contents (products, quantities, totals).
     * Sets standard page metadata.
     *
     * @return void Renders the 'pages/cart' view or redirects to login.
     */
    public function cart(): void
    {
        // Redirect to login if user is not authenticated
        if (!$this->session->isAuthenticated()) {
            Redirect::to('/login');
            return;
        }

        // Use the CartHelper to get fully processed cart data (including product details, totals)
        $cartData = $this->cartHelper->getCartData();

        // Merge cart data with standard page data
        $data = array_merge($cartData, [
            'page_title' => 'Your Shopping Cart - GhibliGroceries',
            'meta_description' => 'View and manage your shopping cart at GhibliGroceries.',
            'meta_keywords' => 'shopping cart, checkout, grocery store, GhibliGroceries',
            'logged_in' => $this->session->isAuthenticated(), // Pass login status (redundant check, but good practice)
            'additional_css_files' => ['/assets/css/cart.css'], // Specific CSS
        ]);

        // Render the cart view
        $this->view('pages/cart', $data);
    }
}
