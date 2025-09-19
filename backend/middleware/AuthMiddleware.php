<?php
/**
 * Authentication Middleware
 * Handles JWT token validation and role-based access control
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';

class AuthMiddleware {
    private $db;
    private $jwtSecret;
    
    public function __construct() {
        $this->db = new DatabaseConfig();
        $this->jwtSecret = $this->getJWTSecret();
    }
    
    /**
     * Get JWT secret from environment or config
     */
    private function getJWTSecret() {
        // Try to get from environment variable first
        $secret = getenv('JWT_SECRET');
        if ($secret) {
            return $secret;
        }
        
        // Fallback to default (should be changed in production)
        return 'antique-cuirass-jwt-secret-key-2024';
    }
    
    /**
     * Authenticate user from Authorization header
     */
    public function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorized('No valid authorization header found');
        }
        
        $token = $matches[1];
        $payload = $this->validateJWT($token);
        
        if (!$payload) {
            return $this->unauthorized('Invalid or expired token');
        }
        
        $user = $this->getUserById($payload['user_id']);
        if (!$user || !$user->IsActive) {
            return $this->unauthorized('User not found or inactive');
        }
        
        // Update last login
        $this->updateLastLogin($user->UserID);
        
        return [
            'success' => true,
            'user' => $user
        ];
    }
    
    /**
     * Check if user has required permission
     */
    public function requirePermission($permission) {
        $authResult = $this->authenticate();
        
        if (!$authResult['success']) {
            return $authResult;
        }
        
        $user = $authResult['user'];
        if (!$user->hasPermission($permission)) {
            return $this->forbidden("Insufficient permissions. Required: {$permission}");
        }
        
        return $authResult;
    }
    
    /**
     * Check if user has any of the required roles
     */
    public function requireRole($roles) {
        $authResult = $this->authenticate();
        
        if (!$authResult['success']) {
            return $authResult;
        }
        
        $user = $authResult['user'];
        $userRole = $user->Role;
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($userRole, $roles)) {
            return $this->forbidden("Access denied. Required roles: " . implode(', ', $roles));
        }
        
        return $authResult;
    }
    
    /**
     * Check if user is admin
     */
    public function requireAdmin() {
        return $this->requireRole(User::ROLE_ADMIN);
    }
    
    /**
     * Check if user is admin or content manager
     */
    public function requireAdminOrContentManager() {
        return $this->requireRole([User::ROLE_ADMIN, User::ROLE_CONTENT_MANAGER]);
    }
    
    /**
     * Validate JWT token
     */
    private function validateJWT($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            $header = json_decode($this->base64UrlDecode($parts[0]), true);
            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            $signature = $this->base64UrlDecode($parts[2]);
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->jwtSecret, true);
            if (!hash_equals($signature, $expectedSignature)) {
                return false;
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate JWT token
     */
    public function generateToken($user) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user->UserID,
            'username' => $user->Username,
            'role' => $user->Role,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]);
        
        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->jwtSecret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        $users = $this->db->getTableData('Users');
        foreach ($users as $userData) {
            if ($userData['UserID'] == $userId) {
                return User::fromArray($userData);
            }
        }
        return null;
    }
    
    /**
     * Update user's last login
     */
    private function updateLastLogin($userId) {
        $users = $this->db->getTableData('Users');
        foreach ($users as $index => $userData) {
            if ($userData['UserID'] == $userId) {
                $users[$index]['LastLogin'] = date('Y-m-d H:i:s');
                $this->db->updateTableData('Users', $users);
                break;
            }
        }
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Return unauthorized response
     */
    private function unauthorized($message = 'Unauthorized') {
        http_response_code(401);
        return [
            'success' => false,
            'error' => $message
        ];
    }
    
    /**
     * Return forbidden response
     */
    private function forbidden($message = 'Forbidden') {
        http_response_code(403);
        return [
            'success' => false,
            'error' => $message
        ];
    }
    
    /**
     * Get current user from request
     */
    public function getCurrentUser() {
        $authResult = $this->authenticate();
        return $authResult['success'] ? $authResult['user'] : null;
    }
    
    /**
     * Check if user is authenticated (without throwing errors)
     */
    public function isAuthenticated() {
        $authResult = $this->authenticate();
        return $authResult['success'];
    }
}
