<?php
header('Content-Type: application/json');
require_once '../db.php';

$COST_PER_MINUTE = 1.00; // PHP 1 per minute

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON or POST data from Arduino
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $uid = $data['uid'] ?? $_POST['uid'] ?? '';
    $pc_number = $data['pc_number'] ?? $_POST['pc_number'] ?? 'PC-01';

    if (empty($uid)) {
        echo json_encode(['success' => false, 'message' => 'UID is required']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Find card & user
        $stmt = $conn->prepare("SELECT r.id as card_id, u.id as user_id, u.balance FROM rfid_cards r JOIN users u ON r.user_id = u.id WHERE r.uid = ? AND r.status = 'active'");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid or Unregistered Card']);
            $conn->rollBack();
            exit;
        }

        $user_id = $user['user_id'];
        $balance = $user['balance'];

        // Check if there is an active session
        $stmt = $conn->prepare("SELECT * FROM computer_sessions WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
        $active_session = $stmt->fetch();

        if ($active_session) {
            // End session
            $start_time = strtotime($active_session['start_time']);
            $end_time = time();
            $duration_minutes = ceil(($end_time - $start_time) / 60);
            
            // Minimum 1 min charge if duration is < 1
            if ($duration_minutes < 1) $duration_minutes = 1;

            $total_cost = $duration_minutes * $COST_PER_MINUTE;

            // Update session
            $stmt = $conn->prepare("UPDATE computer_sessions SET end_time = FROM_UNIXTIME(?), status = 'completed', cost = ? WHERE id = ?");
            $stmt->execute([$end_time, $total_cost, $active_session['id']]);

            // Deduct balance
            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$total_cost, $user_id]);

            // Record transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'deduction', CONCAT('PC Session: ', ?))");
            $stmt->execute([$user_id, $total_cost, $duration_minutes . ' min']);

            $conn->commit();

            echo json_encode([
                'success' => true, 
                'action' => 'logout', 
                'message' => 'Session Ended', 
                'duration' => $duration_minutes, 
                'cost' => $total_cost, 
                'remaining_balance' => $balance - $total_cost
            ]);

        } else {
            // Start session if sufficient balance (e.g., minimum balance for 1 minute = $COST_PER_MINUTE)
            if ($balance < $COST_PER_MINUTE) {
                echo json_encode(['success' => false, 'message' => 'Insufficient Balance']);
                $conn->rollBack();
                exit;
            }

            // Insert new session
            $stmt = $conn->prepare("INSERT INTO computer_sessions (user_id, pc_number) VALUES (?, ?)");
            $stmt->execute([$user_id, $pc_number]);

            $conn->commit();

            echo json_encode([
                'success' => true, 
                'action' => 'login', 
                'message' => 'Session Started', 
                'balance' => $balance
            ]);
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
