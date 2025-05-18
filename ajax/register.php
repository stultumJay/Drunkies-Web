<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Validate required fields
$required_fields = ['username', 'email', 'password', 'confirm_password', 'name', 'phone', 'address'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
}

// Validate terms acceptance
if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
    echo json_encode(['success' => false, 'message' => 'You must accept the terms and conditions']);
    exit;
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);

// Validate password match
if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Validate password strength
$password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
if (!preg_match($password_pattern, $password)) {
    echo json_encode(['success' => false, 'message' => 'Password does not meet requirements']);
    exit;
}

// Check if username exists
$query = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}

// Check if email exists
$query = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$query = "INSERT INTO users (username, email, password, name, phone, address) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('ssssss', $username, $email, $hashed_password, $name, $phone, $address);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
} 