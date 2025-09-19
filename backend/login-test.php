<?php
/**
 * Simple Login Test - Direct Access
 */

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

require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = new DatabaseConfig();
    $users = $db->getTableData('Users');
    
    // Find admin user
    $adminUser = null;
    foreach ($users as $userData) {
        if ($userData['Username'] === 'admin') {
            $adminUser = User::fromArray($userData);
            break;
        }
    }
    
    if (!$adminUser) {
        echo json_encode([
            'success' => false,
            'error' => 'Admin user not found'
        ]);
        exit;
    }
    
    // Get input data
    $inputData = isset($GLOBALS['mock_input']) ? $GLOBALS['mock_input'] : file_get_contents('php://input');
    $input = json_decode($inputData, true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Check credentials
    if ($input['username'] === 'admin' && $adminUser->verifyPassword($input['password'])) {
        // Generate token
        $token = 'admin-token-' . time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $adminUser->toArray()
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid credentials'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
