<?php
/**
 * Main API Router
 * Routes all API requests to appropriate endpoints
 */

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

// Find the API index in the path
$apiIndex = array_search('api', $pathSegments);

if ($apiIndex === false) {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Extract the remaining path after 'api'
$apiPath = array_slice($pathSegments, $apiIndex + 1);

if (empty($apiPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not specified']);
    exit;
}

$endpoint = $apiPath[0];

// Route to appropriate endpoint
switch ($endpoint) {
    case 'users':
        require_once __DIR__ . '/users.php';
        break;
    case 'products':
        require_once __DIR__ . '/products.php';
        break;
    case 'categories':
        require_once __DIR__ . '/categories.php';
        break;
    case 'health':
        require_once __DIR__ . '/health.php';
        break;
    case 'simple-login':
        require_once __DIR__ . '/simple-login.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found: ' . $endpoint]);
        break;
}
