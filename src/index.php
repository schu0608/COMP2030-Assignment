<?php
require_once "./inc/dbconn.inc.php";

$pdo = db();

echo "<h1>COMP2030 Assignment Dev Environment Setup</h1>";
echo "<h2>Connected successfully to MySQL database!</h2>";

// Create table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert demo message if empty
$count = (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
if ($count === 0) {
    $stmt = $pdo->prepare("INSERT INTO messages (message) VALUES (:msg)");
    $stmt->execute(['msg' => 'Connection and insert into db successful']);
}

// Display messages
$messages = $pdo->query("SELECT id, message, reg_date FROM messages")->fetchAll();
if ($messages) {
    echo "<h2>Messages:</h2><ul>";
    foreach ($messages as $row) {
        echo "<li>{$row['id']} - {$row['message']} ({$row['reg_date']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No messages yet.</p>";
}
