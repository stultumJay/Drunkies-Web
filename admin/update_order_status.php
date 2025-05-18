<?php
require_once '../config/database.php';

// Flow: AJAX endpoint for updating order status
// 1. Include database connection

// 2. Authentication Check
// Flow: Verify admin access and return JSON response if unauthorized
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// 3. Request Validation
// Flow: Check if request is POST and has required order_id and status parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// 4. Data Preparation
// Flow: Cast order_id to integer and get status string
$order_id = (int)$_POST['order_id'];
$status = $_POST['status'];

// 5. Status Validation
// Flow: Check if provided status is one of the allowed values
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// 6. Database Update
// Flow: Update order status in database and return result
$query = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $status, $order_id);

// 7. Response
// Flow: Return JSON success/failure response based on update result
if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
} 