<?php

function simulate_tap($uid, $pc_number) {
    echo "Tapping UID: $uid at $pc_number\n";
    $url = 'http://localhost:8000/api/arduino/tap.php';
    $data = array('uid' => $uid, 'pc_number' => $pc_number);
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) { 
        echo "Error calling API\n";
    } else {
        echo "Response: " . $result . "\n";
    }
}

require_once 'api/db.php'; // Corrected path since this is inside server_side/

// Cleanup old tests
$conn->exec("DELETE FROM users WHERE name = 'Test User'");
$conn->exec("DELETE FROM rfid_cards WHERE uid = 'TEST_UID_123'");

// Add User
$conn->exec("INSERT INTO users (name, balance) VALUES ('Test User', 50.00)");
$user_id = $conn->lastInsertId();

// Add Card
$uid = 'TEST_UID_123';
$conn->exec("INSERT INTO rfid_cards (uid, user_id) VALUES ('$uid', $user_id)");

echo "Created User ID: $user_id with Balance: 50.00\n";
echo "Created Card UID: $uid\n\n";

echo "--- Simulating Tap IN ---\n";
simulate_tap($uid, 'PC-07');

// Cheat Time (Backdate the session start_time by 5 minutes)
echo "\n--- Cheating Time (Backdating session by 5 minutes) ---\n";
$conn->exec("UPDATE computer_sessions SET start_time = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE user_id = $user_id AND status = 'active'");
echo "Time manipulated in DB.\n\n";

echo "--- Simulating Tap OUT ---\n";
simulate_tap($uid, 'PC-07');

// Fetch final balance
$stmt = $conn->query("SELECT balance FROM users WHERE id = $user_id");
$final_balance = $stmt->fetchColumn();
echo "\nFinal Balance: $final_balance (Expected: 45.00)\n";

?>
