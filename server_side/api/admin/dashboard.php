<?php
session_start();
require_once '../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Collect stats
    $stats = [];

    // Total Revenue (Topups)
    $stmt = $conn->query("SELECT SUM(amount) as total FROM topups");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

    // Active Sessions Count
    $stmt = $conn->query("SELECT COUNT(*) as active FROM computer_sessions WHERE status = 'active'");
    $stats['active_sessions'] = $stmt->fetch()['active'];

    // Total Users Count
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch()['total'];

    // Today's Revenue
    $stmt = $conn->query("SELECT SUM(amount) as total FROM topups WHERE DATE(created_at) = CURDATE()");
    $stats['revenue_today'] = $stmt->fetch()['total'] ?? 0;

    // Charts Data (Revenue last 7 days)
    $stmt = $conn->query("
        SELECT DATE(created_at) as date, SUM(amount) as revenue 
        FROM topups 
        WHERE created_at >= DATE(NOW() - INTERVAL 7 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
    $chartData = $stmt->fetchAll();

    // Active Sessions List
    $stmt = $conn->query("
        SELECT cs.id, cs.pc_number, cs.start_time, u.name as user_name 
        FROM computer_sessions cs 
        JOIN users u ON cs.user_id = u.id 
        WHERE cs.status = 'active'
    ");
    $activeSessionsList = $stmt->fetchAll();

    echo json_encode([
        'success' => true, 
        'stats' => $stats, 
        'chart_data' => $chartData,
        'active_sessions_list' => $activeSessionsList
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
