<?php
define('SECURE_ACCESS', true);
require_once 'config/database.php';

echo "<h2>Admin Users in Database:</h2>";

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query('SELECT id, username FROM admin LIMIT 10');
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th></tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>" . htmlspecialchars($row['id']) . "</td><td>" . htmlspecialchars($row['username']) . "</td></tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<p><a href='quick_login.php'>Back to Login</a></p>";
?>