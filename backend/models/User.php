<?php
/**
 * User Model
 * Handles user data and role-based permissions
 */

class User {
    public $UserID;
    public $Username;
    public $Email;
    public $PasswordHash;
    public $FirstName;
    public $LastName;
    public $Role;
    public $IsActive;
    public $LastLogin;
    public $DateCreated;
    public $DateModified;
    
    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_CONTENT_MANAGER = 'content_manager';
    const ROLE_CUSTOMER = 'customer';
    
    // Permission constants
    const PERMISSION_VIEW_PRODUCTS = 'view_products';
    const PERMISSION_CREATE_PRODUCTS = 'create_products';
    const PERMISSION_EDIT_PRODUCTS = 'edit_products';
    const PERMISSION_DELETE_PRODUCTS = 'delete_products';
    const PERMISSION_VIEW_ORDERS = 'view_orders';
    const PERMISSION_MANAGE_ORDERS = 'manage_orders';
    const PERMISSION_VIEW_CUSTOMERS = 'view_customers';
    const PERMISSION_MANAGE_CUSTOMERS = 'manage_customers';
    const PERMISSION_MANAGE_USERS = 'manage_users';
    const PERMISSION_VIEW_ANALYTICS = 'view_analytics';
    const PERMISSION_MANAGE_CATEGORIES = 'manage_categories';
    const PERMISSION_UPLOAD_IMAGES = 'upload_images';
    
    /**
     * Role permissions mapping
     */
    private static $rolePermissions = [
        self::ROLE_ADMIN => [
            self::PERMISSION_VIEW_PRODUCTS,
            self::PERMISSION_CREATE_PRODUCTS,
            self::PERMISSION_EDIT_PRODUCTS,
            self::PERMISSION_DELETE_PRODUCTS,
            self::PERMISSION_VIEW_ORDERS,
            self::PERMISSION_MANAGE_ORDERS,
            self::PERMISSION_VIEW_CUSTOMERS,
            self::PERMISSION_MANAGE_CUSTOMERS,
            self::PERMISSION_MANAGE_USERS,
            self::PERMISSION_VIEW_ANALYTICS,
            self::PERMISSION_MANAGE_CATEGORIES,
            self::PERMISSION_UPLOAD_IMAGES
        ],
        self::ROLE_CONTENT_MANAGER => [
            self::PERMISSION_VIEW_PRODUCTS,
            self::PERMISSION_CREATE_PRODUCTS,
            self::PERMISSION_EDIT_PRODUCTS,
            self::PERMISSION_VIEW_ORDERS,
            self::PERMISSION_VIEW_CUSTOMERS,
            self::PERMISSION_MANAGE_CATEGORIES,
            self::PERMISSION_UPLOAD_IMAGES
        ],
        self::ROLE_CUSTOMER => [
            self::PERMISSION_VIEW_PRODUCTS
        ]
    ];
    
    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permission) {
        if (!$this->IsActive) {
            return false;
        }
        
        $permissions = self::$rolePermissions[$this->Role] ?? [];
        return in_array($permission, $permissions);
    }
    
    /**
     * Get all permissions for user's role
     */
    public function getPermissions() {
        if (!$this->IsActive) {
            return [];
        }
        
        return self::$rolePermissions[$this->Role] ?? [];
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->Role === self::ROLE_ADMIN && $this->IsActive;
    }
    
    /**
     * Check if user is content manager
     */
    public function isContentManager() {
        return $this->Role === self::ROLE_CONTENT_MANAGER && $this->IsActive;
    }
    
    /**
     * Check if user is customer
     */
    public function isCustomer() {
        return $this->Role === self::ROLE_CUSTOMER && $this->IsActive;
    }
    
    /**
     * Get user's full name
     */
    public function getFullName() {
        return trim($this->FirstName . ' ' . $this->LastName);
    }
    
    /**
     * Validate user data
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->Username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($this->Username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        
        if (empty($this->Email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->Email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($this->Role)) {
            $errors[] = 'Role is required';
        } elseif (!in_array($this->Role, [self::ROLE_ADMIN, self::ROLE_CONTENT_MANAGER, self::ROLE_CUSTOMER])) {
            $errors[] = 'Invalid role';
        }
        
        if (!empty($this->FirstName) && strlen($this->FirstName) < 2) {
            $errors[] = 'First name must be at least 2 characters';
        }
        
        if (!empty($this->LastName) && strlen($this->LastName) < 2) {
            $errors[] = 'Last name must be at least 2 characters';
        }
        
        return $errors;
    }
    
    /**
     * Hash password
     */
    public function setPassword($password) {
        $this->PasswordHash = password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->PasswordHash);
    }
    
    /**
     * Convert to array for API response
     */
    public function toArray($includeSensitive = false) {
        $data = [
            'UserID' => $this->UserID,
            'Username' => $this->Username,
            'Email' => $this->Email,
            'FirstName' => $this->FirstName,
            'LastName' => $this->LastName,
            'Role' => $this->Role,
            'IsActive' => $this->IsActive,
            'LastLogin' => $this->LastLogin,
            'DateCreated' => $this->DateCreated,
            'DateModified' => $this->DateModified,
            'FullName' => $this->getFullName(),
            'Permissions' => $this->getPermissions()
        ];
        
        if ($includeSensitive) {
            $data['PasswordHash'] = $this->PasswordHash;
        }
        
        return $data;
    }
    
    /**
     * Create from array
     */
    public static function fromArray($data) {
        $user = new self();
        
        $user->UserID = $data['UserID'] ?? null;
        $user->Username = $data['Username'] ?? '';
        $user->Email = $data['Email'] ?? '';
        $user->PasswordHash = $data['PasswordHash'] ?? '';
        $user->FirstName = $data['FirstName'] ?? '';
        $user->LastName = $data['LastName'] ?? '';
        $user->Role = $data['Role'] ?? self::ROLE_CUSTOMER;
        $user->IsActive = $data['IsActive'] ?? true;
        $user->LastLogin = $data['LastLogin'] ?? null;
        $user->DateCreated = $data['DateCreated'] ?? date('Y-m-d H:i:s');
        $user->DateModified = $data['DateModified'] ?? date('Y-m-d H:i:s');
        
        return $user;
    }
    
    /**
     * Get all available roles
     */
    public static function getAvailableRoles() {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_CONTENT_MANAGER => 'Content Manager',
            self::ROLE_CUSTOMER => 'Customer'
        ];
    }
    
    /**
     * Get all available permissions
     */
    public static function getAvailablePermissions() {
        return [
            self::PERMISSION_VIEW_PRODUCTS => 'View Products',
            self::PERMISSION_CREATE_PRODUCTS => 'Create Products',
            self::PERMISSION_EDIT_PRODUCTS => 'Edit Products',
            self::PERMISSION_DELETE_PRODUCTS => 'Delete Products',
            self::PERMISSION_VIEW_ORDERS => 'View Orders',
            self::PERMISSION_MANAGE_ORDERS => 'Manage Orders',
            self::PERMISSION_VIEW_CUSTOMERS => 'View Customers',
            self::PERMISSION_MANAGE_CUSTOMERS => 'Manage Customers',
            self::PERMISSION_MANAGE_USERS => 'Manage Users',
            self::PERMISSION_VIEW_ANALYTICS => 'View Analytics',
            self::PERMISSION_MANAGE_CATEGORIES => 'Manage Categories',
            self::PERMISSION_UPLOAD_IMAGES => 'Upload Images'
        ];
    }
}
