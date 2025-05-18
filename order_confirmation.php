<?php
// Flow: Order Confirmation Page
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isLoggedIn()) {
    flashMessage("Please login to view order details", "warning");
    redirect('login.php');
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.first_name, u.last_name, a.street, a.city, a.state, a.postal_code, a.country
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON u.id = a.user_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    flashMessage("Order not found", "danger");
    redirect('index.php');
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- Order Status -->
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h2 class="mb-2">Thank You for Your Order!</h2>
                        <p class="text-muted mb-0">Order #<?php echo $order_id; ?></p>
                        <p class="text-muted">
                            <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                        </p>
                    </div>

                    <!-- Order Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Shipping Address</h5>
                            <p class="mb-1">
                                <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                            </p>
                            <p class="mb-1"><?php echo htmlspecialchars($order['street']); ?></p>
                            <p class="mb-1">
                                <?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['postal_code']); ?>
                            </p>
                            <p class="mb-0"><?php echo htmlspecialchars($order['country']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Payment Method</h5>
                            <p class="mb-1">
                                <?php echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Credit Card'; ?>
                            </p>
                            <h5 class="mt-3">Order Status</h5>
                            <span class="badge bg-<?php 
                                echo match($order['status']) {
                                    'completed' => 'success',
                                    'pending' => 'warning',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h5 class="mb-3">Order Items</h5>
                    <?php foreach ($order_items as $item): ?>
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="img-thumbnail me-3" style="width: 64px; height: 64px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">
                                        Quantity: <?php echo $item['quantity']; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <strong><?php echo formatPrice($item['price'] * $item['quantity']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Order Summary -->
                    <div class="card bg-light mt-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <strong>FREE</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total:</span>
                                <strong class="text-primary"><?php echo formatPrice($order['total_amount']); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary me-2">
                            Continue Shopping
                        </a>
                        <a href="#" class="btn btn-outline-primary" onclick="window.print()">
                            Print Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 