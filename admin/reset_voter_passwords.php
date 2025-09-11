<?php
/**
 * Admin Utility: Reset Voter Passwords
 * This utility helps reset passwords for voters who cannot log in
 */

require_once '../config/database.php';
require_once '../config/voter_config.php';
require_once '../config/email.php';

// Simple authentication check
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reset_single') {
        $student_id = trim($_POST['student_id'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        
        if (empty($student_id) || empty($new_password)) {
            $error = 'Please provide both Student ID and new password.';
        } else {
            try {
                $db = getDBConnection();
                
                // Check if voter exists
                $stmt = $db->prepare("SELECT * FROM voters WHERE student_id = ?");
                $stmt->execute([$student_id]);
                $voter = $stmt->fetch();
                
                if (!$voter) {
                    $error = "Voter with Student ID '$student_id' not found.";
                } else {
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password
                    $updateStmt = $db->prepare("UPDATE voters SET password = ?, updated_at = NOW() WHERE student_id = ?");
                    $result = $updateStmt->execute([$hashed_password, $student_id]);
                    
                    if ($result) {
                        $message = "Password reset successfully for {$voter['first_name']} {$voter['last_name']} (Student ID: $student_id). New password: $new_password";
                        
                        // Optionally send email (if email system is working)
                        try {
                            $emailUtility = new EmailUtility();
                            $emailSent = $emailUtility->sendPasswordEmail(
                                $voter['email'],
                                $voter['first_name'] . ' ' . $voter['last_name'],
                                $student_id,
                                $new_password
                            );
                            
                            if ($emailSent) {
                                $message .= " Email sent to {$voter['email']}.";
                            } else {
                                $message .= " (Email could not be sent - please provide password manually)";
                            }
                        } catch (Exception $e) {
                            $message .= " (Email system error: " . $e->getMessage() . ")";
                        }
                    } else {
                        $error = 'Failed to update password in database.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'reset_all') {
        $default_password = trim($_POST['default_password'] ?? 'voter123');
        
        try {
            $db = getDBConnection();
            
            // Get all active voters
            $stmt = $db->query("SELECT * FROM voters WHERE is_active = 1");
            $voters = $stmt->fetchAll();
            
            $updated_count = 0;
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            
            foreach ($voters as $voter) {
                $updateStmt = $db->prepare("UPDATE voters SET password = ?, updated_at = NOW() WHERE id = ?");
                if ($updateStmt->execute([$hashed_password, $voter['id']])) {
                    $updated_count++;
                }
            }
            
            $message = "Password reset for $updated_count voters. Default password: $default_password";
            
        } catch (Exception $e) {
            $error = 'Error resetting passwords: ' . $e->getMessage();
        }
    }
}

// Get list of voters for display
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT student_id, first_name, last_name, email, is_active, last_login FROM voters ORDER BY student_id");
    $voters = $stmt->fetchAll();
} catch (Exception $e) {
    $voters = [];
    $error = 'Could not load voters: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Voter Passwords - Admin Utility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-key"></i> Reset Voter Passwords</h1>
                <p class="text-muted">This utility helps reset passwords for voters who cannot log in due to forgotten auto-generated passwords.</p>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Reset Single Voter -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-user"></i> Reset Single Voter Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="reset_single">
                                    <div class="mb-3">
                                        <label for="student_id" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="text" class="form-control" id="new_password" name="new_password" value="voter123" required>
                                        <div class="form-text">Use a simple password for testing purposes.</div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reset All Voters -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-users"></i> Reset All Voter Passwords</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to reset passwords for ALL voters?');">
                                    <input type="hidden" name="action" value="reset_all">
                                    <div class="mb-3">
                                        <label for="default_password" class="form-label">Default Password</label>
                                        <input type="text" class="form-control" id="default_password" name="default_password" value="voter123" required>
                                        <div class="form-text">This password will be set for all active voters.</div>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-users-cog"></i> Reset All Passwords
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Voters List -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Current Voters</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($voters)): ?>
                            <p class="text-muted">No voters found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Last Login</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($voters as $voter): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($voter['student_id']); ?></code></td>
                                                <td><?php echo htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($voter['email']); ?></td>
                                                <td>
                                                    <?php if ($voter['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($voter['last_login']): ?>
                                                        <?php echo date('Y-m-d H:i', strtotime($voter['last_login'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>