<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    // $admin_id = $_SESSION['admin_id'] ?? null; // For simpleness, can be null or 1
    $admin_id = 1;

    if (empty($user_id) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid User ID and Amount are required.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Add balance to user
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);

        // Record topup
        $stmt = $conn->prepare("INSERT INTO topups (user_id, admin_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $admin_id, $amount]);

        // Record transaction
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'topup', 'Manual Topup via Admin')");
        $stmt->execute([$user_id, $amount]);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Topup successful.']);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
