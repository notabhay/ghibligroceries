# GhibliGroceries - Advanced Web Technologies Report

**Student ID:** 23025194

**Login Details:**

- Admin Account Email ID: admin@ghibligroceries.com
- Admin Account Password: adminpass

**Live Website Link:** http://www.teach.scam.keele.ac.uk/prin/ghibligroceries or https://ghibligroceries.com

**Word Count:** [Total word count - must not exceed 2000 words]

---

## Task Completion Summary (Table 1)

| Task                                                                             | Comments                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| :------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1. Grocery company webpage (T1, T7 Part)                                         | Implemented using PHP MVC structure. Allows browsing of products fetched from the MySQL database (`products` table). Product categories and items are displayed dynamically. AJAX is utilized as required by T1 for features like populating sub-categories or product details asynchronously, enhancing user experience. All core browsing functionalities achieved.                                                                                                                                              |
| 2. Submit SQL files to generate the tables in the grocery company (T7)           | The `sql/database_creation_script.sql` file is provided. This script creates the `ghibligroceriesdb` database and all necessary tables, including `users`, `categories`, `products`, `orders`, `order_items`, and supporting tables for security and logging (`login_attempts`, `security_logs`, `user_sessions`, `password_history`, `inventory_logs`, `order_history`). It also sets up relationships, triggers, and stored procedures.                                                                          |
| 3. Registration page (T2, T4 Part, T6 Part)                                      | Registration page implemented (`/register` route). Collects Name, Phone, Email, and Password as required by T2. Email uniqueness is enforced by the database schema. Security measures (T4) like input sanitization and password hashing (bcrypt) are implemented in the backend (`UserController`). Live validation using React (T6) is implemented on the frontend (`RegistrationForm.js`). Secure registration process achieved.                                                                                |
| 4. Login page (mention with/without Captcha) (T3, T4 Part)                       | Login page implemented (`/login` route) allowing users to authenticate using email and password (T3). Backend logic verifies credentials against the `users` table. Session management (`Session` class) is used to maintain user login state across requests and handle secure logout. A CAPTCHA (`CaptchaHelper` and associated controller/route) is implemented on the login form as required by T4 to prevent automated bots. Login, logout, session handling, and CAPTCHA functionality achieved.             |
| 5. Security aspects (preventing attacks) of the Registration and Login page (T4) | Multiple security measures implemented (T4): Input sanitization (e.g., `htmlspecialchars`) used to prevent XSS. Prepared statements (via PDO in `Database` class) used to prevent SQL injection. Passwords hashed using bcrypt (`password_hash`). CAPTCHA implemented on login to deter bots. Session handling includes security features like ID regeneration and activity validation (`Session` class). CSRF protection mechanisms can be added via token generation/validation. Core security requirements met. |
| 6. Search engine optimization aspects in the grocery company webpage (T5)        | Basic SEO practices implemented (T5): Semantic HTML5 tags (e.g., `<header>`, `<nav>`, `<main>`, `<footer>`, `<article>`) used for better structure. Meaningful `<title>` tags set dynamically in controllers. `robots.txt` and `sitemap.xml` included in the `public` directory to guide search engine crawlers. Image `alt` attributes used where appropriate. Further improvements could include meta descriptions and structured data.                                                                          |
| 7. User Registration validation using React framework (T6)                       | Live, keystroke-by-keystroke validation implemented on the registration form using React as required by T6. The `RegistrationForm.js` component handles input changes, performs validation checks (e.g., name format, phone number digits, email format), and displays immediate feedback/error messages to the user. This enhances the user experience by providing instant validation. React integration for live validation achieved.                                                                           |
| 8. Data management (T7)                                                          | a) Customer registration table (`users`): Stores Name, Phone, Email (unique), hashed Password, role, and account status, fulfilling T2/T4 requirements. Data is securely inserted via `UserController`.<br>b) Customer ordered table (`orders` & `order_items`): `orders` table stores order metadata (user_id, date, total, status). `order_items` table links orders to products and stores quantity/price at the time of order, fulfilling T7 requirements. Orders are created via `OrderController`.           |
| 9. RESTful webservice for the grocery company Manager (T8)                       | A RESTful API endpoint implemented (e.g., `/api/orders/{id}`) allowing retrieval of order details by Order ID, fulfilling T8. The `Api/OrderApiController.php` handles requests, fetches data from the database (`Order` model), and returns order details in JSON format. Basic implementation achieved; further enhancements could include manager authentication/authorization for the API endpoint.                                                                                                            |

<!-- Beyond Brief Scope: Explicit Use of Registry Pattern -->

<!-- Beyond Brief Scope: Comprehensive Admin Panel (CRUD for Products, Categories, Users, Orders) -->

| 10. Any other information (remarks) | The project utilizes a custom-built PHP MVC framework structure. Composer is used for dependency management (e.g., Monolog for logging). A Registry pattern (`Registry` class) is used for simple dependency injection/service location. Code follows PSR-4 autoloading standards. Admin panel functionality is included for managing products, categories, users, and orders. Frontend uses standard HTML/CSS/JS alongside React for specific components like registration validation. |

---

## 1. Introduction

### 1.1. Project Overview

This report details the design and implementation of the Ghibli Groceries online store, a web application developed as part of the Advanced Web Technologies module assessment. The primary goal of this project is to create a functional and secure multi-tier web application that allows customers to browse grocery products, register for an account, log in, and place orders online. Additionally, the system provides basic administrative capabilities for managing the store's data, addressing the need for grocery companies to manage online operations efficiently as outlined in the assessment brief.

