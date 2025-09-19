<?php
/**
 * Simple Profile Test - Direct Access
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
    
    // For now, just return the admin user (in a real app, you'd check the token)
    echo json_encode([
        'success' => true,
        'data' => $adminUser->toArray()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
