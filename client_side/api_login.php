<?php
session_start();
require_once '../server_side/api/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    // Optional password field can go here if extending later, 
    // but the provided logic uses "Futuristic Login" and we matched Admin users earlier. 
    // Wait, the client_side login currently assumes a username + password. 
    // Let's authenticate a normal user by name for testing, or assume we just want to look up their balance.
    $password = $_POST['password'] ?? ''; 
    
    // In our schema, `users` don't have passwords yet. 
    // For a realistic client login that matches the UI inputs, let's just 
    // find the user by their exact name to "log them in" and return balance/session info.
    // If you plan to add passwords to regular users, you must alter the `users` table.

    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, name, balance FROM users WHERE name = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['client_id'] = $user['id'];
        
        // Find if they have an active session
        $stmt_sess = $conn->prepare("SELECT pc_number, start_time FROM computer_sessions WHERE user_id = ? AND status = 'active'");
        $stmt_sess->execute([$user['id']]);
        $active_session = $stmt_sess->fetch();

        echo json_encode([
            'success' => true, 
            'message' => 'Login successful.',
            'user' => [
                'name' => $user['name'],
                'balance' => $user['balance'],
                'has_active_session' => (bool)$active_session,
                'session_info' => $active_session
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found. Check spelling.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
