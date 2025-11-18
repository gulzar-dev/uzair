<?php
/**
 * Get Bookings API Endpoint
 * Retrieves booking data from database
 */

require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(false, 'Only GET method is allowed', null, 405);
}

try {
    $conn = getDB();
    
    // Get query parameters
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $booking_id = isset($_GET['booking_id']) ? sanitizeInput($_GET['booking_id']) : null;
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
    $customer_phone = isset($_GET['customer_phone']) ? sanitizeInput($_GET['customer_phone']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Build query
    $query = "SELECT * FROM bookings WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($user_id !== null) {
        $query .= " AND user_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    if ($booking_id !== null) {
        $query .= " AND booking_id = ?";
        $params[] = $booking_id;
        $types .= "s";
    }
    
    if ($status !== null) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if ($customer_phone !== null) {
        $query .= " AND customer_phone = ?";
        $params[] = $customer_phone;
        $types .= "s";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM bookings WHERE 1=1";
    $count_params = [];
    $count_types = "";
    
    if ($user_id !== null) {
        $count_query .= " AND user_id = ?";
        $count_params[] = $user_id;
        $count_types .= "i";
    }
    
    if ($booking_id !== null) {
        $count_query .= " AND booking_id = ?";
        $count_params[] = $booking_id;
        $count_types .= "s";
    }
    
    if ($status !== null) {
        $count_query .= " AND status = ?";
        $count_params[] = $status;
        $count_types .= "s";
    }

    if ($customer_phone !== null) {
        $count_query .= " AND customer_phone = ?";
        $count_params[] = $customer_phone;
        $count_types .= "s";
    }
    
    $count_stmt = $conn->prepare($count_query);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total = $count_result->fetch_assoc()['total'];
    
    sendJSONResponse(true, 'Bookings retrieved successfully', [
        'bookings' => $bookings,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
    $stmt->close();
    $count_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    sendJSONResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>

