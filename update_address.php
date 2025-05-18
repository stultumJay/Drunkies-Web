<?php
// Flow: Address Update Handler
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$street = $_POST['street'] ?? '';
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$country = $_POST['country'] ?? '';

// Validate required fields
if (empty($street) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Check if user already has an address
    $stmt = $conn->prepare("SELECT id FROM addresses WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing address
        $stmt = $conn->prepare("
            UPDATE addresses 
            SET street = ?, city = ?, state = ?, postal_code = ?, country = ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param("sssssi", $street, $city, $state, $postal_code, $country, $_SESSION['user_id']);
    } else {
        // Insert new address
        $stmt = $conn->prepare("
            INSERT INTO addresses (user_id, street, city, state, postal_code, country) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $_SESSION['user_id'], $street, $city, $state, $postal_code, $country);
    }

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Address updated successfully',
            'address' => [
                'street' => $street,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postal_code,
                'country' => $country
            ]
        ]);
    } else {
        throw new Exception("Failed to update address");
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 