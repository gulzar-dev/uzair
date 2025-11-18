<?php
/**
 * Update Booking API Endpoint
 * Updates booking status and information
 */

require_once '../config.php';
require_once '../db.php';



header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(false, 'Only PUT or POST method is allowed', null, 405);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // If JSON is empty, try form data
    if (empty($input)) {
        $input = $_POST;
    }
    
    if (empty($input['booking_id'])) {
        sendJSONResponse(false, 'Booking ID is required', null, 400);
    }
    
    $booking_id = sanitizeInput($input['booking_id']);
    $conn = getDB();
    
    // Check if booking exists
    $check_stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
    $check_stmt->bind_param("s", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendJSONResponse(false, 'Booking not found', null, 404);
    }
    
    $check_stmt->close();
    
    // Build update query dynamically based on provided fields
    $update_fields = [];
    $update_values = [];
    $types = "";
    
    $allowed_fields = [
        'status' => 's',
        'pickup_location' => 's',
        'dropoff_location' => 's',
        'ride_date' => 's',
        'ride_time' => 's',
        'car_type' => 's',
        'additional_notes' => 's'
    ];
    
    foreach ($allowed_fields as $field => $type) {
        if (isset($input[$field])) {
            $update_fields[] = "$field = ?";
            $update_values[] = sanitizeInput($input[$field]);
            $types .= $type;
        }
    }
    
    if (empty($update_fields)) {
        sendJSONResponse(false, 'No fields to update', null, 400);
    }
    
    $update_values[] = $booking_id;
    $types .= "s";
    
    $query = "UPDATE bookings SET " . implode(", ", $update_fields) . " WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$update_values);
    
    if ($stmt->execute()) {
        // Get updated booking
        $get_stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
        $get_stmt->bind_param("s", $booking_id);
        $get_stmt->execute();
        $updated_result = $get_stmt->get_result();
        $updated_booking = $updated_result->fetch_assoc();
        
        sendJSONResponse(true, 'Booking updated successfully', $updated_booking);
        
        $get_stmt->close();
    } else {
        sendJSONResponse(false, 'Failed to update booking: ' . $stmt->error, null, 500);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    sendJSONResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>