<?php
// Flow: Core Functions Library
// Contains essential functions used throughout the application

// 1. Session and Dependencies
// Flow: Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flow: Include error handling and database connection
require_once __DIR__ . '/error_handling.php';
require_once __DIR__ . '/../config/database.php';

// 2. Authentication Functions
// Flow: Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Flow: Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Flow: Get current user's data from database
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    global $conn;
    $userId = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// 3. Navigation and Security Functions
// Flow: Redirect to another page
function redirect($location) {
    header("Location: $location");
    exit();
}

// Flow: Sanitize input to prevent XSS
function sanitize($input) {
    return htmlspecialchars(strip_tags($input));
}

// 4. CSRF Protection
// Flow: Generate CSRF token for forms
function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Flow: Validate submitted CSRF token
function validateToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// 5. Flash Messages
// Flow: Set flash message for next request
function flashMessage($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Flow: Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// 6. Formatting Functions
// Flow: Format price with peso sign and decimals
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

// 7. File Handling
// Flow: Upload and validate image files
function uploadImage($file, $directory = 'uploads/') {
    $target_dir = __DIR__ . '/../' . $directory;
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Flow: Check if file type is allowed
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }

    // Flow: Move uploaded file to target directory
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $directory . $new_filename;
    }
    return false;
}

// 8. Data Retrieval Functions
// Flow: Get all brands for navigation and forms
function getAllBrands() {
    global $conn;
    $query = "SELECT * FROM brands ORDER BY name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Flow: Get all categories for navigation and forms
function getAllCategories() {
    global $conn;
    $query = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}
?> 