<?php

/**
 * Application Route Definitions
 *
 * This file defines all the web and API routes for the application.
 * It instantiates the Router and uses its methods (get, post, group)
 * to map URI patterns and HTTP methods to specific controller actions.
 *
 * The defined routes cover:
 * - Public pages (home, about, contact, categories, products)
 * - User authentication (register, login, logout)
 * - Order management (view form, process, list user orders, view details, cancel)
 * - Cart functionality (view cart page)
 * - CAPTCHA generation
 * - AJAX endpoints (email check, product filtering)
 * - API endpoints for cart management (add, view, update, remove, clear, count)
 * - API endpoints for order management (v1)
 * - Admin panel routes (dashboard, user management, order management, product management, category management)
 *
 * It utilizes route grouping for API and Admin sections and returns the configured Router instance.
 */

// Import necessary classes
use App\Core\Router;
use App\Core\Registry; // Although not directly used here, often needed in included files or controllers.
use App\Controllers\PageController;
use App\Controllers\ProductController;
use App\Controllers\UserController;
use App\Controllers\OrderController;
use App\Controllers\CaptchaController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\Admin\AdminOrderController;
use App\Controllers\Admin\AdminProductController;
use App\Controllers\Admin\AdminCategoryController;
use App\Middleware\AdminAuthMiddleware; // Middleware for admin routes (though applied within the group callback).
use App\Controllers\Api\OrderApiController;
use App\Controllers\Api\CartApiController;
use App\Controllers\AiSearchController;

// Instantiate the main router object.
// This instance is typically bound to the registry in the application's bootstrap process.
$router = new Router();

// --- DevTools/Utility Routes (to prevent log noise) ---
// Handle common browser/devtool requests gracefully to avoid cluttering logs with 404 exceptions.
$router->get('.well-known/appspecific/com.chrome.devtools.json', [PageController::class, 'silentNotFound']);

// --- Public Page Routes ---
$router->get('', [PageController::class, 'index']); // Homepage
$router->get('about', [PageController::class, 'about']); // About page
$router->get('contact', [PageController::class, 'contact']); // Contact page view
$router->post('contact/submit', [PageController::class, 'submitContact']); // Contact form submission

// --- Product & Category Routes ---
$router->get('categories', [ProductController::class, 'showCategories']); // List main categories
$router->get('products', [ProductController::class, 'listProducts']); // List products (potentially filtered)
$router->get('search', [ProductController::class, 'search']); // Search products

$router->get('product/{id}/{slug}', [ProductController::class, 'showProductDetail']); // Show product detail page
// --- User Authentication Routes ---
$router->get('register', [UserController::class, 'showRegister']); // Registration form view
$router->post('register', [UserController::class, 'register']); // Registration form submission
$router->get('login', [UserController::class, 'showLogin']); // Login form view
$router->post('login', [UserController::class, 'login']); // Login form submission
$router->get('logout', [UserController::class, 'logout']); // Logout action

// --- Order Routes (Requires Login - enforced by Router::direct or middleware) ---
$router->get('order', [OrderController::class, 'showOrderForm']); // Show general order form (likely from cart)
$router->post('order/process', [OrderController::class, 'processOrder']); // Process order submission
$router->get('orders', [OrderController::class, 'myOrders']); // List user's own orders
$router->get('my-orders', [OrderController::class, 'myOrders']); // Alias for listing user's orders
$router->get('order/confirmation/{id}', [OrderController::class, 'orderConfirmation']); // Show order confirmation page by ID
$router->get('order/details/{id}', [OrderController::class, 'orderDetails']); // Show details of a specific order by ID
$router->post('order/cancel/{id}', [OrderController::class, 'cancelOrder']); // Action to cancel an order by ID
$router->get('order/product/{productId}', [OrderController::class, 'showSingleProductOrderForm']); // Show form to order a single product
$router->post('order/product/{productId}', [OrderController::class, 'processSingleProductOrder']); // Process single product order

// --- Cart Page Route ---
$router->get('cart', [PageController::class, 'cart']); // Display the shopping cart page

// --- Utility Routes ---
$router->get('captcha', [CaptchaController::class, 'generate']); // Generate and display a CAPTCHA image

// --- AJAX Routes ---
$router->post('ajax/check-email', [UserController::class, 'checkEmail']); // Check if an email is already registered (used during registration)
$router->get('ajax/products-by-category', [ProductController::class, 'ajaxGetProductsByCategory']); // Get products by category for filtering
$router->get('ajax/subcategories', [ProductController::class, 'ajaxGetSubcategories']); // Get subcategories based on a parent category ID

