<?php
/**
 * Health Check API Endpoint
 * Provides system status and database information
 */

require_once __DIR__ . '/../api/JSONDatabase.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $db = new JSONDatabase();
    $stats = $db->getStats();
    
    $healthData = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'database' => [
            'type' => 'JSON',
            'file' => 'converted_database.json',
            'status' => 'connected',
            'stats' => $stats
        ],
        'api' => [
            'endpoints' => [
                'products' => '/api/products',
                'categories' => '/api/categories',
                'health' => '/api/health'
            ],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE']
        ]
    ];
    
    echo json_encode($healthData, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage()
    ]);
}
