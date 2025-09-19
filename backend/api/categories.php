<?php
/**
 * Categories API Endpoint
 * Handles all category-related API requests using JSON database
 */

require_once __DIR__ . '/../api/JSONDatabase.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the URI to extract path segments
$pathSegments = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));
$apiIndex = array_search('api', $pathSegments);

if ($apiIndex === false) {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Extract the remaining path after 'api'
$apiPath = array_slice($pathSegments, $apiIndex + 1);

// Initialize database
$db = new JSONDatabase();

// Route the request
try {
    switch ($method) {
        case 'GET':
            if (count($apiPath) === 1 && $apiPath[0] === 'categories') {
                // GET /api/categories
                $categories = $db->getCategories();
                echo json_encode([
                    'success' => true,
                    'data' => $categories,
                    'count' => count($categories)
                ]);
            } elseif (count($apiPath) === 2 && $apiPath[0] === 'categories') {
                // GET /api/categories/{id}
                $categoryId = $apiPath[1];
                $category = $db->getCategory($categoryId);
                
                if ($category) {
                    echo json_encode([
                        'success' => true,
                        'data' => $category
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => ['message' => 'Category not found']
                    ]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if (count($apiPath) === 1 && $apiPath[0] === 'categories') {
                // POST /api/categories
                $input = json_decode(file_get_contents('php://input'), true);
                if ($input === null) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON data']);
                    exit;
                }
                
                $success = $db->createCategory($input);
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Category created successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => ['message' => 'Failed to create category']
                    ]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            if (count($apiPath) === 2 && $apiPath[0] === 'categories') {
                // PUT /api/categories/{id}
                $categoryId = $apiPath[1];
                $input = json_decode(file_get_contents('php://input'), true);
                if ($input === null) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON data']);
                    exit;
                }
                
                $success = $db->updateCategory($categoryId, $input);
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Category updated successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => ['message' => 'Failed to update category']
                    ]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'DELETE':
            if (count($apiPath) === 2 && $apiPath[0] === 'categories') {
                // DELETE /api/categories/{id}
                $categoryId = $apiPath[1];
                $success = $db->deleteCategory($categoryId);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Category deleted successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => ['message' => 'Failed to delete category']
                    ]);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
