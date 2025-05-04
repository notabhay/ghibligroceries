<?php

namespace App\Core;

/**
 * Class Request
 *
 * Encapsulates HTTP request information, providing convenient access to
 * GET, POST, FILES, SERVER variables, headers, and the raw request body.
 * It sanitizes input arrays upon initialization.
 *
 * @package App\Core
 */
class Request
{
    /** @var array Filtered GET parameters ($_GET). */
    private array $getParams;
    /** @var array Filtered POST parameters ($_POST). */
    private array $postParams;
    /** @var array Uploaded file information ($_FILES). */
    private array $files;
    /** @var array Server environment variables ($_SERVER). */
    private array $server;
    /** @var array Parsed HTTP request headers. */
    private array $headers;
    /** @var string|null|false Cached raw input stream content. */
    private $inputStream;

    /**
     * Request constructor.
     *
     * Initializes the request object by capturing and filtering global request arrays
     * like $_GET, $_POST, $_FILES, and $_SERVER. It also extracts HTTP headers.
     */
    public function __construct()
    {
        // Filter GET parameters using FILTER_DEFAULT for basic sanitization.
        $this->getParams = filter_input_array(INPUT_GET, FILTER_DEFAULT) ?? [];
        // Filter POST parameters using FILTER_DEFAULT for basic sanitization.
        $this->postParams = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? [];
        // Store the raw $_FILES array. File handling requires specific validation elsewhere.
        $this->files = $_FILES;
        // Store the raw $_SERVER array.
        $this->server = $_SERVER;
        // Extract and normalize HTTP headers.
        $this->headers = $this->extractHeaders();
        // Initialize raw input stream cache to null.
        $this->inputStream = null;
    }

    /**
     * Extracts and normalizes HTTP headers from the $_SERVER array.
     *
     * Attempts to use `getallheaders()` if available (Apache). Otherwise, iterates
     * through $_SERVER variables prefixed with 'HTTP_' or specific keys like
     * 'CONTENT_TYPE' and 'CONTENT_LENGTH' to reconstruct the headers array.
     * Header keys are normalized to capitalized words separated by hyphens (e.g., 'Content-Type').
     *
     * @return array An associative array of HTTP headers.
     */
    private function extractHeaders(): array
    {
        // Prefer getallheaders() if it exists (typically Apache).
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            // Ensure it returns an array, return empty array on failure.
            return $headers !== false ? $headers : [];
        }

