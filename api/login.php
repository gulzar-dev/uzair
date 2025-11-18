<?php
require_once '../db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['userName'] ?? null;
    $phone = $data['userPhone'] ?? null;

    if (!$name || !$phone) {
        $response['message'] = 'Invalid input. Full name and phone number are required.';
        echo json_encode($response);
        exit;
    }

    $db = getDB();

    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // User exists
        $response['success'] = true;
        $response['message'] = 'Login successful.';
        $response['user'] = [
            'id' => $user['id'],
            'fullName' => $user['full_name'],
            'phone' => $user['phone']
        ];
    } else {
        // User does not exist, create a new one
        $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
        $email = $phone . '@example.com'; // Placeholder email
        $password = password_hash($phone, PASSWORD_DEFAULT); // Use phone as password for simplicity

        $insertStmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param("sssss", $username, $email, $password, $name, $phone);

        if ($insertStmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'User created and logged in successfully.';
            $response['user'] = [
                'id' => $db->insert_id,
                'fullName' => $name,
                'phone' => $phone
            ];
        } else {
            $response['message'] = 'Failed to create a new user.';
        }
        $insertStmt->close();
    }

    $stmt->close();
    $db->close();
} else {
    $response['message'] = 'Invalid request method.';
}



if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = json_decode(file_get_contents('php://input'), true);
    $uid = $data['uid'] ?? null;

    if (!$uid) {
        $response['message'] = 'Invalid input. Full name and phone number are required.';
        echo json_encode($response);
        exit;
    }

    $db = getDB();

    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // User exists
        $response['success'] = true;
        $response['message'] = 'user found successful.';
        $response['user'] = [
            'id' => $user['id'],
            'fullName' => $user['full_name'],
            'phone' => $user['phone']
        ];
    } else {
        // User does not exist, create a new one
        $response['success'] = false;
        $response['message'] = 'user not found.';
        $insertStmt->close();
    }

    $stmt->close();
    $db->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
