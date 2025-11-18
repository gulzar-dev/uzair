<?php
/**
 * Save Booking API Endpoint
 * Handles booking creation and storage in MySQL
 */

require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Log received data for debugging
    error_log("Received booking data: " . print_r($input, true));
    
    // If JSON is empty, try form data
    if (empty($input)) {
        $input = $_POST;
    }
    
    // Validate required fields
    $required_fields = ['pickupLocation', 'dropoffLocation', 'rideDate', 'rideTime', 'carType', 'customerName', 'customerPhone'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit();
    }
    
    // Sanitize inputs
    $pickup_location = sanitizeInput($input['pickupLocation']);
    $dropoff_location = sanitizeInput($input['dropoffLocation']);
    $ride_date = sanitizeInput($input['rideDate']);
    $ride_time = sanitizeInput($input['rideTime']);
    $car_type = sanitizeInput($input['carType']);
    $customer_name = sanitizeInput($input['customerName']);
    $customer_phone = sanitizeInput($input['customerPhone']);
    $additional_notes = isset($input['additionalNotes']) ? sanitizeInput($input['additionalNotes']) : '';
    
    // Get database connection
    $conn = getDB();
    
    if (!$conn) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database connection failed'
        ]);
        exit();
    }
    
    // Generate unique booking ID
    $booking_id = 'BK' . strtoupper(uniqid());
    
    // Check if booking ID already exists (highly unlikely but good practice)
    $check_stmt = $conn->prepare("SELECT id FROM bookings WHERE booking_id = ?");
    $check_stmt->bind_param("s", $booking_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    while ($result->num_rows > 0) {
        $booking_id = 'BK' . strtoupper(uniqid());
        $check_stmt->bind_param("s", $booking_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
    }
    $check_stmt->close();
    
    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (booking_id, customer_name, customer_phone, pickup_location, dropoff_location, ride_date, ride_time, car_type, additional_notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    if (!$stmt) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database prepare error: ' . $conn->error
        ]);
        exit();
    }
    
    $stmt->bind_param("sssssssss", $booking_id, $customer_name, $customer_phone, $pickup_location, $dropoff_location, $ride_date, $ride_time, $car_type, $additional_notes);
    
    if ($stmt->execute()) {
        $booking_data = [
            'id' => $conn->insert_id,
            'booking_id' => $booking_id,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'pickup_location' => $pickup_location,
            'dropoff_location' => $dropoff_location,
            'ride_date' => $ride_date,
            'ride_time' => $ride_time,
            'car_type' => $car_type,
            'additional_notes' => $additional_notes,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking saved successfully!',
            'data' => $booking_data
        ]);
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save booking: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
