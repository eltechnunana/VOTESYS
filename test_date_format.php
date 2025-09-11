<?php
define('SECURE_ACCESS', true);
require_once 'config/database.php';

// Test different date formats
$test_dates = [
    '2025-08-22T09:00',  // datetime-local format
    '2025-08-22 09:00:00', // MySQL datetime format
    '2025-08-22 09:00',    // MySQL datetime without seconds
];

echo "<h2>Date Format Testing</h2>";

foreach ($test_dates as $date) {
    echo "<h3>Testing: $date</h3>";
    
    $timestamp = strtotime($date);
    echo "strtotime result: $timestamp<br>";
    
    if ($timestamp !== false) {
        echo "Converted to: " . date('Y-m-d H:i:s', $timestamp) . "<br>";
        echo "MySQL format: " . date('Y-m-d H:i:s', $timestamp) . "<br>";
    } else {
        echo "<span style='color: red;'>FAILED to parse date</span><br>";
    }
    
    echo "<hr>";
}

// Test actual database update
echo "<h2>Database Update Test</h2>";

try {
    $pdo = getDBConnection();
    
    // Get current election data
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = 26");
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($election) {
        echo "<h3>Current Election Data:</h3>";
        echo "Start Date: " . $election['start_date'] . "<br>";
        echo "End Date: " . $election['end_date'] . "<br>";
        
        // Test update with datetime-local format
        $new_start = '2025-08-22T10:00';
        $new_end = '2025-08-22T18:00';
        
        echo "<h3>Testing Update with datetime-local format:</h3>";
        echo "New Start: $new_start<br>";
        echo "New End: $new_end<br>";
        
        $stmt = $pdo->prepare("
            UPDATE elections 
            SET start_date = ?, end_date = ?, updated_at = NOW()
            WHERE id = 26
        ");
        
        $result = $stmt->execute([$new_start, $new_end]);
        
        if ($result) {
            echo "<span style='color: green;'>UPDATE SUCCESSFUL - Rows affected: " . $stmt->rowCount() . "</span><br>";
            
            // Verify the update
            $stmt = $pdo->prepare("SELECT start_date, end_date FROM elections WHERE id = 26");
            $stmt->execute();
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "Updated Start Date: " . $updated['start_date'] . "<br>";
            echo "Updated End Date: " . $updated['end_date'] . "<br>";
        } else {
            echo "<span style='color: red;'>UPDATE FAILED</span><br>";
        }
    } else {
        echo "Election with ID 26 not found<br>";
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red;'>Database error: " . $e->getMessage() . "</span><br>";
}
?>