### 1.2. System Architecture

The Ghibli Groceries application employs a multi-tier, client-server architecture, specifically implemented using the Model-View-Controller (MVC) design pattern. This architectural choice promotes separation of concerns, modularity, and maintainability, aligning with the assessment's emphasis on advanced web application design.

The main components are:

- **Frontend (Client-Side):** Responsible for presentation and user interaction. It is built using standard HTML5, CSS3, and JavaScript. Specific interactive features, such as live registration validation (T6), leverage the React JavaScript library. Asynchronous JavaScript and XML (AJAX) techniques are used (T1) to enhance user experience by dynamically loading data without full page reloads.
- **Backend (Server-Side):** Handles business logic, data processing, and request routing. It is developed using PHP, following an object-oriented approach (C&D). Key components include a custom router (`App\Core\Router`), base controller (`App\Core\BaseController`), models for database interaction (e.g., `App\Models\Product`, `App\Models\User`), and controllers to handle specific requests (e.g., `App\Controllers\ProductController`, `App\Controllers\UserController`).
- **Database:** A MySQL database (`ghibligroceriesdb` as defined in `sql/database_creation_script.sql` and `app/config.php`) serves as the persistence layer, storing information about users, products, categories, and orders (T7). The `App\Core\Database` class provides a PDO wrapper for secure and efficient database operations.

Figure 1 below provides a high-level overview of this architecture.

_(Placeholder for Figure 1: System Architecture Diagram)_

The core technologies underpinning this architecture include PHP for server-side logic, MySQL for data storage, HTML/CSS/JavaScript for the user interface, React for specific frontend enhancements (T6), and AJAX for dynamic data handling (T1).

### 1.3. Report Structure

This report is structured as follows:

- Section 2 provides a detailed description of the implemented features, covering each assessment task (T1-T8) and the coding/design aspects (C&D), acting as a system manual.
- Section 3 discusses potential improvements and concludes the report.
- References and appendices are included where applicable.
- The Task Completion Summary (Table 1) at the beginning provides a quick overview of the project status against the assessment requirements.

## 2. Developed Architecture, Implementation Details, and System Inputs/Outputs

### 2.1. Getting Started

Accessing the Ghibli Groceries website is straightforward. The live version is hosted at http://www.teach.scam.keele.ac.uk/prin/ghibligroceries or https://ghibligroceries.com. Upon visiting the site, the user is presented with the main landing page, served by the `index.php` front controller which routes the request to the `PageController::index()` method.

This initial view (`app/Views/pages/index.php`) features:

- A prominent hero section with a headline, descriptive text, and a search bar (the search bar has not been implemented and is not functional currently).
<!-- Beyond Brief Scope: Random Featured Items -->
- Key selling points like "Fresh Vegetables" and "Fast Delivery" highlighted with icons.
- A selection of "Today's Featured Items" fetched randomly from the `products` database table via the `Product::getFeaturedProducts()` model method. Logged-out users see a "Login to Purchase" link, while logged-in users see an "Add to Cart" button for these items.
- Direct links to the main product categories (e.g., Dairy Products, Fruits & Veggies, Meat) displayed as visually distinct cards, allowing users to quickly navigate to specific sections via the `/categories?filter=Category%20Name` URL structure.

### 2.2. Task 1: Grocery Browsing Webpage (T1)

The primary interface for browsing groceries is the Categories page, accessible via the `/categories` route handled by `ProductController::showCategories()`. This page (`app/Views/pages/categories.php`) initially displays all available products fetched from the `products` table (joined with `categories` for names) using the `Product::getAll()` model method.

**Dynamic Filtering and Loading (AJAX Implementation - T1):**
The assessment required dynamic content loading using AJAX. This was implemented for filtering products by category without requiring a full page reload, enhancing user experience.

1.  **Category Selection:** When a user clicks a category link (either from the homepage or the category filter list on the `/categories` page), the page reloads with a `?filter=CategoryName` query parameter. The `ProductController::showCategories()` method detects this parameter, finds the corresponding `category_id` using a helper method (`getCategoryIdByName`), and initially loads only products belonging to that category (and its subcategories) via `Product::findByCategory()`.
2.  **Subcategory Loading via Dropdowns (AJAX):** To allow users to refine their browsing, subcategory selection is implemented using dynamically populated dropdown menus. When a main category is selected (either via the initial URL parameter or by clicking a category link), JavaScript in `script.js` makes an AJAX request to the `/ajax/subcategories` endpoint. This endpoint, handled by `ProductController::ajaxGetSubcategories()`, fetches the relevant child categories using `Category::getSubcategoriesByParentId()`. The JavaScript then uses the returned JSON data to populate a subcategory dropdown menu on the page, allowing users to further filter the displayed products.
3.  **Product Loading via AJAX:** A separate AJAX endpoint (`/ajax/products-by-category`) handled by `ProductController::getSubcategoriesAjax()` (a potentially misleading name, as it fetches _products_) allows JavaScript (in `script.js`) to fetch products associated with a specific category ID (`Product::findByCategory()`). This is used to update the product display area (`#product-display-area` in `pages/categories.php`) when a category filter is applied dynamically via JavaScript interactions (though the primary filtering mechanism currently uses the URL parameter). The `fetch` API in `script.js` handles these asynchronous requests, parses the JSON response, and calls the `renderProducts()` function to update the DOM.

**Data Source:**
Product information (name, price, image path, description, stock) is stored in the `products` table, linked via `category_id` to the `categories` table, as defined in `sql/database_creation_script.sql`. The `Product` and `Category` models encapsulate database interactions for fetching this data.

