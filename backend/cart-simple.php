<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Parse the request URI
$path = parse_url($requestUri, PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Remove 'AntiqueCuirass/backend' from path if present
if (in_array('AntiqueCuirass', $pathSegments)) {
    $pathSegments = array_slice($pathSegments, 2);
}
if (in_array('backend', $pathSegments)) {
    $pathSegments = array_slice($pathSegments, 1);
}

// Get the action from the path
$action = end($pathSegments);

// Initialize database
$db = new DatabaseConfig();

// Mock input for testing
$input = null;
if ($method === 'POST' || $method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $input = json_decode($rawInput, true);
    }
    
    // Mock input for testing
    if (!$input) {
        $input = [
            'productId' => '1',
            'quantity' => 1
        ];
    }
}

try {
    switch ($method) {
        case 'GET':
            if ($action === 'cart' || $action === 'cart-simple.php' || empty($action)) {
                handleGetCart();
            } else {
                sendResponse(false, 'Invalid endpoint', null, 404);
            }
            break;
            
        case 'POST':
            if ($action === 'add') {
                handleAddToCart($input);
            } else if ($action === 'clear') {
                handleClearCart();
            } else {
                sendResponse(false, 'Invalid endpoint', null, 404);
            }
            break;
            
        case 'PUT':
            if (is_numeric($action)) {
                handleUpdateCartItem($action, $input);
            } else {
                sendResponse(false, 'Invalid endpoint', null, 404);
            }
            break;
            
        case 'DELETE':
            if (is_numeric($action)) {
                handleRemoveFromCart($action);
            } else {
                sendResponse(false, 'Invalid endpoint', null, 404);
            }
            break;
            
        default:
            sendResponse(false, 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    sendResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
}

function handleGetCart() {
    global $db;
    
    // For now, return empty cart
    // In a real application, this would get cart from database or session
    $cart = [];
    
    sendResponse(true, 'Cart retrieved successfully', $cart);
}

function handleAddToCart($data) {
    global $db;
    
    $productId = $data['productId'] ?? null;
    $quantity = intval($data['quantity'] ?? 1);
    
    if (!$productId) {
        sendResponse(false, 'Product ID is required', null, 400);
        return;
    }
    
    // Get product details
    $products = $db->getTableData('Products');
    $product = null;
    foreach ($products as $p) {
        if ($p['ProductID'] == $productId) {
            $product = $p;
            break;
        }
    }
    
    if (!$product) {
        sendResponse(false, 'Product not found', null, 404);
        return;
    }
    
    // Create cart item
    $cartItem = [
        'id' => uniqid(),
        'productId' => $productId,
        'quantity' => $quantity,
        'product' => $product,
        'addedAt' => date('Y-m-d H:i:s')
    ];
    
    sendResponse(true, 'Item added to cart successfully', $cartItem);
}

function handleUpdateCartItem($itemId, $data) {
    $quantity = intval($data['quantity'] ?? 1);
    
    if ($quantity <= 0) {
        sendResponse(false, 'Quantity must be greater than 0', null, 400);
        return;
    }
    
    // In a real application, this would update the cart item in database
    sendResponse(true, 'Cart item updated successfully', [
        'id' => $itemId,
        'quantity' => $quantity
    ]);
}

function handleRemoveFromCart($itemId) {
    // In a real application, this would remove the item from database
    sendResponse(true, 'Item removed from cart successfully', [
        'id' => $itemId
    ]);
}

function handleClearCart() {
    // In a real application, this would clear the cart in database
    sendResponse(true, 'Cart cleared successfully', []);
}

function sendResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}
?>
