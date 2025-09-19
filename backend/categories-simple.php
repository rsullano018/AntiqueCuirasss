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
            'CategoryName' => 'Test Category',
            'Description' => 'Test category description'
        ];
    }
}

try {
    switch ($method) {
        case 'GET':
            if ($action === 'categories' || $action === 'categories-simple.php') {
                handleGetCategories();
            } else if (is_numeric($action)) {
                handleGetCategory($action);
            } else {
                sendResponse(false, 'Invalid endpoint', null, 404);
            }
            break;
            
        case 'POST':
            if ($action === 'categories' || $action === 'categories-simple.php') {
                handleCreateCategory($input);
            } else {
                sendResponse(false, 'Invalid endpoint', null, 404);
            }
            break;
            
        case 'PUT':
            if (is_numeric($action)) {
                handleUpdateCategory($action, $input);
            } else {
                sendResponse(false, 'Invalid endpoint', null, 404);
            }
            break;
            
        case 'DELETE':
            if (is_numeric($action)) {
                handleDeleteCategory($action);
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

function handleGetCategories() {
    global $db;
    
    try {
        $categories = $db->getTableData('Categories');
        
        if (empty($categories)) {
            // Create default categories if none exist
            $defaultCategories = [
                [
                    'CategoryID' => 1,
                    'CategoryName' => 'Military Antiques',
                    'Description' => 'Authentic military equipment and memorabilia from various historical periods',
                    'Status' => 'Active',
                    'DateCreated' => date('Y-m-d H:i:s'),
                    'DateModified' => date('Y-m-d H:i:s')
                ],
                [
                    'CategoryID' => 2,
                    'CategoryName' => 'Medieval Armor',
                    'Description' => 'Medieval armor pieces including helmets, cuirasses, and protective gear',
                    'Status' => 'Active',
                    'DateCreated' => date('Y-m-d H:i:s'),
                    'DateModified' => date('Y-m-d H:i:s')
                ],
                [
                    'CategoryID' => 3,
                    'CategoryName' => 'Cuirasses',
                    'Description' => 'Breastplates and torso armor from various historical periods',
                    'Status' => 'Active',
                    'DateCreated' => date('Y-m-d H:i:s'),
                    'DateModified' => date('Y-m-d H:i:s')
                ],
                [
                    'CategoryID' => 4,
                    'CategoryName' => 'Helmets',
                    'Description' => 'Military and historical helmets from different eras',
                    'Status' => 'Active',
                    'DateCreated' => date('Y-m-d H:i:s'),
                    'DateModified' => date('Y-m-d H:i:s')
                ],
                [
                    'CategoryID' => 5,
                    'CategoryName' => 'Weapons',
                    'Description' => 'Historical weapons including swords, daggers, and other military equipment',
                    'Status' => 'Active',
                    'DateCreated' => date('Y-m-d H:i:s'),
                    'DateModified' => date('Y-m-d H:i:s')
                ]
            ];
            
            $db->updateTableData('Categories', $defaultCategories);
            $categories = $defaultCategories;
        }
        
        sendResponse(true, 'Categories retrieved successfully', $categories);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to retrieve categories: ' . $e->getMessage(), null, 500);
    }
}

function handleGetCategory($categoryId) {
    global $db;
    
    try {
        $categories = $db->getTableData('Categories');
        $category = null;
        
        foreach ($categories as $cat) {
            if ($cat['CategoryID'] == $categoryId) {
                $category = $cat;
                break;
            }
        }
        
        if ($category) {
            sendResponse(true, 'Category retrieved successfully', $category);
        } else {
            sendResponse(false, 'Category not found', null, 404);
        }
    } catch (Exception $e) {
        sendResponse(false, 'Failed to retrieve category: ' . $e->getMessage(), null, 500);
    }
}

function handleCreateCategory($data) {
    global $db;
    
    $categoryName = $data['CategoryName'] ?? '';
    $description = $data['Description'] ?? '';
    
    if (empty($categoryName)) {
        sendResponse(false, 'Category name is required', null, 400);
        return;
    }
    
    try {
        $categories = $db->getTableData('Categories');
        
        // Find the next available ID
        $maxId = 0;
        foreach ($categories as $cat) {
            $id = intval($cat['CategoryID']);
            if ($id > $maxId) {
                $maxId = $id;
            }
        }
        $newId = $maxId + 1;
        
        // Create new category
        $newCategory = [
            'CategoryID' => $newId,
            'CategoryName' => $categoryName,
            'Description' => $description,
            'Status' => 'Active',
            'DateCreated' => date('Y-m-d H:i:s'),
            'DateModified' => date('Y-m-d H:i:s')
        ];
        
        // Add category to database
        $categories[] = $newCategory;
        $db->updateTableData('Categories', $categories);
        
        sendResponse(true, 'Category created successfully', $newCategory);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to create category: ' . $e->getMessage(), null, 500);
    }
}

function handleUpdateCategory($categoryId, $data) {
    global $db;
    
    try {
        $categories = $db->getTableData('Categories');
        $categoryIndex = -1;
        
        foreach ($categories as $index => $cat) {
            if ($cat['CategoryID'] == $categoryId) {
                $categoryIndex = $index;
                break;
            }
        }
        
        if ($categoryIndex === -1) {
            sendResponse(false, 'Category not found', null, 404);
            return;
        }
        
        // Update category
        $categories[$categoryIndex]['CategoryName'] = $data['CategoryName'] ?? $categories[$categoryIndex]['CategoryName'];
        $categories[$categoryIndex]['Description'] = $data['Description'] ?? $categories[$categoryIndex]['Description'];
        $categories[$categoryIndex]['Status'] = $data['Status'] ?? $categories[$categoryIndex]['Status'];
        $categories[$categoryIndex]['DateModified'] = date('Y-m-d H:i:s');
        
        $db->updateTableData('Categories', $categories);
        
        sendResponse(true, 'Category updated successfully', $categories[$categoryIndex]);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to update category: ' . $e->getMessage(), null, 500);
    }
}

function handleDeleteCategory($categoryId) {
    global $db;
    
    try {
        $categories = $db->getTableData('Categories');
        $categoryIndex = -1;
        
        foreach ($categories as $index => $cat) {
            if ($cat['CategoryID'] == $categoryId) {
                $categoryIndex = $index;
                break;
            }
        }
        
        if ($categoryIndex === -1) {
            sendResponse(false, 'Category not found', null, 404);
            return;
        }
        
        // Remove category
        array_splice($categories, $categoryIndex, 1);
        $db->updateTableData('Categories', $categories);
        
        sendResponse(true, 'Category deleted successfully', ['id' => $categoryId]);
    } catch (Exception $e) {
        sendResponse(false, 'Failed to delete category: ' . $e->getMessage(), null, 500);
    }
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
