<?php
// Complete login flow test
echo "=== COMPLETE LOGIN FLOW TEST ===\n\n";

// Step 1: Test login
echo "1. Testing login with customer account...\n";
$loginUrl = 'http://localhost/AntiqueCuirass/backend/login-simple.php';
$loginData = json_encode(['username' => 'testcustomer456', 'password' => 'password123']);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Code: $loginHttpCode\n";
echo "Login Response: " . substr($loginResponse, 0, 300) . "...\n";

$loginData = json_decode($loginResponse, true);
if ($loginData && isset($loginData['data']['token'])) {
    $token = $loginData['data']['token'];
    $user = $loginData['data']['user'];
    echo "Login User Role: " . $user['Role'] . "\n";
    echo "Login User ID: " . $user['UserID'] . "\n";
    echo "Token: " . substr($token, 0, 50) . "...\n\n";
    
    // Step 2: Test profile endpoint
    echo "2. Testing profile endpoint with token...\n";
    $profileUrl = 'http://localhost/AntiqueCuirass/backend/profile-simple.php';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $profileUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $profileResponse = curl_exec($ch);
    $profileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Profile HTTP Code: $profileHttpCode\n";
    echo "Profile Response: " . substr($profileResponse, 0, 300) . "...\n";
    
    $profileData = json_decode($profileResponse, true);
    if ($profileData && isset($profileData['data']['Role'])) {
        echo "Profile User Role: " . $profileData['data']['Role'] . "\n";
        echo "Profile User ID: " . $profileData['data']['UserID'] . "\n";
        echo "Profile Username: " . $profileData['data']['Username'] . "\n";
        
        // Compare login vs profile data
        echo "\n=== COMPARISON ===\n";
        echo "Login Role: " . $user['Role'] . " vs Profile Role: " . $profileData['data']['Role'] . "\n";
        echo "Login UserID: " . $user['UserID'] . " vs Profile UserID: " . $profileData['data']['UserID'] . "\n";
        echo "Login Username: " . $user['Username'] . " vs Profile Username: " . $profileData['data']['Username'] . "\n";
        
        if ($user['Role'] !== $profileData['data']['Role']) {
            echo "❌ MISMATCH: Login and Profile return different roles!\n";
        } else {
            echo "✅ MATCH: Login and Profile return same role\n";
        }
    } else {
        echo "❌ Profile endpoint failed or returned no data\n";
    }
} else {
    echo "❌ Login failed or returned no token\n";
}
?>
