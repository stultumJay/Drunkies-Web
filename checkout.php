<?php
// Flow: Checkout Process Page
// 1. Include required files and check authentication
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Redirect to login if not authenticated
if (!isLoggedIn()) {
    flashMessage("Please login to continue with checkout", "warning");
    redirect('login.php');
}

// Get current user and their address
$user = getCurrentUser();
$address = null;

// Get address from database
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $address = $result->fetch_assoc();
}

// Get cart items
$stmt = $conn->prepare("
    SELECT ci.*, p.name, p.price, p.image 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['quantity'] * $item['price'];
}
?>

<div class="container py-5">
    <div class="row">
        <!-- Checkout Form -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">Checkout</h2>
                    
                    <!-- Shipping Information -->
                    <div class="mb-4">
                        <h4>Shipping Information</h4>
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <div id="address-display">
                                    <?php if ($address): ?>
                                        <p class="mb-1">
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                        </p>
                                        <p class="mb-1"><?php echo htmlspecialchars($address['street']); ?></p>
                                        <p class="mb-1">
                                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                        </p>
                                        <p class="mb-0"><?php echo htmlspecialchars($address['country']); ?></p>
                                    <?php else: ?>
                                        <p class="text-danger">No shipping address found. Please add one.</p>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" 
                                        data-bs-toggle="modal" data-bs-target="#addressModal">
                                    <?php echo $address ? 'Edit Address' : 'Add Address'; ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <form id="checkout-form" method="POST" action="process_order.php">
                        <div class="mb-4">
                            <h4>Payment Method</h4>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="credit-card" value="credit-card" checked>
                                        <label class="form-check-label" for="credit-card">
                                            Credit Card
                                        </label>
                                    </div>
                                    
                                    <div id="credit-card-info" class="mb-3">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Card Number</label>
                                                <input type="text" class="form-control" name="card_number" 
                                                       placeholder="1234 5678 9012 3456">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Expiration Date</label>
                                                <input type="text" class="form-control" name="exp_date" 
                                                       placeholder="MM/YY">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">CVV</label>
                                                <input type="text" class="form-control" name="cvv" 
                                                       placeholder="123">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="cod" value="cod">
                                        <label class="form-check-label" for="cod">
                                            Cash on Delivery
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100" 
                                <?php echo !$address ? 'disabled' : ''; ?>>
                            Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">Order Summary</h4>
                    
                    <!-- Cart Items -->
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex mb-3">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="img-thumbnail me-3" style="width: 64px; height: 64px; object-fit: cover;">
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <small class="text-muted">
                                Quantity: <?php echo $item['quantity']; ?>
                            </small>
                            <div>
                                <?php echo formatPrice($item['quantity'] * $item['price']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <hr>

                    <!-- Total -->
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal:</span>
                        <strong><?php echo formatPrice($total); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping:</span>
                        <strong>FREE</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total:</span>
                        <strong class="text-primary"><?php echo formatPrice($total); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Shipping Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="address-form" action="update_address.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Street Address</label>
                        <input type="text" class="form-control" name="street" required
                               value="<?php echo $address ? htmlspecialchars($address['street']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" required
                               value="<?php echo $address ? htmlspecialchars($address['city']) : ''; ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State/Province</label>
                            <input type="text" class="form-control" name="state" required
                                   value="<?php echo $address ? htmlspecialchars($address['state']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postal Code</label>
                            <input type="text" class="form-control" name="postal_code" required
                                   value="<?php echo $address ? htmlspecialchars($address['postal_code']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" name="country" required
                               value="<?php echo $address ? htmlspecialchars($address['country']) : ''; ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Payment method toggle
document.querySelectorAll('input[name="payment_method"]').forEach((elem) => {
    elem.addEventListener('change', function(event) {
        const creditCardInfo = document.getElementById('credit-card-info');
        creditCardInfo.style.display = event.target.value === 'credit-card' ? 'block' : 'none';
    });
});

// Form validation
document.getElementById('checkout-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    
    if (paymentMethod === 'credit-card') {
        const cardNumber = document.querySelector('input[name="card_number"]').value;
        const expDate = document.querySelector('input[name="exp_date"]').value;
        const cvv = document.querySelector('input[name="cvv"]').value;
        
        if (!cardNumber || !expDate || !cvv) {
            alert('Please fill in all credit card details');
            return;
        }
    }
    
    // Show confirmation
    if (confirm('Are you sure you want to place this order?')) {
        this.submit();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 