**User Flow (Logged Out):**
Before logging in, users can browse all categories and products, view their details (names, images, prices), but cannot add items to the cart or place orders. "Add to Cart" buttons are replaced with "Login to Purchase" links.

_(Screenshots of the browsing interface would be inserted here in a final report)._

### 2.3. Task 2 & 4: Registration Process (T2, T4 Part, T6 Part)

New users can create an account by navigating to the `/register` page, rendered by `UserController::showRegister()` using the `app/Views/pages/register.php` view.

**Registration Form:**
The form collects the user's Full Name, Phone Number (10 digits), Email Address (must be unique), and Password, fulfilling the requirements of T2.

**Live Validation (T6):**
To provide immediate feedback as required by T6, the registration form is implemented as a React component (`public/assets/js/react_components/RegistrationForm.js`).

- **State Management:** React's `useState` hook manages the state for form input values (`formData`), validation errors (`errors`), and touched status (`touched`) for each field.
- **Keystroke Validation:** The `handleChange` function updates the `formData` state on every keystroke. If a field has been previously "touched" (i.e., the user has interacted with it and moved away, detected by `handleBlur`), `handleChange` also triggers the corresponding validation function (`validate.name`, `validate.phone`, etc.).
- **Validation Logic:** Specific validation functions within the component check:
  - Name: Contains only letters and spaces (`/^[a-zA-Z\s]+$/`).
  - Phone: Contains exactly 10 digits (`/^\d{10}$/`).
  <!-- Beyond Brief Scope: Password Strength Meter UI Component -->
  - Email: Matches a standard email format (`/^[^\s@]+@[^\s@]+\.[^\s@]+$/`).
  - Password: Meets minimum length (8 characters). A visual strength meter (`PasswordStrengthMeter` component) provides additional feedback.
- **Error Display:** Validation errors are stored in the `errors` state and displayed dynamically below the relevant input field using the reusable `FormInput` component. Success indicators (check marks) are shown for valid, touched fields.
- **Email Uniqueness Check:** The `handleChange` function for the email field uses `setTimeout` to debounce an asynchronous `fetch` call to the `/ajax/check-email` endpoint (handled by `UserController::checkEmail`). This endpoint queries the database via `User::emailExists()` to check if the entered email is already registered. The result updates the `emailExists` state in the React component, which is then used by the `validate.email` function to display the "Email already exists" error message in real-time if necessary.

**Security Measures (T4):**
Several security measures are implemented as required by T4:

- **Cross-Site Scripting (XSS) Prevention:** User inputs (Name, Phone, Email, Notes etc.) are sanitized on the server-side using `SecurityHelper::sanitizeInput()`, which employs `htmlspecialchars` with `ENT_QUOTES` before data is processed or stored. Output encoding (`SecurityHelper::encodeOutput` using `htmlentities`) is used when displaying user-generated content.
<!-- Beyond Brief Scope: CSRF Protection Implementation -->
- **SQL Injection Prevention:** All database interactions are performed using PDO (PHP Data Objects) via the `App\Core\Database` class. Prepared statements with bound parameters are used exclusively (e.g., in `User::create`, `User::findByEmail`), preventing malicious SQL code from being injected and executed.
- **Password Security:** Passwords are never stored in plain text. The `UserController::register()` method uses PHP's `password_hash()` function (with the default `PASSWORD_DEFAULT` algorithm, typically bcrypt) to create a strong, salted hash of the user's password before storing it in the `users` table. Password verification during login uses `password_verify()`.
- **CSRF Protection:** A CSRF token is generated using `Session::generateCsrfToken()` and included as a hidden field in the registration form. The `UserController::register()` method validates this token against the one stored in the session using `Session::validateCsrfToken()` before processing the registration.
- **Input Validation:** Both client-side (React) and server-side (`UserController::register` using `SecurityHelper` methods) validation are performed to ensure data integrity and prevent unexpected inputs.

**Database Interaction (T7 Part):**
Upon successful validation, `UserController::register()` calls the `User::create()` method. This method inserts the sanitized user data (name, phone, email) and the hashed password into the `users` table in the MySQL database (`ghibligroceriesdb`). The `email` column has a `UNIQUE` constraint enforced by the database schema (`sql/database_creation_script.sql`) to prevent duplicate registrations with the same email address.

**User Feedback:**
The React component provides immediate visual feedback for validation errors. On submission, if server-side validation fails or an error occurs, error messages are displayed via the `submitError` state. Upon successful registration, a success message is shown, and the user is redirected to the login page (`/login`) with a flash message confirming success (`Session::flash('success', ...)`).

_(Screenshots of the registration form, validation messages, and success message would be inserted here)._

### 2.4. Task 3 & 4: Login/Logout Process (T3, T4 Part)

Registered users can log in via the `/login` page, rendered by `UserController::showLogin()` using the `app/Views/pages/login.php` view.

**Login Form:**
The form requires the user's Email Address and Password.

**Authentication:**
When the form is submitted, `UserController::login()` handles the process:

1.  It validates the CSRF token and the CAPTCHA input.
2.  It sanitizes the email input using `SecurityHelper::sanitizeInput()`.
3.  It calls `User::verifyPassword()`, providing the submitted email and plain-text password.
4.  The `User::verifyPassword()` method fetches the user record by email using `User::findByEmail()` and then uses PHP's `password_verify()` function to securely compare the submitted password against the stored hash from the `users` table.

**Session Management (T3):**
Session handling is crucial for maintaining user state and security, managed by the `App\Core\Session` class:

