<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_POST['username']) || !isset($_POST['password'])) {
    error_log("Login attempt failed: Missing credentials");
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

try {
    // Get user data
    $query = "SELECT u.*, GROUP_CONCAT(r.name) as roles 
              FROM users u 
              LEFT JOIN user_roles ur ON u.id = ur.user_id 
              LEFT JOIN roles r ON ur.role_id = r.id 
              WHERE u.username = ?
              GROUP BY u.id";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['roles'] = explode(',', $user['roles']);
        $_SESSION['is_admin'] = strpos($user['roles'], 'admin') !== false;
        
        // Handle remember me
        if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (86400 * 30); // 30 days
            
            setcookie('remember_token', $token, $expiry, '/', '', true, true);
            
            // Store token in database
            $updateQuery = "UPDATE users SET remember_token = ?, token_expiry = FROM_UNIXTIME(?) WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param('sii', $token, $expiry, $user['id']);
            $stmt->execute();
        }
        
        error_log("Successful login for user: " . $username);
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $_SESSION['is_admin'] ? 'admin/index.php' : 'index.php'
        ]);
    } else {
        error_log("Failed login attempt for username: " . $username);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during login']);
} 