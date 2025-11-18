<?php
/**
 * Ride Detail API Endpoint
 * Returns detailed information for a single ride
 */

require_once '../config.php';
require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSONResponse(false, 'Only GET method is allowed', null, 405);
}

try {
    $booking_id = isset($_GET['booking_id']) ? sanitizeInput($_GET['booking_id']) : null;
    $ride_history_id = isset($_GET['ride_history_id']) ? intval($_GET['ride_history_id']) : null;

    if (!$booking_id && !$ride_history_id) {
        sendJSONResponse(false, 'booking_id or ride_history_id is required', null, 400);
    }

    $conn = getDB();

    $query = "
        SELECT 
            b.booking_id,
            b.customer_name,
            b.customer_phone,
            b.pickup_location,
            b.dropoff_location,
            b.ride_date,
            b.ride_time,
            b.car_type,
            b.additional_notes,
            b.status AS booking_status,
            b.created_at,
            rh.id AS ride_history_id,
            rh.driver_name,
            rh.driver_phone,
            rh.vehicle_number,
            rh.start_time,
            rh.end_time,
            rh.distance,
            rh.duration,
            rh.fare
        FROM bookings b
        LEFT JOIN ride_history rh ON rh.booking_id = b.booking_id
        WHERE 1 = 1
    ";

    $params = [];
    $types = "";

    if ($booking_id) {
        $query .= " AND b.booking_id = ?";
        $params[] = $booking_id;
        $types .= "s";
    }

    if ($ride_history_id) {
        $query .= " AND rh.id = ?";
        $params[] = $ride_history_id;
        $types .= "i";
    }

    $query .= " LIMIT 1";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendJSONResponse(false, 'Ride not found', null, 404);
    }

    $row = $result->fetch_assoc();

    $rideStatus = strtolower($row['booking_status'] ?? 'pending');
    $now = new DateTime('now');
    $startTime = $row['start_time'] ? new DateTime($row['start_time']) : null;
    $endTime = $row['end_time'] ? new DateTime($row['end_time']) : null;
    $scheduledDateTime = null;
    if (!empty($row['ride_date'])) {
        $timePart = !empty($row['ride_time']) ? $row['ride_time'] : '00:00:00';
        $scheduledDateTime = new DateTime("{$row['ride_date']} {$timePart}");
    }

    if ($rideStatus !== 'cancelled') {
        if ($endTime) {
            $rideStatus = 'completed';
        } elseif ($startTime) {
            $rideStatus = 'active';
        } elseif ($scheduledDateTime && $scheduledDateTime > $now) {
            $rideStatus = 'upcoming';
        } elseif ($scheduledDateTime && $scheduledDateTime <= $now) {
            $rideStatus = 'completed';
        }
    }

    $ride = [
        'ride_history_id' => $row['ride_history_id'],
        'booking_id' => $row['booking_id'],
        'customer_name' => $row['customer_name'],
        'customer_phone' => $row['customer_phone'],
        'pickup_location' => $row['pickup_location'],
        'dropoff_location' => $row['dropoff_location'],
        'ride_date' => $row['ride_date'],
        'ride_time' => $row['ride_time'],
        'car_type' => $row['car_type'],
        'additional_notes' => $row['additional_notes'],
        'ride_status' => $rideStatus,
        'distance' => $row['distance'],
        'duration' => $row['duration'],
        'fare' => $row['fare'],
        'driver_name' => $row['driver_name'],
        'driver_phone' => $row['driver_phone'],
        'vehicle_number' => $row['vehicle_number'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'created_at' => $row['created_at']
    ];

    sendJSONResponse(true, 'Ride detail retrieved successfully', $ride);

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    sendJSONResponse(false, 'Error: ' . $e->getMessage(), null, 500);
}
?>

