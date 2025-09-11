<?php
define('SECURE_ACCESS', true);
session_start();
require_once '../../config/database.php';
require_once '../../config/session.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $election_id = $_POST['election_id'] ?? null;
    $voting_status = $_POST['voting_status'] ?? null;
    
    if (!$election_id || !$voting_status) {
        echo json_encode(['success' => false, 'message' => 'Election ID and voting status are required']);
        exit;
    }
    
    // Validate voting status
    if (!in_array($voting_status, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid voting status']);
        exit;
    }
    
    // Convert status to database format
    $is_active = ($voting_status === 'active') ? 1 : 0;
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Update the election's voting status
    $stmt = $pdo->prepare("UPDATE elections SET is_active = ? WHERE id = ?");
    $result = $stmt->execute([$is_active, $election_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Voting status updated successfully',
            'status' => $voting_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update voting status']);
    }
    
} catch (Exception $e) {
    error_log("Error updating voting status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>