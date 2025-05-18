<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    error_log("Update cart failed: User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit;
}

if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    error_log("Update cart failed: Invalid request parameters");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];

if ($quantity < 0) {
    error_log("Update cart failed: Invalid quantity ($quantity)");
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    $conn->begin_transaction();

    // If quantity is 0, remove the item
    if ($quantity === 0) {
        $stmt = $conn->prepare("
            DELETE FROM cart_items 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Item not found in cart');
        }
    } else {
        // Get product with lock
        $stmt = $conn->prepare("
            SELECT id, price, stock, is_active 
            FROM products 
            WHERE id = ? 
            FOR UPDATE
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if (!$product) {
            throw new Exception('Product not found');
        }

        if (!$product['is_active']) {
            throw new Exception('Product is no longer available');
        }

        if ($product['stock'] < $quantity) {
            throw new Exception("Only {$product['stock']} items available");
        }

        // Update cart item
        $stmt = $conn->prepare("
            UPDATE cart_items 
            SET quantity = ?, updated_at = NOW() 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception('Item not found in cart');
        }
    }

    // Calculate new cart totals
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

    echo json_encode([
        'success' => true,
        'message' => $quantity === 0 ? 'Item removed from cart' : 'Cart updated',
        'cart_total' => number_format(($result['cart_total'] ?? 0), 2),
        'item_count' => (int)($result['item_count'] ?? 0)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Update cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 