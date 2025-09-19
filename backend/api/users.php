<?php
/**
 * Users API Endpoint
 * Handles user management and authentication
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Parse the URI to extract path segments
$pathSegments = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));
$apiIndex = array_search('api', $pathSegments);

if ($apiIndex === false) {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
    exit;
}

// Extract the remaining path after 'api'
$apiPath = array_slice($pathSegments, $apiIndex + 1);

// Remove 'users' from the path since we're already in the users endpoint
if (count($apiPath) > 0 && $apiPath[0] === 'users') {
    $apiPath = array_slice($apiPath, 1);
}

// Debug: Log the path for troubleshooting
error_log("API Path: " . json_encode($apiPath));

// Initialize dependencies
$db = new DatabaseConfig();
$auth = new AuthMiddleware();

// Route the request
try {
    switch ($method) {
        case 'POST':
            if (count($apiPath) === 1 && $apiPath[0] === 'register') {
                // POST /api/users/register
                handleRegister();
            } elseif (count($apiPath) === 1 && $apiPath[0] === 'login') {
                // POST /api/users/login
                handleLogin();
            } elseif (count($apiPath) === 1 && $apiPath[0] === 'logout') {
                // POST /api/users/logout
                handleLogout();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found: ' . json_encode($apiPath)]);
            }
            break;
            
        case 'GET':
            if (count($apiPath) === 1 && $apiPath[0] === 'profile') {
                // GET /api/users/profile
                handleGetProfile();
            } elseif (count($apiPath) === 0) {
                // GET /api/users (admin only)
                handleGetUsers();
            } elseif (count($apiPath) === 1 && is_numeric($apiPath[0])) {
                // GET /api/users/{id} (admin only)
                $userId = $apiPath[0];
                handleGetUser($userId);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found: ' . json_encode($apiPath)]);
            }
            break;
            
        case 'PUT':
            if (count($apiPath) === 1 && $apiPath[0] === 'profile') {
                // PUT /api/users/profile
                handleUpdateProfile();
            } elseif (count($apiPath) === 1 && is_numeric($apiPath[0])) {
                // PUT /api/users/{id} (admin only)
                $userId = $apiPath[0];
                handleUpdateUser($userId);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found: ' . json_encode($apiPath)]);
            }
            break;
            
        case 'DELETE':
            if (count($apiPath) === 1 && is_numeric($apiPath[0])) {
                // DELETE /api/users/{id} (admin only)
                $userId = $apiPath[0];
                handleDeleteUser($userId);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found: ' . json_encode($apiPath)]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle user registration
 */
function handleRegister() {
    global $db;
    
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['username', 'email', 'password', 'firstName', 'lastName'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '{$field}' is required"]);
            return;
        }
    }
    
    // Check if username or email already exists
    $users = $db->getTableData('Users');
    foreach ($users as $userData) {
        if ($userData['Username'] === $input['username']) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }
        if ($userData['Email'] === $input['email']) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            return;
        }
    }
    
    // Create new user
    $user = new User();
    $user->UserID = $db->getNextId('Users');
    $user->Username = $input['username'];
    $user->Email = $input['email'];
    $user->setPassword($input['password']);
    $user->FirstName = $input['firstName'];
    $user->LastName = $input['lastName'];
    $user->Role = User::ROLE_CUSTOMER; // Default role
    $user->IsActive = true;
    $user->DateCreated = date('Y-m-d H:i:s');
    $user->DateModified = date('Y-m-d H:i:s');
    
    // Validate user data
    $errors = $user->validate();
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
        return;
    }
    
    // Save user to database
    $userData = $user->toArray(true);
    if ($db->addRecord('Users', $userData)) {
        // Remove sensitive data from response
        unset($userData['PasswordHash']);
        echo json_encode([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $userData
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user']);
    }
}

/**
 * Handle user login
 */
function handleLogin() {
    global $db, $auth;
    
    // Handle mock input for testing
    $inputData = isset($GLOBALS['mock_input']) ? $GLOBALS['mock_input'] : file_get_contents('php://input');
    $input = json_decode($inputData, true);
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    if (empty($input['username']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }
    
    // Find user by username or email
    $users = $db->getTableData('Users');
    $user = null;
    foreach ($users as $userData) {
        if ($userData['Username'] === $input['username'] || $userData['Email'] === $input['username']) {
            $user = User::fromArray($userData);
            break;
        }
    }
    
    if (!$user || !$user->verifyPassword($input['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        return;
    }
    
    if (!$user->IsActive) {
        http_response_code(401);
        echo json_encode(['error' => 'Account is deactivated']);
        return;
    }
    
    // Generate JWT token
    $token = $auth->generateToken($user);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'token' => $token,
            'user' => $user->toArray()
        ]
    ]);
}

/**
 * Handle user logout
 */
function handleLogout() {
    // For JWT, logout is handled client-side by removing the token
    echo json_encode([
        'success' => true,
        'message' => 'Logout successful'
    ]);
}

/**
 * Handle get user profile
 */
function handleGetProfile() {
    global $auth;
    
    $authResult = $auth->authenticate();
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode($authResult);
        return;
    }
    
    $user = $authResult['user'];
    echo json_encode([
        'success' => true,
        'data' => $user->toArray()
    ]);
}

