<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check login status
if (!isLoggedIn()) {
    error_log("Add to cart failed: User not logged in");
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Validate input
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    error_log("Add to cart failed: Invalid request parameters");
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];

if ($quantity < 1) {
    error_log("Add to cart failed: Invalid quantity ($quantity)");
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    $conn->begin_transaction();

    // Get product with lock
    $stmt = $conn->prepare("
        SELECT id, name, price, stock, is_active 
        FROM products 
        WHERE id = ? 
        FOR UPDATE
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        throw new Exception('Product not found');
    }

    if (!$product['is_active']) {
        throw new Exception('Product is no longer available');
    }

    // Check stock
    if ($product['stock'] < $quantity) {
        throw new Exception("Only {$product['stock']} items available");
    }

    // Get existing cart item if any
    $stmt = $conn->prepare("
        SELECT quantity 
        FROM cart_items 
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $cart_item = $stmt->get_result()->fetch_assoc();

    $new_quantity = $quantity;
    if ($cart_item) {
        $new_quantity += $cart_item['quantity'];
        if ($new_quantity > $product['stock']) {
            throw new Exception("Cannot add {$quantity} more items. Only " . ($product['stock'] - $cart_item['quantity']) . " available");
        }

        // Update existing cart item
        $stmt = $conn->prepare("
            UPDATE cart_items 
            SET quantity = ?, updated_at = NOW() 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
    } else {
        // Insert new cart item
        $stmt = $conn->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $product['price']);
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to update cart');
    }

    // Calculate cart total
    $stmt = $conn->prepare("
        SELECT SUM(quantity * price) as total 
        FROM cart_items 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $conn->commit();

    error_log("Successfully added product #$product_id to cart for user #$user_id");
    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart',
        'cart_total' => number_format($cart_total, 2),
        'cart_quantity' => $new_quantity
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 