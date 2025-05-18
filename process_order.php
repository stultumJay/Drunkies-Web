<?php
// Flow: Order Processing
// 1. Include required files and check authentication
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    flashMessage("Please login to continue", "warning");
    redirect('login.php');
}

// 2. Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashMessage("Invalid request method", "danger");
    redirect('checkout.php');
}

// 3. Get user and cart data
$user_id = $_SESSION['user_id'];
$payment_method = $_POST['payment_method'] ?? '';

// Validate payment method
if (!in_array($payment_method, ['credit-card', 'cod'])) {
    flashMessage("Invalid payment method", "danger");
    redirect('checkout.php');
}

try {
    // 4. Start transaction
    $conn->begin_transaction();

    // Get cart items
    $stmt = $conn->prepare("
        SELECT ci.*, p.price, p.stock 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($cart_items)) {
        throw new Exception("Your cart is empty");
    }

    // Calculate total
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['quantity'] * $item['price'];
    }

    // 5. Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, status, payment_method) 
        VALUES (?, ?, ?, ?)
    ");
    $status = 'pending';
    $stmt->bind_param("idss", $user_id, $total, $status, $payment_method);
    $stmt->execute();
    $order_id = $conn->insert_id;

    // 6. Create order items and update stock
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    
    $update_stock = $conn->prepare("
        UPDATE products 
        SET stock = stock - ? 
        WHERE id = ? AND stock >= ?
    ");

    foreach ($cart_items as $item) {
        // Add to order items
        $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();

        // Update stock
        $update_stock->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
        $update_stock->execute();
        if ($update_stock->affected_rows === 0) {
            throw new Exception("Not enough stock for some items");
        }
    }

    // 7. Clear cart
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // 8. Commit transaction
    $conn->commit();

    // 9. Success message and redirect
    flashMessage("Order placed successfully! Your order number is #" . $order_id);
    redirect('order_confirmation.php?id=' . $order_id);

} catch (Exception $e) {
    // 10. Rollback on error
    $conn->rollback();
    flashMessage($e->getMessage(), "danger");
    redirect('checkout.php');
}
?> 