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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $election_id = $_GET['election_id'] ?? null;
    
    if (!$election_id) {
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        exit;
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Get the election's voting status
    $stmt = $pdo->prepare("SELECT is_active, election_title FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($election) {
        $status = $election['is_active'] ? 'active' : 'inactive';
        
        echo json_encode([
            'success' => true,
            'status' => $status,
            'election_title' => $election['election_title']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Election not found']);
    }
    
} catch (Exception $e) {
    error_log("Error getting voting status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>