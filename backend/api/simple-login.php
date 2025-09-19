<?php
/**
 * Simple Login API for testing
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

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new DatabaseConfig();
    $users = $db->getTableData('Users');
    
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
    
    // Find user by username
    $userData = null;
    foreach ($users as $u) {
        if ($u['Username'] === $input['username']) {
            $userData = $u;
            break;
        }
    }
    
    if (!$userData) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }
    
    // Create user object and verify password
    $user = User::fromArray($userData);
    
    if ($user->verifyPassword($input['password'])) {
        // Generate token
        $tokenData = [
            'userId' => $user->UserID,
            'username' => $user->Username,
            'role' => $user->Role,
            'expires' => time() + (24 * 60 * 60) // 24 hours
        ];
        $token = base64_encode(json_encode($tokenData));
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user->toArray()
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
