<?php
/**
 * Delete Booking API Endpoint
 * Deletes a booking from the database
 */

require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get booking ID
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
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
    
    $booking = $result->fetch_assoc();
    $check_stmt->close();
    
    // Delete booking
    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("s", $booking_id);
    
    if ($stmt->execute()) {
        sendJSONResponse(true, 'Booking deleted successfully', ['booking_id' => $booking_id]);
    } else {
        sendJSONResponse(false, 'Failed to delete booking: ' . $stmt->error, null, 500);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    sendJSONResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>

