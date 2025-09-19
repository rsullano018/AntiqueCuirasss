<?php
/**
 * Products API Endpoint
 * Handles all product-related API requests using JSON database
 */

require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

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

// Remove 'products' from the path since we're already in the products endpoint
if (count($apiPath) > 0 && $apiPath[0] === 'products') {
    $apiPath = array_slice($apiPath, 1);
}

// Initialize dependencies
$controller = new ProductController();
$auth = new AuthMiddleware();

// Route the request
try {
    switch ($method) {
        case 'GET':
            if (count($apiPath) === 1 && $apiPath[0] === 'products') {
                // GET /api/products
                $filters = $_GET;
                $controller->handleGet($filters);
            } elseif (count($apiPath) === 2 && $apiPath[0] === 'products' && $apiPath[1] === 'featured') {
                // GET /api/products/featured
                $limit = intval($_GET['limit'] ?? 6);
                $controller->handleGetFeatured($limit);
            } elseif (count($apiPath) === 3 && $apiPath[0] === 'products' && $apiPath[1] === 'category') {
                // GET /api/products/category/{id}
                $categoryId = $apiPath[2];
                $limit = intval($_GET['limit'] ?? 20);
                $offset = intval($_GET['offset'] ?? 0);
                $controller->handleGetByCategory($categoryId, $limit, $offset);
            } elseif (count($apiPath) === 2 && $apiPath[0] === 'products') {
                // GET /api/products/{id}
                $productId = $apiPath[1];
                $controller->handleGetById($productId);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if (count($apiPath) === 1 && $apiPath[0] === 'products') {
                // POST /api/products - Require content manager or admin
                $authResult = $auth->requirePermission('create_products');
                if (!$authResult['success']) {
                    http_response_code(403);
                    echo json_encode($authResult);
                    exit;
                }
                
                $input = json_decode(file_get_contents('php://input'), true);
                if ($input === null) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON data']);
                    exit;
                }
                $controller->handlePost($input);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            if (count($apiPath) === 2 && $apiPath[0] === 'products') {
                // PUT /api/products/{id} - Require content manager or admin
                $authResult = $auth->requirePermission('edit_products');
                if (!$authResult['success']) {
                    http_response_code(403);
                    echo json_encode($authResult);
                    exit;
                }
                
                $productId = $apiPath[1];
                $input = json_decode(file_get_contents('php://input'), true);
                if ($input === null) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON data']);
                    exit;
                }
                $controller->handlePut($productId, $input);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'DELETE':
            if (count($apiPath) === 2 && $apiPath[0] === 'products') {
                // DELETE /api/products/{id} - Require admin only
                $authResult = $auth->requirePermission('delete_products');
                if (!$authResult['success']) {
                    http_response_code(403);
                    echo json_encode($authResult);
                    exit;
                }
                
                $productId = $apiPath[1];
                $controller->handleDelete($productId);
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