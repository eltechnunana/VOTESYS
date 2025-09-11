<?php
define('SECURE_ACCESS', true);
require_once 'config/database.php';

echo "<h2>Election Update Verification</h2>";

try {
    $pdo = getDBConnection();
    
    // Get current election data
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = 26");
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$election) {
        echo "<div style='color: red;'>Election not found</div>";
        exit();
    }
    
    echo "<h3>Current Election Data (ID: 26)</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    
    foreach ($election as $key => $value) {
        $style = '';
        if (in_array($key, ['start_date', 'end_date', 'updated_at'])) {
            $style = 'background-color: #ffffcc;'; // Highlight date fields
        }
        echo "<tr style='$style'><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
    
    // Check if election is currently active based on dates
    $now = time();
    $start_time = strtotime($election['start_date']);
    $end_time = strtotime($election['end_date']);
    
    echo "<h3>Election Status Analysis</h3>";
    echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . " (" . $now . ")</p>";
    echo "<p><strong>Start Time:</strong> " . date('Y-m-d H:i:s', $start_time) . " (" . $start_time . ")</p>";
    echo "<p><strong>End Time:</strong> " . date('Y-m-d H:i:s', $end_time) . " (" . $end_time . ")</p>";
    
    $status = '';
    $color = '';
    
    if ($now < $start_time) {
        $status = 'NOT STARTED';
        $color = 'orange';
    } elseif ($now >= $start_time && $now <= $end_time) {
        $status = 'ACTIVE';
        $color = 'green';
    } else {
        $status = 'ENDED';
        $color = 'red';
    }
    
    echo "<p><strong>Calculated Status:</strong> <span style='color: $color; font-weight: bold;'>$status</span></p>";
    echo "<p><strong>Database is_active:</strong> " . ($election['is_active'] ? 'TRUE (1)' : 'FALSE (0)') . "</p>";
    
    // Check if there's a mismatch
    $should_be_active = ($now >= $start_time && $now <= $end_time);
    $is_database_active = (bool)$election['is_active'];
    
    if ($should_be_active !== $is_database_active) {
        echo "<div style='color: red; background-color: #ffeeee; padding: 10px; border: 1px solid red;'>";
        echo "<strong>⚠ STATUS MISMATCH DETECTED!</strong><br>";
        echo "Based on dates, election should be: " . ($should_be_active ? 'ACTIVE' : 'INACTIVE') . "<br>";
        echo "Database shows: " . ($is_database_active ? 'ACTIVE' : 'INACTIVE');
        echo "</div>";
    } else {
        echo "<div style='color: green; background-color: #eeffee; padding: 10px; border: 1px solid green;'>";
        echo "<strong>✓ STATUS CONSISTENT</strong><br>";
        echo "Election status matches the date/time logic.";
        echo "</div>";
    }
    
    // Show timezone information
    echo "<h3>Timezone Information</h3>";
    echo "<p><strong>PHP Default Timezone:</strong> " . date_default_timezone_get() . "</p>";
    echo "<p><strong>Current PHP Time:</strong> " . date('Y-m-d H:i:s T') . "</p>";
    
    // Check database timezone
    $stmt = $pdo->query("SELECT NOW() as db_time, @@session.time_zone as session_tz, @@global.time_zone as global_tz");
    $tz_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Database Time:</strong> " . $tz_info['db_time'] . "</p>";
    echo "<p><strong>DB Session Timezone:</strong> " . $tz_info['session_tz'] . "</p>";
    echo "<p><strong>DB Global Timezone:</strong> " . $tz_info['global_tz'] . "</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}

echo "<p style='margin-top: 30px;'><a href='automated_election_test.php'>Run Automated Test Again</a></p>";
echo "<p><a href='admin/edit_election.php?id=26'>Go to Edit Election Page</a></p>";
?>