<?php
/**
 * Product Model
 * Handles product data operations using JSON database
 */

require_once __DIR__ . '/../api/JSONDatabase.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = new JSONDatabase();
    }
    
    /**
     * Get all products with optional filters
     */
    public function getAll($filters = [], $limit = 20, $offset = 0) {
        $result = $this->db->getProducts($filters, $limit, $offset);
        return $result['data'];
    }
    
    /**
     * Get a single product by ID
     */
    public function getById($productId) {
        return $this->db->getProduct($productId);
    }
    
    /**
     * Search products
     */
    public function search($query, $filters = []) {
        return $this->db->searchProducts($query, $filters);
    }
    
    /**
     * Create a new product
     */
    public function create($data) {
        $productData = $this->validateProductData($data);
        return $this->db->createProduct($productData);
    }
    
    /**
     * Update a product
     */
    public function update($productId, $data) {
        $productData = $this->validateProductData($data);
        return $this->db->updateProduct($productId, $productData);
    }
    
    /**
     * Delete a product
     */
    public function delete($productId) {
        return $this->db->deleteProduct($productId);
    }
    
    /**
     * Get featured products
     */
    public function getFeatured($limit = 6) {
        return $this->db->getFeaturedProducts($limit);
    }
    
    /**
     * Get products by category
     */
    public function getByCategory($categoryId, $limit = 20, $offset = 0) {
        $result = $this->db->getProductsByCategory($categoryId, $limit, $offset);
        return $result['data'];
    }
    
    /**
     * Format product data for API response
     */
    public function formatProduct($record) {
        return [
            'ProductID' => $record['ProductID'] ?? null,
            'ProductName' => $record['ProductName'] ?? '',
            'SKU' => $record['SKU'] ?? '',
            'Price' => floatval($record['Price'] ?? 0),
            'OriginalPrice' => floatval($record['OriginalPrice'] ?? 0),
            'ShortDescription' => $record['ShortDescription'] ?? '',
            'FullDescription' => $record['FullDescription'] ?? '',
            'CategoryID' => $record['CategoryID'] ?? null,
            'StockQuantity' => intval($record['StockQuantity'] ?? 0),
            'ConditionRating' => $record['ConditionRating'] ?? '',
            'Material' => $record['Material'] ?? '',
            'Weight' => $record['Weight'] ?? '',
            'Dimensions' => $record['Dimensions'] ?? '',
            'EraPeriod' => $record['EraPeriod'] ?? '',
            'Manufacturer' => $record['Manufacturer'] ?? '',
            'OriginCountry' => $record['OriginCountry'] ?? '',
            'YearManufactured' => $record['YearManufactured'] ?? '',
            'Provenance' => $record['Provenance'] ?? '',
            'AuthenticityVerified' => $record['AuthenticityVerified'] ?? false,
            'Featured' => $record['Featured'] ?? false,
            'Status' => $record['Status'] ?? 'Active',
            'MetaTitle' => $record['MetaTitle'] ?? '',
            'MetaDescription' => $record['MetaDescription'] ?? '',
            'Slug' => $record['Slug'] ?? '',
            'DateCreated' => $record['DateCreated'] ?? null,
            'DateModified' => $record['DateModified'] ?? null,
            'image' => $this->getProductImage($record['ProductID'])
        ];
    }
    
    /**
     * Format multiple products
     */
    public function formatProducts($records) {
        $products = [];
        foreach ($records as $record) {
            $products[] = $this->formatProduct($record);
        }
        return $products;
    }
    
    /**
     * Get product image
     */
    private function getProductImage($productId) {
        $images = $this->db->getProductImages($productId);
        foreach ($images as $image) {
            if ($image['IsPrimary'] || !isset($primaryImage)) {
                return $image['ImageURL'] ?? '/assets/images/placeholder.jpg';
            }
        }
        return '/assets/images/placeholder.jpg';
    }
    
    /**
     * Validate product data before saving
     */
    private function validateProductData($data) {
        $validated = [];
        
        // Required fields
        $requiredFields = ['ProductName', 'SKU', 'Price', 'CategoryID'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
            $validated[$field] = $data[$field];
        }
        
        // Optional fields with validation
        $optionalFields = [
            'OriginalPrice' => 'float',
            'ShortDescription' => 'string',
            'FullDescription' => 'string',
            'StockQuantity' => 'int',
            'ConditionRating' => 'string',
            'Material' => 'string',
            'Weight' => 'string',
            'Dimensions' => 'string',
            'EraPeriod' => 'string',
            'Manufacturer' => 'string',
            'OriginCountry' => 'string',
            'YearManufactured' => 'string',
            'Provenance' => 'string',
            'AuthenticityVerified' => 'bool',
            'Featured' => 'bool',
            'Status' => 'string',
            'MetaTitle' => 'string',
            'MetaDescription' => 'string',
            'Slug' => 'string'
        ];
        
        foreach ($optionalFields as $field => $type) {
            if (isset($data[$field])) {
                switch ($type) {
                    case 'float':
                        $validated[$field] = floatval($data[$field]);
                        break;
                    case 'int':
                        $validated[$field] = intval($data[$field]);
                        break;
                    case 'bool':
                        $validated[$field] = (bool)$data[$field];
                        break;
                    default:
                        $validated[$field] = (string)$data[$field];
                }
            }
        }
        
        return $validated;
    }
}