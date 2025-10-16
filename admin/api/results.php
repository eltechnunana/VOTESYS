<?php
define('SECURE_ACCESS', true);

// Determine the correct path to config files
$config_path = '';
if (file_exists('../../config/database.php')) {
    $config_path = '../../';
} elseif (file_exists('./config/database.php')) {
    $config_path = './';
} else {
    $config_path = dirname(dirname(__DIR__)) . '/';
}

require_once $config_path . 'config/database.php';
require_once $config_path . 'config/session.php';

// Establish PDO connection
$pdo = getDBConnection();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Handle JSON input
$input = [];
if ($method === 'POST') {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $input = json_decode($raw_input, true) ?? [];
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

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
        case 'get_voter_counts':
            getVoterCounts();
            break;
        case 'send_results_email':
            sendResultsEmail();
            break;
        case 'toggle_publish':
            togglePublishResults();
            break;
        case 'get_recipient_counts':
            getRecipientCounts();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
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
            LEFT JOIN election_specific_positions esp ON e.id = esp.election_id
            LEFT JOIN candidates c ON esp.id = c.election_specific_position_id AND c.is_approved = 1
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
        
        // Get results by position - using election_specific_positions
        $stmt = $pdo->prepare("
            SELECT 
                esp.id as position_id,
                esp.position_title as position_title,
                esp.max_candidates as max_winners
            FROM election_specific_positions esp
            WHERE esp.election_id = ? AND esp.is_active = 1
            ORDER BY esp.display_order, esp.position_title
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
                WHERE c.election_specific_position_id = ? AND c.is_approved = 1
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
                
                // Handle photo data - encode as base64 if it exists, otherwise use placeholder
                $photo_data = null;
                if (!empty($candidate['photo'])) {
                    // Check if it's valid UTF-8, if not, base64 encode it
                    if (mb_check_encoding($candidate['photo'], 'UTF-8')) {
                        $photo_data = $candidate['photo'];
                    } else {
                        $photo_data = 'data:image/jpeg;base64,' . base64_encode($candidate['photo']);
                    }
                }
                
                $position_data['candidates'][] = [
                    'candidate_id' => $candidate['candidate_id'],
                    'full_name' => $candidate['full_name'],
                    'photo' => $photo_data,
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
        $stmt = $pdo->prepare("SELECT id, election_title as title, description FROM elections WHERE id = ?");
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
                esp.position_title as position,
                c.full_name as candidate,
                COUNT(v.id) as votes,
                ROUND((COUNT(v.id) * 100.0 / NULLIF(total_votes.total, 0)), 2) as percentage
            FROM election_specific_positions esp
            LEFT JOIN candidates c ON esp.id = c.election_specific_position_id AND c.is_approved = 1
            LEFT JOIN votes v ON c.id = v.candidate_id
            CROSS JOIN (
                SELECT COUNT(*) as total
                FROM votes 
                WHERE election_id = ?
            ) total_votes
            WHERE esp.election_id = ?
            GROUP BY esp.id, esp.position_title, c.id, c.full_name, total_votes.total
            ORDER BY esp.display_order, votes DESC
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
                    $result['position'] ?? 'Unknown Position',
                    $result['candidate'] ?: 'No candidates',
                    $result['votes'] ?? 0,
                    ($result['percentage'] ?? 0) . '%'
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
        // First check if election exists
        $stmt = $pdo->prepare("SELECT id, is_active FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();
        
        if (!$election) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Election not found']);
            return;
        }
        
        // Check if the election is already in the desired state
        $current_status = (bool)$election['is_active'];
        $desired_status = (bool)$publish;
        
        if ($current_status === $desired_status) {
            $action = $publish ? 'Published' : 'Unpublished';
            echo json_encode([
                'success' => true,
                'message' => 'Results already ' . strtolower($action)
            ]);
            return;
        }
        
        // Update the election status
        $stmt = $pdo->prepare("
            UPDATE elections 
            SET is_active = ? 
            WHERE id = ?
        ");
        $stmt->execute([$publish ? 1 : 0, $election_id]);
        
        $action = $publish ? 'Published' : 'Unpublished';
        logAdminAction('Results ' . $action, 'Election ID: ' . $election_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Results ' . strtolower($action) . ' successfully'
        ]);
        
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
                c.full_name,
                c.photo,
                COUNT(v.id) as vote_count,
                RANK() OVER (PARTITION BY p.id ORDER BY COUNT(v.id) DESC) as rank_position
            FROM positions p
            INNER JOIN election_positions ep ON p.id = ep.position_id
            JOIN candidates c ON p.id = c.position_id AND c.is_active = 1
            LEFT JOIN votes v ON c.id = v.candidate_id
            WHERE ep.election_id = ? AND ep.is_active = 1
            GROUP BY p.id, p.position_title, ep.max_candidates, c.id, c.full_name, c.photo
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

function getVoterCounts() {
    global $pdo;
    
    $election_id = $_GET['election_id'] ?? null;
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        // Get total registered voters
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM voters WHERE is_active = 1");
        $stmt->execute();
        $total_voters = $stmt->fetchColumn();
        
        // Get participants (voters who voted in this election)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT voter_id) as participants FROM votes WHERE election_id = ?");
        $stmt->execute([$election_id]);
        $participants = $stmt->fetchColumn();
        
        // Get candidates count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as candidates 
            FROM candidates c 
            JOIN election_specific_positions esp ON c.election_specific_position_id = esp.id 
            WHERE esp.election_id = ? AND c.is_approved = 1
        ");
        $stmt->execute([$election_id]);
        $candidates = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_voters' => $total_voters,
                'participants' => $participants,
                'candidates' => $candidates
            ]
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

function sendResultsEmail() {
    global $pdo, $config_path, $input;
    
    $election_id = $_POST['election_id'] ?? $input['election_id'] ?? null;
    $recipients = $_POST['recipients'] ?? $input['recipients'] ?? [];
    $subject = $_POST['subject'] ?? $input['subject'] ?? 'Election Results';
    $message = $_POST['message'] ?? $input['message'] ?? '';
    $include_charts = $_POST['include_charts'] ?? $input['include_charts'] ?? false;
    $include_statistics = $_POST['include_statistics'] ?? $input['include_statistics'] ?? false;
    $attach_pdf = $_POST['attach_pdf'] ?? $input['attach_pdf'] ?? false;
    
    if (!$election_id || empty($recipients)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID and recipients are required']);
        return;
    }
    
    try {
        // Get election details
        $stmt = $pdo->prepare("SELECT election_title, description FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();
        
        if (!$election) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Election not found']);
            return;
        }
        
        // Include email utility
        require_once $config_path . 'config/email.php';
        $emailUtil = new EmailUtility();
        
        $sent_count = 0;
        $email_list = [];
        
        // Build recipient list based on selected groups
        foreach ($recipients as $group) {
            switch ($group) {
                case 'all_voters':
                    $stmt = $pdo->prepare("SELECT email, CONCAT(first_name, ' ', last_name) as full_name FROM voters WHERE is_active = 1");
                    $stmt->execute();
                    $voters = $stmt->fetchAll();
                    foreach ($voters as $voter) {
                        $email_list[] = ['email' => $voter['email'], 'name' => $voter['full_name']];
                    }
                    break;
                    
                case 'participants':
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT v.email, CONCAT(v.first_name, ' ', v.last_name) as full_name 
                        FROM voters v 
                        JOIN votes vt ON v.id = vt.voter_id 
                        WHERE vt.election_id = ?
                    ");
                    $stmt->execute([$election_id]);
                    $participants = $stmt->fetchAll();
                    foreach ($participants as $participant) {
                        $email_list[] = ['email' => $participant['email'], 'name' => $participant['full_name']];
                    }
                    break;
                    
                case 'candidates':
                    // Note: Candidates table doesn't have email field, skipping candidates for email notifications
                    // This would require additional voter_id field in candidates table to link to voters
                    break;
                    
                case 'admins':
                    $stmt = $pdo->prepare("SELECT email, username as full_name FROM admin WHERE is_active = 1");
                    $stmt->execute();
                    $admins = $stmt->fetchAll();
                    foreach ($admins as $admin) {
                        $email_list[] = ['email' => $admin['email'], 'name' => $admin['full_name']];
                    }
                    break;
            }
        }
        
        // Remove duplicates
        $unique_emails = [];
        foreach ($email_list as $recipient) {
            $unique_emails[$recipient['email']] = $recipient;
        }
        $email_list = array_values($unique_emails);
        
        // Prepare email content
        $email_subject = $subject ?: "Results for {$election['election_title']}";
        $email_body = generateResultsEmailBody($election, $message, $include_statistics, $election_id);
        
        // Send emails
        foreach ($email_list as $recipient) {
            try {
                $personalizedHtmlBody = str_replace('[RECIPIENT_NAME]', $recipient['name'], $email_body);
                
                // Create plain text version for better compatibility
                $plainTextBody = "Dear " . $recipient['name'] . ",\n\n";
                $plainTextBody .= "The results for the election '" . $election['election_title'] . "' are now available.\n\n";
                
                // Add public results link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $public_results_url = $protocol . '://' . $host . '/election_results.php?id=' . $election_id;
                $plainTextBody .= "VIEW COMPLETE RESULTS ONLINE:\n";
                $plainTextBody .= $public_results_url . "\n\n";
                $plainTextBody .= "Click the link above to view the full interactive results with candidate photos and detailed statistics.\n\n";
                
                if ($message) {
                    $plainTextBody .= $message . "\n\n";
                }
                
                if ($include_statistics) {
                    // Get basic statistics for plain text
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total_votes FROM votes WHERE election_id = ?");
                    $stmt->execute([$election_id]);
                    $total_votes = $stmt->fetchColumn();
                    
                    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT voter_id) as unique_voters FROM votes WHERE election_id = ?");
                    $stmt->execute([$election_id]);
                    $unique_voters = $stmt->fetchColumn();
                    
                    $plainTextBody .= "Election Statistics:\n";
                    $plainTextBody .= "- Total Votes Cast: {$total_votes}\n";
                    $plainTextBody .= "- Unique Voters: {$unique_voters}\n\n";
                }
                
                // Add candidate results in plain text
                $stmt = $pdo->prepare("
                    SELECT 
                        c.full_name,
                        COALESCE(v.vote_count, 0) as votes,
                        CASE 
                            WHEN (SELECT COUNT(*) FROM votes WHERE election_id = ?) > 0 
                            THEN ROUND((COALESCE(v.vote_count, 0) * 100.0 / (SELECT COUNT(*) FROM votes WHERE election_id = ?)), 2)
                            ELSE 0 
                        END as percentage
                    FROM candidates c
                    LEFT JOIN (
                        SELECT candidate_id, COUNT(*) as vote_count
                        FROM votes 
                        WHERE election_id = ?
                        GROUP BY candidate_id
                    ) v ON c.id = v.candidate_id
                    WHERE c.election_id = ?
                    ORDER BY votes DESC, c.full_name ASC
                ");
                $stmt->execute([$election_id, $election_id, $election_id, $election_id]);
                $candidates = $stmt->fetchAll();
                
                if (!empty($candidates)) {
                    $plainTextBody .= "Election Results:\n";
                    $plainTextBody .= str_repeat("-", 50) . "\n";
                    
                    $is_first = true;
                    foreach ($candidates as $candidate) {
                        $winner_mark = $is_first ? " (WINNER)" : "";
                        $plainTextBody .= $candidate['full_name'] . $winner_mark . "\n";
                        $plainTextBody .= "  Votes: " . $candidate['votes'] . " (" . $candidate['percentage'] . "%)\n\n";
                        $is_first = false;
                    }
                }
                
                // Grouped results by position (plain text)
                $plainTextBody .= "Results by Position:\n";
                $plainTextBody .= str_repeat("-", 50) . "\n";
                $posStmt = $pdo->prepare("\n                    SELECT id, position_title\n                    FROM election_specific_positions\n                    WHERE election_id = ? AND is_active = 1\n                    ORDER BY display_order, position_title\n                ");
                $posStmt->execute([$election_id]);
                $positions = $posStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($positions as $position) {
                    $candStmt = $pdo->prepare("\n                        SELECT c.full_name, COUNT(v.id) AS votes\n                        FROM candidates c\n                        LEFT JOIN votes v ON v.candidate_id = c.id\n                        WHERE c.election_specific_position_id = ?\n                        GROUP BY c.id, c.full_name\n                        ORDER BY votes DESC, c.full_name ASC\n                    ");
                    $candStmt->execute([$position['id']]);
                    $cands = $candStmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($cands)) {
                        $plainTextBody .= strtoupper($position['position_title']) . ":\n";
                        $plainTextBody .= str_repeat("-", 30) . "\n";
                        $totalPositionVotes = 0; foreach ($cands as $cd) { $totalPositionVotes += (int)$cd['votes']; }
                        $isFirst = true;
                        foreach ($cands as $cd) {
                            $pct = $totalPositionVotes > 0 ? round(($cd['votes'] * 100.0 / $totalPositionVotes), 2) : 0;
                            $winnerMark = $isFirst ? " (WINNER)" : "";
                            $plainTextBody .= " - " . $cd['full_name'] . $winnerMark . "\n";
                            $plainTextBody .= "   Votes: " . $cd['votes'] . " (" . $pct . "%)\n";
                            $isFirst = false;
                        }
                        $plainTextBody .= "\n";
                    }
                }

                $plainTextBody .= "Thank you for your participation in the democratic process!\n\n";
                $plainTextBody .= "Best regards,\n";
                $plainTextBody .= "Election Administration Team";
                
                if ($emailUtil->send(
                    $recipient['email'],
                    $email_subject,
                    $personalizedHtmlBody,
                    $plainTextBody // Plain text alternative
                )) {
                    $sent_count++;
                }
            } catch (Exception $e) {
                error_log("Failed to send email to {$recipient['email']}: " . $e->getMessage());
            }
        }
        
        // Log the action
        logAdminAction('Send Results Email', "Sent to {$sent_count} recipients for election: {$election['election_title']}");
        
        echo json_encode([
            'success' => true,
            'data' => [
                'sent_count' => $sent_count,
                'total_recipients' => count($email_list)
            ],
            'message' => "Successfully sent emails to {$sent_count} recipients"
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

function generateResultsEmailBody($election, $custom_message, $include_statistics, $election_id) {
    global $pdo;
    
    // Get the base URL for the public results page
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Detect application base path (handles subdirectory installs like /VOTESYS)
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $appBasePath = rtrim(preg_replace('#/admin/api/.*$#', '', $scriptName), '/');
    // Ensure empty base resolves to root
    if ($appBasePath === '') { $appBasePath = ''; }
    $public_results_url = $protocol . '://' . $host . $appBasePath . '/election_results.php?id=' . $election_id;
    
    // Start building HTML email content
    $html_body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: white; border: 1px solid #ddd; border-radius: 8px;">
        <div style="background: linear-gradient(135deg, #4a6b8a 0%, #6b8db5 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0;">
            <h2 style="margin: 0; font-size: 24px;">Election Results - ' . htmlspecialchars($election['election_title']) . '</h2>
        </div>
        <div style="padding: 20px;">
            <p>Dear [RECIPIENT_NAME],</p>
            <p>The results for the election <strong>' . htmlspecialchars($election['election_title']) . '</strong> are now available.</p>
            
            <!-- Public Results Link -->
            <div style="margin: 20px 0; text-align: center;">
                <a href="' . $public_results_url . '" 
                   style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
                          color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; 
                          font-weight: bold; font-size: 16px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);">
                    🗳️ View Complete Results Online
                </a>
                <p style="margin-top: 10px; font-size: 14px; color: #666;">
                    Click the button above to view the full interactive results with candidate photos and detailed statistics.
                </p>
            </div>';
    
    if ($custom_message) {
        $html_body .= '<div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 6px; white-space: pre-line;">' . 
                      htmlspecialchars($custom_message) . '</div>';
    }
    
    if ($include_statistics) {
        // Get comprehensive statistics
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_votes FROM votes WHERE election_id = ?");
        $stmt->execute([$election_id]);
        $total_votes = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT voter_id) as unique_voters FROM votes WHERE election_id = ?");
        $stmt->execute([$election_id]);
        $unique_voters = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_candidates 
            FROM candidates c
            JOIN election_specific_positions esp ON c.election_specific_position_id = esp.id
            WHERE esp.election_id = ?
        ");
        $stmt->execute([$election_id]);
        $total_candidates = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_voters FROM voters WHERE is_active = 1");
        $stmt->execute();
        $total_registered = $stmt->fetchColumn();
        
        $turnout_rate = $total_registered > 0 ? round(($unique_voters / $total_registered) * 100, 2) : 0;
        
        $html_body .= '
            <div style="margin: 20px 0;">
                <h4 style="color: #4a6b8a; border-bottom: 2px solid #4a6b8a; padding-bottom: 5px;">Election Statistics</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                    <div style="text-align: center; padding: 10px; background: #e8f4fd; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #4a6b8a;">' . $total_votes . '</div>
                        <div style="font-size: 14px; color: #666;">Total Votes</div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #e8f4fd; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #4a6b8a;">' . $unique_voters . '</div>
                        <div style="font-size: 14px; color: #666;">Unique Voters</div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #e8f4fd; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #4a6b8a;">' . $turnout_rate . '%</div>
                        <div style="font-size: 14px; color: #666;">Turnout Rate</div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #e8f4fd; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: #4a6b8a;">' . $total_candidates . '</div>
                        <div style="font-size: 14px; color: #666;">Candidates</div>
                    </div>
                </div>
            </div>';
    }

    // Results by Position (card-style section)
    $html_body .= '        <div style="margin: 20px 0;">'
                . '            <h4 style="color: #4a6b8a; border-bottom: 2px solid #4a6b8a; padding-bottom: 5px;">Results by Position</h4>';

    $posStmt = $pdo->prepare("\n        SELECT id, position_title, display_order\n        FROM election_specific_positions\n        WHERE election_id = ? AND is_active = 1\n        ORDER BY display_order, position_title\n    ");
    $posStmt->execute([$election_id]);
    $positions = $posStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($positions as $position) {
        $candStmt = $pdo->prepare("\n            SELECT \n                c.id, c.full_name, c.photo,\n                COALESCE(vc.vote_count, 0) AS vote_count,\n                COALESCE(vc.total_votes, 0) AS total_votes\n            FROM candidates c\n            LEFT JOIN (\n                SELECT \n                    candidate_id, COUNT(*) AS vote_count,\n                    (SELECT COUNT(*) FROM votes v2\n                     JOIN candidates c2 ON v2.candidate_id = c2.id\n                     WHERE c2.election_specific_position_id = ?) AS total_votes\n                FROM votes v\n                JOIN candidates ci ON v.candidate_id = ci.id\n                WHERE ci.election_specific_position_id = ?\n                GROUP BY candidate_id\n            ) vc ON c.id = vc.candidate_id\n            WHERE c.election_specific_position_id = ? AND c.is_approved = 1\n            ORDER BY vc.vote_count DESC, c.full_name ASC\n        ");
        $candStmt->execute([$position['id'], $position['id'], $position['id']]);
        $cands = $candStmt->fetchAll(PDO::FETCH_ASSOC);

        $maxVotes = 0;
        foreach ($cands as $c) { if ($c['vote_count'] > $maxVotes) $maxVotes = $c['vote_count']; }

        $html_body .= '            <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:15px; margin-bottom:15px;">'
                    . '                <h5 style="margin:0 0 10px; color:#334155;">' . htmlspecialchars($position['position_title']) . '</h5>';

        $idx = 0;
        foreach ($cands as $cand) {
            $percentage = ($cand['total_votes'] > 0) ? round(($cand['vote_count'] * 100.0 / $cand['total_votes']), 2) : 0;
            $relative = ($maxVotes > 0) ? round(($cand['vote_count'] * 100.0 / $maxVotes), 2) : 0;
            $badge = ($idx === 0) ? '<span style="display:inline-block; background:#22c55e; color:#fff; padding:2px 8px; border-radius:12px; font-size:12px; margin-left:8px;">🏆 Winner</span>' : '';
            $photo = '';
            if (!empty($cand['photo'])) { $photo = 'data:image/jpeg;base64,' . base64_encode($cand['photo']); }

            $html_body .= '                <div style="margin:10px 0;">'
                        . '                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">'
                        . '                        <div style="display:flex; align-items:center; gap:8px;">'
                        .                              (!empty($photo) ? '<img src="' . $photo . '" alt="' . htmlspecialchars($cand['full_name']) . '" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">' : '')
                        .                              '<div style="font-weight:600;color:#334155;">' . htmlspecialchars($cand['full_name']) . '</div>'
                        . '                        </div>'
                        . '                        <div style="text-align:right;">'
                        . '                            <div style="font-weight:700;color:#0ea5e9;">' . $cand['vote_count'] . ' votes ' . $badge . '</div>'
                        . '                            <div style="font-size:12px;color:#64748b;">' . $percentage . '%</div>'
                        . '                        </div>'
                        . '                    </div>'
                        . '                    <div style="background:#e5e7eb;height:8px;border-radius:6px;overflow:hidden;">'
                        . '                        <div style="height:8px;width:' . $relative . '%;background:' . ($idx===0 ? '#22c55e' : '#3b82f6') . ';"></div>'
                        . '                    </div>'
                        . '                </div>';
            $idx++;
        }

        $html_body .= '            </div>';
    }

    $html_body .= '        </div>';

    // Get detailed election results
    $stmt = $pdo->prepare("
        SELECT 
            c.full_name,
            COALESCE(v.vote_count, 0) as votes,
            CASE 
                WHEN (SELECT COUNT(*) FROM votes WHERE election_id = ?) > 0 
                THEN ROUND((COALESCE(v.vote_count, 0) * 100.0 / (SELECT COUNT(*) FROM votes WHERE election_id = ?)), 2)
                ELSE 0 
            END as percentage
        FROM candidates c
        LEFT JOIN (
            SELECT candidate_id, COUNT(*) as vote_count
            FROM votes 
            WHERE election_id = ?
            GROUP BY candidate_id
        ) v ON c.id = v.candidate_id
        WHERE c.election_id = ?
        ORDER BY votes DESC, c.full_name ASC
    ");
    $stmt->execute([$election_id, $election_id, $election_id, $election_id]);
    $candidates = $stmt->fetchAll();
    
    if (!empty($candidates)) {
        $html_body .= '
            <div style="margin: 20px 0;">
                <h4 style="color: #4a6b8a; border-bottom: 2px solid #4a6b8a; padding-bottom: 5px;">Election Results</h4>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #4a6b8a; color: white;">
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Candidate</th>
                            <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Votes</th>
                            <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $is_first = true;
        foreach ($candidates as $candidate) {
            $row_style = $is_first ? 'background: #d4edda; font-weight: bold;' : 'background: white;';
            $winner_icon = $is_first ? '🏆 ' : '';
            
            $html_body .= '
                        <tr style="' . $row_style . '">
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                ' . $winner_icon . htmlspecialchars($candidate['full_name']) . '
                            </td>
                            <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">' . $candidate['votes'] . '</td>
                            <td style="padding: 10px; text-align: center; border: 1px solid #ddd;">' . $candidate['percentage'] . '%</td>
                        </tr>';
            $is_first = false;
        }
        
        $html_body .= '
                    </tbody>
                </table>
            </div>';
    }
    
    $html_body .= '
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 14px;">
                <p>This is an automated message from the Election Management System.</p>
                <p>Thank you for your participation in the democratic process!</p>
                
                <!-- Alternative text link for email clients that don\'t support buttons -->
                <div style="margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                    <p style="margin: 5px 0; font-size: 13px; color: #495057;">
                        <strong>View Full Results:</strong><br>
                        <a href="' . $public_results_url . '" style="color: #007bff; text-decoration: underline;">
                            ' . $public_results_url . '
                        </a>
                    </p>
                </div>
                
                <p><strong>Best regards,<br>Election Administration Team</strong></p>
            </div>
        </div>
    </div>';
    
    return $html_body;
}

function togglePublishResults() {
    global $pdo;
    
    $election_id = $_POST['election_id'] ?? null;
    $publish = filter_var($_POST['publish'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    if (!$election_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        // Check if election exists
        $stmt = $pdo->prepare("SELECT id, election_title, results_published FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();
        
        if (!$election) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Election not found']);
            return;
        }
        
        // Update publication status and timestamp
        if ($publish) {
            // Set published_at to current timestamp when publishing
            $stmt = $pdo->prepare("UPDATE elections SET results_published = 1, published_at = NOW() WHERE id = ?");
            $stmt->execute([$election_id]);
        } else {
            // Clear published_at when unpublishing
            $stmt = $pdo->prepare("UPDATE elections SET results_published = 0, published_at = NULL WHERE id = ?");
            $stmt->execute([$election_id]);
        }
        
        $action = $publish ? 'Published' : 'Unpublished';
        logAdminAction("Results {$action}", "Election: {$election['election_title']}");
        
        echo json_encode([
            'success' => true,
            'message' => "Results {$action} successfully",
            'data' => [
                'published' => $publish
            ]
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

// Log admin action
function logAdminAction($action, $details = '') {
    global $pdo;
    
    try {
        // Skip logging if no admin session (for API testing)
        if (!isset($_SESSION['admin_id'])) {
            error_log('Skipping audit log - no admin session');
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'admin',
            $_SESSION['admin_id'],
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't stop execution
        error_log('Failed to log admin action: ' . $e->getMessage());
    }
}

function getRecipientCounts() {
    global $pdo;
    
    $election_id = $_POST['election_id'] ?? $_GET['election_id'] ?? null;
    
    if (!$election_id) {
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    try {
        // Get total registered voters
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM voters WHERE is_active = 1");
        $stmt->execute();
        $totalVoters = $stmt->fetch()['count'];
        
        // Get participants (voters who voted in this election)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT voter_id) as count 
            FROM votes 
            WHERE election_id = ?
        ");
        $stmt->execute([$election_id]);
        $participants = $stmt->fetch()['count'];
        
        // Get candidates for this election
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id) as count
            FROM candidates c
            JOIN positions p ON c.position_id = p.id
            JOIN election_positions ep ON p.id = ep.position_id
            WHERE ep.election_id = ? AND c.is_approved = 1
        ");
        $stmt->execute([$election_id]);
        $candidates = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'voters' => $totalVoters,
                'participants' => $participants,
                'candidates' => $candidates
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>