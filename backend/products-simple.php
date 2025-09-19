<?php
/**
 * Simple Products API Endpoint
 */

require_once __DIR__ . '/config/database.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $db = new DatabaseConfig();
    $products = $db->getTableData('Products');
    $categories = $db->getTableData('Categories');
    
    if ($method === 'GET') {
        // Get all products
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $products
        ]);
        
    } elseif ($method === 'POST') {
        // Create new product
        $input = isset($GLOBALS['mock_input']) ? $GLOBALS['mock_input'] : file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            exit;
        }
        
        // Generate new product ID
        $maxId = 0;
        foreach ($products as $product) {
            $id = intval($product['ProductID']);
            if ($id > $maxId) {
                $maxId = $id;
            }
        }
        $newId = $maxId + 1;
        
        // Create new product
        $newProduct = [
            'ProductID' => (string)$newId,
            'ProductName' => $data['ProductName'] ?? '',
            'SKU' => $data['SKU'] ?? '',
            'Price' => floatval($data['Price'] ?? 0),
            'OriginalPrice' => isset($data['OriginalPrice']) ? floatval($data['OriginalPrice']) : null,
            'ShortDescription' => $data['ShortDescription'] ?? '',
            'FullDescription' => $data['FullDescription'] ?? '',
            'CategoryID' => intval($data['CategoryID'] ?? 0),
            'StockQuantity' => intval($data['StockQuantity'] ?? 0),
            'ConditionRating' => isset($data['ConditionRating']) ? intval($data['ConditionRating']) : null,
            'Material' => $data['Material'] ?? '',
            'EraPeriod' => $data['EraPeriod'] ?? '',
            'Manufacturer' => $data['Manufacturer'] ?? '',
            'YearManufactured' => isset($data['YearManufactured']) ? intval($data['YearManufactured']) : null,
            'Featured' => (bool)($data['Featured'] ?? false),
            'AuthenticityVerified' => (bool)($data['AuthenticityVerified'] ?? false),
            'Status' => $data['Status'] ?? 'Active',
            'Images' => $data['Images'] ?? [],
            'DateCreated' => date('Y-m-d H:i:s'),
            'DateModified' => date('Y-m-d H:i:s')
        ];
        
        // Add product to database
        $products[] = $newProduct;
        $db->updateTableData('Products', $products);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $newProduct
        ]);
        
    } elseif ($method === 'PUT') {
        // Update product
        $input = isset($GLOBALS['mock_input']) ? $GLOBALS['mock_input'] : file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($data === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            exit;
        }
        
        // Get product ID from URL path
        $path = $_SERVER['REQUEST_URI'] ?? '';
        $pathParts = explode('/', trim($path, '/'));
        $productId = end($pathParts);
        
        if (empty($productId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID required']);
            exit;
        }
        
        // Find and update product
        $updated = false;
        foreach ($products as $index => $product) {
            if ($product['ProductID'] == $productId) {
                $products[$index] = array_merge($product, $data);
                $products[$index]['DateModified'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            $db->updateTableData('Products', $products);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $products[$index]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
        
    } elseif ($method === 'DELETE') {
        // Delete product
        $path = $_SERVER['REQUEST_URI'] ?? '';
        $pathParts = explode('/', trim($path, '/'));
        $productId = end($pathParts);
        
        if (empty($productId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID required']);
            exit;
        }
        
        // Find and remove product
        $updated = false;
        foreach ($products as $index => $product) {
            if ($product['ProductID'] == $productId) {
                unset($products[$index]);
                $products = array_values($products); // Re-index array
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            $db->updateTableData('Products', $products);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
