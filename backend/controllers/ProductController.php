<?php
/**
 * Product Controller
 * Handles HTTP requests for product operations
 */

require_once __DIR__ . '/../models/Product.php';

class ProductController {
    private $product;
    
    public function __construct() {
        $this->product = new Product();
    }
    
    /**
     * Handle GET request for products
     */
    public function handleGet($request) {
        try {
            $filters = $request['filters'] ?? [];
            $limit = intval($request['limit'] ?? 20);
            $offset = intval($request['offset'] ?? 0);
            
            // Check if it's a search request
            if (!empty($request['search'])) {
                $products = $this->product->search($request['search'], $filters);
            } else {
                $products = $this->product->getAll($filters, $limit, $offset);
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => $products,
                'count' => count($products)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch products: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle GET request for single product
     */
    public function handleGetById($productId) {
        try {
            $product = $this->product->getById($productId);
            
            if (!$product) {
                $this->sendError('Product not found', 404);
                return;
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => $product
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch product: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle POST request to create product
     */
    public function handlePost($data) {
        try {
            $productId = $this->product->create($data);
            
            if ($productId) {
                $this->sendResponse([
                    'success' => true,
                    'data' => ['ProductID' => $productId],
                    'message' => 'Product created successfully'
                ], 201);
            } else {
                $this->sendError('Failed to create product');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to create product: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle PUT request to update product
     */
    public function handlePut($productId, $data) {
        try {
            $success = $this->product->update($productId, $data);
            
            if ($success) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Product updated successfully'
                ]);
            } else {
                $this->sendError('Failed to update product');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to update product: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle DELETE request to delete product
     */
    public function handleDelete($productId) {
        try {
            $success = $this->product->delete($productId);
            
            if ($success) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
            } else {
                $this->sendError('Failed to delete product');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to delete product: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle GET request for featured products
     */
    public function handleGetFeatured($limit = 6) {
        try {
            $products = $this->product->getFeatured($limit);
            
            $this->sendResponse([
                'success' => true,
                'data' => $products,
                'count' => count($products)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch featured products: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle GET request for products by category
     */
    public function handleGetByCategory($categoryId, $limit = 20, $offset = 0) {
        try {
            $products = $this->product->getByCategory($categoryId, $limit, $offset);
            
            $this->sendResponse([
                'success' => true,
                'data' => $products,
                'count' => count($products)
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch products by category: ' . $e->getMessage());
        }
    }
    
    /**
     * Send successful response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $statusCode
            ]
        ]);
        exit;
    }
}
