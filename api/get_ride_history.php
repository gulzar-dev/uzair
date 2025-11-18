<?php

$log_file = __DIR__ . '/debug.log';
file_put_contents($log_file, ""); // Clear log file
error_log("--- New Request at " . date('Y-m-d H:i:s') . " ---\n", 3, $log_file);

try {
    error_log("Step 1: Loading includes...\n", 3, $log_file);
    require_once '../config.php';
    require_once '../db.php';
    error_log("Step 2: Includes loaded.\n", 3, $log_file);

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'message' => 'Only GET method is allowed']);
        exit();
    }

    error_log("Step 3: Processing GET request.\n", 3, $log_file);
    $customer_phone = isset($_GET['customer_phone']) ? sanitizeInput($_GET['customer_phone']) : null;
    error_log("Step 4: Customer phone is '$customer_phone'.\n", 3, $log_file);

    if (!$customer_phone) {
        echo json_encode(['success' => false, 'message' => 'customer_phone is required']);
        exit();
    }

    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
    error_log("Step 5: Limit is $limit, Offset is $offset.\n", 3, $log_file);

    error_log("Step 6: Attempting DB connection...\n", 3, $log_file);
    $conn = getDB();
    error_log("Step 7: DB connection object received.\n", 3, $log_file);
    
    if (!$conn) {
        error_log("ERROR: DB connection is null or false.\n", 3, $log_file);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    error_log("Step 8: DB connection successful.\n", 3, $log_file);

    $query = "
        SELECT 
            b.booking_id, b.customer_name, b.customer_phone, b.pickup_location,
            b.dropoff_location, b.ride_date, b.ride_time, b.car_type,
            b.additional_notes, b.status, b.created_at, rh.driver_name,
            rh.driver_phone, rh.vehicle_number, rh.distance, rh.fare
        FROM bookings AS b
        LEFT JOIN ride_history AS rh ON b.booking_id = rh.booking_id
        WHERE b.customer_phone = ?
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ";
    error_log("Step 9: Query defined.\n", 3, $log_file);

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("ERROR: Prepare failed: " . $conn->error . "\n", 3, $log_file);
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    error_log("Step 10: Statement prepared.\n", 3, $log_file);

    $stmt->bind_param("sii", $customer_phone, $limit, $offset);
    error_log("Step 11: Params bound.\n", 3, $log_file);

    $stmt->execute();
    error_log("Step 12: Statement executed.\n", 3, $log_file);

    $result = $stmt->get_result();
    error_log("Step 13: Result obtained.\n", 3, $log_file);

    $rides = [];
    while ($row = $result->fetch_assoc()) {
        $rideStatus = strtolower($row['status'] ?? 'pending');
        $now = new DateTime();
        
        if ($rideStatus !== 'cancelled') {
            if (!empty($row['ride_date'])) {
                $rideDateTime = new DateTime($row['ride_date'] . ' ' . ($row['ride_time'] ?? '00:00:00'));
                if ($rideDateTime > $now) {
                    $rideStatus = 'upcoming';
                } else {
                    $rideStatus = 'completed';
                }
            }
        }
        $rides[] = $row + ['ride_status' => $rideStatus];
    }
    error_log("Step 14: " . count($rides) . " rides processed.\n", 3, $log_file);

    echo json_encode([
        'success' => true, 
        'message' => 'Ride history retrieved successfully',
        'data' => ['rides' => $rides, 'total' => count($rides), 'limit' => $limit, 'offset' => $offset]
    ]);
    error_log("Step 15: JSON response sent.\n", 3, $log_file);

    $stmt->close();
    $conn->close();
    
} catch (Throwable $e) { // Catch Throwable to get all errors, including parse errors in includes
    error_log("FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n", 3, $log_file);
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>
