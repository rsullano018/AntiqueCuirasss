<?php
/**
 * Simple Login Endpoint
 */

require_once __DIR__ . '/config/database.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    if (empty($data['username']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        exit;
    }
    
    // Initialize database
    $db = new DatabaseConfig();
    $users = $db->getTableData('Users');
    
    // Find user by username or email
    $user = null;
    foreach ($users as $u) {
        if ($u['Username'] === $data['username'] || $u['Email'] === $data['username']) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    // Verify password
    if (!password_verify($data['password'], $user['PasswordHash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    // Check if user is active
    if (!$user['IsActive']) {
        http_response_code(401);
        echo json_encode(['error' => 'Account is deactivated']);
        exit;
    }
    
    // Update last login
    $user['LastLogin'] = date('Y-m-d H:i:s');
    $user['DateModified'] = date('Y-m-d H:i:s');
    
    // Update user in database
    foreach ($users as $index => $u) {
        if ($u['Username'] === $user['Username']) {
            $users[$index] = $user;
            break;
        }
    }
    $db->updateTableData('Users', $users);
    
    // Remove password from response
    unset($user['PasswordHash']);
    
    // Generate a simple token (in a real app, use JWT)
    $token = base64_encode(json_encode([
        'userId' => $user['UserID'],
        'username' => $user['Username'],
        'role' => $user['Role'],
        'expires' => time() + (24 * 60 * 60) // 24 hours
    ]));
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user' => $user,
            'token' => $token
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