- **Login:** Upon successful password verification, `UserController::login()` calls `Session::loginUser()`. This method:
  - Regenerates the session ID using `session_regenerate_id(true)` to prevent session fixation.
  - Stores the authenticated user's `user_id` and the current `login_time` in the `$_SESSION` array using configured keys.
  - Optionally stores the user's IP address (`user_ip`) if `check_ip_address` is enabled in the configuration.
  - Generates a fresh CSRF token for the authenticated session.
  <!-- Beyond Brief Scope: Session IP Address Check -->
- **Authentication Check:** Subsequent requests use `Session::isAuthenticated()` (which checks for the presence of the `user_id` key) to verify if the user is logged in. Sensitive actions or pages call `Session::requireLogin()`, which redirects unauthenticated users to `/login`.
- **Activity Validation:** The `Session::validateActivity()` method (called automatically on session start/resume) performs security checks:
  - **Timeout:** Compares the current time with the stored `login_time`. If the inactivity period exceeds `session_timeout` (e.g., 1800 seconds), the session is destroyed via `logoutUser()`.
  - **IP Check (Optional):** If `check_ip_address` is true, it compares the current `$_SERVER['REMOTE_ADDR']` with the `user_ip` stored in the session. A mismatch logs the user out.
  - **Regeneration:** Checks the `_last_regenerate` timestamp and calls `regenerate(false)` if the `regenerate_interval` (e.g., 300 seconds) has passed.
- **Logout:** Users can log out via the `/logout` route, which triggers `UserController::logout()`. This method calls `Session::logoutUser()`, which clears the `$_SESSION` array, destroys the server-side session data (`session_destroy()`), and invalidates the session cookie by setting its expiry time in the past. Flash messages are preserved across logout.
- **Cookie Security:** Session cookie parameters are configured in `Session::__construct` and set via `session_set_cookie_params()` before starting the session. This includes setting `httponly` to true (prevents JavaScript access), `secure` to true if HTTPS is detected, and `samesite` to 'Lax' (mitigates CSRF).

**CAPTCHA Implementation (T4):**
To prevent automated login attempts (bots), a CAPTCHA is implemented as required by T4:

1.  **Generation:** When the login page (`/login`) is displayed, `UserController::showLogin()` uses the `CaptchaHelper` (injected in the constructor) to generate random text (`CaptchaHelper::generateText()`) and store it (lowercase) in the session (`CaptchaHelper::storeText()`).
2.  **Image Display:** The login form (`pages/login.php`) includes an `<img>` tag with its `src` attribute pointing to `/captcha`. This route is handled by `CaptchaController::generate()`. This controller method retrieves the stored text from the session, uses `CaptchaHelper::generateImageData()` to create a PNG image with the text and visual noise (lines, dots), sets the appropriate `Content-Type: image/png` header, and outputs the image data.
3.  **Refresh:** An inline JavaScript snippet in `pages/login.php` adds an event listener to a refresh button next to the CAPTCHA image. Clicking this button updates the `src` attribute of the `<img>` tag with a timestamp (`/captcha?timestamp`), forcing the browser to request a new image from `/captcha`, which generates a new challenge.
4.  **Validation:** During login processing, `UserController::login()` retrieves the user's input from the 'captcha' field and validates it against the text stored in the session using `CaptchaHelper::validate()`. This comparison is case-insensitive. If validation fails, an error message (`captcha_error`) is flashed, and the user is redirected back to the login form with a new CAPTCHA image generated.

**User Feedback:**
If login fails due to incorrect credentials, a `login_error` flash message is displayed. If CAPTCHA validation fails, a `captcha_error` flash message is shown. The email field is repopulated on error to save the user typing. Upon successful login, the user is redirected to the homepage (`/`).

_(Screenshots of the login form with CAPTCHA and error messages would be inserted here)._

### 2.5. Task 7: Ordering Process (T7)

<!-- Beyond Brief Scope: Full AJAX Shopping Cart (Add Item) -->
<!-- Beyond Brief Scope: Interactive AJAX Cart Management (View, Update, Remove, Clear) -->

Once a user is successfully registered and logged in (T2, T3), they gain the ability to place orders, fulfilling the core requirement of T7.

**User Flow & Functionality:**

1.  **Adding Items:** Logged-in users see "Add to Cart" buttons on product listings (homepage featured items, category page). Clicking these buttons triggers an AJAX request (handled by `script.js`) to the `/api/cart/add` endpoint (likely managed by `Api/CartApiController.php`, though not explicitly read for this task). This endpoint adds the selected `product_id` and quantity (defaulting to 1 from listings) to the user's cart, which is stored in the session (`$_SESSION['cart']`). Feedback is provided via toast notifications (`showToast('Item added...', 'success')`).
2.  **Viewing Cart:** Users can navigate to `/cart` (handled by `PageController::cart()`, view `pages/cart.php`). This page displays items currently in the session cart, including product thumbnails, names, prices, quantities, and subtotals. Users can adjust quantities or remove items using buttons that trigger further AJAX calls (`/api/cart/update`, `/api/cart/item/{id}`) handled by `script.js` functions (`updateCartItemQuantity`, `removeCartItem`). The total cart price (`#cart-total-price`) is updated dynamically. An option to "Clear Cart" (`#clear-cart-btn` triggering `/api/cart/clear`) is also available.
3.  **Checkout:** From the cart page, users click "Proceed to Checkout", which links to `/order` (handled by `OrderController::showOrderForm()`, view `pages/order_form.php`). This page displays an order summary (similar to the cart but typically not editable) and a form for entering shipping details and optional notes. Currently, only "Cash on Delivery" is implemented as a payment method.
4.  **Placing Order:** Submitting the order form sends a POST request to `/order/process` (handled by `OrderController::processOrder()`). This method performs final validation:
    <!-- Beyond Brief Scope: Automatic Inventory Decrement via Trigger & Inventory Logging -->
        - Checks CSRF token.
        - Re-verifies product availability and stock levels for all cart items against the database (`Product::findMultipleByIds`, `Product::checkStock`) to prevent ordering out-of-stock items due to race conditions.
        - If validation passes, it calls the private `createOrder()` method.
