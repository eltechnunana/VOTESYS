<?php
define('SECURE_ACCESS', true);
require_once 'config/database.php';
require_once 'config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>Raw Input:</h3>";
    echo "<pre>";
    echo file_get_contents('php://input');
    echo "</pre>";
    
    // Test the actual update
    if (isset($_POST['action']) && $_POST['action'] === 'update_election') {
        $pdo = getDBConnection();
        
        $election_id = (int)($_POST['election_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        echo "<h3>Processed Values:</h3>";
        echo "Election ID: $election_id<br>";
        echo "Title: $title<br>";
        echo "Description: $description<br>";
        echo "Status: $status<br>";
        echo "Start Date: $start_date<br>";
        echo "End Date: $end_date<br>";
        
        // Validate dates
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        echo "<h3>Date Validation:</h3>";
        echo "Start timestamp: $start_timestamp (" . date('Y-m-d H:i:s', $start_timestamp) . ")<br>";
        echo "End timestamp: $end_timestamp (" . date('Y-m-d H:i:s', $end_timestamp) . ")<br>";
        
        if ($start_timestamp >= $end_timestamp) {
            echo "<div style='color: red;'>ERROR: End date must be after start date</div>";
        } else {
            echo "<div style='color: green;'>Date validation passed</div>";
            
            // Convert status to is_active boolean
            $is_active = ($status === 'active') ? 1 : 0;
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE elections 
                    SET election_title = ?, description = ?, is_active = ?, start_date = ?, end_date = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$title, $description, $is_active, $start_date, $end_date, $election_id]);
                
                if ($result) {
                    echo "<div style='color: green;'>UPDATE SUCCESSFUL - Rows affected: " . $stmt->rowCount() . "</div>";
                    
                    // Verify the update
                    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
                    $stmt->execute([$election_id]);
                    $updated_election = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<h3>Updated Election Data:</h3>";
                    echo "<pre>";
                    print_r($updated_election);
                    echo "</pre>";
                } else {
                    echo "<div style='color: red;'>UPDATE FAILED</div>";
                }
            } catch (PDOException $e) {
                echo "<div style='color: red;'>Database error: " . $e->getMessage() . "</div>";
            }
        }
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Election Update</title>
</head>
<body>
    <h2>Debug Election Date/Time Update</h2>
    <form method="POST" action="">
        <input type="hidden" name="action" value="update_election">
        <input type="hidden" name="election_id" value="26">
        
        <label>Title:</label><br>
        <input type="text" name="title" value="BSA Test" required><br><br>
        
        <label>Description:</label><br>
        <textarea name="description">Business Students Association Test</textarea><br><br>
        
        <label>Status:</label><br>
        <select name="status">
            <option value="inactive">Inactive</option>
            <option value="active" selected>Active</option>
        </select><br><br>
        
        <label>Start Date & Time:</label><br>
        <input type="datetime-local" name="start_date" value="2025-08-22T09:00" required><br><br>
        
        <label>End Date & Time:</label><br>
        <input type="datetime-local" name="end_date" value="2025-08-22T17:00" required><br><br>
        
        <button type="submit">Test Update</button>
    </form>
</body>
</html>