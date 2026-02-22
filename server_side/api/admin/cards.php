<?php
session_start();
require_once '../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all cards with user info
    $stmt = $conn->query("SELECT r.id, r.uid, r.status, r.created_at, u.name as user_name FROM rfid_cards r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.id DESC");
    $cards = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $cards]);
} elseif ($method === 'POST') {
    // Register new card to a user
    $uid = $_POST['uid'] ?? '';
    $user_id = $_POST['user_id'] ?? null;

    if (empty($uid)) {
        echo json_encode(['success' => false, 'message' => 'UID is required.']);
        exit;
    }

    try {
        // Check if card limits or exists
        $stmt = $conn->prepare("SELECT id FROM rfid_cards WHERE uid = ?");
        $stmt->execute([$uid]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Card already registered.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO rfid_cards (uid, user_id) VALUES (?, ?)");
        $stmt->execute([$uid, $user_id ? $user_id : null]);
        
        echo json_encode(['success' => true, 'message' => 'Card registered successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
