<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('Please login to add a review');
    }

    // Validate input
    $required_fields = ['product_id', 'rating', 'comment'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception(ucfirst($field) . ' is required');
        }
    }

    $productId = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    if ($productId === false) {
        throw new Exception('Invalid product ID');
    }

    $userId = $_SESSION['user_id'];
    $rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT);
    if ($rating === false || $rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }

    $comment = trim($_POST['comment']);
    if (strlen($comment) < 10) {
        throw new Exception('Review comment must be at least 10 characters long');
    }

    $conn->begin_transaction();

    // Check if product exists and is active
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Product not found or is no longer available');
    }

    // Check if user has purchased the product
    $stmt = $conn->prepare("
        SELECT 1 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
        LIMIT 1
    ");
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('You can only review products you have purchased');
    }

    // Check if user has already reviewed this product
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        throw new Exception('You have already reviewed this product');
    }

    // Add the review
    $stmt = $conn->prepare("
        INSERT INTO reviews (product_id, user_id, rating, comment) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('iiis', $productId, $userId, $rating, $comment);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to add review');
    }

    // Update product rating
    $stmt = $conn->prepare("
        UPDATE products p 
        SET rating = (
            SELECT AVG(rating) 
            FROM reviews 
            WHERE product_id = ?
        ),
        review_count = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE product_id = ?
        )
        WHERE id = ?
    ");
    $stmt->bind_param('iii', $productId, $productId, $productId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update product rating');
    }

    $conn->commit();
    
    error_log("Review added successfully for product #$productId by user #$userId");
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your review!'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Add review error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 