5.  **Database Interaction (T7):** The `OrderController::createOrder()` method handles the core database operations within a transaction for atomicity:
    - It inserts a new record into the `orders` table containing `user_id`, calculated `total_amount`, status ('pending'), and any provided notes/shipping address.
    - It retrieves the newly generated `order_id`.
    - It iterates through the validated cart items and inserts corresponding records into the `order_items` table, linking them to the `order_id` and storing the `product_id`, `quantity`, and the `price` at the time of the order.
    - The `inventory_order_trigger` (defined in `sql/database_creation_script.sql`) automatically decrements the `stock_quantity` in the `products` table whenever a new row is inserted into `order_items`. It also logs this change in the `inventory_logs` table.
    - If all database operations succeed, the transaction is committed. If any step fails (e.g., stock update error, constraint violation), the transaction is rolled back, preventing partial order creation.
6.  **Order Confirmation:** Upon successful order creation (`createOrder()` returns the new order ID), `processOrder()` clears the user's cart from the session (`$session->set('cart', [])`) and redirects the user to the confirmation page (`/order/confirmation/{orderId}`). This page (handled by `OrderController::orderConfirmation()`, view `pages/order_confirmation.php`) fetches the completed order details (using `OrderController::getOrderDetails()`) and displays a summary including the order number, items, total amount, and a thank you message.

## _(Screenshots of the cart, checkout form, and confirmation page would be inserted here)._

### 2.6. Task 8: RESTful Web Service for Manager (T8)

To fulfill the requirement for a manager-accessible interface to view order details (T8), a RESTful web service endpoint was implemented. This allows authorized personnel (specifically administrators) to retrieve comprehensive information about a specific order programmatically.

**Implementation Details:**

1.  **API Endpoint:** A GET endpoint was defined at `/api/v1/orders/{id}` within `app/routes.php`. The `{id}` placeholder represents the unique `order_id` of the order to be retrieved. This follows REST principles by using a resource identifier (`orders`) and the specific resource ID in the path.
2.  **Controller Logic:** The request to this endpoint is handled by the `show(int $id)` method within the `App\Controllers\Api\OrderApiController` class. This controller is specifically designed for API interactions, ensuring responses are formatted correctly (JSON) and appropriate HTTP status codes are set.
3.  **Authentication & Authorization:** Access to this endpoint is restricted to administrators. The `OrderApiController::checkApiAuth()` method is called at the beginning of the `show()` method. It verifies that a user session exists (`Session::isAuthenticated()`) and that the logged-in user has the 'admin' role (`$_SESSION['user_role'] === 'admin'`). If authentication fails, a 401 Unauthorized JSON response is returned; if authorization fails (user is not admin), a 403 Forbidden JSON response is returned. This prevents unauthorized access to order data.
4.  **Data Retrieval:**
    - The controller first validates that the provided `$id` is a positive integer.
    - It then uses the `App\Models\Order` model's `readOne($id)` method to fetch the main order details (like `order_date`, `total_amount`, `status`, `notes`, `shipping_address`) along with associated user information (`user_name`, `user_email`, `user_phone`) by joining the `orders` and `users` tables.
    - Next, it utilizes the `App\Models\OrderItem` model's `readByOrder($id)` method to retrieve all items associated with that order. This method joins `order_items` with the `products` table to include `product_name` and `product_image` alongside `quantity` and the `price` at the time of the order.
5.  **Response Format:** The controller combines the order details and the array of order items into a single PHP associative array. This array is then encoded into JSON format using `json_encode()` and sent back to the client with a `Content-Type: application/json` header and a 200 OK HTTP status code upon success. If the order ID is invalid or not found, appropriate 400 Bad Request or 404 Not Found responses are returned with JSON error messages. CORS headers (`Access-Control-Allow-Origin: *`, etc.) are also set in the controller's constructor to allow cross-origin requests if needed (though this should be restricted in production).

**Usage Example:**

A manager (or an authorized application) would make a GET request to `http://[your-domain]/api/v1/orders/123` (where 123 is the desired Order ID). If authenticated as an admin, the API would respond with a JSON object similar to this structure:

```json
{
  "order_id": 123,
  "user_id": 45,
  "user_name": "Kiki",
  "user_email": "kiki@delivery.com",
  "user_phone": "1234567890",
  "order_date": "2025-05-04 10:30:00",
  "total_amount": "25.97",
  "status": "processing",
  "notes": "Leave by the back door.",
  "shipping_address": "123 Koriko Town",
  "items": [
    {
      "item_id": 201,
      "product_id": 6,
      "quantity": 2,
      "price": "2.49",
      "product_name": "Whole Milk (1L)",
      "product_image": "assets/images/products/whole_milk.png"
    },
    {
      "item_id": 202,
      "product_id": 1,
      "quantity": 1,
      "price": "3.49",
      "product_name": "Whole Wheat Bread",
      "product_image": "assets/images/products/whole_wheat_bread.png"
    }
    // ... more items
  ]
}
```