// --- AI Search Routes ---
$router->post('api/ai-search', [AiSearchController::class, 'search']); // AI-powered search using Gemini API (POST for AJAX)
$router->get('api/ai-search', [AiSearchController::class, 'search']); // AI-powered search using Gemini API (GET for direct API testing)

// --- Cart API Routes (Typically called via JavaScript/AJAX) ---
$router->post('api/cart/add', [CartApiController::class, 'add']); // Add an item to the cart
$router->get('api/cart/view', [CartApiController::class, 'getCart']); // Get the current cart contents
$router->post('api/cart/update', [CartApiController::class, 'update']); // Update item quantity in the cart
$router->post('api/cart/remove', [CartApiController::class, 'remove']); // Remove an item from the cart (likely expects product ID in POST data)
$router->post('api/cart/item/{product_id}', [CartApiController::class, 'removeItem']); // Remove an item by product ID specified in URL
$router->post('api/cart/clear', [CartApiController::class, 'clearCart']); // Empty the entire cart
$router->get('api/cart/count', [CartApiController::class, 'getCartCount']); // Get the number of items in the cart

// --- Order API Routes (Version 1) ---
// Grouping API routes under '/api/v1' prefix.
// Authentication for these might be handled by API keys/tokens or session (if applicable).
// The hardcoded check in Router::direct covers session auth for '/api/orders...'
Router::group('api/v1', function () use ($router) {
    $router->get('orders', [OrderApiController::class, 'index']); // List orders (potentially filtered, context-dependent)
    $router->get('orders/{id}', [OrderApiController::class, 'show']); // Get details of a specific order
    $router->put('orders/{id}', [OrderApiController::class, 'update']); // Update an order (e.g., address - requires careful implementation)
    $router->put('orders/{id}/status', [OrderApiController::class, 'updateStatus']); // Update the status of an order
});

// --- Admin Panel Routes ---
// Grouping admin routes under '/admin' prefix.
// Authentication/Authorization should be handled here, potentially via middleware.
Router::group('admin', function () use ($router) {
    // Instantiate and potentially apply middleware for admin access control.
    // Note: Middleware application logic might reside within the Router or a dedicated dispatcher.
    // Here, it's instantiated but not explicitly applied in a standard middleware pattern.
    // Access control might rely on checks within each controller or a base admin controller.
    $adminMiddleware = new AdminAuthMiddleware(); // Example instantiation

    // Admin Dashboard
    $router->get('dashboard', [AdminDashboardController::class, 'index']);

    // Admin User Management
    $router->get('users', [AdminUserController::class, 'index']); // List users
    $router->get('users/{id}', [AdminUserController::class, 'show']); // View user details
    $router->get('users/{id}/edit', [AdminUserController::class, 'edit']); // Show user edit form
    $router->post('users/{id}', [AdminUserController::class, 'update']); // Update user details
    $router->post('users/{id}/reset-password', [AdminUserController::class, 'triggerPasswordReset']); // Trigger password reset for user

    // Admin Order Management
    $router->get('orders', [AdminOrderController::class, 'index']); // List all orders
    $router->get('orders/{id}', [AdminOrderController::class, 'show']); // View specific order details
    $router->post('orders/{id}/status', [AdminOrderController::class, 'updateStatus']); // Update order status

    // Admin Product Management
    $router->get('products', [AdminProductController::class, 'index']); // List products
    $router->get('products/create', [AdminProductController::class, 'create']); // Show product creation form
    $router->post('products', [AdminProductController::class, 'store']); // Store newly created product
    $router->get('products/{id}/edit', [AdminProductController::class, 'edit']); // Show product edit form
    $router->post('products/{id}', [AdminProductController::class, 'update']); // Update existing product
    $router->post('products/{id}/toggle-active', [AdminProductController::class, 'toggleActive']); // Activate/deactivate product

    // Admin Category Management
    $router->get('categories', [AdminCategoryController::class, 'index']); // List categories
    $router->get('categories/create', [AdminCategoryController::class, 'create']); // Show category creation form
    $router->post('categories', [AdminCategoryController::class, 'store']); // Store newly created category
    $router->get('categories/{id}/edit', [AdminCategoryController::class, 'edit']); // Show category edit form
    $router->post('categories/{id}', [AdminCategoryController::class, 'update']); // Update existing category
    $router->post('categories/{id}/delete', [AdminCategoryController::class, 'destroy']); // Delete category
});

// Return the configured router instance to the bootstrap process (e.g., public/index.php).
return $router;