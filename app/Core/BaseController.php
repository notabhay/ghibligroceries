<?php

namespace App\Core;

use App\Core\Registry; // Import the Registry for accessing shared services.

/**
 * Class BaseController
 *
 * An abstract base controller providing common functionality for other controllers,
 * primarily focused on rendering views within a standard layout.
 * Controllers extending this class inherit the `view` method.
 *
 * @package App\Core
 */
abstract class BaseController
{
    /**
     * Renders a view file within the default layout.
     *
     * This method takes the name of a view file (using dot notation for directories)
     * and an optional array of data to be extracted and made available within the view
     * and layout files. It handles locating the view and layout files, extracting data,
     * capturing the view's output, and including it within the layout.
     * It also attempts to determine the current request path to make it available to the view.
     *
     * @param string $view The name of the view file to render (e.g., 'pages.index', 'admin.users.show').
     *                     Uses dot notation relative to the `app/Views/` directory.
     * @param array $data An associative array of data to extract into variables for the view and layout.
     *                    Keys become variable names (e.g., ['title' => 'My Page'] makes $title available).
     * @return void This method outputs the rendered HTML directly and does not return a value.
     *              It terminates execution with an error message if view or layout files are missing
     *              or if there's an error during view rendering.
     */
    protected function view(string $view, array $data = []): void
    {
        // Construct the full path to the view file based on the provided name.
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        // Define the path to the default layout file.
        $layoutPath = __DIR__ . '/../Views/layouts/default.php';

        // Check if the view file exists. If not, trigger a warning and exit.
        if (!file_exists($viewPath)) {
            trigger_error("View file not found: {$viewPath}", E_USER_WARNING);
            echo "Error: View file '{$view}' not found.";
            exit; // Stop execution if view is missing.
        }

        // Check if the layout file exists. If not, trigger a warning and exit.
        if (!file_exists($layoutPath)) {
            trigger_error("Layout file not found: {$layoutPath}", E_USER_WARNING);
            echo "Error: Default layout file not found.";
            exit; // Stop execution if layout is missing.
        }

        // Attempt to get the current request URI to pass to the view (e.g., for active navigation links).
        try {
            $request = Registry::get('request'); // Get the Request object from the Registry.
            $uri = $request->uri(); // Get the cleaned URI path.
            $currentPath = '/' . ($uri ?: ''); // Prepend slash, handle empty URI for homepage.
            $data['currentPath'] = $currentPath; // Add the current path to the data array.
        } catch (\Exception $e) {
            // If fetching the request fails, default the current path.
            $data['currentPath'] = '/';
        }

        // Extract the data array into individual variables accessible by the view and layout.
        // For example, $data['title'] becomes $title.
        extract($data);

        // Start output buffering to capture the view's content.
        ob_start();

        try {
            // Include the view file. Variables from extract() are available here.
            include $viewPath;
        } catch (\Throwable $e) {
            // If an error occurs while rendering the view, clean the buffer and show an error.
            ob_end_clean(); // Discard the buffered content.
            // Log the actual error here in a real application using Registry::get('logger')
            echo "Error rendering view '{$view}'. Please check the logs.";
            exit; // Stop execution.
        }

        // Get the captured content from the output buffer and clean the buffer.
        $content = ob_get_clean();

        // Add the captured view content to the data array under the key 'content'.
        $data['content'] = $content;

        // Extract the data array again so the $content variable (and potentially updated $data)
        // is available directly within the layout file.
        extract($data);

        // Include the layout file. It should use the $content variable to display the view's output.
        include $layoutPath;
    }
}
