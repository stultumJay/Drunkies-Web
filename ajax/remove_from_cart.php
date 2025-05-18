<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    error_log("Remove from cart failed: User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login to remove items from cart']);
    exit;
}

if (!isset($_POST['product_id'])) {
    error_log("Remove from cart failed: Invalid request parameters");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$user_id = $_SESSION['user_id'];

try {
    $conn->begin_transaction();

    // Remove item from cart
    $stmt = $conn->prepare("
        DELETE FROM cart_items 
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Item not found in cart');
    }

    // Get updated cart totals
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

    $conn->commit();

    error_log("Successfully removed product #$product_id from cart for user #$user_id");
    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart',
        'cart_total' => number_format(($result['cart_total'] ?? 0), 2),
        'item_count' => (int)($result['item_count'] ?? 0)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Remove from cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 