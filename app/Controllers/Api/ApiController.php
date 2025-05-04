<?php

/**
 * API Documentation Generator
 *
 * This script dynamically generates an HTML page providing documentation for the
 * Fresh Grocery Store RESTful API. It outlines available endpoints,
 * expected request/response formats, authentication requirements, rate limiting,
 * and error codes.
 *
 * It fetches configuration details like API version, rate limits, and base URL
 * to keep the documentation consistent with the application settings.
 *
 * Note: This file does not contain actual API endpoint logic, but rather serves
 * as a self-documenting guide for API consumers. It directly outputs HTML content.
 */

namespace App\Controllers\Api;

// Ensure BASE_PATH is defined if accessed directly or via unusual include
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(dirname(__DIR__))));
}

// Load application configuration to get API details
$config = require BASE_PATH . '/app/config.php';

// Extract configuration values for use in the documentation template
$api_version = $config['API_VERSION'] ?? 'N/A';
$api_rate_limit = $config['API_RATE_LIMIT'] ?? 'N/A';
$api_base_url = rtrim($config['SITE_URL'] ?? 'http://localhost/', '/') . ($config['API_BASE_PATH'] ?? '/api');
$items_per_page = $config['ITEMS_PER_PAGE'] ?? 10; // Default if not set

// Define the HTML structure for the API documentation using HEREDOC syntax
$api_doc = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="API Documentation for the Fresh Grocery Store">
    <title>Fresh Grocery Store API - Documentation</title>
    <style>
        /* Basic styling for the documentation page */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            max-width: 1200px; /* Limit width for better readability */
            margin: 0 auto; /* Center the content */
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #3498db;
            margin-top: 30px;
        }
        h3 {
            color: #2980b9;
            margin-top: 25px;
        }
        /* Styling for endpoint blocks */
        .endpoint {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        /* Styling for HTTP methods */
        .method {
            font-weight: bold;
            margin-right: 10px;
        }
        .method-get { color: #2ecc71; }
        .method-put { color: #f39c12; }
        .method-post { color: #3498db; }
        .method-delete { color: #e74c3c; }
        /* Styling for API paths */
        .path {
            font-family: monospace;
            font-size: 16px;
        }
        .description {
            margin-top: 10px;
        }
        /* Styling for code snippets and blocks */
        code {
            background-color: #f4f4f4;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto; /* Allow horizontal scrolling for long code lines */
        }
        /* Styling for tables */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        /* Styling for informational notes */
        .note {
            background-color: #fff8dc; /* Light yellow */
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #f1c40f; /* Yellow border */
            margin: 20px 0;
        }
        /* Styling for authentication notes */
        .auth-note {
            background-color: #e8f4f8; /* Light blue */
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #3498db; /* Blue border */
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Fresh Grocery Store API Documentation</h1>
    <p>Welcome to the Fresh Grocery Store API documentation. This RESTful API allows you to access order information and manage orders.</p>

    <!-- API Version Note -->
    <div class="note">
        <strong>Note:</strong> This API is currently in version {$api_version}.
    </div>

    <!-- Authentication Section -->
    <h2>Authentication</h2>
    <p>All API requests require authentication using a Bearer token in the Authorization header.</p>
    <div class="auth-note">
        <p><strong>Authorization Header Example:</strong></p>
        <code>Authorization: Bearer your_token_here</code>
    </div>
    <h3>Access Levels</h3>
    <p>There are two types of access tokens:</p>
    <ul>
        <li><strong>User tokens</strong> - Allow regular users to view their own orders</li>
        <li><strong>Manager tokens</strong> - Allow managers to view all orders and update order status</li>
    </ul>

    <!-- Rate Limiting Section -->
    <h2>Rate Limiting</h2>
    <p>This API implements rate limiting to prevent abuse. The current limit is {$api_rate_limit} requests per hour per client IP.</p>
    <p>Rate limit information is provided in the response headers:</p>
    <ul>
        <li><code>X-RateLimit-Limit</code>: Maximum requests allowed per hour</li>
        <li><code>X-RateLimit-Remaining</code>: Remaining requests in the current time window</li>
        <li><code>X-RateLimit-Reset</code>: Timestamp (UTC epoch seconds) when the rate limit will reset</li>
    </ul>

    <!-- Endpoints Section -->
    <h2>Endpoints</h2>

    <!-- GET /orders Endpoint -->
    <div class="endpoint">
        <h3><span class="method method-get">GET</span> <span class="path">{$api_base_url}/orders</span></h3>
        <div class="description">
            <p>Get a list of orders. For regular users, this returns only their orders. For managers, this returns all orders in the system.</p>
            <h4>Query Parameters</h4>
            <table>
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>status</td>
                        <td>string</td>
                        <td>Optional. Filter orders by status (e.g., 'pending', 'processing', 'completed', 'cancelled')</td>
                    </tr>
                    <tr>
                        <td>limit</td>
                        <td>integer</td>
                        <td>Optional. Number of orders to return per page (default: {$items_per_page})</td>
                    </tr>
                    <tr>
                        <td>page</td>
                        <td>integer</td>
                        <td>Optional. Page number for pagination (default: 1)</td>
                    </tr>
                </tbody>
            </table>
            <h4>Response Example (Manager)</h4>
            <pre>{
  "total": 25, // Total number of orders matching the criteria
  "page": 1, // Current page number
  "limit": 10, // Orders per page
  "total_pages": 3, // Total number of pages
  "orders": [
    {
      "order_id": 1,
      "user_id": 2,
      "order_date": "2023-05-10 14:35:12",
      "total_amount": 45.75,
      "status": "completed",
      "user_name": "John Doe", // Included for managers
      "user_email": "john@example.com" // Included for managers
    },
    // ... more orders
  ]
}</pre>
            <h4>Response Example (Regular User)</h4>
            <pre>{
  "orders": [
    {
      "order_id": 1,
      "order_date": "2023-05-10 14:35:12",
      "total_amount": 45.75,
      "status": "completed"
    },
    {
      "order_id": 5,
      "order_date": "2023-05-15 16:45:30",
      "total_amount": 28.90,
      "status": "pending"
    }
    // ... more orders belonging to the authenticated user
  ]
}</pre>
        </div>
    </div>

    <!-- GET /orders/{order_id} Endpoint -->
    <div class="endpoint">
        <h3><span class="method method-get">GET</span> <span class="path">{$api_base_url}/orders/{order_id}</span></h3>
        <div class="description">
            <p>Get detailed information about a specific order. Regular users can only view their own orders. Managers can view any order.</p>
            <h4>Path Parameters</h4>
            <table>
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>order_id</td>
                        <td>integer</td>
                        <td>Required. The ID of the order to retrieve.</td>
                    </tr>
                </tbody>
            </table>
            <h4>Response Example</h4>
            <pre>{
  "order_id": 1,
  "user_id": 2,
  "user_name": "John Doe",
  "user_email": "john@example.com",
  "user_phone": "1234567890",
  "order_date": "2023-05-10 14:35:12",
  "total_amount": 45.75,
  "status": "completed",
  "notes": "Please leave at the front door",
  "items": [ // Array of items included in the order
    {
      "item_id": 1,
      "product_id": 101,
      "product_name": "Russet Potato (1kg)",
      "product_image": "vegetables/russet_potato.jpg", // Relative image path
      "quantity": 2,
      "price": 2.49, // Price per unit at the time of order
      "subtotal": 4.98 // quantity * price
    },
    {
      "item_id": 2,
      "product_id": 205,
      "product_name": "Organic Chicken Breast (500g)",
      "product_image": "meat/chicken_breast.jpg",
      "quantity": 1,
      "price": 8.99,
      "subtotal": 8.99
    }
    // ... more items
  ]
}</pre>
        </div>
    </div>

    <!-- PUT /orders/{order_id} Endpoint -->
    <div class="endpoint">
        <h3><span class="method method-put">PUT</span> <span class="path">{$api_base_url}/orders/{order_id}</span></h3>
        <div class="description">
            <p>Update the status of a specific order. <strong>Requires Manager authentication.</strong></p>
            <h4>Path Parameters</h4>
            <table>
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>order_id</td>
                        <td>integer</td>
                        <td>Required. The ID of the order to update.</td>
                    </tr>
                </tbody>
            </table>
            <h4>Request Body (JSON)</h4>
            <pre>{
  "status": "processing" // Required. The new status for the order.
}</pre>
            <p>Valid status values:</p>
            <ul>
                <li><code>pending</code></li>
                <li><code>processing</code></li>
                <li><code>completed</code></li>
                <li><code>cancelled</code></li>
            </ul>
            <h4>Response Example (Success)</h4>
            <pre>{
  "message": "Order status updated successfully"
}</pre>
            <h4>Response Example (Error)</h4>
            <pre>{
  "error": "Invalid status value provided." // Or other error messages
}</pre>
        </div>
    </div>

    <!-- Error Responses Section -->
    <h2>Error Responses</h2>
    <p>The API uses standard HTTP status codes to indicate the success or failure of a request. Error responses are returned in JSON format.</p>
    <table>
        <thead>
            <tr>
                <th>Status Code</th>
                <th>Meaning</th>
                <th>Example Response Body</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>200 OK</td>
                <td>Request was successful.</td>
                <td><pre>{ "data": { ... } } // Or specific success message</pre></td>
            </tr>
            <tr>
                <td>201 Created</td>
                <td>Resource was successfully created.</td>
                <td><pre>{ "message": "Resource created", "id": 123 }</pre></td>
            </tr>
            <tr>
                <td>400 Bad Request</td>
                <td>The request was malformed or contained invalid data (e.g., missing parameters, invalid values).</td>
                <td><pre>{ "error": "Status is required" }</pre></td>
            </tr>
            <tr>
                <td>401 Unauthorized</td>
                <td>Authentication failed or was not provided. A valid Bearer token is required.</td>
                <td><pre>{ "error": "Authentication required" }</pre></td>
            </tr>
            <tr>
                <td>403 Forbidden</td>
                <td>The authenticated user does not have permission to perform the requested action (e.g., a regular user trying to update an order).</td>
                <td><pre>{ "error": "You are not authorized to view this order" }</pre></td>
            </tr>
            <tr>
                <td>404 Not Found</td>
                <td>The requested resource (e.g., a specific order) could not be found.</td>
                <td><pre>{ "error": "Order not found" }</pre></td>
            </tr>
            <tr>
                <td>405 Method Not Allowed</td>
                <td>The HTTP method used is not supported for the requested endpoint.</td>
                <td><pre>{ "error": "Method GET not allowed for this endpoint" }</pre></td>
            </tr>
            <tr>
                <td>429 Too Many Requests</td>
                <td>The client has exceeded the rate limit.</td>
                <td><pre>{ "error": "Rate limit exceeded. Try again later." }</pre></td>
            </tr>
            <tr>
                <td>500 Internal Server Error</td>
                <td>An unexpected error occurred on the server.</td>
                <td><pre>{ "error": "Failed to retrieve orders due to a server issue" }</pre></td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Fresh Grocery Store. All rights reserved.</p>
</footer>
</body>

</html>
HTML;

// Output the generated HTML documentation
echo $api_doc;
