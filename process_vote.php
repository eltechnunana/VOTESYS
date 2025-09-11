<?php
/**
 * Heritage Christian University Online Voting System
 * Vote Processing Handler
 */

// Timezone is set in constants.php to UTC (GMT+00:00)

require_once 'config/voter_config.php';

// Disable error display for JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Require authentication
    requireVoterAuth();
    
    $auth = new VoterAuth();
    $db = VoterDatabase::getInstance()->getConnection();
    $voter = $auth->getCurrentVoter();
    
    if (!$voter) {
        throw new Exception('Authentication required');
    }
    
    // Get election ID from form submission
    $election_id = isset($_POST['election_id']) ? (int)$_POST['election_id'] : CURRENT_ELECTION_ID;
    
    // Validate election ID
    if ($election_id <= 0) {
        throw new Exception('Invalid election ID');
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('Invalid security token');
    }
    
    // Check if voter has already voted in this election
    if ($auth->hasVotedInElection($voter['id'])) {
        throw new Exception('You have already voted in this election');
    }
    
    // Get current active election
    $stmt = $db->prepare("SELECT * FROM elections WHERE id = ? AND is_active = 1 AND start_date <= NOW() AND end_date > NOW()");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch();
    
    if (!$election) {
        throw new Exception('No active election found');
    }
    
    // Validate votes data
    $votes = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'position_') === 0 && !empty($value)) {
            $position_id = str_replace('position_', '', $key);
            $candidate_id = intval($value);
            
            // Validate position and candidate using new structure
            $stmt = $db->prepare("
                SELECT c.id, c.election_specific_position_id, esp.position_title, c.full_name as candidate_name
                FROM candidates c 
                JOIN election_specific_positions esp ON c.election_specific_position_id = esp.id 
                WHERE c.id = ? AND c.election_specific_position_id = ? AND c.is_approved = 1
            ");
            $stmt->execute([$candidate_id, $position_id]);
            $candidate = $stmt->fetch();
            
            if (!$candidate) {
                throw new Exception('Invalid candidate or position selection');
            }
            
            // Check if voter already voted for this position
            $stmt = $db->prepare("SELECT id FROM votes WHERE voter_id = ? AND election_specific_position_id = ?");
            $stmt->execute([$voter['id'], $position_id]);
            if ($stmt->fetch()) {
                throw new Exception('You have already voted for position: ' . $candidate['position_title']);
            }
            
            $votes[] = [
                'position_id' => $position_id,
                'candidate_id' => $candidate_id,
                'position_title' => $candidate['position_title'],
                'candidate_name' => $candidate['candidate_name']
            ];
        }
    }
    
    if (empty($votes)) {
        throw new Exception('No valid votes submitted');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Insert votes
        $stmt = $db->prepare("
            INSERT INTO votes (voter_id, candidate_id, election_id, position_id, election_specific_position_id, voted_at, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        foreach ($votes as $vote) {
            $stmt->execute([
                $voter['id'],
                $vote['candidate_id'],
                $election_id,
                $vote['position_id'],
                $vote['position_id'],
                $ip_address,
                $user_agent
            ]);
            
            // Vote count is calculated dynamically from votes table
        }
        
        // Mark voter as having voted
        $stmt = $db->prepare("UPDATE voters SET has_voted = 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$voter['id']]);
        
        // Log the voting action
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_type, user_id, action, table_name, record_id, details, ip_address, user_agent, created_at) 
            VALUES ('voter', ?, 'VOTE_CAST', 'votes', NULL, ?, ?, ?, NOW())
        ");
        $details = json_encode([
            'votes_cast' => count($votes),
            'positions' => array_column($votes, 'position_title')
        ]);
        $stmt->execute([$voter['id'], $details, $ip_address, $user_agent]);
        
        // Commit transaction
        $db->commit();
        
        // Update session to reflect voting status
        $_SESSION['has_voted'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'Your votes have been submitted successfully!',
            'votes_cast' => count($votes),
            'election' => $election['name']
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Vote processing error: " . $e->getMessage());
    
    // Return appropriate error response
    $status_code = 400;
    if (strpos($e->getMessage(), 'Authentication') !== false) {
        $status_code = 401;
    } elseif (strpos($e->getMessage(), 'already voted') !== false) {
        $status_code = 409;
    }
    
    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>