<?php
define('SECURE_ACCESS', true);
require_once '../../config/database.php';
require_once '../../config/session.php';

// Session check removed for development purposes

$pdo = getDBConnection();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_elections':
            getElections();
            break;
        case 'get_results':
            getResults();
            break;
        case 'get_detailed_results':
            getDetailedResults();
            break;
        case 'export_results':
            exportResults();
            break;
        case 'publish_results':
            publishResults();
            break;
        case 'get_winner_analysis':
            getWinnerAnalysis();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getElections() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.election_title as title,
                e.description,
                e.start_date,
                e.end_date,
                e.is_active,
                COUNT(DISTINCT v.id) as total_votes,
                COUNT(DISTINCT c.id) as total_candidates
            FROM elections e
            LEFT JOIN votes v ON e.id = v.election_id
            LEFT JOIN election_positions ep ON e.id = ep.election_id
            LEFT JOIN positions p ON ep.position_id = p.id
            LEFT JOIN candidates c ON p.id = c.position_id AND c.is_approved = 1
            GROUP BY e.id, e.election_title, e.description, e.start_date, e.end_date, e.is_active
            ORDER BY e.start_date DESC
        ");
        $stmt->execute();
        $elections = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $elections
        ]);
    } catch (Exception $e) {
        error_log("DEBUG: Exception in getResults: " . $e->getMessage());
        error_log("DEBUG: Exception trace: " . $e->getTraceAsString());
        throw $e;
    }
}

function getResults() {
    global $pdo;
    
    $election_id = $_GET['election_id'] ?? null;
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        // Get election info
        $stmt = $pdo->prepare("
            SELECT id, election_title as title, description, start_date, end_date, is_active
            FROM elections 
            WHERE id = ?
        ");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();
        
        if (!$election) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Election not found']);
            return;
        }
        
        // Get results by position - using election_positions relationship
        $stmt = $pdo->prepare("
            SELECT 
                p.id as position_id,
                p.position_title as position_title,
                ep.max_candidates as max_winners
            FROM positions p
            INNER JOIN election_positions ep ON p.id = ep.position_id
            WHERE ep.election_id = ? AND ep.is_active = 1
            ORDER BY ep.display_order, p.position_title
        ");
        $stmt->execute([$election_id]);
        $positions = $stmt->fetchAll();
        
        // For each position, get candidates and votes
        $grouped_results = [];
        foreach ($positions as $position) {
            $position_data = [
                'position_id' => $position['position_id'],
                'position_title' => $position['position_title'],
                'max_winners' => $position['max_winners'],
                'candidates' => []
            ];
            
            // Get candidates for this position
            $stmt = $pdo->prepare("
                SELECT 
                    c.id as candidate_id,
                    c.full_name,
                    c.photo,
                    COUNT(v.id) as vote_count
                FROM candidates c
                LEFT JOIN votes v ON c.id = v.candidate_id
                WHERE c.position_id = ? AND c.is_approved = 1
                GROUP BY c.id, c.full_name, c.photo
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$position['position_id']]);
            $candidates = $stmt->fetchAll();
            
            // Get total votes for percentage calculation
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votes WHERE election_id = ?");
            $stmt->execute([$election_id]);
            $total_votes = $stmt->fetch()['total'];
            
            foreach ($candidates as $candidate) {
                $vote_percentage = $total_votes > 0 ? round(($candidate['vote_count'] * 100.0) / $total_votes, 2) : 0;
                $position_data['candidates'][] = [
                    'candidate_id' => $candidate['candidate_id'],
                    'full_name' => $candidate['full_name'],
                    'photo' => $candidate['photo'],
                    'vote_count' => $candidate['vote_count'],
                    'vote_percentage' => $vote_percentage
                ];
            }
            
            $grouped_results[] = $position_data;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'election' => $election,
                'results' => $grouped_results
            ]
        ]);
    } catch (Exception $e) {
        error_log("DEBUG: Exception in getResults: " . $e->getMessage());
        error_log("DEBUG: Exception trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error retrieving results: ' . $e->getMessage()]);
    }
}

