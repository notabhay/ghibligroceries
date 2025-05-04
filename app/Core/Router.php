<?php

namespace App\Core;

use Exception; // Import base Exception class.
use ReflectionClass; // Import ReflectionClass for dependency injection.
use ReflectionParameter; // Import ReflectionParameter for dependency injection.
use App\Helpers\CaptchaHelper; // Specific dependency potentially needed by controllers.
use App\Core\Session; // Import Session for authentication checks.
use App\Core\Redirect; // Import Redirect for handling unauthorized access.

/**
 * Class Router
 *
 * Handles routing requests to the appropriate controller actions based on URI and HTTP method.
 * Supports defining routes for GET, POST, and PUT methods, route parameters,
 * route grouping with prefixes, and basic dependency injection for controllers.
 * It also includes a hardcoded check for authentication on specific routes.
 *
 * @package App\Core
 */
class Router
{
    /**
     * @var array Stores all defined routes, grouped by HTTP method (GET, POST, PUT).
     *            Each method key holds an array where keys are regex patterns and values
     *            are arrays containing 'controller' and 'action' strings.
     *            Example: ['GET']['#^/users/(?P<id>[^/]+)$#i'] => ['controller' => UserController::class, 'action' => 'show']
     */
    protected array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
    ];

    /**
     * @var array Stores the parameters extracted from the matched URI pattern.
     *            Keys are parameter names defined in the route (e.g., 'id'), values are the matched segments.
     */
    protected array $params = [];

    /**
     * @var array|null Stores the controller and action information of the matched route.
     *                 Example: ['controller' => UserController::class, 'action' => 'show']
     */
    protected ?array $matchedController = null;

    /**
     * @var string|null Stores the regex pattern of the matched route.
     *                  Used internally, e.g., for the hardcoded authentication check.
     */
    protected ?string $matchedPattern = null;

    /**
     * @var string Stores the current prefix being applied to routes defined within a group.
     */
    protected static string $routePrefix = '';

    /**
     * Loads route definitions from a specified file.
     *
     * Creates a new Router instance and includes the given PHP file.
     * The included file is expected to define routes using the router instance (e.g., $router->get(...)).
     *
     * @param string $file The path to the PHP file containing route definitions.
     * @return self A new Router instance populated with routes from the file.
     */
    public static function load(string $file): self
    {
        $router = new static; // Create a new instance of the current class (Router or a subclass).
        require $file; // Include the routes file, which should interact with $router.
        return $router; // Return the configured router instance.
    }

    /**
     * Defines a route that responds to GET requests.
     *
     * @param string $uri The URI pattern for the route (e.g., 'users', 'products/{id}').
     * @param array $action An array containing the controller class name and the method name to execute.
     *                      Example: [UserController::class, 'index']
     * @return void
     */
    public function get(string $uri, array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    /**
     * Defines a route that responds to POST requests.
     *
     * @param string $uri The URI pattern for the route (e.g., 'login', 'users').
     * @param array $action An array containing the controller class name and the method name to execute.
     *                      Example: [AuthController::class, 'login']
     * @return void
     */
    public function post(string $uri, array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    /**
     * Defines a route that responds to PUT requests.
     *
     * @param string $uri The URI pattern for the route (e.g., 'api/users/{id}').
     * @param array $action An array containing the controller class name and the method name to execute.
     *                      Example: [ApiUserController::class, 'update']
     * @return void
     */
    public function put(string $uri, array $action): void
    {
        $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Groups routes under a common URI prefix.
     *
     * Routes defined within the callback function will automatically have the prefix prepended.
     * Supports nested groups.
     *
     * @param string $prefix The URI prefix for the group (e.g., 'admin', 'api/v1').
     * @param callable $callback A function that defines routes within the group. The router instance is typically
     *                           passed via `use ($router)` if routes are defined on an instance.
     * @return void
     */
    public static function group(string $prefix, callable $callback): void
    {
        // Store the previous prefix to restore it after the group.
        $previousPrefix = self::$routePrefix ?? '';
        // Append the new prefix, ensuring correct slash handling.
        self::$routePrefix = $previousPrefix . '/' . trim($prefix, '/');
        // Execute the callback, which defines the routes within this group's context.
        $callback();
        // Restore the previous prefix, allowing subsequent routes or groups to be defined correctly.
        self::$routePrefix = $previousPrefix;
    }


    /**
     * Adds a route definition to the internal routes array.
     *
     * Combines the current route prefix with the given URI, validates the action format,
     * converts the URI pattern into a regular expression for matching, and stores it.
     *
     * @param string $method The HTTP method (e.g., 'GET', 'POST').
     * @param string $uri The URI pattern relative to the current group prefix.
     * @param array $action The controller class and method array.
     * @throws Exception If the action format is invalid.
     * @return void
     */
    protected function addRoute(string $method, string $uri, array $action): void
    {
        // Construct the full URI by prepending the current prefix and trimming slashes.
        $fullUri = trim(self::$routePrefix . '/' . trim($uri, '/'), '/');
        // Handle the root URI case.
        if ($fullUri === '')
            $fullUri = '/';

        // Validate the action format: must be a two-element array of strings.
        if (count($action) !== 2 || !is_string($action[0]) || !is_string($action[1])) {
            throw new Exception("Invalid action format for route [{$method}] {$fullUri}. Expected [Controller::class, 'method'].");
        }

        $controllerClass = $action[0];
        $methodName = $action[1];

        // Generate the regex pattern for matching the URI.
        if ($fullUri === '/') {
            // Specific pattern for the root URI.
            $pattern = '#^/$#i';
        } else {
            // Convert route parameters like {id} into named capture groups `(?P<id>[^/]+)`.
            // Matches any character except '/' one or more times.
            // Case-insensitive matching (#i).
            $pattern = '#^' . preg_replace('/\{([a-z_]+)\}/', '(?P<\1>[^/]+)', $fullUri) . '$#i';
        }

        // Store the route information keyed by the regex pattern under the specific HTTP method.
        $this->routes[$method][$pattern] = ['controller' => $controllerClass, 'action' => $methodName];
    }

    /**
     * Attempts to match the given URI and request type against defined routes.
     *
     * Iterates through the routes defined for the specific request method and checks
     * if the URI matches any of the regex patterns. If a match is found, it extracts
     * named parameters, stores the matched controller/action and pattern, and returns true.
     *
     * @param string $uri The request URI path to match (e.g., 'users/123').
     * @param string $requestType The HTTP request method (e.g., 'GET', 'POST').
     * @return bool True if a matching route is found, false otherwise.
     */
    protected function match(string $uri, string $requestType): bool
    {
        // Check if any routes are defined for this request method.
        if (!isset($this->routes[$requestType])) {
            return false;
        }

        // Normalize the URI by trimming slashes.
        $uri = trim($uri, '/');
        // Handle the root URI case.
        if ($uri === '')
            $uri = '/';

        // Iterate through the patterns defined for the request type.
        foreach ($this->routes[$requestType] as $pattern => $routeInfo) {
            // Attempt to match the URI against the pattern.
            if (preg_match($pattern, $uri, $matches)) {
                // Clear previous params.
                $this->params = [];
                // Extract named capture groups (route parameters) from the matches.
                foreach ($matches as $key => $match) {
                    if (is_string($key)) { // Only process named captures.
                        // Convert numeric matches to integers for convenience.
                        $this->params[$key] = is_numeric($match) ? (int) $match : $match;
                    }
                }
                // Store the matched controller/action info and the pattern.
                $this->matchedController = $routeInfo;
                $this->matchedPattern = $pattern;
                return true; // Match found.
            }
        }

        return false; // No match found.
    }

    /**
     * Dispatches the request to the matched controller action.
     *
     * Takes the request URI and method, finds a matching route using `match()`,
     * performs a hardcoded authentication check for specific route patterns,
     * and then calls the appropriate controller action using `callAction()`.
     *
     * @param string $uri The full request URI (including potential query string, though only path is used for matching).
     * @param string $requestType The HTTP request method (e.g., 'GET', 'POST').
     * @throws Exception If no route is defined for the URI (404 Not Found).
     * @return mixed The result returned by the controller action.
     */
    public function direct(string $uri, string $requestType)
    {
        // Extract and clean the path component of the URI.
        $uriPath = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uriPathClean = trim($uriPath, '/');
        if ($uriPathClean === '')
            $uriPathClean = '/'; // Handle root path.

        // Attempt to match the route.
        if ($this->match($uriPath, $requestType) && $this->matchedController) {

            // --- Hardcoded Authentication Check ---
            // Define patterns for routes that require authentication.
            // TODO: Refactor this into a proper middleware system.
            $protectedRoutePatterns = [
                '#^orders$#i',
                '#^order/process$#i',
                '#^order/details/(?P<id>[^/]+)$#i',
                '#^order/confirmation/(?P<id>[^/]+)$#i',
                '#^order/cancel/(?P<id>[^/]+)$#i',
                '#^api/orders$#i',
                '#^api/orders/(?P<id>[^/]+)$#i',
                // Add other protected patterns here (e.g., admin routes if not handled by group middleware)
            ];
            $isProtectedRoute = false;
            // Check if the matched pattern is directly in the list.
            if ($this->matchedPattern && in_array($this->matchedPattern, $protectedRoutePatterns, true)) {
                $isProtectedRoute = true;
            }
            // Fallback check: Match the cleaned URI path against the patterns (less precise but covers cases without direct pattern match).
            if (!$isProtectedRoute) {
                foreach ($protectedRoutePatterns as $pattern) {
                    if (preg_match($pattern, $uriPathClean)) {
                        $isProtectedRoute = true;
                        break;
                    }
                }
            }

            // If it's a protected route and the user is not authenticated...
            if ($isProtectedRoute && !Registry::get('session')->isAuthenticated()) {
                // Handle API requests with a JSON 401 response.
                if (strpos($uriPathClean, 'api/') === 0) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['error' => 'Authentication required.']);
                    exit();
                } else {
                    // Handle web requests by flashing a message and redirecting to login.
                    Registry::get('session')->flash('error', 'Please log in to access that page.');
                    Redirect::to('/login');
                    exit(); // exit() is called within Redirect::to()
                }
            }
            // --- End Hardcoded Authentication Check ---


            // Get the controller class and action method from the matched route.
            $controllerClass = $this->matchedController['controller'];
            $action = $this->matchedController['action'];

            // Call the action method on an instance of the controller.
            return $this->callAction($controllerClass, $action, $this->params);
        }

        // If no route matched, throw a 404 exception.
        throw new Exception("No route defined for this URI: {$uriPath} [{$requestType}]", 404);
    }

    /**
     * Instantiates a controller and calls the specified action method, handling dependencies.
     *
     * Uses Reflection to inspect the controller's constructor. If dependencies are type-hinted
     * (and are known services like Database, Session, or registered in the Registry),
     * they are automatically resolved and injected. Then, the specified action method
     * is called on the controller instance, passing any route parameters.
     *
     * @param string $controllerClass The fully qualified name of the controller class.
     * @param string $action The name of the method to call on the controller.
     * @param array $params An associative array of parameters extracted from the URI to pass to the action method.
     * @throws Exception If the controller class or action method does not exist, or if a dependency cannot be resolved.
     * @return mixed The result returned by the controller action method.
     */
    protected function callAction(string $controllerClass, string $action, array $params = [])
    {
        // Check if the controller class exists.
        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class {$controllerClass} not found.");
        }

        // Use Reflection to analyze the controller class.
        $reflection = new ReflectionClass($controllerClass);
        $constructor = $reflection->getConstructor(); // Get the constructor method.
        $dependencies = []; // Array to hold resolved dependencies.

        // If the controller has a constructor, resolve its dependencies.
        if ($constructor) {
            $constructorParams = $constructor->getParameters(); // Get constructor parameters.
            foreach ($constructorParams as $param) {
                $paramType = $param->getType(); // Get the type hint of the parameter.
                $resolved = false; // Flag to track if dependency was resolved.

                // Check if the parameter has a non-builtin type hint (i.e., it's a class/interface).
                if ($paramType && !$paramType->isBuiltin() && $paramType instanceof \ReflectionNamedType) {
                    $dependencyClassName = $paramType->getName(); // Get the class/interface name.

                    // --- Dependency Resolution Logic ---
                    // Attempt to resolve known core dependencies directly from the Registry.
                    if ($dependencyClassName === Database::class && Registry::has('database')) {
                        $dependencies[] = Registry::get('database');
                        $resolved = true;
                    } elseif ($dependencyClassName === Session::class && Registry::has('session')) {
                        $dependencies[] = Registry::get('session');
                        $resolved = true;
                    } elseif ($dependencyClassName === CaptchaHelper::class && Registry::has('captchaHelper')) {
                        $dependencies[] = Registry::get('captchaHelper');
                        $resolved = true;
                    }
                    // Add more specific core dependencies here if needed.

                    // If not resolved yet, try getting from Registry using the class name as key.
                    if (!$resolved && Registry::has($dependencyClassName)) {
                        $dependencies[] = Registry::get($dependencyClassName);
                        $resolved = true;
                    }
                    // If still not resolved, try getting from Registry using the parameter name (lowercase) as key.
                    elseif (!$resolved && Registry::has(strtolower($param->getName()))) {
                        $dependencies[] = Registry::get(strtolower($param->getName()));
                        $resolved = true;
                    }
                    // If still not resolved, check if the parameter has a default value or is optional.
                    elseif (!$resolved) {
                        if ($param->isOptional()) {
                            // Use default value if available, otherwise null for optional params.
                            $dependencies[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                            $resolved = true;
                        } else {
                            // If it's a required dependency and couldn't be resolved, throw an error.
                            throw new Exception("Cannot resolve dependency '{$dependencyClassName}' for {$controllerClass} constructor.");
                        }
                    }
                    // --- End Dependency Resolution Logic ---

                } else {
                    // Handle built-in types or parameters without type hints.
                    if ($param->isDefaultValueAvailable()) {
                        $dependencies[] = $param->getDefaultValue(); // Use default value.
                    } elseif ($param->isOptional()) {
                        $dependencies[] = null; // Use null for optional parameters without defaults.
                    } else {
                        // This case might indicate an issue (required scalar without default),
                        // but we'll pass null for now. Consider throwing an error if stricter handling is needed.
                        // Log warning maybe? "Required scalar parameter '{$param->getName()}' in {$controllerClass} constructor has no default value."
                        $dependencies[] = null;
                    }
                }
            }
            // Instantiate the controller with the resolved dependencies.
            $controllerInstance = $reflection->newInstanceArgs($dependencies);
        } else {
            // If no constructor, simply instantiate the controller directly.
            $controllerInstance = new $controllerClass;
        }

        // Check if the action method exists in the controller instance.
        if (!method_exists($controllerInstance, $action)) {
            throw new Exception(
                "{$controllerClass} does not respond to the {$action} action."
            );
        }

        // Call the action method on the controller instance, passing the route parameters.
        // The controller action receives the $params array containing matched route segments.
        return $controllerInstance->$action($params);
    }
}
