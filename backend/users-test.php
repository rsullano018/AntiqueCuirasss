<?php
/**
 * Simple Users List Test
 */

require_once __DIR__ . '/config/database.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
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
    // Initialize database
    $db = new DatabaseConfig();
    $users = $db->getTableData('Users');
    
    // Remove password hashes from response and add FullName
    foreach ($users as &$user) {
        unset($user['PasswordHash']);
        // Create FullName field from FirstName and LastName
        $user['FullName'] = $user['FirstName'] . ' ' . $user['LastName'];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
