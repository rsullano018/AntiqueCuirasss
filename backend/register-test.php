<?php
/**
 * Simple User Registration Test
 */

require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/config/database.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get input data (handle mock input for testing)
    $input = isset($GLOBALS['mock_input']) ? $GLOBALS['mock_input'] : file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate required fields
    $required = ['username', 'email', 'password', 'firstName', 'lastName', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Initialize database
    $db = new DatabaseConfig();
    $users = $db->getTableData('Users');
    
    // Check if username or email already exists
    foreach ($users as $user) {
        if ($user['Username'] === $data['username']) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            exit;
        }
        if ($user['Email'] === $data['email']) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            exit;
        }
    }
    
    // Create new user
    $newUser = [
        'UserID' => (string)(count($users) + 1),
        'Username' => $data['username'],
        'Email' => $data['email'],
        'PasswordHash' => password_hash($data['password'], PASSWORD_DEFAULT),
        'FirstName' => $data['firstName'],
        'LastName' => $data['lastName'],
        'Role' => $data['role'],
        'IsActive' => isset($data['isActive']) ? (bool)$data['isActive'] : true,
        'LastLogin' => null,
        'DateCreated' => date('Y-m-d H:i:s'),
        'DateModified' => date('Y-m-d H:i:s')
    ];
    
    // Add user to database
    $users[] = $newUser;
    $db->updateTableData('Users', $users);
    
    // Remove password from response
    unset($newUser['PasswordHash']);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'data' => $newUser
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
