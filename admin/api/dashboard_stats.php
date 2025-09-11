<?php
/**
 * Heritage Christian University Online Voting System
 * Dashboard Statistics API Endpoint
 * Provides real-time statistics for the admin dashboard
 */

// Security check
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

SecurityConfig::setSecurityHeaders();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get total voters
    $stmt = $conn->prepare("SELECT COUNT(*) as total_voters FROM voters WHERE is_active = 1");
    $stmt->execute();
    $totalVoters = $stmt->fetch(PDO::FETCH_ASSOC)['total_voters'];
    
    // Get active elections
    $stmt = $conn->prepare("SELECT COUNT(*) as active_elections FROM elections WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
    $stmt->execute();
    $activeElections = $stmt->fetch(PDO::FETCH_ASSOC)['active_elections'];
    
    // Get total votes cast
    $stmt = $conn->prepare("SELECT COUNT(*) as total_votes FROM votes");
    $stmt->execute();
    $totalVotes = $stmt->fetch(PDO::FETCH_ASSOC)['total_votes'];
    
    // Get total candidates
    $stmt = $conn->prepare("SELECT COUNT(*) as total_candidates FROM candidates WHERE is_approved = 1");
    $stmt->execute();
    $totalCandidates = $stmt->fetch(PDO::FETCH_ASSOC)['total_candidates'];
    
    // Get recent activity (last 24 hours)
    $stmt = $conn->prepare("SELECT COUNT(*) as recent_votes FROM votes WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $recentVotes = $stmt->fetch(PDO::FETCH_ASSOC)['recent_votes'];
    
    // Get voter turnout percentage for active elections
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(ROUND((COUNT(DISTINCT v.voter_id) / COUNT(DISTINCT vo.id)) * 100, 2), 0) as turnout_percentage
        FROM voters vo
        LEFT JOIN votes v ON vo.id = v.voter_id 
            AND v.election_id IN (
                SELECT id FROM elections 
                WHERE is_active = 1 
                AND start_date <= NOW() 
                AND end_date >= NOW()
            )
        WHERE vo.is_active = 1
    ");
    $stmt->execute();
    $turnoutPercentage = $stmt->fetch(PDO::FETCH_ASSOC)['turnout_percentage'];
    
    // Get election status breakdown
    $stmt = $conn->prepare("
        SELECT 
            CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status,
            COUNT(*) as count
        FROM elections 
        GROUP BY is_active
    ");
    $stmt->execute();
    $electionStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get voting activity by hour (last 24 hours)
    $stmt = $conn->prepare("
        SELECT 
            HOUR(voted_at) as hour,
            COUNT(*) as votes
        FROM votes 
        WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY HOUR(voted_at)
        ORDER BY hour
    ");
    $stmt->execute();
    $hourlyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top performing elections (by vote count)
    $stmt = $conn->prepare("
        SELECT 
            e.election_title as title,
            COUNT(v.id) as vote_count
        FROM elections e
        LEFT JOIN votes v ON e.id = v.election_id
        WHERE e.is_active = 1
        GROUP BY e.id, e.election_title
        ORDER BY vote_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topElections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'overview' => [
                'total_voters' => (int)$totalVoters,
                'active_elections' => (int)$activeElections,
                'total_votes' => (int)$totalVotes,
                'total_candidates' => (int)$totalCandidates,
                'recent_votes' => (int)$recentVotes,
                'turnout_percentage' => (float)$turnoutPercentage
            ],
            'election_status' => $electionStatus,
            'hourly_activity' => $hourlyActivity,
            'top_elections' => $topElections,
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>