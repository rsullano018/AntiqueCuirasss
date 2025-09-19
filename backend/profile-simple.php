<?php
/**
 * Simple Profile Endpoint
 */

require_once __DIR__ . '/config/database.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get the Authorization header from multiple sources
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    // If not found in $_SERVER, try getallheaders()
    if (empty($authHeader)) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    
    // Check if token is provided
    if (empty($authHeader)) {
        http_response_code(401);
        echo json_encode(['error' => 'No authentication provided']);
        exit;
    }
    
    // Extract token from "Bearer TOKEN" format
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    } else {
        $token = $authHeader;
    }
    
    // Decode the token
    $tokenData = json_decode(base64_decode($token), true);
    
    if (!$tokenData || !isset($tokenData['userId'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Check if token is expired
    if (isset($tokenData['expires']) && $tokenData['expires'] < time()) {
        http_response_code(401);
        echo json_encode(['error' => 'Token expired']);
        exit;
    }
    
    // Get user from database
    $db = new DatabaseConfig();
    $users = $db->getTableData('Users');
    
    $user = null;
    foreach ($users as $u) {
        if ($u['UserID'] == $tokenData['userId'] || $u['Username'] === $tokenData['username']) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Remove password from response
    unset($user['PasswordHash']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
