<?php
/**
 * API Test Script
 * Test the JSON database API endpoints
 */

require_once __DIR__ . '/api/JSONDatabase.php';

echo "<h1>Antique Cuirass API Test</h1>\n";

try {
    $db = new JSONDatabase();
    
    echo "<h2>Database Statistics</h2>\n";
    $stats = $db->getStats();
    echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h2>Categories</h2>\n";
    $categories = $db->getCategories();
    echo "<pre>" . json_encode($categories, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h2>Products</h2>\n";
    $products = $db->getProducts([], 5, 0);
    echo "<pre>" . json_encode($products, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h2>Featured Products</h2>\n";
    $featured = $db->getFeaturedProducts(3);
    echo "<pre>" . json_encode($featured, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h2>Search Test</h2>\n";
    $searchResults = $db->searchProducts('French');
    echo "<pre>" . json_encode($searchResults, JSON_PRETTY_PRINT) . "</pre>\n";
    
    echo "<h2>API Endpoints Test</h2>\n";
    echo "<p><a href='api/health.php' target='_blank'>Health Check</a></p>\n";
    echo "<p><a href='api/categories.php' target='_blank'>Categories API</a></p>\n";
    echo "<p><a href='api/products.php' target='_blank'>Products API</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>\n";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>\n";
}
