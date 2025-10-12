<?php
require_once "./inc/dbconn.inc.php";

$pdo = db(); // get PDO instance from dbconn.inc.php

echo "<h1>COMP2030 Assignment Dev Environment Setup</h1><h2>Connected successfully to MySQL database!</h2>";

// Create a table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
try {
    $pdo->exec($sql);
    echo "<p>Table \"messages\" created successfully or already exists.</p>";
} catch (PDOException $e) {
    echo "<p>Error creating table: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Insert some data only if table is empty
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS count FROM messages");
    $row = $stmt->fetch();
    if ($row['count'] == 0) {
        $insert_sql = "INSERT INTO messages (message) VALUES ('Connection and insert into db successful')";
        $pdo->exec($insert_sql);
        echo "<p>New record created successfully.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error inserting data: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Display messages
try {
    $stmt = $pdo->query("SELECT id, message, reg_date FROM messages");
    $messages = $stmt->fetchAll();
    
    if ($messages) {
        echo "<h2>Messages:</h2><ul>";
        foreach ($messages as $row) {
            echo "<li>" . htmlspecialchars($row['id']) . " - " . 
                 htmlspecialchars($row['message']) . " (" . 
                 htmlspecialchars($row['reg_date']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No messages yet.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error fetching messages: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
