<?php
/**
 * Ride History API Endpoint
 * Returns ride history records for a customer
 */

// Database configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'cab_booking');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');

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

try {
    $customer_phone = isset($_GET['customer_phone']) ? sanitizeInput($_GET['customer_phone']) : null;
    if (!$customer_phone) {
        echo json_encode(['success' => false, 'message' => 'customer_phone is required']);
        exit();
    }

    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

    $conn = getDB();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    // Simple query to get bookings for the customer
    $query = "
        SELECT 
            booking_id,
            customer_name,
            customer_phone,
            pickup_location,
            dropoff_location,
            ride_date,
            ride_time,
            car_type,
            additional_notes,
            status,
            created_at
        FROM bookings 
        WHERE customer_phone = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $customer_phone, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $rides = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate ride status based on dates
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

        $rides[] = [
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
            'booking_status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Ride history retrieved successfully',
        'data' => [
            'rides' => $rides,
            'total' => count($rides),
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);

    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Database connection function for XAMPP
function getDB() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                return false;
            }
            $conn->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            return false;
        }
    }
    
    return $conn;
}

// Input sanitization function
function sanitizeInput($data) {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($data)));
}
?>