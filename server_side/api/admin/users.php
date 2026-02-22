<?php
session_start();
require_once '../db.php';

// Check if admin is logged in (optional, based on your auth structure, simple check for now)
if (!isset($_SESSION['admin_id'])) {
    // For testing without login, you can comment this out or pass a bypass token.
    // echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all users
    $stmt = $conn->query("SELECT id, name, balance, created_at FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $users]);
} elseif ($method === 'POST') {
    // Create new user
    $name = $_POST['name'] ?? '';
    $initial_balance = $_POST['balance'] ?? 0;

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO users (name, balance) VALUES (?, ?)");
        $stmt->execute([$name, $initial_balance]);
        $userId = $conn->lastInsertId();
        
        // Log transaction if initial balance > 0
        if ($initial_balance > 0) {
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'topup', 'Initial Balance')");
            $stmt->execute([$userId, $initial_balance]);
        }

        echo json_encode(['success' => true, 'message' => 'User created successfully.', 'id' => $userId]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
