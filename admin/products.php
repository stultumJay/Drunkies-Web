<?php
require_once '../includes/functions.php';
require_once '../includes/database.php';

// Flow: Admin Products Management Page
// 1. Include required files and check authentication

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

$db = Database::getInstance();

// 2. Handle Product Creation
// Flow: Process form submission when adding new product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and prepare product data
    $name = sanitize($_POST['name']);
    $brand_id = (int)$_POST['brand_id'];
    $price = (float)$_POST['price'];
    $size = sanitize($_POST['size']);
    $stock = (int)$_POST['stock'];
    $description = sanitize($_POST['description']);
    
    // Flow: Handle image upload if provided, otherwise use default
    $image_path = 'assets/images/no-image.jpg'; // Default image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaded_image = uploadImage($_FILES['image'], 'assets/images/products/');
        if ($uploaded_image) {
            $image_path = $uploaded_image;
        }
    }
    
    // Flow: Insert new product into database
    $stmt = $db->prepare("INSERT INTO products (name, brand_id, price, size, stock, description, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sidsiss", $name, $brand_id, $price, $size, $stock, $description, $image_path);
    
    if ($stmt->execute()) {
        flashMessage("Product added successfully!");
        redirect('products.php');
    } else {
        flashMessage("Error adding product.", "danger");
    }
}

// 3. Fetch Data for Display
// Flow: Get brands for dropdown in add/edit forms
$brands = $db->query("SELECT * FROM brands ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Flow: Get all products with their brand names
$products = $db->query("
    SELECT p.*, b.name as brand_name 
    FROM products p 
    LEFT JOIN brands b ON p.brand_id = b.id 
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// 4. Include Header Template
require_once 'includes/header.php';
?>

<!-- 5. Page Layout -->
<div class="container-fluid py-4">
    <!-- Header with Add Product Button -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Products Management</h1>
            <!-- Flow: Button triggers Add Product Modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-1"></i> Add New Product
            </button>
        </div>
    </div>

    <!-- 6. Products Table -->
    <!-- Flow: Display all products with actions -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Size</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <!-- Flow: Each row shows product details and action buttons -->
                        <tr>
                            <td>
                                <img src="../<?php echo $product['image']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="img-thumbnail"
                                     style="max-width: 50px;">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['brand_name']); ?></td>
                            <td><?php echo formatPrice($product['price']); ?></td>
                            <td><?php echo htmlspecialchars($product['size']); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <!-- Flow: Edit button triggers edit modal, Delete button calls deleteProduct() -->
                                <div class="btn-group">
                                    <button type="button" 
                                            class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 7. Add Product Modal -->
<!-- Flow: Form for adding new products -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="brand_id" class="form-label">Brand</label>
                                <select class="form-select" id="brand_id" name="brand_id" required>
                                    <option value="">Select Brand</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>">
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="size" class="form-label">Size</label>
                                <input type="text" class="form-control" id="size" name="size" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" required>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">Leave empty to use default 'No Image' placeholder</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 8. JavaScript Functions -->
<script>
// Flow: Confirmation before deleting product
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        // Flow: Redirects to products.php with delete parameter
        window.location.href = `products.php?delete=${productId}`;
    }
}
</script>

<!-- 9. Include Footer Template -->
<?php require_once 'includes/footer.php'; ?> 