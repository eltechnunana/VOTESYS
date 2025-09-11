<?php
define('SECURE_ACCESS', true);
require_once '../../config/database.php';
require_once '../../config/session.php';

// Direct access allowed - no login check required

$pdo = getDBConnection();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_live_stats':
            getLiveStats();
            break;
        case 'get_election_votes':
            getElectionVotes();
            break;
        case 'get_candidate_votes':
            getCandidateVotes();
            break;
        case 'get_voting_activity':
            getVotingActivity();
            break;
        case 'get_hourly_activity':
            getVotingActivity();
            break;
        case 'get_turnout_stats':
            getTurnoutStats();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getLiveStats() {
    global $pdo;
    
    try {
        // Get total elections
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_elections FROM elections");
        $stmt->execute();
        $totalElections = $stmt->fetch()['total_elections'];
        
        // Get active elections
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_elections 
            FROM elections 
            WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW()
        ");
        $stmt->execute();
        $activeElections = $stmt->fetch()['active_elections'];
        
        // Get total candidates
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_candidates FROM candidates");
        $stmt->execute();
        $totalCandidates = $stmt->fetch()['total_candidates'];
        
        // Get active candidates
        $stmt = $pdo->prepare("SELECT COUNT(*) as active_candidates FROM candidates WHERE is_approved = 1");
        $stmt->execute();
        $activeCandidates = $stmt->fetch()['active_candidates'];
        
        // Get total registered voters
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_voters FROM voters WHERE is_active = 1");
        $stmt->execute();
        $totalVoters = $stmt->fetch()['total_voters'];
        
        // Get voters who voted today
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT voter_id) as voted_today 
            FROM votes 
            WHERE DATE(voted_at) = CURDATE()
        ");
        $stmt->execute();
        $votedToday = $stmt->fetch()['voted_today'];
        
        // Get total votes (all time)
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_votes FROM votes");
        $stmt->execute();
        $totalVotes = $stmt->fetch()['total_votes'];
        
        // Get total votes today
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as votes_today 
            FROM votes 
            WHERE DATE(voted_at) = CURDATE()
        ");
        $stmt->execute();
        $votesToday = $stmt->fetch()['votes_today'];
        
        // Get votes in last hour
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as votes_last_hour 
            FROM votes 
            WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute();
        $votesLastHour = $stmt->fetch()['votes_last_hour'];
        
        // Calculate turnout percentage
        $turnoutPercentage = $totalVoters > 0 ? round(($votedToday / $totalVoters) * 100, 2) : 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_elections' => $totalElections,
                'active_elections' => $activeElections,
                'total_candidates' => $totalCandidates,
                'active_candidates' => $activeCandidates,
                'total_voters' => $totalVoters,
                'voted_today' => $votedToday,
                'total_votes' => $totalVotes,
                'votes_today' => $votesToday,
                'votes_last_hour' => $votesLastHour,
                'turnout_percentage' => $turnoutPercentage
            ]
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getElectionVotes() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.election_title as title,
                CASE WHEN e.is_active = 1 THEN 'active' ELSE 'inactive' END as status,
                COUNT(v.id) as vote_count,
                e.start_date,
                e.end_date
            FROM elections e
            LEFT JOIN votes v ON e.id = v.election_id
            GROUP BY e.id, e.election_title, e.is_active, e.start_date, e.end_date
            ORDER BY e.start_date DESC
        ");
        $stmt->execute();
        $elections = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $elections
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getCandidateVotes() {
    global $pdo;
    
    $election_id = $_GET['election_id'] ?? null;
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                u.full_name,
                p.position_title as position,
                COUNT(v.id) as vote_count,
                c.photo
            FROM candidates c
            JOIN users u ON c.user_id = u.id
            JOIN positions p ON c.position_id = p.id
            LEFT JOIN votes v ON c.id = v.candidate_id
            WHERE c.position_id IN (SELECT id FROM positions WHERE election_id = ?) AND c.is_approved = 1
            GROUP BY c.id, u.full_name, p.position_title, c.photo
            ORDER BY p.position_title, vote_count DESC
        ");
        $stmt->execute([$election_id]);
        $candidates = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $candidates
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getVotingActivity() {
    global $pdo;
    
    try {
        // Get hourly voting activity for the last 24 hours
        $stmt = $pdo->prepare("
            SELECT 
                HOUR(voted_at) as hour,
                COUNT(*) as vote_count,
                DATE(voted_at) as vote_date
            FROM votes 
            WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY HOUR(voted_at), DATE(voted_at)
            ORDER BY vote_date DESC, hour ASC
        ");
        $stmt->execute();
        $activity = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $activity
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getTurnoutStats() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.election_title as title,
                COUNT(DISTINCT v.voter_id) as voted_count,
                (SELECT COUNT(*) FROM voters WHERE is_active = 1) as total_voters,
                ROUND((COUNT(DISTINCT v.voter_id) / (SELECT COUNT(*) FROM voters WHERE is_active = 1)) * 100, 2) as turnout_percentage
            FROM elections e
            LEFT JOIN votes v ON e.id = v.election_id
            WHERE e.is_active = 1
            GROUP BY e.id, e.election_title
            ORDER BY e.start_date DESC
        ");
        $stmt->execute();
        $turnout = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $turnout
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

// Log admin action
function logAdminAction($action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'admin',
            $_SESSION['admin_id'],
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't stop execution
        error_log('Failed to log admin action: ' . $e->getMessage());
    }
}
?>