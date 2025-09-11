<?php
define('SECURE_ACCESS', true);
require_once 'config/database.php';
require_once 'config/session.php';

echo "<h2>Automated Election Update Test</h2>";

// Step 1: Simulate admin login
echo "<h3>Step 1: Simulating Admin Login</h3>";
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT id, username, password FROM admin WHERE username = ?");
$stmt->execute(['testadmin']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin && password_verify('test123', $admin['password'])) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_logged_in'] = true;
    echo "<div style='color: green;'>✓ Admin login successful</div>";
} else {
    echo "<div style='color: red;'>✗ Admin login failed</div>";
    exit();
}

// Step 2: Get current election data
echo "<h3>Step 2: Current Election Data (ID: 26)</h3>";
$stmt = $pdo->prepare("SELECT * FROM elections WHERE id = 26");
$stmt->execute();
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    echo "<div style='color: red;'>✗ Election not found</div>";
    exit();
}

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Current Value</th></tr>";
foreach ($election as $key => $value) {
    echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

// Step 3: Test datetime-local format conversion
echo "<h3>Step 3: Testing Datetime-Local Format Conversion</h3>";

// Simulate form data as it would come from datetime-local input
$current_start = $election['start_date'];
$current_end = $election['end_date'];

// Convert to datetime-local format (what the form would show)
$form_start = date('Y-m-d\TH:i', strtotime($current_start));
$form_end = date('Y-m-d\TH:i', strtotime($current_end));

echo "<p><strong>Current DB Start:</strong> $current_start</p>";
echo "<p><strong>Form Start Format:</strong> $form_start</p>";
echo "<p><strong>Current DB End:</strong> $current_end</p>";
echo "<p><strong>Form End Format:</strong> $form_end</p>";

// Step 4: Test new dates (extend the election by 1 day)
echo "<h3>Step 4: Testing Date Update (Extending by 1 day)</h3>";

$new_start_timestamp = strtotime($current_start);
$new_end_timestamp = strtotime($current_end) + (24 * 60 * 60); // Add 1 day

$new_start_datetime = date('Y-m-d H:i:s', $new_start_timestamp);
$new_end_datetime = date('Y-m-d H:i:s', $new_end_timestamp);

// Simulate what would come from datetime-local input
$form_new_start = date('Y-m-d\TH:i', $new_start_timestamp);
$form_new_end = date('Y-m-d\TH:i', $new_end_timestamp);

echo "<p><strong>New Start (form format):</strong> $form_new_start</p>";
echo "<p><strong>New End (form format):</strong> $form_new_end</p>";
echo "<p><strong>New Start (DB format):</strong> $new_start_datetime</p>";
echo "<p><strong>New End (DB format):</strong> $new_end_datetime</p>";

// Step 5: Simulate the exact API call
echo "<h3>Step 5: Simulating API Update Call</h3>";

// Simulate $_POST data as it would come from the form
$_POST = [
    'action' => 'update_election',
    'election_id' => '26',
    'title' => $election['election_title'],
    'description' => $election['description'],
    'status' => $election['is_active'] ? 'active' : 'inactive',
    'start_date' => $form_new_start,
    'end_date' => $form_new_end
];

echo "<p><strong>Simulated POST data:</strong></p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Step 6: Execute the update logic (copied from admin/api/edit_election.php)
echo "<h3>Step 6: Executing Update Logic</h3>";

$election_id = (int)($_POST['election_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

echo "<p><strong>Processed values:</strong></p>";
echo "<ul>";
echo "<li>Election ID: $election_id</li>";
echo "<li>Title: '$title'</li>";
echo "<li>Status: '$status'</li>";
echo "<li>Start Date: '$start_date'</li>";
echo "<li>End Date: '$end_date'</li>";
echo "</ul>";

// Validation
$errors = [];
if (empty($title)) $errors[] = 'Title required';
if (empty($election_id)) $errors[] = 'Election ID required';
if (!in_array($status, ['inactive', 'active', 'completed'])) $errors[] = 'Invalid status';
if (empty($start_date) || empty($end_date)) $errors[] = 'Dates required';

$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);

echo "<p><strong>Date parsing:</strong></p>";
echo "<ul>";
echo "<li>Start timestamp: $start_timestamp (" . ($start_timestamp ? date('Y-m-d H:i:s', $start_timestamp) : 'INVALID') . ")</li>";
echo "<li>End timestamp: $end_timestamp (" . ($end_timestamp ? date('Y-m-d H:i:s', $end_timestamp) : 'INVALID') . ")</li>";
echo "</ul>";

if ($start_timestamp === false || $end_timestamp === false) {
    $errors[] = 'Invalid date format';
}

if ($start_timestamp >= $end_timestamp) {
    $errors[] = 'End date must be after start date';
}

if (!empty($errors)) {
    echo "<div style='color: red;'><strong>Validation Errors:</strong></div>";
    foreach ($errors as $error) {
        echo "<div style='color: red;'>• $error</div>";
    }
} else {
    echo "<div style='color: green;'><strong>✓ Validation passed</strong></div>";
    
    // Step 7: Execute the database update
    echo "<h3>Step 7: Database Update</h3>";
    
    $is_active = ($status === 'active') ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE elections 
            SET election_title = ?, description = ?, is_active = ?, start_date = ?, end_date = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        echo "<p><strong>SQL Parameters:</strong> [" . implode(', ', [$title, $description, $is_active, $start_date, $end_date, $election_id]) . "]</p>";
        
        $result = $stmt->execute([$title, $description, $is_active, $start_date, $end_date, $election_id]);
        
        if ($result) {
            $rowCount = $stmt->rowCount();
            echo "<div style='color: green;'><strong>✓ UPDATE SUCCESSFUL - Rows affected: $rowCount</strong></div>";
            
            if ($rowCount > 0) {
                // Verify the update
                $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
                $stmt->execute([$election_id]);
                $updated_election = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<h3>Step 8: Verification - Updated Election Data</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Field</th><th>Old Value</th><th>New Value</th><th>Changed</th></tr>";
                
                foreach ($updated_election as $key => $new_value) {
                    $old_value = $election[$key] ?? 'N/A';
                    $changed = ($old_value != $new_value) ? '✓' : '';
                    $color = $changed ? 'background-color: #ffffcc;' : '';
                    
                    echo "<tr style='$color'>";
                    echo "<td>$key</td>";
                    echo "<td>" . htmlspecialchars($old_value) . "</td>";
                    echo "<td>" . htmlspecialchars($new_value) . "</td>";
                    echo "<td>$changed</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<div style='color: green; font-size: 18px; margin-top: 20px;'><strong>🎉 ELECTION UPDATE TEST COMPLETED SUCCESSFULLY!</strong></div>";
            } else {
                echo "<div style='color: orange;'>⚠ No rows were affected - possibly no changes made</div>";
            }
        } else {
            echo "<div style='color: red;'>✗ UPDATE FAILED</div>";
        }
    } catch (PDOException $e) {
        echo "<div style='color: red;'><strong>Database error:</strong> " . $e->getMessage() . "</div>";
    }
}

echo "<p style='margin-top: 30px;'><a href='admin/edit_election.php?id=26'>Go to Edit Election Page</a></p>";
echo "<p><a href='quick_login.php'>Back to Login</a></p>";
?>