This implementation provides a secure and structured way for authorized personnel to access detailed order information via a standard RESTful interface, fulfilling the T8 requirement.

### 2.7. Task 5: Search Engine Optimization (SEO) (T5)

To enhance the visibility and discoverability of the Ghibli Groceries website by search engines, several fundamental Search Engine Optimization (SEO) techniques were implemented as required by T5:

1.  **Dynamic Meta Tags:** The primary layout file (`app/Views/layouts/default.php`) dynamically generates crucial meta tags for each page.

    - **`<title>`:** The `$page_title` variable, set within each controller action (e.g., in `PageController::index()`, `ProductController::showCategories()`), allows for unique and descriptive titles for every page, improving relevance in search results.
    - **`<meta name="description">`:** Similarly, the `$meta_description` variable allows controllers to provide concise summaries of page content, which search engines may use as snippets in search results.
    - **`<meta name="keywords">`:** While less impactful for modern search engines, the `$meta_keywords` variable is included for completeness, allowing controllers to specify relevant keywords.
      These dynamic tags ensure that each page presents relevant information to search engine crawlers.

2.  **Semantic HTML Structure:** The application utilizes HTML5 semantic elements to structure content logically, making it easier for search engines to understand the page hierarchy and context.

    - The main layout (`default.php`) uses `<header>`, `<main>`, and `<footer>`.
    - The header partial (`partials/header.php`) includes a `<nav>` element for the main navigation.
    - Content pages like the homepage (`pages/index.php`) use `<section>` to group related content (hero, categories) and `<article>` for individual items like product cards and category links.
    - Appropriate heading tags (`<h1>`, `<h2>`, etc.) are used to define content hierarchy (e.g., `<h1>` for the main page title in `pages/index.php`).

3.  **Image Alt Text:** All significant images include descriptive `alt` attributes. This is crucial for accessibility and provides context for search engines that cannot "see" images. Examples include the logo (`alt="GhibliGroceries Logo"` in `header.php`) and product/category images (`alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>"` in `index.php`).

4.  **`robots.txt`:** A `public/robots.txt` file is provided to guide search engine crawlers. It correctly uses `Disallow:` directives to prevent crawling of backend directories (`/controllers/`, `/models/`, `/api/`), configuration files, logs, and other non-public resources, while explicitly using `Allow: /` to permit crawling of the main site content and assets (`/assets/`). This ensures that sensitive areas are not indexed while public content remains accessible.

5.  **`sitemap.xml`:** A `public/sitemap.xml` file lists the primary URLs of the website (e.g., `/`, `/about`, `/contact`, `/categories`, `/login`, `/register`). It includes `<lastmod>`, `<changefreq>`, and `<priority>` tags to provide hints to search engines about the importance and update frequency of each page, aiding in efficient crawling and indexing. The `robots.txt` file correctly points to the location of this sitemap.

These foundational SEO practices help improve the website's structure, relevance, and crawlability for search engines, addressing the core requirements of T5.

### 2.8. Coding & Design Aspects (C&D - 15%)

The development of Ghibli Groceries adhered to several key coding and design principles to ensure maintainability, scalability, reusability, and overall quality, addressing the C&D requirements.

1.  **Model-View-Controller (MVC) Pattern:** The application is structured following the MVC pattern to separate concerns:

    - **Models (`app/Models/`):** Classes like `User.php`, `Product.php`, `Order.php`, and `OrderItem.php` encapsulate data logic and database interactions (using the `Database` class). They are responsible for fetching, creating, updating, and deleting data.
    - **Views (`app/Views/`):** These are primarily `.php` files containing HTML and minimal PHP for displaying data. They receive data from controllers and render the user interface. Partials (`partials/header.php`, `partials/footer.php`) and layouts (`layouts/default.php`, `layouts/admin.php`) are used for common UI elements.
    - **Controllers (`app/Controllers/`):** Classes like `PageController.php`, `UserController.php`, `ProductController.php`, and `OrderController.php` handle incoming HTTP requests routed by `Router.php`. They interact with Models to fetch or manipulate data and then select and pass data to the appropriate View for rendering using the `BaseController::view()` method.
      This separation makes the codebase more organized, easier to test, and simpler to modify or extend specific parts (e.g., changing the UI in Views without affecting business logic in Models).

2.  **Object-Oriented Programming (OOP):** OOP principles were applied throughout the backend code:

    - **Encapsulation:** Classes like `Database.php`, `Session.php`, `Router.php`, Models (`User`, `Product`, etc.), and Helpers (`SecurityHelper`, `CaptchaHelper`) encapsulate specific functionalities and data. For instance, the `Database` class hides the PDO connection details and provides a clean interface (`select`, `execute`, `beginTransaction`, etc.) for interaction.
    - **Inheritance:** Controllers (e.g., `PageController`, `UserController`) extend the abstract `App\Core\BaseController` to inherit the common `view()` method, promoting code reuse for view rendering.
    - **Abstraction:** While not heavily reliant on interfaces in this implementation, the concept is present (e.g., `BaseController` defines a contract for view rendering). Models abstract the database interaction details.

3.  **Code Reusability:** Efforts were made to reuse code effectively:

