<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_GET['id'])) {
        throw new Exception('Product ID is required');
    }

    $productId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($productId === false) {
        throw new Exception('Invalid product ID');
    }

    // Get product details with related information
    $query = "
        SELECT 
            p.*,
            b.name as brand_name,
            b.description as brand_description,
            c.name as category_name,
            c.description as category_description,
            COALESCE(r.review_count, 0) as review_count,
            COALESCE(r.avg_rating, 0) as avg_rating
        FROM products p 
        JOIN brands b ON p.brand_id = b.id 
        JOIN categories c ON p.category_id = c.id 
        LEFT JOIN (
            SELECT 
                product_id,
                COUNT(*) as review_count,
                AVG(rating) as avg_rating
            FROM reviews
            GROUP BY product_id
        ) r ON p.id = r.product_id
        WHERE p.id = ? AND p.is_active = 1
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $productId);
    if (!$stmt->execute()) {
        throw new Exception('Failed to fetch product details');
    }

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        throw new Exception('Product not found or is no longer available');
    }

    // Get related products
    $relatedQuery = "
        SELECT p.id, p.name, p.price, p.image_url, p.rating
        FROM products p
        WHERE p.category_id = ? 
        AND p.id != ? 
        AND p.is_active = 1
        ORDER BY p.rating DESC
        LIMIT 4
    ";

    $stmt = $conn->prepare($relatedQuery);
    $stmt->bind_param('ii', $product['category_id'], $productId);
    $stmt->execute();
    $relatedResult = $stmt->get_result();
    
    $relatedProducts = [];
    while ($related = $relatedResult->fetch_assoc()) {
        $relatedProducts[] = [
            'id' => $related['id'],
            'name' => $related['name'],
            'price' => number_format($related['price'], 2),
            'image_url' => $related['image_url'],
            'rating' => $related['rating']
        ];
    }

    // Format response
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'brand' => [
                'name' => $product['brand_name'],
                'description' => $product['brand_description']
            ],
            'category' => [
                'name' => $product['category_name'],
                'description' => $product['category_description']
            ],
            'description' => $product['description'],
            'price' => number_format($product['price'], 2),
            'stock' => $product['stock'],
            'image_url' => $product['image_url'],
            'alcohol_content' => $product['alcohol_content'],
            'container_type' => $product['container_type'],
            'volume' => $product['volume'],
            'rating' => number_format($product['avg_rating'], 1),
            'review_count' => $product['review_count'],
            'is_available' => $product['stock'] > 0
        ],
        'related_products' => $relatedProducts
    ]);

} catch (Exception $e) {
    error_log("Get product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 