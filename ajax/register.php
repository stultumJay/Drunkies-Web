<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Debug incoming data
error_log("AJAX Registration - POST data received: " . print_r($_POST, true));

// Validate required fields
$required_fields = [
    'username', 
    'email', 
    'password', 
    'confirm_password', 
    'first_name',
    'last_name',
    'birthdate'
];

$errors = [];

// Validate each required field
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
    }
}

// Validate terms acceptance
if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
    $errors[] = 'You must accept the terms and conditions';
}

if (!empty($errors)) {
    error_log("AJAX Registration - Validation errors: " . implode(", ", $errors));
    echo json_encode(['success' => false, 'message' => implode("<br>", $errors)]);
    exit;
}

// Get and sanitize input
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$birthdate = trim($_POST['birthdate']);
$street = trim($_POST['street'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$country = trim($_POST['country'] ?? '');

// Validate birthdate format and age
if (!empty($birthdate)) {
    // Attempt to normalize date if in dd/mm/yyyy format
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $birthdate)) {
        list($day, $month, $year) = explode('/', $birthdate);
        $birthdate = "$year-$month-$day"; // Convert to yyyy-mm-dd
        error_log("AJAX Registration - Converted birthdate to YYYY-MM-DD: " . $birthdate);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        error_log("AJAX Registration - Invalid birthdate format: " . $birthdate);
        echo json_encode(['success' => false, 'message' => 'Birthdate must be in YYYY-MM-DD format']);
        exit;
    }

    try {
        $birthdateObj = new DateTime($birthdate);
        $today = new DateTime();
        $age = $today->diff($birthdateObj)->y;
        
        error_log("AJAX Registration - Calculated age: " . $age);
        
        if ($age < 18) {
            echo json_encode(['success' => false, 'message' => "You must be at least 18 years old to register (you are $age years old)"]);
            exit;
        }
    } catch (Exception $e) {
        error_log("AJAX Registration - DateTime error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Invalid birthdate format']);
        exit;
    }
}

// Validate password match
if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Validate password strength
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least 8 characters, including uppercase, lowercase, numbers, and special characters']);
    exit;
}

try {
    $conn->begin_transaction();

    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Username already exists');
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (username, first_name, last_name, email, password, birthdate) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssss', $username, $first_name, $last_name, $email, $hashed_password, $birthdate);
    
    if (!$stmt->execute()) {
        throw new Exception('Error creating user account');
    }
    
    $user_id = $conn->insert_id;

    // Insert address if provided
    if (!empty($street) && !empty($city) && !empty($state) && !empty($postal_code) && !empty($country)) {
        $stmt = $conn->prepare("
            INSERT INTO addresses (user_id, street, city, state, postal_code, country) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('isssss', $user_id, $street, $city, $state, $postal_code, $country);
        
        if (!$stmt->execute()) {
            throw new Exception('Error saving address information');
        }
    }

    // Assign customer role
    $stmt = $conn->prepare("
        INSERT INTO user_roles (user_id, role_id) 
        SELECT ?, id FROM roles WHERE name = 'customer'
    ");
    $stmt->bind_param('i', $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error assigning user role');
    }

    $conn->commit();
    error_log("AJAX Registration - Successfully registered user: " . $username);
    echo json_encode(['success' => true, 'message' => 'Registration successful! You can now login.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("AJAX Registration - Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 