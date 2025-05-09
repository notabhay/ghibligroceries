<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Registry;
use App\Core\Request;
use App\Models\Product;
use App\Services\GeminiService;

/**
 * Controller for handling AI-powered search functionality
 * 
 * Manages AJAX requests for AI-enhanced product searches using the Gemini API
 */
class AiSearchController extends BaseController
{
    /**
     * @var Request HTTP request handling instance
     */
    private Request $request;
    
    /**
     * @var GeminiService Service for interacting with Gemini API
     */
    private GeminiService $geminiService;
    
    /**
     * @var Product Product model instance
     */
    private Product $productModel;
    
    /**
     * @var \Psr\Log\LoggerInterface Logger instance
     */
    private $logger;

    /**
     * Constructor
     * 
     * Initializes dependencies from the Registry
     */
    public function __construct()
    {
        $this->request = Registry::get('request');
        $this->logger = Registry::get('logger');
        
        // Initialize the Gemini service
        $this->geminiService = new GeminiService();
        
        // Initialize the Product model
        $db = Registry::get('database');
        $this->productModel = new Product($db->getConnection());
    }

    /**
     * Handles AI search requests
     * 
     * Processes requests to /api/ai-search, coordinates with GeminiService
     * and ProductModel to perform AI-enhanced product searches.
     * Accepts query from either JSON POST body or GET parameter.
     * 
     * @return void Outputs JSON response
     */
    public function search(): void
    {
        // Get the search query from the JSON body of the POST request
        $jsonData = $this->request->json();
        $query = isset($jsonData['q']) ? trim($jsonData['q']) : '';
        
        // If query is empty, check GET parameters (for direct API calls or fallback)
        if (empty($query)) {
            $query = trim($this->request->get('q', ''));
        }
        
        $this->logger->info('AI search initiated', ['query' => $query]);
        
        // Validate the search query
        if (empty($query)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
            return;
        }
        
        try {
            // Get product categories for context
            $categories = $this->productModel->getProductCategories();
            
            // Call Gemini API to enhance the search query
            $enhancedParams = $this->geminiService->callGeminiApi(
                $this->geminiService->constructPrompt($query, $categories)
            );
            
            // If Gemini API call fails and fallback is enabled, use traditional search
            if ($enhancedParams === null) {
                if ($this->geminiService->isFallbackEnabled()) {
                    $this->logger->info('Falling back to traditional search', ['query' => $query]);
                    $products = $this->productModel->searchByNameOrDescription($query);
                    
                    $this->jsonResponse([
                        'success' => true,
                        'products' => $products,
                        'totalResults' => count($products),
                        'searchTerm' => $query,
                        'fallback' => true
                    ]);
                    return;
                } else {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'AI search is temporarily unavailable'
                    ], 503);
                    return;
                }
            }
            
// Log the full enhanced parameters received from Gemini for debugging
            $this->logger->debug('Full Gemini Output (Enhanced Params)', ['enhanced_params' => $enhancedParams]);
            // Log the enhanced search parameters
            $this->logger->info('Enhanced search parameters', [
                'original_query' => $query,
                'corrected_query' => $enhancedParams['correctedQuery'],
                'keywords_count' => count($enhancedParams['keywords']),
                'suggested_categories_count' => count($enhancedParams['categories'])
            ]);
            
            // Search for products using the enhanced parameters
            $products = $this->productModel->searchWithEnhancedTerms($enhancedParams);
            
            // Prepare the response
            $response = [
                'success' => true,
                'products' => $products,
                'totalResults' => count($products),
                'searchTerm' => $query
            ];
            
            // Add corrected term suggestion if available
            if (!empty($enhancedParams['correctedQuery']) && $enhancedParams['correctedQuery'] !== $query) {
                $response['correctedTerm'] = $enhancedParams['correctedQuery'];
            }
            
            $this->jsonResponse($response);
            
        } catch (\Exception $e) {
            $this->logger->error('Error during AI search', [
                'query' => $query,
                'exception' => $e->getMessage()
            ]);
            
            // Handle errors gracefully
            if ($this->geminiService->isFallbackEnabled()) {
                // Fall back to traditional search
                $products = $this->productModel->searchByNameOrDescription($query);
                
                $this->jsonResponse([
                    'success' => true,
                    'products' => $products,
                    'totalResults' => count($products),
                    'searchTerm' => $query,
                    'fallback' => true
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'An error occurred during search'
                ], 500);
            }
        }
    }

    /**
     * Sends a JSON response
     * 
     * @param mixed $data The data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return void
     */
    private function jsonResponse($data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($statusCode);
        } else {
            $this->logger->warning('Headers already sent, cannot set JSON response headers');
        }
        
        echo json_encode($data);
    }
}