        // Fallback for environments where getallheaders() is not available (e.g., Nginx with FPM).
        $headers = [];
        foreach ($this->server as $key => $value) {
            // Check for headers prefixed with HTTP_.
            if (strpos($key, 'HTTP_') === 0) {
                // Normalize the header key: remove 'HTTP_', replace '_' with '-', lowercase, then capitalize words.
                $headerKey = str_replace('_', '-', strtolower(substr($key, 5)));
                $headerKey = ucwords($headerKey, '-');
                $headers[$headerKey] = $value;
            } // Check for specific non-HTTP_ prefixed headers.
            elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                // Normalize the key similarly.
                $headerKey = str_replace('_', '-', strtolower($key));
                $headerKey = ucwords($headerKey, '-');
                $headers[$headerKey] = $value;
            }
        }
        return $headers;
    }

    /**
     * Gets the HTTP request method (e.g., 'GET', 'POST', 'PUT').
     *
     * Defaults to 'GET' if the 'REQUEST_METHOD' server variable is not set.
     *
     * @return string The request method in uppercase.
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Gets the request URI path, excluding the query string.
     *
     * Parses the 'REQUEST_URI' server variable and returns only the path component,
     * with leading/trailing slashes trimmed.
     *
     * @return string The cleaned request URI path (e.g., 'users/profile', 'about'). Returns '' for the root '/'.
     */
    public function uri(): string
    {
        // Parse the URI and get the path component.
        $uri = parse_url($this->server['REQUEST_URI'] ?? '', PHP_URL_PATH);
        // Trim slashes from the path.
        return trim($uri ?: '', '/');
    }

    /**
     * Retrieves a specific GET parameter by key.
     *
     * @param string $key The key of the GET parameter.
     * @param mixed $default The default value to return if the key is not found. Defaults to null.
     * @return mixed The value of the GET parameter or the default value.
     */
    public function get(string $key, $default = null)
    {
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Retrieves all GET parameters as an associative array.
     *
     * @return array The filtered GET parameters.
     */
    public function allGet(): array
    {
        return $this->getParams;
    }

    /**
     * Retrieves a specific POST parameter by key.
     *
     * @param string $key The key of the POST parameter.
     * @param mixed $default The default value to return if the key is not found. Defaults to null.
     * @return mixed The value of the POST parameter or the default value.
     */
    public function post(string $key, $default = null)
    {
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Retrieves all POST parameters as an associative array.
     *
     * @return array The filtered POST parameters.
     */
    public function allPost(): array
    {
        return $this->postParams;
    }

    /**
     * Retrieves an input parameter by key, checking POST first, then GET.
     *
     * Useful for retrieving data regardless of the request method (e.g., form submissions).
     *
     * @param string $key The key of the input parameter.
     * @param mixed $default The default value to return if the key is not found in POST or GET. Defaults to null.
     * @return mixed The value of the input parameter or the default value.
     */
    public function input(string $key, $default = null)
    {
        // Check POST parameters first.
        if (isset($this->postParams[$key])) {
            return $this->postParams[$key];
        }
        // Fallback to GET parameters.
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Retrieves all input parameters (merged POST and GET) as an associative array.
     *
     * Note: If a key exists in both POST and GET, the POST value takes precedence due to the merge order.
     *
     * @return array A merged array of GET and POST parameters.
     */
    public function allInput(): array
    {
        return array_merge($this->getParams, $this->postParams);
    }

    /**
     * Retrieves the raw request body content.
     *
     * Reads the content from the 'php://input' stream. The result is cached
     * after the first read.
     *
     * @return string|false The raw request body as a string, or false on failure to read.
     */
    public function rawInput()
    {
        // Check if the input stream has already been read.
        if ($this->inputStream === null) {
            // Read the raw input stream and cache it.
            $this->inputStream = file_get_contents('php://input');
        }
        return $this->inputStream;
    }

    /**
     * Decodes the raw request body assuming it's JSON.
     *
     * @param bool $associative When true, returns objects as associative arrays. Defaults to true.
     * @param int $depth Maximum nesting depth. Defaults to 512.
     * @param int $flags Bitmask of JSON decode options (e.g., JSON_BIGINT_AS_STRING). Defaults to 0.
     * @return mixed|null The decoded JSON data (array or object), or null if the body is empty or decoding fails.
     */
    public function json(bool $associative = true, int $depth = 512, int $flags = 0)
    {
        $rawInput = $this->rawInput();
        // Return null if the raw input is empty.
        if (empty($rawInput)) {
            return null;
        }
        // Attempt to decode the JSON string.
        $decoded = json_decode($rawInput, $associative, $depth, $flags);
        // Return the decoded data only if JSON decoding was successful.
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }

    /**
     * Retrieves information about an uploaded file by its form input key.
     *
     * @param string $key The key (name attribute of the file input field) of the uploaded file.
     * @return array|null An array containing file information (name, type, tmp_name, error, size)
     *                    or null if no file was uploaded with that key.
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Retrieves information about all uploaded files.
     *
     * @return array An associative array where keys are input names and values are file info arrays.
     */
    public function allFiles(): array
    {
        return $this->files;
    }

    /**
     * Checks if a file was successfully uploaded for the given key.
     *
     * Verifies that a file entry exists for the key and its 'error' code is UPLOAD_ERR_OK.
     *
     * @param string $key The key (name attribute of the file input field).
     * @return bool True if a file was successfully uploaded, false otherwise.
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Retrieves a specific HTTP header value by key (case-insensitive).
     *
     * @param string $key The name of the header (e.g., 'Content-Type', 'Authorization'). Case-insensitive.
     * @param mixed $default The default value to return if the header is not found. Defaults to null.
     * @return string|null The header value as a string, or the default value.
     */
    public function header(string $key, $default = null): ?string
    {
        // Normalize the requested key to lowercase for case-insensitive comparison.
        $normalizedKey = strtolower($key);
        // Iterate through the extracted headers.
        foreach ($this->headers as $headerName => $headerValue) {
            // Compare normalized keys.
            if (strtolower($headerName) === $normalizedKey) {
                return $headerValue;
            }
        }
        // Return the default value if the header is not found.
        return $default;
    }

    /**
     * Retrieves all HTTP headers as an associative array.
     *
     * @return array The normalized headers.
     */
    public function allHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Retrieves a specific server variable by key from the $_SERVER array.
     *
     * @param string $key The key of the server variable (e.g., 'REQUEST_URI', 'HTTP_HOST').
     * @param mixed $default The default value to return if the key is not found. Defaults to null.
     * @return mixed The value of the server variable or the default value.
     */
    public function server(string $key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Gets the client's IP address.
     *
     * Retrieves the IP address from the 'REMOTE_ADDR' server variable.
     * Note: This might not be the true client IP if behind a proxy. Consider checking
     * 'HTTP_X_FORWARDED_FOR' or similar headers in proxy scenarios, but validate them carefully.
     *
     * @return string|null The client's IP address or null if not available.
     */
    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    /**
     * Checks if the request is an AJAX (XMLHttpRequest) request.
     *
     * Looks for the 'X-Requested-With' header with a value of 'XMLHttpRequest'.
     *
     * @return bool True if it appears to be an AJAX request, false otherwise.
     */
    public function isAjax(): bool
    {
        // Check the 'X-Requested-With' header (case-insensitive value comparison).
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * Checks if the request method is POST.
     *
     * @return bool True if the method is POST, false otherwise.
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Checks if the request method is GET.
     *
     * @return bool True if the method is GET, false otherwise.
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }
}
