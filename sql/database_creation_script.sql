-- =====================================================================
-- ADVANCED WEB TECHNOLOGIES - GROCERY STORE DATABASE CREATION SCRIPT
-- =====================================================================
-- This script creates and initializes the complete database schema for 
-- the Grocery Store web application, including tables, relationships, 
-- triggers, stored procedures, and sample data.
-- =====================================================================
-- ---------------------------------------------------------------------
-- STEP 0: DATABASE INITIALIZATION
-- ---------------------------------------------------------------------
-- First, drop any existing database to ensure a clean setup
DROP DATABASE IF EXISTS ghibligroceriesdb;

-- Create the new database
CREATE DATABASE IF NOT EXISTS ghibligroceriesdb;

-- Select the database for use
USE ghibligroceriesdb;

-- ---------------------------------------------------------------------
-- STEP 1: CORE APPLICATION TABLES
-- ---------------------------------------------------------------------
-- ---------------------------------------------------------------------
-- Users Table: Stores customer account information with security features
-- This table fulfills the registration requirements in tasks T2, T3, and T4
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each user
    name VARCHAR(100) NOT NULL,
    -- User's full name as required in T3
    phone VARCHAR(15) NOT NULL,
    -- Phone number as required in T3
    email VARCHAR(100) NOT NULL UNIQUE,
    -- Email (unique identifier) as required in T3
    password VARCHAR(255) NOT NULL,
    -- Bcrypt hashed password (60 chars) for security as in T4
    role VARCHAR(20) NOT NULL DEFAULT 'customer',
    -- User role (customer, admin, etc.)
    account_status ENUM('active', 'inactive', 'locked') DEFAULT 'active',
    -- Account status for security
    failed_login_attempts INT DEFAULT 0,
    -- Tracks failed login attempts for security as in T4
    last_login_date DATETIME,
    -- Records last successful login time
    password_changed_date DATETIME,
    -- Tracks when password was last changed
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When user registered
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Auto-update timestamp
    verification_token VARCHAR(64),
    -- For email verification if implemented
    reset_token VARCHAR(64),
    -- For password reset functionality
    reset_token_expires DATETIME,
    -- Expiration for reset token
    INDEX idx_email (email),
    -- Index on email for faster login queries
    INDEX idx_role (role) -- Index on role for faster role-based lookups
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- Categories Table: Stores product categories (e.g., Vegetables, Meat)
-- This supports the hierarchical dropdown menus required in task T1
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each category
    category_name VARCHAR(50) NOT NULL,
    -- Name of the category
    parent_id INT NULL,
    -- Self-referencing FK for nested categories
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE CASCADE -- Nested structure
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- Products Table: Stores grocery product information
-- This supports product browsing functionality in task T1
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each product
    category_id INT NOT NULL,
    -- Foreign key to the category table
    name VARCHAR(100) NOT NULL,
    -- Product name
    price DECIMAL(10, 2) NOT NULL,
    -- Product price with 2 decimal places
    image_path VARCHAR(255) NOT NULL,
    -- Path to product image as required in T1
    description TEXT,
    -- Detailed product description
    stock_quantity INT NOT NULL DEFAULT 100,
    -- Available quantity in stock
    low_stock_threshold INT NOT NULL DEFAULT 10,
    -- Threshold for low stock alerts
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    -- Whether product is available for purchase
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE -- Category relationship
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- Orders Table: Stores customer orders
-- This supports order placement functionality in task T7
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each order
    user_id INT NOT NULL,
    -- Foreign key to users table
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When order was placed
    total_amount DECIMAL(10, 2) NOT NULL,
    -- Total order amount
    status ENUM(
        -- Order status tracking
        'pending',
        'processing',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',
    shipping_address TEXT,
    -- Delivery address information
    payment_method VARCHAR(50),
    -- Payment method used
    notes TEXT,
    -- Any additional order notes
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Auto-update timestamp
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    -- User relationship
    INDEX idx_user_id (user_id),
    -- Index for faster queries by user
    INDEX idx_status (status),
    -- Index for faster status filtering
    INDEX idx_order_date (order_date) -- Index for date-based queries
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- Order_Items Table: Stores individual items within an order
-- Maps the many-to-many relationship between orders and products
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each order item
    order_id INT NOT NULL,
    -- Foreign key to orders table
    product_id INT NOT NULL,
    -- Foreign key to products table
    quantity INT NOT NULL,
    -- Quantity ordered
    price DECIMAL(10, 2) NOT NULL,
    -- Price at time of order (may differ from current price)
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    -- Order relationship
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
    -- Product relationship
    INDEX idx_order_id (order_id),
    -- Index for faster order lookups
    INDEX idx_product_id (product_id) -- Index for faster product lookups
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- STEP 2: SECURITY TABLES
-- These tables support security features required in task T4
-- ---------------------------------------------------------------------
-- ---------------------------------------------------------------------
-- Login_Attempts Table: Records all login attempts for security monitoring
-- Helps prevent brute force attacks
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each attempt
    email VARCHAR(100),
    -- Email used in attempt
    ip_address VARCHAR(45) NOT NULL,
    -- IP address of the attempt
    user_agent VARCHAR(255),
    -- Browser/device information
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When attempt occurred
    success BOOLEAN DEFAULT FALSE,
    -- Whether login was successful
    INDEX idx_email (email),
    -- Index for faster email lookups
    INDEX idx_ip_address (ip_address),
    -- Index for faster IP lookups
    INDEX idx_attempt_time (attempt_time) -- Index for time-based queries
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- Security_Logs Table: Logs security events for audit purposes
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS security_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each log entry
    user_id INT,
    -- User related to the event (if applicable)
    event_type VARCHAR(50) NOT NULL,
    -- Type of security event
    event_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When event occurred
    ip_address VARCHAR(45),
    -- IP address related to event
    description TEXT,
    -- Detailed description of the event
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE
    SET
        NULL -- User relationship
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- User_Sessions Table: Tracks active user sessions
-- Helps with advanced session management beyond PHP's default
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    -- PHP session ID as primary key
    user_id INT NOT NULL,
    -- User to whom session belongs
    ip_address VARCHAR(45) NOT NULL,
    -- IP address of the session
    user_agent VARCHAR(255),
    -- Browser/device information
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When session started
    expires_at DATETIME NOT NULL,
    -- When session expires
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Last activity time
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    -- User relationship
    INDEX idx_user_id (user_id),
    -- Index for faster user lookups
    INDEX idx_expires_at (expires_at) -- Index for expiration queries
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- Password_History Table: Stores password history to prevent reuse
-- Enhances password security as part of task T4
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each history entry
    user_id INT NOT NULL,
    -- User whose password is stored
    password_hash VARCHAR(255) NOT NULL,
    -- Hashed previous password
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When password was changed
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    -- User relationship
    INDEX idx_user_id (user_id) -- Index for faster user lookups
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- STEP 3: ORDER MANAGEMENT AND INVENTORY TABLES
-- These tables support advanced inventory and order management
-- ---------------------------------------------------------------------
-- ---------------------------------------------------------------------
-- Inventory_Logs Table: Tracks inventory changes (stock movements)
-- Supports RESTful API for managers in task T8
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each log
    product_id INT NOT NULL,
    -- Product being affected
    event_type ENUM('order', 'restock', 'adjustment', 'low_stock') NOT NULL,
    -- Type of inventory event
    quantity INT NOT NULL,
    -- Quantity changed
    before_quantity INT,
    -- Quantity before change
    after_quantity INT,
    -- Quantity after change
    order_id INT,
    -- Related order (if applicable)
    user_id INT,
    -- User who made the change (if applicable)
    low_stock_threshold INT,
    -- Threshold at time of change (if applicable)
    description TEXT,
    -- Description of the change
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When change occurred
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    -- Product relationship
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE
    SET
        NULL,
        -- Order relationship
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE
    SET
        NULL,
        -- User relationship
        INDEX idx_product_id (product_id),
        -- Index for faster product lookups
        INDEX idx_event_type (event_type),
        -- Index for event type filtering
        INDEX idx_log_date (log_date) -- Index for date-based queries
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- Order_History Table: Tracks changes to order status
-- Provides audit trail for order processing
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Unique identifier for each history entry
    order_id INT NOT NULL,
    -- Order being tracked
    status VARCHAR(20) NOT NULL,
    -- Status at time of entry
    user_id INT,
    -- User who made the change (if applicable)
    notes TEXT,
    -- Notes about the change
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- When change occurred
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    -- Order relationship
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE
    SET
        NULL,
        -- User relationship
        INDEX idx_order_id (order_id),
        -- Index for faster order lookups
        INDEX idx_change_date (change_date) -- Index for date-based queries
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------
-- STEP 4: TRIGGERS AND STORED PROCEDURES
-- Automation and business logic implementation
-- ---------------------------------------------------------------------
-- ---------------------------------------------------------------------
-- Password History Trigger: Automatically records password changes
-- Enhances security features for task T4
-- ---------------------------------------------------------------------
DELIMITER //
CREATE TRIGGER IF NOT EXISTS password_history_trigger
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Only record if password has changed
    IF OLD.password != NEW.password THEN
INSERT INTO
    password_history (user_id, password_hash)
VALUES
    (NEW.user_id, NEW.password);

END IF;

END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Order Status Change Trigger: Logs changes to order status
-- Supports order management functionality
-- ---------------------------------------------------------------------
DELIMITER //
CREATE TRIGGER IF NOT EXISTS order_status_change_trigger
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    -- Only record if status has changed
    IF OLD.status != NEW.status THEN
INSERT INTO
    order_history (order_id, status, user_id, notes)
VALUES
    (
        NEW.order_id,
        NEW.status,
        NULL,
        CONCAT(
            'Status changed from ',
            OLD.status,
            ' to ',
            NEW.status
        )
    );

END IF;

END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Inventory Order Trigger: Updates product stock when order is placed
-- Implements inventory management functionality
-- ---------------------------------------------------------------------
DELIMITER //
CREATE TRIGGER IF NOT EXISTS inventory_order_trigger
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;

DECLARE product_name VARCHAR(100);

-- Get current stock and product name
SELECT
    stock_quantity,
    name INTO current_stock,
    product_name
FROM
    products
WHERE
    product_id = NEW.product_id;

-- Calculate new stock level
SET
    current_stock = current_stock - NEW.quantity;

-- Update product stock
UPDATE
    products
SET
    stock_quantity = current_stock
WHERE
    product_id = NEW.product_id;

-- Log the inventory change
INSERT INTO
    inventory_logs (
        product_id,
        event_type,
        quantity,
        before_quantity,
        after_quantity,
        order_id,
        description
    )
VALUES
    (
        NEW.product_id,
        'order',
        NEW.quantity,
        current_stock + NEW.quantity,
        current_stock,
        NEW.order_id,
        CONCAT(
            'Order #',
            NEW.order_id,
            ': ',
            NEW.quantity,
            ' units of "',
            product_name,
            '" purchased'
        )
    );

END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Record Login Attempt Procedure: Tracks login success/failure
-- Implements security features for task T4
-- ---------------------------------------------------------------------
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS record_login_attempt(
    IN p_email VARCHAR(100),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent VARCHAR(255),
    IN p_success BOOLEAN
) BEGIN -- Record the login attempt
INSERT INTO
    login_attempts (email, ip_address, user_agent, success)
VALUES
    (p_email, p_ip_address, p_user_agent, p_success);

-- If successful login, reset failed attempts counter
IF p_success
AND p_email IS NOT NULL THEN
UPDATE
    users
SET
    failed_login_attempts = 0,
    last_login_date = NOW()
WHERE
    email = p_email;

-- If failed login, increment failed attempts and possibly lock account
ELSEIF NOT p_success
AND p_email IS NOT NULL THEN
UPDATE
    users
SET
    failed_login_attempts = failed_login_attempts + 1
WHERE
    email = p_email;

-- Lock account after 5 failed attempts (configurable in app/config.php)
UPDATE
    users
SET
    account_status = 'locked'
WHERE
    email = p_email
    AND failed_login_attempts >= 5;

END IF;

END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Log Security Event Procedure: Records security-related events
-- Enhances security audit trail for task T4
-- ---------------------------------------------------------------------
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS log_security_event(
    IN p_user_id INT,
    IN p_event_type VARCHAR(50),
    IN p_ip_address VARCHAR(45),
    IN p_description TEXT
) BEGIN
INSERT INTO
    security_logs (user_id, event_type, ip_address, description)
VALUES
    (
        p_user_id,
        p_event_type,
        p_ip_address,
        p_description
    );

END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Clean Expired Sessions Procedure: Removes outdated sessions
-- Enhances session security for task T3 and T4
-- ---------------------------------------------------------------------
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS clean_expired_sessions()
BEGIN
DELETE FROM
    user_sessions
WHERE
    expires_at < NOW();

END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- Create scheduled event to clean up expired sessions hourly
-- Automates security maintenance
-- ---------------------------------------------------------------------
CREATE EVENT IF NOT EXISTS clean_sessions_event ON SCHEDULE EVERY 1 HOUR DO CALL clean_expired_sessions();

-- ---------------------------------------------------------------------
-- Create Order Procedure: Comprehensive order processing logic
-- Supports order placement functionality in task T7
-- ---------------------------------------------------------------------
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS create_order(
    IN p_user_id INT,
    -- User placing the order
    IN p_items JSON,
    -- JSON array of items (product_id, quantity)
    IN p_shipping_address TEXT,
    -- Delivery address
    IN p_payment_method VARCHAR(50),
    -- Method of payment
    IN p_notes TEXT,
    -- Additional order notes
    OUT p_order_id INT,
    -- Returns the created order ID
    OUT p_success BOOLEAN,
    -- Returns success status
    OUT p_message VARCHAR(255) -- Returns status message
) proc: BEGIN DECLARE v_total DECIMAL(10, 2) DEFAULT 0;

DECLARE v_item_count INT DEFAULT 0;

DECLARE v_product_id INT;

DECLARE v_quantity INT;

DECLARE v_price DECIMAL(10, 2);

DECLARE v_stock INT;

DECLARE v_product_name VARCHAR(100);

DECLARE i INT DEFAULT 0;

DECLARE v_items_length INT;

-- Declare error handler for transaction
DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK;

SET
    p_success = FALSE;

SET
    p_message = 'An error occurred while processing the order.';

END;

-- Start transaction to ensure data integrity
START TRANSACTION;

-- Validate user exists
IF NOT EXISTS (
    SELECT
        1
    FROM
        users
    WHERE
        user_id = p_user_id
) THEN
SET
    p_success = FALSE;

SET
    p_message = 'User not found.';

ROLLBACK;

LEAVE proc;

END IF;

-- Calculate total and validate items
SET
    v_items_length = JSON_LENGTH(p_items);

IF v_items_length = 0 THEN
SET
    p_success = FALSE;

SET
    p_message = 'No items in order.';

ROLLBACK;

LEAVE proc;

END IF;

-- Process each item in the order
WHILE i < v_items_length DO -- Extract item data from JSON
SET
    v_product_id = JSON_EXTRACT(p_items, CONCAT('$[', i, '].product_id'));

SET
    v_quantity = JSON_EXTRACT(p_items, CONCAT('$[', i, '].quantity'));

-- Check product exists and is active
SELECT
    stock_quantity,
    price,
    name,
    is_active INTO v_stock,
    v_price,
    v_product_name,
    @is_active
FROM
    products
WHERE
    product_id = v_product_id;

IF v_price IS NULL THEN
SET
    p_success = FALSE;

SET
    p_message = CONCAT('Product ID ', v_product_id, ' not found.');

ROLLBACK;

LEAVE proc;

END IF;

IF @is_active = 0 THEN
SET
    p_success = FALSE;

SET
    p_message = CONCAT(
        'Product "',
        v_product_name,
        '" is not available for purchase.'
    );

ROLLBACK;

LEAVE proc;

END IF;

-- Check stock availability
IF v_stock < v_quantity THEN
SET
    p_success = FALSE;

SET
    p_message = CONCAT(
        'Not enough stock for "',
        v_product_name,
        '". Available: ',
        v_stock
    );

ROLLBACK;

LEAVE proc;

END IF;

-- Add to order total
SET
    v_total = v_total + (v_price * v_quantity);

SET
    v_item_count = v_item_count + 1;

-- Move to next item
SET
    i = i + 1;

END WHILE;

-- Create the order record
INSERT INTO
    orders (
        user_id,
        total_amount,
        shipping_address,
        payment_method,
        notes
    )
VALUES
    (
        p_user_id,
        v_total,
        p_shipping_address,
        p_payment_method,
        p_notes
    );

-- Get the new order ID
SET
    p_order_id = LAST_INSERT_ID();

-- Reset counter for second pass
SET
    i = 0;

-- Add order items
WHILE i < v_items_length DO -- Extract item data again
SET
    v_product_id = JSON_EXTRACT(p_items, CONCAT('$[', i, '].product_id'));

SET
    v_quantity = JSON_EXTRACT(p_items, CONCAT('$[', i, '].quantity'));

-- Get current product price
SELECT
    price INTO v_price
FROM
    products
WHERE
    product_id = v_product_id;

-- Insert order item
INSERT INTO
    order_items (order_id, product_id, quantity, price)
VALUES
    (p_order_id, v_product_id, v_quantity, v_price);

-- Move to next item
SET
    i = i + 1;

END WHILE;

-- Commit transaction
COMMIT;

SET
    p_success = TRUE;

SET
    p_message = CONCAT(
        'Order created successfully with ',
        v_item_count,
        ' items.'
    );

END //
DELIMITER ;

-- ---------------------------------------------------------------------
-- STEP 5: SAMPLE DATA INSERTION
-- Provides initial data for testing and demonstration
-- ---------------------------------------------------------------------
-- ---------------------------------------------------------------------
-- Insert main product categories (Level 1)
-- ---------------------------------------------------------------------
INSERT INTO
    categories (category_id, category_name, parent_id)
VALUES
    (1, 'Baked Goods', NULL),
    -- ID 1: Baked goods category
    (2, 'Dairy Products', NULL),
    -- ID 2: Dairy products category
    (3, 'Fruits & Veggies', NULL),
    -- ID 3: Fruits and vegetables category
    (4, 'Meat', NULL),
    -- ID 4: Meat category
    (5, 'Spices & Seasonings', NULL);

-- ID 5: Spices and seasonings category
-- ---------------------------------------------------------------------
-- Insert sub-categories (Level 2)
-- ---------------------------------------------------------------------
INSERT INTO
    categories (category_id, category_name, parent_id)
VALUES
    -- Baked Goods Sub-categories
    (6, 'Bread', 1),
    -- ID 6: Bread subcategory
    (7, 'Pastries & Sweets', 1),
    -- ID 7: Pastries subcategory
    -- Dairy Products Sub-categories
    (8, 'Milk & Cream', 2),
    -- ID 8: Milk subcategory
    (9, 'Cheese & Yogurt', 2),
    -- ID 9: Cheese subcategory
    (10, 'Butter', 2),
    -- ID 10: Butter subcategory
    -- Fruits & Veggies Sub-categories
    (11, 'Fruits', 3),
    -- ID 11: Fruits subcategory
    (12, 'Vegetables', 3),
    -- ID 12: Vegetables subcategory
    -- Meat Sub-categories
    (13, 'Poultry', 4),
    -- ID 13: Poultry subcategory
    (14, 'Beef & Pork', 4),
    -- ID 14: Beef and pork subcategory
    (15, 'Fish', 4),
    -- ID 15: Fish subcategory
    -- Spices & Seasonings Sub-categories
    (16, 'Ground Spices', 5),
    -- ID 16: Ground spices subcategory
    (17, 'Whole Spices & Herbs', 5);

-- ID 17: Whole spices subcategory
-- ---------------------------------------------------------------------
-- Insert products for baked goods category (Using sub-category IDs)
-- ---------------------------------------------------------------------
INSERT INTO
    products (
        product_id,
        category_id,
        name,
        price,
        image_path,
        description,
        stock_quantity
    )
VALUES
    (
        1,
        -- Product ID 1
        6,
        -- Category: Bread (ID 6)
        'Whole Wheat Bread',
        -- Product name
        3.49,
        -- Price: $3.49
        'assets/images/products/whole_wheat_bread.png',
        -- Image path
        'Freshly baked whole wheat bread, perfect for sandwiches.',
        -- Description
        150 -- Initial stock quantity: 150 units
    ),
    (
        2,
        -- Product ID 2
        6,
        -- Category: Bread (ID 6)
        'French Baguette',
        -- Product name
        2.99,
        -- Price: $2.99
        'assets/images/products/french_baguette.png',
        -- Image path
        'Authentic French baguette with crispy crust and soft interior.',
        -- Description
        120 -- Initial stock quantity: 120 units
    ),
    (
        3,
        -- Product ID 3
        7,
        -- Category: Pastries & Sweets (ID 7)
        'Chocolate Chip Cookies (Pack of 12)',
        -- Product name
        4.99,
        -- Price: $4.99
        'assets/images/products/chocolate_chip_cookies.png',
        -- Image path
        'Homemade style chocolate chip cookies, soft and chewy.',
        -- Description
        100 -- Initial stock quantity: 100 units
    ),
    (
        4,
        -- Product ID 4
        7,
        -- Category: Pastries & Sweets (ID 7)
        'Blueberry Muffins (Pack of 6)',
        -- Product name
        5.99,
        -- Price: $5.99
        'assets/images/products/blueberry_muffins.png',
        -- Image path
        'Fluffy muffins loaded with juicy blueberries.',
        -- Description
        80 -- Initial stock quantity: 80 units
    ),
    (
        5,
        -- Product ID 5
        7,
        -- Category: Pastries & Sweets (ID 7)
        'Croissants (Pack of 4)',
        -- Product name
        6.49,
        -- Price: $6.49
        'assets/images/products/croissants.png',
        -- Image path
        'Buttery, flaky croissants baked to golden perfection.',
        -- Description
        90 -- Initial stock quantity: 90 units
    );

-- ---------------------------------------------------------------------
-- Insert products for dairy products category
-- ---------------------------------------------------------------------
INSERT INTO
    products (
        product_id,
        category_id,
        name,
        price,
        image_path,
        description,
        stock_quantity
    )
VALUES
    (
        6,
        -- Product ID 6
        8,
        -- Category: Milk & Cream (ID 8)
        'Whole Milk (1L)',
        -- Product name
        2.49,
        -- Price: $2.49
        'assets/images/products/whole_milk.png',
        -- Image path
        'Fresh whole milk from local farms.',
        -- Description
        200 -- Initial stock quantity: 200 units
    ),
    (
        7,
        -- Product ID 7
        9,
        -- Category: Cheese & Yogurt (ID 9)
        'Cheddar Cheese (250g)',
        -- Product name
        4.99,
        -- Price: $4.99
        'assets/images/products/cheddar_cheese.png',
        -- Image path
        'Sharp cheddar cheese, perfect for sandwiches and cooking.',
        -- Description
        150 -- Initial stock quantity: 150 units
    ),
    (
        8,
        -- Product ID 8
        9,
        -- Category: Cheese & Yogurt (ID 9)
        'Greek Yogurt (500g)',
        -- Product name
        3.99,
        -- Price: $3.99
        'assets/images/products/greek_yogurt.png',
        -- Image path
        'Creamy Greek yogurt, high in protein.',
        -- Description
        120 -- Initial stock quantity: 120 units
    ),
    (
        9,
        -- Product ID 9
        10,
        -- Category: Butter (ID 10)
        'Butter (250g)',
        -- Product name
        3.49,
        -- Price: $3.49
        'assets/images/products/butter.png',
        -- Image path
        'Pure butter made from pasteurized cream.',
        -- Description
        180 -- Initial stock quantity: 180 units
    ),
    (
        10,
        -- Product ID 10
        8,
        -- Category: Milk & Cream (ID 8)
        'Sour Cream (300g)',
        -- Product name
        2.79,
        -- Price: $2.79
        'assets/images/products/sour_cream.png',
        -- Image path
        'Rich and tangy sour cream, perfect for dips and toppings.',
        -- Description
        100 -- Initial stock quantity: 100 units
    );

-- ---------------------------------------------------------------------
-- Insert products for fruits and vegetables category
-- ---------------------------------------------------------------------
INSERT INTO
    products (
        product_id,
        category_id,
        name,
        price,
        image_path,
        description,
        stock_quantity
    )
VALUES
    (
        11,
        -- Product ID 11
        11,
        -- Category: Fruits (ID 11)
        'Bananas (1kg)',
        -- Product name
        1.99,
        -- Price: $1.99
        'assets/images/products/bananas.png',
        -- Image path
        'Fresh yellow bananas, rich in potassium.',
        -- Description
        250 -- Initial stock quantity: 250 units
    ),
    (
        12,
        -- Product ID 12
        12,
        -- Category: Vegetables (ID 12)
        'Broccoli (each)',
        -- Product name
        2.29,
        -- Price: $2.29
        'assets/images/products/broccoli.png',
        -- Image path
        'Crisp, fresh broccoli heads, locally sourced.',
        -- Description
        150 -- Initial stock quantity: 150 units
    ),
    (
        13,
        -- Product ID 13
        11,
        -- Category: Fruits (ID 11)
        'Avocado (each)',
        -- Product name
        1.79,
        -- Price: $1.79
        'assets/images/products/avocado.png',
        -- Image path
        'Ripe avocados, perfect for guacamole or toast.',
        -- Description
        120 -- Initial stock quantity: 120 units
    ),
    (
        14,
        -- Product ID 14
        12,
        -- Category: Vegetables (ID 12)
        'Spinach (300g)',
        -- Product name
        2.99,
        -- Price: $2.99
        'assets/images/products/spinach.png',
        -- Image path
        'Fresh spinach leaves, washed and ready to use.',
        -- Description
        100 -- Initial stock quantity: 100 units
    ),
    (
        15,
        -- Product ID 15
        11,
        -- Category: Fruits (ID 11)
        'Red Apples (1kg)',
        -- Product name
        3.49,
        -- Price: $3.49
        'assets/images/products/red_apples.png',
        -- Image path
        'Crisp and sweet red apples, perfect for snacking.',
        -- Description
        180 -- Initial stock quantity: 180 units
    );

-- ---------------------------------------------------------------------
-- Insert products for meat category
-- ---------------------------------------------------------------------
INSERT INTO
    products (
        product_id,
        category_id,
        name,
        price,
        image_path,
        description,
        stock_quantity
    )
VALUES
    (
        16,
        -- Product ID 16
        13,
        -- Category: Poultry (ID 13)
        'Chicken Breast (500g)',
        -- Product name
        7.99,
        -- Price: $7.99
        'assets/images/products/chicken_breast.png',
        -- Image path
        'Boneless, skinless chicken breast, high in protein.',
        -- Description
        150 -- Initial stock quantity: 150 units
    ),
    (
        17,
        -- Product ID 17
        14,
        -- Category: Beef & Pork (ID 14)
        'Ground Beef (500g)',
        -- Product name
        6.99,
        -- Price: $6.99
        'assets/images/products/ground_beef.png',
        -- Image path
        'Lean ground beef, ideal for burgers and pasta dishes.',
        -- Description
        130 -- Initial stock quantity: 130 units
    ),
    (
        18,
        -- Product ID 18
        15,
        -- Category: Fish (ID 15)
        'Salmon Fillet (300g)',
        -- Product name
        9.99,
        -- Price: $9.99
        'assets/images/products/salmon_fillet.png',
        -- Image path
        'Fresh Atlantic salmon fillet, rich in omega-3.',
        -- Description
        80 -- Initial stock quantity: 80 units
    ),
    (
        19,
        -- Product ID 19
        14,
        -- Category: Beef & Pork (ID 14)
        'Pork Chops (500g)',
        -- Product name
        8.49,
        -- Price: $8.49
        'assets/images/products/pork_chops.png',
        -- Image path
        'Tender pork chops, perfect for grilling or roasting.',
        -- Description
        100 -- Initial stock quantity: 100 units
    ),
    (
        20,
        -- Product ID 20
        13,
        -- Category: Poultry (ID 13)
        'Turkey Breast (400g)',
        -- Product name
        7.49,
        -- Price: $7.49
        'assets/images/products/turkey_breast.png',
        -- Image path
        'Lean turkey breast, low in fat and high in protein.',
        -- Description
        90 -- Initial stock quantity: 90 units
    );

-- ---------------------------------------------------------------------
-- Insert products for spices and seasonings category
-- ---------------------------------------------------------------------
INSERT INTO
    products (
        product_id,
        category_id,
        name,
        price,
        image_path,
        description,
        stock_quantity
    )
VALUES
    (
        21,
        -- Product ID 21
        16,
        -- Category: Ground Spices (ID 16)
        'Ground Cinnamon (50g)',
        -- Product name
        2.99,
        -- Price: $2.99
        'assets/images/products/ground_cinnamon.png',
        -- Image path
        'Aromatic ground cinnamon, perfect for baking and beverages.',
        -- Description
        150 -- Initial stock quantity: 150 units
    ),
    (
        22,
        -- Product ID 22
        17,
        -- Category: Whole Spices & Herbs (ID 17)
        'Black Peppercorns (100g)',
        -- Product name
        3.49,
        -- Price: $3.49
        'assets/images/products/black_peppercorns.png',
        -- Image path
        'Whole black peppercorns for fresh grinding.',
        -- Description
        120 -- Initial stock quantity: 120 units
    ),
    (
        23,
        -- Product ID 23
        16,
        -- Category: Ground Spices (ID 16)
        'Paprika (40g)',
        -- Product name
        2.49,
        -- Price: $2.49
        'assets/images/products/paprika.png',
        -- Image path
        'Sweet Hungarian paprika, adds color and flavor to dishes.',
        -- Description
        100 -- Initial stock quantity: 100 units
    ),
    (
        24,
        -- Product ID 24
        17,
        -- Category: Whole Spices & Herbs (ID 17)
        'Italian Herb Mix (30g)',
        -- Product name
        3.99,
        -- Price: $3.99
        'assets/images/products/italian_herbs.png',
        -- Image path
        'Blend of oregano, basil, thyme, and other Mediterranean herbs.',
        -- Description
        90 -- Initial stock quantity: 90 units
    ),
    (
        25,
        -- Product ID 25
        16,
        -- Category: Ground Spices (ID 16)
        'Garlic Powder (60g)',
        -- Product name
        2.79,
        -- Price: $2.79
        'assets/images/products/garlic_powder.png',
        -- Image path
        'Fine garlic powder, great for seasoning any dish.',
        -- Description
        110 -- Initial stock quantity: 110 units
    );

-- -- ---------------------------------------------------------------------
-- -- Insert ONLY the Admin User
-- -- ---------------------------------------------------------------------
-- INSERT INTO
--     users (
--         user_id,
--         name,
--         phone,
--         email,
--         password,
--         role,
--         registration_date
--     )
-- VALUES
--     (
--         1,
--         -- Use ID 1 for the admin user for simplicity
--         'Admin User',
--         -- Name
--         '0000000000',
--         -- Placeholder Phone
--         'admin@ghibligroceries.com',
--         -- Email (unique)
--         '$2y$12$YNkTKqBBzX/1JGkXgHNVM.JGQIIpa1LOBuVFBLLOshZ1hAg2Ks1py',
--         -- Correct bcrypt hash for 'Admin123!'
--         'admin',
--         -- Set role directly to admin
--         NOW() -- Use current timestamp for registration
--     );

-- Note: No other sample data (categories, products, orders, etc.) is inserted.
-- =====================================================================
-- END OF DATABASE CREATION SCRIPT
-- =====================================================================