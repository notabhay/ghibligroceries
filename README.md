# GhibliGroceries - Online Grocery Store

GhibliGroceries is a PHP-based online grocery store application developed as a project for an Advanced Web Technologies module. It provides a comprehensive e-commerce platform for customers to browse and purchase groceries, and for administrators to manage the store's operations.

## Table of Contents

- [Features](#features)
  - [Customer Features](#customer-features)
  - [Admin Features](#admin-features)
- [Architecture Overview](#architecture-overview)
  - [Core Components](#core-components)
  - [Models](#models)
  - [Controllers](#controllers)
  - [Middleware](#middleware)
  - [Helpers](#helpers)
- [Database Schema](#database-schema)
- [Setup and Installation](#setup-and-installation)
- [Dependencies](#dependencies)
- [API Overview](#api-overview)

## Features

### Customer Features

- **User Registration & Login:** Secure account creation and login with CAPTCHA verification.
- **Product Browsing:** View products by categories and subcategories.
- **Product Filtering:** Filter products based on various criteria.
- **Shopping Cart:** Add items, update quantities, remove items, and clear the cart.
- **Order Placement:** Secure checkout process for single products or the entire cart.
- **Order History:** View past orders and their details.
- **Order Cancellation:** Ability to cancel pending orders.
- **Responsive Design:** User-friendly interface across various devices.

### Admin Features

- **Admin Dashboard:** Overview of store statistics (users, orders, products, categories, low stock items) and recent orders.
- **User Management:** List, view, and edit user details (name, phone, role, account status). Trigger password resets for users.
- **Category Management:** Create, read, update, and delete product categories with support for hierarchical structures.
- **Product Management:** Add, edit, and manage product details, including name, description, price, stock quantity, category, image, and active status.
- **Order Management:** List all customer orders with filtering options (status, date range). View detailed order information and update order statuses (pending, processing, completed, cancelled).
- **Secure Admin Area:** Access restricted to users with an 'admin' role.
- **Dark Mode:** A dark mode option for the admin panel interface.
- **Collapsible Sidebar:** For improved navigation on smaller screens in the admin panel.

## Architecture Overview

GhibliGroceries follows an MVC-like architecture:

- **Front Controller (`public/index.php`):** Single entry point for all HTTP requests. Initializes core components and the router.
- **Routing (`app/routes.php`):** Defines URL patterns and maps them to controller actions. Supports GET, POST, PUT methods, route parameters, and route grouping.
- **Core Components (`app/Core/`):**
  - `Router.php`: Handles URI matching and dispatches requests to controllers.
  - `Registry.php`: A simple static service container for managing shared dependencies (e.g., database, session, logger).
  - `Database.php`: A PDO wrapper for database connections and operations.
  - `Session.php`: Manages PHP sessions with security features (CSRF, timeout, IP validation).
  - `Request.php`: Encapsulates HTTP request data (GET, POST, FILES, headers).
  - `BaseController.php`: Abstract base class for controllers, providing a common `view` method for rendering.
  - `Redirect.php`: Utility for HTTP redirections.
- **Models (`app/Models/`):** Represent data entities and handle database interactions.
  - `User.php`: Manages user data, authentication, and roles.
  - `Category.php`: Manages product categories and their hierarchy.
  - `Product.php`: Manages product details, stock, and filtering.
  - `Order.php`: Manages customer orders and their statuses.
  - `OrderItem.php`: Manages items within an order.
- **Controllers (`app/Controllers/`):** Handle user input, interact with models, and select views.
  - **Public Controllers:** `PageController`, `UserController`, `ProductController`, `OrderController`, `CaptchaController`.
  - **API Controllers (`Api/`):** `CartApiController`, `OrderApiController`, `ApiController` (for documentation).
  - **Admin Controllers (`Admin/`):** `AdminDashboardController`, `AdminUserController`, `AdminCategoryController`, `AdminProductController`, `AdminOrderController`.
- **Middleware (`app/Middleware/`):**
  - `AdminAuthMiddleware.php`: Protects admin routes, ensuring only authenticated administrators can access them.
- **Helpers (`app/Helpers/`):** Provide utility functions.
  - `SecurityHelper.php`: Input/output sanitization, validation, token generation, security headers.
  - `CartHelper.php`: Shopping cart logic using sessions.
  - `CaptchaHelper.php`: CAPTCHA generation and validation.
  - `DatabaseHelper.php`: Procedural database utility functions (supplementary to `Core/Database.php`).
  - `captcha_image.php`: Standalone script for CAPTCHA image generation.
- **Views (`app/Views/`):** HTML templates organized into layouts, partials, and specific pages for both the public site and the admin panel. The registration form utilizes a React component (`RegistrationForm.js`) for an enhanced user experience.

## Database Schema

The database schema (`ghibligroceriesdb`) is defined in `sql/database_creation_script.sql`. Key tables include:

- `users`: Stores customer and admin information, roles, and security details.
- `categories`: Manages product categories with hierarchical support.
- `products`: Contains product details, pricing, stock, and images.
- `orders`: Tracks customer orders, including status and shipping information.
- `order_items`: Details individual items within each order.
- **Security & Logging Tables:** `login_attempts`, `security_logs`, `user_sessions`, `password_history`.
- **Management Tables:** `inventory_logs`, `order_history`.

The schema also includes triggers for logging password changes and order status updates, and for managing inventory. Stored procedures are used for tasks like recording login attempts, logging security events, and creating orders within a transaction. A scheduled event is defined for cleaning expired sessions.

## Setup and Installation

1.  **Database Setup:**

    - Ensure you have a MySQL server running.
    - Create a database (e.g., `ghibligroceriesdb`).
    - Import the schema and sample data using the `sql/database_creation_script.sql` file.
    - Update the database credentials in `app/config.php` (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

2.  **Web Server Configuration:**

    - Configure your web server (e.g., Apache, Nginx) to point the document root to the `public/` directory.
    - Ensure URL rewriting is enabled (e.g., `mod_rewrite` for Apache) to allow the front controller (`public/index.php`) to handle all requests. An `.htaccess` file might be needed in the `public/` directory for Apache.

3.  **PHP Dependencies:**

    - Ensure PHP version > 8 is installed.
    - Install Composer: [https://getcomposer.org/](https://getcomposer.org/)
    - Navigate to the project root directory in your terminal and run:
      ```bash
      composer install
      ```
      This will install the `monolog/monolog` dependency.

4.  **Permissions:**

    - Ensure the `logs/` directory (and `public/assets/uploads/products/` if used for image uploads directly by PHP scripts, though `AdminProductController` handles this relative to `public`) is writable by the web server user.

5.  **Accessing the Application:**
    - Open your web browser and navigate to the `SITE_URL` configured in `app/config.php` (e.g., `http://localhost/ghibligroceries/` if your project is in a subdirectory).

## Dependencies

- **PHP:** Version > 8
- **Composer Packages:**
  - `monolog/monolog`: >3 (for logging)
- **Client-Side (for specific features):**
  - React & ReactDOM (for the registration form)
  - Babel Standalone (for JSX transpilation in the browser for the React registration form - development only)
  - Font Awesome (for icons)
  - Google Fonts (Poppins)

## API Overview

The application includes a RESTful API for managing orders and cart functionality.

- **Base Path:** `/api` (configurable in `app/config.php`)
- **Authentication:** Requires Bearer token authentication for most endpoints.
- **Rate Limiting:** Implemented to prevent abuse (details in `app/config.php`).
- **Documentation:** An auto-generated API documentation page is available at `/api` (served by `ApiController.php`).

### Key API Endpoints:

- **Cart API (`/api/cart/...`):**
  - `POST /add`: Add item to cart.
  - `GET /view`: View cart contents.
  - `POST /update`: Update item quantity.
  - `POST /remove` or `POST /item/{product_id}`: Remove item from cart.
  - `POST /clear`: Clear entire cart.
  - `GET /count`: Get cart item count.
- **Order API (`/api/v1/orders/...`):** (Primarily for admin/manager roles)
  - `GET /orders`: List orders (all for manager, user's own for regular user). Supports filtering and pagination.
  - `GET /orders/{id}`: Get details of a specific order.
  - `PUT /orders/{id}` or `PUT /orders/{id}/status`: Update order status (manager only).

Refer to the API documentation endpoint (`/api`) for detailed request/response formats and error codes.

---

This README provides a comprehensive overview of the GhibliGroceries application. For more specific details, refer to the source code and inline comments.
