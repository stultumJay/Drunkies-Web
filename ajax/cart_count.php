<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $count = 0;
    $total = 0;
    
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("
            SELECT 
                SUM(quantity) as item_count,
                SUM(quantity * price) as cart_total
            FROM cart_items 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $count = (int)($result['item_count'] ?? 0);
        $total = number_format(($result['cart_total'] ?? 0), 2);
    }
    
    echo json_encode([
        'count' => $count,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    error_log("Cart count error: " . $e->getMessage());
    echo json_encode([
        'count' => 0,
        'total' => '0.00',
        'error' => 'Failed to get cart count'
    ]);
} 