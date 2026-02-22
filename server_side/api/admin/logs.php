<?php
session_start();
require_once '../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get transactions
    $type = $_GET['type'] ?? 'all'; // 'topup', 'deduction', or 'all'
    
    $query = "
        SELECT t.id, t.amount, t.type, t.description, t.created_at, u.name as user_name 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
    ";
    
    if ($type !== 'all') {
        $query .= " WHERE t.type = :type ";
    }
    
    $query .= " ORDER BY t.id DESC";

    $stmt = $conn->prepare($query);
    if ($type !== 'all') {
        $stmt->bindParam(':type', $type);
    }
    $stmt->execute();
    $transactions = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $transactions]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
