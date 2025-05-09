<?php

namespace App\Services;

use App\Core\Registry;

/**
 * Service class for interacting with Google Gemini API.
 * 
 * Handles constructing prompts, making API calls, and processing responses
 * for AI-enhanced product search functionality.
 */
class GeminiService
{
    /**
     * @var string The API key for Google Gemini API
     */
    private string $apiKey;

    /**
     * @var string The API endpoint URL
     */
    private string $apiEndpoint;

    /**
     * @var float The temperature setting for Gemini API (controls randomness)
     */
    private float $temperature;

    /**
     * @var int Timeout in seconds for API requests
     */
    private int $timeout;

    /**
     * @var bool Whether to enable fallback to traditional search
     */
    private bool $fallbackEnabled;

    /**
     * @var \Psr\Log\LoggerInterface Logger instance
     */
    private $logger;

    /**
     * Constructor
     * 
     * Initializes the service with configuration from app/config.php
     */
    public function __construct()
    {
        $config = Registry::get('config');
        $this->logger = Registry::get('logger');
        
        $this->apiKey = $config['GEMINI_API_KEY'] ?? '';
        $this->apiEndpoint = $config['GEMINI_API_ENDPOINT'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro-preview-05-06:generateContent';
        $this->temperature = (float)($config['GEMINI_TEMPERATURE'] ?? 0.7);
        $this->timeout = (int)($config['GEMINI_TIMEOUT'] ?? 10);
        $this->fallbackEnabled = (bool)($config['GEMINI_FALLBACK_ENABLED'] ?? true);
        
        if (empty($this->apiKey)) {
            $this->logger->warning('GeminiService initialized without API key');
        }
    }

    /**
     * Constructs a prompt for the Gemini API based on user query and product categories
     * 
     * @param string $query The user's search query
     * @param array $categories Available product categories for context
     * @return string The constructed prompt
     */
    public function constructPrompt(string $query, array $categories): string
    {
        // Extract category names for the prompt
        $categoryNames = array_column($categories, 'category_name');
        $categoriesText = implode(', ', $categoryNames);
        
        // Construct a prompt that guides Gemini to enhance the search
        $prompt = <<<PROMPT
You are a helpful grocery shopping assistant. Your goal is to refine a user's search query to find specific products.
The user is searching for: "{$query}".

Available product categories are: {$categoriesText}.

Please analyze the query and provide:
1.  `correctedQuery`: If the query has typos or misspellings, provide a corrected version. If no correction is needed, provide the original query.
2.  `keywords`: Provide an array of 2-3 highly relevant keywords that directly describe the product's physical form, primary characteristic, or intended use based on the query. For example, if the query is "powder", keywords might include "powdered", "granules", "dust". If the query is "milk", keywords might include "liquid", "dairy", "drink". Avoid overly broad or generic terms.
3.  `categories`: Provide an array of 1-2 product categories from the "Available product categories" list that are most relevant to the refined keywords and corrected query. Only include categories if they strongly match.
4.  `explanation`: A brief explanation of your reasoning (for debugging, not shown to user).

Example for query "baking soda":
{{
  "correctedQuery": "baking soda",
  "keywords": ["powder", "leavening", "sodium bicarbonate"],
  "categories": ["Baking Supplies", "Pantry Staples"],
  "explanation": "Query is clear. Keywords describe its form and use. Categories are directly relevant."
}}

Example for query "fresh bred":
{{
  "correctedQuery": "fresh bread",
  "keywords": ["loaf", "sliced", "bakery"],
  "categories": ["Baked Goods", "Bread"],
  "explanation": "Corrected typo. Keywords describe form. Categories are relevant."
}}

Format your response STRICTLY as a valid JSON object with ONLY the fields described above. No other text, explanations, or formatting before or after the JSON object.
PROMPT;

        return $prompt;
    }

    /**
     * Makes an API call to Google Gemini API
     * 
     * @param string $prompt The prompt to send to the API
     * @return array|null The processed API response or null on failure
     */
    public function callGeminiApi(string $prompt): ?array
    {
        if (empty($this->apiKey)) {
            $this->logger->error('Cannot call Gemini API: API key is not set');
            return null;
        }

        // Prepare the request payload
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->temperature
            ]
        ];

        // Initialize cURL session
        $ch = curl_init($this->apiEndpoint . '?key=' . $this->apiKey);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        // Execute the request
        $this->logger->info('Calling Gemini API', ['prompt_length' => strlen($prompt)]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if ($response === false) {
            $error = curl_error($ch);
            $this->logger->error('Gemini API call failed', [
                'error' => $error,
                'http_code' => $httpCode
            ]);
            curl_close($ch);
            return null;
        }
        
$this->logger->debug('Raw Gemini API Response', ['raw_response' => $response, 'http_code' => $httpCode]);
        curl_close($ch);
        
        // Process the response
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Failed to parse Gemini API response', [
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 200)
            ]);
            return null;
        }
        
        return $this->processApiResponse($responseData);
    }

    /**
     * Processes the raw API response to extract the structured data
     * 
     * @param array $responseData The raw API response data
     * @return array|null The processed data or null on failure
     */
    private function processApiResponse(array $responseData): ?array
    {
        try {
            // Check if the response has the expected structure
            if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $this->logger->error('Unexpected Gemini API response structure', [
                    'response' => json_encode($responseData)
                ]);
                return null;
            }
            
            // Extract the text content from the response
            $textContent = $responseData['candidates'][0]['content']['parts'][0]['text'];
            
            // Attempt to extract JSON from the text content
            // This regex looks for a string that starts with { and ends with }
            // and tries to be as non-greedy as possible with the content inside.
            if (preg_match('/(\{.*\})/s', $textContent, $matches)) {
                $jsonString = $matches[1];
                $parsedContent = json_decode($jsonString, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logger->error('Failed to parse extracted JSON from Gemini response text', [
                        'json_error' => json_last_error_msg(),
                        'extracted_json_string' => $jsonString,
                        'original_text_content' => $textContent
                    ]);
                    return null;
                }
            } else {
                $this->logger->error('No JSON block found in Gemini response text', [
                    'text_content' => $textContent
                ]);
                return null;
            }
            
            // Original check was here, now moved into the successful parsing block above.
            // if (json_last_error() !== JSON_ERROR_NONE) {
            //     $this->logger->error('Failed to parse JSON from Gemini response text', [
            //         'json_error' => json_last_error_msg(),
            //         'text_content' => $textContent
            //     ]);
            //     return null;
            // }
            
            // Validate the parsed content has the expected fields
            $requiredFields = ['correctedQuery', 'keywords', 'categories'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $parsedContent)) {
                    $this->logger->warning("Missing field '{$field}' in Gemini response", [
                        'parsed_content' => $parsedContent
                    ]);
                    // Initialize missing fields with defaults
                    $parsedContent[$field] = $field === 'correctedQuery' ? null : [];
                }
            }
            
            return $parsedContent;
        } catch (\Exception $e) {
            $this->logger->error('Error processing Gemini API response', [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Checks if fallback to traditional search is enabled
     * 
     * @return bool True if fallback is enabled
     */
    public function isFallbackEnabled(): bool
    {
        return $this->fallbackEnabled;
    }
}