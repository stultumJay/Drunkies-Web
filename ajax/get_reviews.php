<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Validate product ID
    if (!isset($_GET['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $productId = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);
    if ($productId === false) {
        throw new Exception('Invalid product ID');
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;

    // Get sort parameters
    $validSortFields = ['rating', 'created_at'];
    $validSortOrders = ['ASC', 'DESC'];
    
    $sortBy = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $validSortFields) 
        ? $_GET['sort_by'] 
        : 'created_at';
    
    $sortOrder = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), $validSortOrders)
        ? strtoupper($_GET['sort_order'])
        : 'DESC';

    // Check if product exists and is active
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Product not found or is no longer available');
    }

    // Get total reviews count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE product_id = ?");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    // Get reviews with user info
    $query = "
        SELECT 
            r.*,
            u.username,
            u.first_name,
            u.last_name,
            (SELECT COUNT(*) FROM reviews WHERE user_id = u.id) as user_review_count,
            EXISTS(
                SELECT 1 
                FROM review_helpful rh 
                WHERE rh.review_id = r.id
            ) as helpful_count
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ? 
        ORDER BY r.$sortBy $sortOrder
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('iii', $productId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($review = $result->fetch_assoc()) {
        $reviews[] = [
            'id' => $review['id'],
            'user' => [
                'username' => $review['username'],
                'name' => trim($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'),
                'review_count' => $review['user_review_count']
            ],
            'rating' => $review['rating'],
            'comment' => $review['comment'],
            'helpful_count' => $review['helpful_count'],
            'created_at' => [
                'formatted' => date('M d, Y', strtotime($review['created_at'])),
                'timestamp' => strtotime($review['created_at'])
            ]
        ];
    }

    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_reviews' => $total,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    error_log("Get reviews error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 