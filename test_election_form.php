<?php
define('SECURE_ACCESS', true);
require_once 'config/database.php';
require_once 'config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin/login.php');
    exit();
}

// Get current election data
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM elections WHERE id = 26");
$stmt->execute();
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    die('Election not found');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Election Form</title>
    <script>
        function submitForm() {
            const form = document.getElementById('testForm');
            const formData = new FormData(form);
            formData.append('action', 'update_election');
            
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            fetch('admin/api/edit_election.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                document.getElementById('result').innerHTML = 
                    '<div style="color: ' + (data.success ? 'green' : 'red') + ';">' + 
                    JSON.stringify(data, null, 2) + '</div>';
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = 
                    '<div style="color: red;">Error: ' + error.message + '</div>';
            });
        }
    </script>
</head>
<body>
    <h2>Test Election Form Submission</h2>
    
    <form id="testForm">
        <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
        
        <label>Title:</label><br>
        <input type="text" name="title" value="<?php echo htmlspecialchars($election['election_title']); ?>" required><br><br>
        
        <label>Description:</label><br>
        <textarea name="description"><?php echo htmlspecialchars($election['description']); ?></textarea><br><br>
        
        <label>Status:</label><br>
        <select name="status">
            <option value="inactive" <?php echo $election['is_active'] == 0 ? 'selected' : ''; ?>>Inactive</option>
            <option value="active" <?php echo $election['is_active'] == 1 ? 'selected' : ''; ?>>Active</option>
            <option value="completed">Completed</option>
        </select><br><br>
        
        <label>Start Date & Time:</label><br>
        <input type="datetime-local" name="start_date" 
               value="<?php echo date('Y-m-d\TH:i', strtotime($election['start_date'])); ?>" required><br><br>
        
        <label>End Date & Time:</label><br>
        <input type="datetime-local" name="end_date" 
               value="<?php echo date('Y-m-d\TH:i', strtotime($election['end_date'])); ?>" required><br><br>
        
        <button type="button" onclick="submitForm()">Test Update</button>
    </form>
    
    <h3>Result:</h3>
    <div id="result"></div>
    
    <h3>Current Election Data:</h3>
    <pre><?php print_r($election); ?></pre>
</body>
</html>