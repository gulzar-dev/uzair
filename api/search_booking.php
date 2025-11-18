<?php
/**
 * Search Booking API Endpoint
 * Searches bookings by pickup and dropoff locations
 */

require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $conn = getDB();
    
    // Get input data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) {
            $input = $_POST;
        }
    } else {
        $input = $_GET;
    }
    
    $pickup = isset($input['pickup']) ? sanitizeInput($input['pickup']) : '';
    $dropoff = isset($input['dropoff']) ? sanitizeInput($input['dropoff']) : '';
    
    if (empty($pickup) || empty($dropoff)) {
        sendJSONResponse(false, 'Pickup and dropoff locations are required', null, 400);
    }
    
    // Search for bookings matching pickup and dropoff locations
    $query = "SELECT * FROM bookings WHERE 
              (pickup_location LIKE ? OR pickup_location LIKE ?) AND 
              (dropoff_location LIKE ? OR dropoff_location LIKE ?)
              ORDER BY created_at DESC LIMIT 50";
    
    $pickup_exact = $pickup;
    $pickup_like = '%' . $pickup . '%';
    $dropoff_exact = $dropoff;
    $dropoff_like = '%' . $dropoff . '%';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $pickup_exact, $pickup_like, $dropoff_exact, $dropoff_like);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    if (empty($bookings)) {
        sendJSONResponse(false, 'No bookings found matching the search criteria', ['bookings' => []], 404);
    }
    
    sendJSONResponse(true, 'Bookings found', [
        'bookings' => $bookings,
        'count' => count($bookings)
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    sendJSONResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>

