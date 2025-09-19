<?php
/**
 * JSON Database API
 * Handles all database operations using the converted_database.json file
 */

require_once __DIR__ . '/../config/database.php';

class JSONDatabase {
    private $db;
    
    public function __construct() {
        $this->db = new DatabaseConfig();
    }
    
    /**
     * Get all products with optional filters
     */
    public function getProducts($filters = [], $limit = 20, $offset = 0) {
        $products = $this->db->filterRecords('Products', $filters);
        
        // Apply pagination
        $total = count($products);
        $products = array_slice($products, $offset, $limit);
        
        return [
            'data' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Get a single product by ID
     */
    public function getProduct($productId) {
        $products = $this->db->getTableData('Products');
        
        foreach ($products as $product) {
            if ($product['ProductID'] == $productId) {
                return $product;
            }
        }
        
        return null;
    }
    
    /**
     * Search products
     */
    public function searchProducts($query, $filters = []) {
        $allProducts = $this->db->getTableData('Products');
        $results = [];
        
        foreach ($allProducts as $product) {
            $searchableFields = [
                $product['ProductName'] ?? '',
                $product['ShortDescription'] ?? '',
                $product['FullDescription'] ?? '',
                $product['Material'] ?? '',
                $product['EraPeriod'] ?? '',
                $product['Manufacturer'] ?? ''
            ];
            
            $searchText = strtolower(implode(' ', $searchableFields));
            $searchQuery = strtolower($query);
            
            if (strpos($searchText, $searchQuery) !== false) {
                // Apply additional filters
                $matches = true;
                foreach ($filters as $field => $value) {
                    if ($value && isset($product[$field]) && $product[$field] != $value) {
                        $matches = false;
                        break;
                    }
                }
                
                if ($matches) {
                    $results[] = $product;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get featured products
     */
    public function getFeaturedProducts($limit = 6) {
        $filters = ['Featured' => 'Yes'];
        $result = $this->getProducts($filters, $limit, 0);
        return $result['data'];
    }
    
    /**
     * Get products by category
     */
    public function getProductsByCategory($categoryId, $limit = 20, $offset = 0) {
        $filters = ['CategoryID' => $categoryId];
        return $this->getProducts($filters, $limit, $offset);
    }
    
    /**
     * Create a new product
     */
    public function createProduct($productData) {
        // Generate new ProductID
        $productData['ProductID'] = $this->db->getNextId('Products');
        
        // Set default values
        $defaults = [
            'DateCreated' => date('Y-m-d H:i:s'),
            'DateModified' => date('Y-m-d H:i:s'),
            'Status' => 'Active',
            'StockQuantity' => 0,
            'Featured' => false,
            'AuthenticityVerified' => false
        ];
        
        $productData = array_merge($defaults, $productData);
        
        return $this->db->addRecord('Products', $productData);
    }
    
    /**
     * Update a product
     */
    public function updateProduct($productId, $productData) {
        $productData['DateModified'] = date('Y-m-d H:i:s');
        return $this->db->updateRecord('Products', $productId, $productData);
    }
    
    /**
     * Delete a product
     */
    public function deleteProduct($productId) {
        return $this->db->deleteRecord('Products', $productId);
    }
    
    /**
     * Get all categories
     */
    public function getCategories() {
        return $this->db->getTableData('Categories');
    }
    
    /**
     * Get a single category by ID
     */
    public function getCategory($categoryId) {
        $categories = $this->db->getTableData('Categories');
        
        foreach ($categories as $category) {
            if ($category['CategoryID'] == $categoryId) {
                return $category;
            }
        }
        
        return null;
    }
    
    /**
     * Create a new category
     */
    public function createCategory($categoryData) {
        $categoryData['CategoryID'] = $this->db->getNextId('Categories');
        $categoryData['DateCreated'] = date('Y-m-d H:i:s');
        $categoryData['Status'] = $categoryData['Status'] ?? 'Active';
        
        return $this->db->addRecord('Categories', $categoryData);
    }
    
    /**
     * Update a category
     */
    public function updateCategory($categoryId, $categoryData) {
        return $this->db->updateRecord('Categories', $categoryId, $categoryData);
    }
    
    /**
     * Delete a category
     */
    public function deleteCategory($categoryId) {
        return $this->db->deleteRecord('Categories', $categoryId);
    }
    
    /**
     * Get all customers
     */
    public function getCustomers() {
        return $this->db->getTableData('Customers');
    }
    
    /**
     * Get a single customer by ID
     */
    public function getCustomer($customerId) {
        $customers = $this->db->getTableData('Customers');
        
        foreach ($customers as $customer) {
            if ($customer['CustomerID'] == $customerId) {
                return $customer;
            }
        }
        
        return null;
    }
    
    /**
     * Get customer by email
     */
    public function getCustomerByEmail($email) {
        $customers = $this->db->getTableData('Customers');
        
        foreach ($customers as $customer) {
            if (strtolower($customer['Email']) === strtolower($email)) {
                return $customer;
            }
        }
        
        return null;
    }
    
    /**
     * Create a new customer
     */
    public function createCustomer($customerData) {
        $customerData['CustomerID'] = $this->db->getNextId('Customers');
        $customerData['DateCreated'] = date('Y-m-d H:i:s');
        $customerData['Status'] = $customerData['Status'] ?? 'Active';
        $customerData['Role'] = $customerData['Role'] ?? 'Customer';
        
        return $this->db->addRecord('Customers', $customerData);
    }
    
    /**
     * Update a customer
     */
    public function updateCustomer($customerId, $customerData) {
        return $this->db->updateRecord('Customers', $customerId, $customerData);
    }
    
    /**
     * Get all orders
     */
    public function getOrders() {
        return $this->db->getTableData('Orders');
    }
    
    /**
     * Get a single order by ID
     */
    public function getOrder($orderId) {
        $orders = $this->db->getTableData('Orders');
        
        foreach ($orders as $order) {
            if ($order['OrderID'] == $orderId) {
                return $order;
            }
        }
        
        return null;
    }
    
    /**
     * Get orders by customer
     */
    public function getOrdersByCustomer($customerId) {
        return $this->db->searchRecords('Orders', ['CustomerID' => $customerId]);
    }
    
    /**
     * Create a new order
     */
    public function createOrder($orderData) {
        $orderData['OrderID'] = $this->db->getNextId('Orders');
        $orderData['OrderNumber'] = 'ORD-' . str_pad($orderData['OrderID'], 6, '0', STR_PAD_LEFT);
        $orderData['OrderDate'] = date('Y-m-d H:i:s');
        $orderData['OrderStatus'] = $orderData['OrderStatus'] ?? 'Pending';
        $orderData['PaymentStatus'] = $orderData['PaymentStatus'] ?? 'Pending';
        
        return $this->db->addRecord('Orders', $orderData);
    }
    
    /**
     * Update an order
     */
    public function updateOrder($orderId, $orderData) {
        return $this->db->updateRecord('Orders', $orderId, $orderData);
    }
    
    /**
     * Get order items
     */
    public function getOrderItems($orderId) {
        return $this->db->searchRecords('Orders_Items', ['OrderID' => $orderId]);
    }
    
    /**
     * Add item to order
     */
    public function addOrderItem($orderId, $productId, $quantity, $unitPrice) {
        $itemData = [
            'OrderItemID' => $this->db->getNextId('Orders_Items'),
            'OrderID' => $orderId,
            'ProductID' => $productId,
            'Quantity' => $quantity,
            'UnitPrice' => $unitPrice,
            'LineTotal' => $quantity * $unitPrice
        ];
        
        return $this->db->addRecord('Orders_Items', $itemData);
    }
    
    /**
     * Get product images
     */
    public function getProductImages($productId) {
        return $this->db->searchRecords('Product_Images', ['ProductID' => $productId]);
    }
    
    /**
     * Add product image
     */
    public function addProductImage($productId, $imageData) {
        $imageData['ImageID'] = $this->db->getNextId('Product_Images');
        $imageData['ProductID'] = $productId;
        $imageData['DateCreated'] = date('Y-m-d H:i:s');
        
        return $this->db->addRecord('Product_Images', $imageData);
    }
    
    /**
     * Delete product image
     */
    public function deleteProductImage($imageId) {
        return $this->db->deleteRecord('Product_Images', $imageId);
    }
    
    /**
     * Get database statistics
     */
    public function getStats() {
        return [
            'products' => count($this->db->getTableData('Products')),
            'categories' => count($this->db->getTableData('Categories')),
            'customers' => count($this->db->getTableData('Customers')),
            'orders' => count($this->db->getTableData('Orders')),
            'order_items' => count($this->db->getTableData('Orders_Items')),
            'images' => count($this->db->getTableData('Product_Images'))
        ];
    }
}