function getDetailedResults() {
    global $pdo;
    
    $election_id = $_GET['election_id'] ?? null;
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        // Get voting statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT v.voter_id) as unique_voters,
                COUNT(v.id) as total_votes,
                (SELECT COUNT(*) FROM voters WHERE is_active = 1) as total_registered_voters,
                ROUND((COUNT(DISTINCT v.voter_id) * 100.0 / (SELECT COUNT(*) FROM voters WHERE is_active = 1)), 2) as turnout_percentage
            FROM votes v
            WHERE v.election_id = ?
        ");
        $stmt->execute([$election_id]);
        $stats = $stmt->fetch();
        
        // Get hourly voting pattern
        $stmt = $pdo->prepare("
            SELECT 
                HOUR(voted_at) as hour,
                COUNT(*) as vote_count
            FROM votes 
            WHERE election_id = ?
            GROUP BY HOUR(voted_at)
            ORDER BY hour
        ");
        $stmt->execute([$election_id]);
        $hourly_pattern = $stmt->fetchAll();
        
        // Get daily voting pattern
        $stmt = $pdo->prepare("
            SELECT 
                DATE(voted_at) as vote_date,
                COUNT(*) as vote_count
            FROM votes 
            WHERE election_id = ?
            GROUP BY DATE(voted_at)
            ORDER BY vote_date
        ");
        $stmt->execute([$election_id]);
        $daily_pattern = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'statistics' => $stats,
                'hourly_pattern' => $hourly_pattern,
                'daily_pattern' => $daily_pattern
            ]
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function exportResults() {
    global $pdo;
    
    $election_id = $_GET['election_id'] ?? null;
    $format = $_GET['format'] ?? 'csv';
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        error_log("DEBUG: getResults called with election_id: " . $election_id);
        
        // Get election info
        $stmt = $pdo->prepare("SELECT id, election_title as title, description, status FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();
        
        error_log("DEBUG: Election found: " . ($election ? 'YES' : 'NO'));
        
        if (!$election) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Election not found']);
            return;
        }
        
        // Get results data
        $stmt = $pdo->prepare("
            SELECT 
                p.position_title as position,
            u.full_name as candidate,
                COUNT(v.id) as votes,
                ROUND((COUNT(v.id) * 100.0 / NULLIF(total_votes.total, 0)), 2) as percentage
            FROM positions p
            INNER JOIN election_positions ep ON p.id = ep.position_id
            LEFT JOIN candidates c ON p.id = c.position_id AND c.is_approved = 1
        LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN votes v ON c.id = v.candidate_id
            CROSS JOIN (
                SELECT COUNT(*) as total
                FROM votes 
                WHERE election_id = ?
            ) total_votes
            WHERE ep.election_id = ? AND ep.is_active = 1
            GROUP BY p.id, p.position_title, c.id, u.full_name, total_votes.total
        ORDER BY p.position_title, votes DESC
        ");
        $stmt->execute([$election_id, $election_id]);
        $results = $stmt->fetchAll();
        
        if ($format === 'csv') {
            // Generate CSV
            $filename = 'election_results_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = '../../uploads/exports/' . $filename;
            
            // Create exports directory if it doesn't exist
            if (!file_exists('../../uploads/exports/')) {
                mkdir('../../uploads/exports/', 0755, true);
            }
            
            $file = fopen($filepath, 'w');
            
            // Write header
            fputcsv($file, ['Election', 'Position', 'Candidate', 'Votes', 'Percentage']);
            
            // Write data
            foreach ($results as $result) {
                fputcsv($file, [
                    $election['title'],
                    $result['position'],
                    $result['candidate'] ?: 'No candidates',
                    $result['votes'],
                    $result['percentage'] . '%'
                ]);
            }
            
            fclose($file);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'download_url' => '../uploads/exports/' . $filename
                ]
            ]);
        } else {
            // Return JSON data
            echo json_encode([
                'success' => true,
                'data' => [
                    'election' => $election['title'],
                    'results' => $results,
                    'exported_at' => date('Y-m-d H:i:s')
                ]
            ]);
        }
        
        // Log admin action
        logAdminAction('Export Results', 'Exported results for election: ' . $election['title']);
        
    } catch (Exception $e) {
        throw $e;
    }
}

function publishResults() {
    global $pdo;
    
    $election_id = $_POST['election_id'] ?? null;
    $publish = $_POST['publish'] ?? false;
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE elections 
            SET is_active = ? 
            WHERE id = ?
        ");
        $stmt->execute([$publish ? 1 : 0, $election_id]);
        
        if ($stmt->rowCount() > 0) {
            $action = $publish ? 'Published' : 'Unpublished';
            logAdminAction('Results ' . $action, 'Election ID: ' . $election_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Results ' . strtolower($action) . ' successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Election not found or no changes made'
            ]);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

function getWinnerAnalysis() {
    global $pdo;
    
    $election_id = $_GET['election_id'] ?? null;
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        // Get winners for each position
        $stmt = $pdo->prepare("
            SELECT 
                p.id as position_id,
                p.position_title as position_title,
                ep.max_candidates as max_winners,
                c.id as candidate_id,
                u.full_name,
                c.photo,
                COUNT(v.id) as vote_count,
                RANK() OVER (PARTITION BY p.id ORDER BY COUNT(v.id) DESC) as rank_position
            FROM positions p
            INNER JOIN election_positions ep ON p.id = ep.position_id
            JOIN candidates c ON p.id = c.position_id AND c.is_approved = 1
        LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN votes v ON c.id = v.candidate_id
            WHERE ep.election_id = ? AND ep.is_active = 1
            GROUP BY p.id, p.position_title, ep.max_candidates, c.id, u.full_name, c.photo
            HAVING rank_position <= ep.max_candidates
            ORDER BY p.position_title, vote_count DESC
        ");
        $stmt->execute([$election_id]);
        $winners = $stmt->fetchAll();
        
        // Group winners by position
        $grouped_winners = [];
        foreach ($winners as $winner) {
            $position_id = $winner['position_id'];
            if (!isset($grouped_winners[$position_id])) {
                $grouped_winners[$position_id] = [
                    'position_id' => $position_id,
                    'position_title' => $winner['position_title'],
                    'max_winners' => $winner['max_winners'],
                    'winners' => []
                ];
            }
            
            $grouped_winners[$position_id]['winners'][] = [
                'candidate_id' => $winner['candidate_id'],
                'full_name' => $winner['full_name'],
                'photo' => $winner['photo'],
                'vote_count' => $winner['vote_count'],
                'rank' => $winner['rank_position']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => array_values($grouped_winners)
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