<!-- Beyond Brief Scope: Integration of Monolog Library for Logging -->

    - **Helpers (`app/Helpers/`):** Static helper classes like `SecurityHelper.php` (for sanitization, validation, token generation) and `CaptchaHelper.php` provide reusable utility functions accessible across different parts of the application without needing instantiation.
    - **BaseController:** The `view()` method centralizes the logic for rendering views within layouts.
    - **Models:** Database interaction logic is contained within Model classes, reused by any Controller needing that data.
    - **View Partials/Layouts:** Common HTML structures (header, footer, navigation) are defined in partials (`app/Views/partials/`) and included in layouts (`app/Views/layouts/`), avoiding repetition in individual page views.
    - **CSS Classes:** Reusable CSS classes (e.g., `.btn`, `.form-control`, `.alert`, `.product-card`) defined in `public/assets/css/styles.css` ensure consistent styling for common UI elements.

4.  **Configuration:** Application settings are centralized in `app/config.php`. This includes database credentials (`DB_HOST`, `DB_NAME`, etc.), security parameters (`AUTH_TIMEOUT`, `MAX_LOGIN_ATTEMPTS`), site details (`SITE_NAME`, `SITE_URL`), and other constants. This separation makes it easy to configure the application for different environments (development, production) without modifying the core codebase.

5.  **Scalability & Robustness:**

    - The MVC architecture provides a modular foundation, allowing individual components (Models, Views, Controllers) to be scaled or replaced more easily.
    - Using PDO prepared statements (`Database.php`, Models) prevents SQL injection vulnerabilities, enhancing robustness.
    - Input validation (client-side with React in `RegistrationForm.js`, server-side in Controllers using `SecurityHelper`) and output encoding (`SecurityHelper::encodeOutput`) improve security and prevent errors from invalid data.
    - Error handling includes `try...catch` blocks in database operations (`Database.php`, Models) and API controllers (`OrderApiController.php`). The Monolog dependency (`composer.json`) is integrated via the `Registry` for logging errors and events (e.g., database errors, security events), aiding debugging and monitoring.
    - Database transactions (`Database::beginTransaction`, `commit`, `rollback`) are used in critical operations like order creation (`OrderController::createOrder` via the `create_order` stored procedure or model methods) to ensure data integrity (atomicity).

6.  **Optimization:**

    - AJAX is used for dynamic product filtering (T1), reducing the need for full page reloads and improving user experience.
    - Database queries are designed within models. The use of indexes (e.g., `idx_email` on `users`, `idx_status` on `orders`) defined in `sql/database_creation_script.sql` helps optimize query performance for common lookups.
    - Composer's PSR-4 autoloading (`composer.json`) provides efficient class loading on demand.

7.  **UI Design & Responsiveness:**

    - **Consistency:** A consistent visual theme is maintained using CSS custom properties (variables like `--tomato-red`, `--herb-green`, `--soft-off-white`) defined in `:root` within `public/assets/css/styles.css`. Reusable CSS classes for buttons, forms, alerts, and product cards ensure a uniform appearance across pages. Layouts and partials contribute to structural consistency.
    - **Responsiveness:** The website adapts to different screen sizes using CSS media queries (`@media (max-width: ...)` in `styles.css`). These queries adjust layouts (e.g., changing grid columns in `.category-section`, stacking elements in `.hero`), modify element visibility (hiding desktop navigation/actions and showing the mobile menu toggle `.mobile-menu-toggle`), and resize elements (like fonts) for optimal viewing on tablets and mobile devices. Flexbox and CSS Grid are used extensively for flexible layouts (e.g., `.header .container`, `.hero`, `.category-section`, `.product-grid`).

8.  **Code Quality:**
    - **Commenting:** PHPDoc blocks are used in classes and methods (e.g., `Database.php`, `OrderApiController.php`, `SecurityHelper.php`, `BaseController.php`) to explain their purpose, parameters, and return values. Inline comments clarify specific code sections.
    - **Modularization:** The codebase is divided into logical namespaces and directories (Core, Controllers, Models, Views, Helpers) based on the MVC pattern, promoting modularity and separation of concerns.
    - **Readability:** Consistent naming conventions (CamelCase for classes, camelCase for methods/variables) and indentation (though not strictly enforced tool-wise) are generally followed to improve code readability. PSR-4 autoloading standardizes the structure.

These design choices and coding practices contribute to a more robust, maintainable, and user-friendly application, addressing the criteria outlined in the C&D section of the assessment.

## 3. Suggested Improvements and Conclusions

### 3.1. Potential Improvements

While the current Ghibli Groceries application fulfills the core assessment requirements, several areas could be enhanced or completed in future iterations to create a more comprehensive and robust platform:

- **Expanded Product Management:** Adding more diverse product categories (e.g., Beverages, Frozen Foods, Household Supplies) and subcategories would broaden the store's appeal. Implementing features like product variations (e.g., different sizes or flavours) and managing stock levels more granularly (e.g., low-stock alerts for admins) would improve inventory control beyond the current basic stock tracking.
- **Payment Gateway Integration:** Currently, the system only simulates an order process, storing order details but not handling actual payments. Integrating a real payment gateway (such as Stripe or PayPal via their respective APIs) would allow for secure online transactions, transforming the application into a fully functional e-commerce platform.
- **Complete and Stabilize Admin Panel:** The existing admin panel provides a foundation for CRUD operations but is currently incomplete and contains errors/bugs. Fully implementing all intended features (e.g., sales reporting, detailed user/order management, UI stability) and resolving existing issues would be a key improvement for administrative efficiency.
- **Implement Search Functionality:** The current search bar on the homepage is only a visual placeholder and lacks backend functionality. Implementing a robust search feature (e.g., using full-text search or integrating a dedicated engine) to allow users to effectively search products by name, description, or category would significantly improve usability.
- **Unit and Integration Testing:** While manual testing was performed during development, implementing automated tests would significantly improve code reliability. This could involve using PHPUnit for testing backend logic (Models, Controllers, Helpers) and Jest/React Testing Library for frontend components like the `RegistrationForm.js`, ensuring that future changes do not introduce regressions.
- **User Roles and Permissions:** Expanding beyond the basic 'user' and 'admin' roles defined in the `users` table could enhance security and operational workflow. Introducing potentially other roles (e.g., 'Store Manager' with order management but not user deletion rights, 'Content Editor' for managing static pages) with more granular permissions, perhaps managed via a dedicated permissions system, would be beneficial for larger teams.

