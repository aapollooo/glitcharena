<?php
session_start();
require_once '../server_side/api/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$client_id = $_SESSION['client_id'];

try {
    // 1. Get User Data
    $stmt = $conn->prepare("SELECT name, balance FROM users WHERE id = ?");
    $stmt->execute([$client_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
         echo json_encode(['success' => false, 'message' => 'User not found.']);
         exit;
    }

    $response = [
        'success' => true,
        'name' => $user['name'],
        'balance' => number_format((float)$user['balance'], 2, '.', ''),
        'has_active_session' => false,
        'session_info' => null
    ];

    // 2. Get Active Session Data (if any)
    $stmt_sess = $conn->prepare("SELECT pc_number, start_time FROM computer_sessions WHERE user_id = ? AND status = 'active'");
    $stmt_sess->execute([$client_id]);
    $active_session = $stmt_sess->fetch(PDO::FETCH_ASSOC);

    if ($active_session) {
        $response['has_active_session'] = true;
        
        // Calculate duration and running cost
        $start = new DateTime($active_session['start_time']);
        $now = new DateTime();
        $interval = $start->diff($now);
        
        $hours = $interval->h + ($interval->days * 24);
        $minutes = $interval->i;
        
        $durationStr = '';
        if ($hours > 0) $durationStr .= $hours . 'h ';
        $durationStr .= $minutes . 'm';

        // Assuming a standard rate, e.g., 20 per hour (This depends on the actual logic. Using 20 as placeholder)
        $ratePerHour = 20.00;
        $totalMinutes = ($hours * 60) + $minutes;
        $runningCost = ($totalMinutes / 60) * $ratePerHour;

        $response['session_info'] = [
            'pc_number' => $active_session['pc_number'],
            'start_time' => $start->format('H:i'),
            'duration' => $durationStr ?: 'Just started',
            'cost' => number_format($runningCost, 2, '.', '')
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