/**
 * Handle get all users (admin only)
 */
function handleGetUsers() {
    global $db, $auth;
    
    $authResult = $auth->requireAdmin();
    if (!$authResult['success']) {
        http_response_code(403);
        echo json_encode($authResult);
        return;
    }
    
    $users = $db->getTableData('Users');
    $userObjects = array_map(function($userData) {
        $user = User::fromArray($userData);
        return $user->toArray();
    }, $users);
    
    echo json_encode([
        'success' => true,
        'data' => $userObjects
    ]);
}

/**
 * Handle get single user (admin only)
 */
function handleGetUser($userId) {
    global $db, $auth;
    
    $authResult = $auth->requireAdmin();
    if (!$authResult['success']) {
        http_response_code(403);
        echo json_encode($authResult);
        return;
    }
    
    $users = $db->getTableData('Users');
    foreach ($users as $userData) {
        if ($userData['UserID'] == $userId) {
            $user = User::fromArray($userData);
            echo json_encode([
                'success' => true,
                'data' => $user->toArray()
            ]);
            return;
        }
    }
    
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
}

/**
 * Handle update user profile
 */
function handleUpdateProfile() {
    global $db, $auth;
    
    $authResult = $auth->authenticate();
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode($authResult);
        return;
    }
    
    $currentUser = $authResult['user'];
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    // Update allowed fields
    $allowedFields = ['firstName', 'lastName', 'email'];
    $updateData = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateData[ucfirst($field)] = $input[$field];
        }
    }
    
    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        return;
    }
    
    // Check if email is being changed and if it already exists
    if (isset($updateData['Email']) && $updateData['Email'] !== $currentUser->Email) {
        $users = $db->getTableData('Users');
        foreach ($users as $userData) {
            if ($userData['Email'] === $updateData['Email'] && $userData['UserID'] != $currentUser->UserID) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                return;
            }
        }
    }
    
    $updateData['DateModified'] = date('Y-m-d H:i:s');
    
    if ($db->updateRecord('Users', $currentUser->UserID, $updateData)) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
}

/**
 * Handle update user (admin only)
 */
function handleUpdateUser($userId) {
    global $db, $auth;
    
    $authResult = $auth->requireAdmin();
    if (!$authResult['success']) {
        http_response_code(403);
        echo json_encode($authResult);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }
    
    // Find user
    $users = $db->getTableData('Users');
    $userFound = false;
    foreach ($users as $index => $userData) {
        if ($userData['UserID'] == $userId) {
            $userFound = true;
            
            // Update allowed fields
            $allowedFields = ['firstName', 'lastName', 'email', 'role', 'isActive'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[ucfirst($field)] = $input[$field];
                }
            }
            
            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                return;
            }
            
            $updateData['DateModified'] = date('Y-m-d H:i:s');
            
            if ($db->updateRecord('Users', $userId, $updateData)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update user']);
            }
            break;
        }
    }
    
    if (!$userFound) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
}

/**
 * Handle delete user (admin only)
 */
function handleDeleteUser($userId) {
    global $db, $auth;
    
    $authResult = $auth->requireAdmin();
    if (!$authResult['success']) {
        http_response_code(403);
        echo json_encode($authResult);
        return;
    }
    
    // Prevent deleting own account
    $currentUser = $authResult['user'];
    if ($currentUser->UserID == $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete your own account']);
        return;
    }
    
    if ($db->deleteRecord('Users', $userId)) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
    }
}