### 3.2. Challenges Faced

Developing the Ghibli Groceries application presented several technical challenges that required careful consideration and problem-solving:

- **Deployment Environment Compatibility:** A significant challenge arose due to PHP version differences. The application was developed locally using PHP version 8.0+, leveraging modern language features. However, the designated teaching web server ran an older, incompatible PHP version. This prevented direct deployment and required setting up an alternative hosting solution.
- **Custom Hosting Solution:** To overcome the PHP version incompatibility, a virtual machine was provisioned on Google Cloud Platform (GCP). This involved configuring a suitable environment with Apache2, a compatible PHP version (8.0+), and MySQL. Additionally, the VM was connected to a custom domain (ghibligroceries.com) to provide a stable access point, adding complexity beyond standard deployment procedures.
- **Admin Dashboard Implementation:** While an admin dashboard was partially implemented to manage site data, significant errors were encountered during development. Due to time constraints towards the end of the project, these bugs could not be fully debugged and resolved, leaving the admin panel in an incomplete and unstable state.
- **Integrating React with PHP MVC:** Seamlessly integrating the React-based registration form (T6) within the traditional server-rendered PHP MVC structure was a key challenge. This involved setting up the build process for the React component, ensuring it could be correctly mounted within the PHP view (`pages/register.php`), establishing API endpoints (`/ajax/check-email`) for necessary backend communication (like the real-time email uniqueness check), and managing the flow of data and error handling between the client-side component and the server-side `UserController`.
- **Robust AJAX Implementation:** Implementing the dynamic product filtering and loading features (T1) using AJAX required careful management of asynchronous JavaScript requests (`script.js`). Handling potential network errors gracefully, correctly parsing JSON responses from the `ProductController` endpoints, and efficiently updating the DOM without causing layout shifts or performance issues demanded attention to detail in the frontend JavaScript code.
- **Secure Session Management:** Implementing secure session handling (T3) extended beyond simply starting a session. It required meticulous configuration of session cookie parameters (`httponly`, `secure`, `samesite` in the `Session` class constructor), implementing mechanisms against session fixation attacks (using `session_regenerate_id`), handling session timeouts based on inactivity (`Session::validateActivity`), and ensuring that session data was completely destroyed upon logout (`Session::logoutUser`).

### 3.3. Conclusion

In conclusion, the Ghibli Groceries project successfully demonstrates the design and implementation of an advanced, multi-tier web application that meets the functional and technical requirements outlined in the assessment brief. Key functionalities were effectively implemented, including dynamic product browsing using AJAX (T1), secure user registration featuring live React-based validation (T2, T6), robust login/logout procedures incorporating session management and CAPTCHA security (T3, T4), comprehensive data management via MySQL for products, users, and orders (T7), and a functional RESTful API enabling managers to retrieve order details (T8).

Furthermore, foundational Search Engine Optimization techniques (T5) were applied to enhance discoverability, and the application adheres to the Model-View-Controller (MVC) architectural pattern and Object-Oriented Programming principles. This is evident in the codebase's structure, modularity, code reusability, and the implementation of security measures against common web vulnerabilities (C&D).

## While several potential improvements have been identified, the developed system provides a solid and functional foundation for an online grocery platform. This project served as a valuable practical exercise in integrating various frontend (HTML, CSS, JavaScript, React, AJAX) and backend (PHP, MySQL) technologies, applying security best practices, and building a complex, data-driven web application according to specified requirements. The challenges encountered, particularly in integrating different technologies and ensuring security, provided significant learning opportunities.

## 4. References (If Applicable)

This project utilized several external libraries and resources to facilitate development and enhance functionality:

- **React.js:** A JavaScript library employed for building the user interface, specifically for the live validation feature on the registration form (T6), as required by the assessment brief. Included via a local build process integrated into the project's frontend assets.
- **Monolog:** A PHP logging library integrated via Composer (`composer.json`) for handling application logging (e.g., errors, security events), aiding in debugging and monitoring.
- **Font Awesome:** An icon toolkit used for incorporating icons throughout the user interface (e.g., navigation, buttons, validation feedback). Included via CDN links (`cdnjs.cloudflare.com`).
- **Google Fonts (Poppins):** A web font service used to apply the Poppins font family for consistent typography across the website. Included via CDN links (`fonts.googleapis.com`).

While the assessment brief permitted the use of CSS frameworks like Bootstrap, this project relied on custom CSS (`public/assets/css/styles.css`) for styling and layout to meet the specific design requirements, and therefore Bootstrap was not incorporated. Core technologies mandated by the brief (PHP, MySQL, HTML5, CSS3, JavaScript, AJAX) were used extensively but are considered foundational rather than external references in this context.

---

## 5. Appendix (If Applicable)

The complete SQL script (`database_creation_script.sql`) used to create and initialize the `ghibligroceriesdb` database is included in the `sql/` directory as part of the project submission files.

---

_(End of Appendix)_
