<?php
define('SECURE_ACCESS', true);
session_start();

// Set timezone to London (GMT/BST)
// Timezone is set in constants.php to UTC (GMT+00:00)

require_once __DIR__ . '/../../config/constants.php';
// Skip session.php and security.php for API calls to avoid session timeout redirects
require_once __DIR__ . '/../../config/database.php';
// Set basic security headers manually
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Direct access allowed - no login check required

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    // Test database connection
    $pdo->query('SELECT 1');
    
    $action = $_GET['action'] ?? '';
    
    // Handle action parameter for dashboard compatibility
    if ($method === 'GET' && $action === 'get_elections') {
        handleGetElections($pdo);
        exit;
    }
    
    if ($method === 'GET' && $action === 'positions') {
        handleGetPositions($pdo);
        exit;
    }
    
    if ($method === 'POST' && $action === 'add_position') {
        handleAddPosition($pdo);
        exit;
    }
    
    if ($method === 'POST' && $action === 'remove_position') {
        handleRemovePosition($pdo);
        exit;
    }
    
    if ($method === 'POST' && $action === 'update_position') {
        handleUpdatePosition($pdo);
        exit;
    }
    
    switch ($method) {
        case 'GET':
            handleGetElections($pdo);
            break;
        case 'POST':
            handleCreateElection($pdo);
            break;
        case 'PUT':
            handleUpdateElection($pdo);
            break;
        case 'DELETE':
            handleDeleteElection($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    // Graceful error handling for database connection issues
    if ($method === 'GET') {
        // For GET requests, return empty data structure
        echo json_encode([
            'success' => true, 
            'data' => [], 
            'message' => 'Database temporarily unavailable. Please ensure MySQL is running.'
        ]);
    } else {
        // For other methods, return failure message
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Database temporarily unavailable. Please ensure MySQL is running.'
        ]);
    }
}

function handleGetElections($pdo) {
    try {
        $election_id = $_GET['id'] ?? null;
        
        if ($election_id) {
            // Get single election
            $stmt = $pdo->prepare("
                SELECT e.id, e.election_title as title, e.description, e.start_date, e.end_date, 
                       e.is_active, e.created_at, e.updated_at,
                       COUNT(DISTINCT esp.id) as position_count,
                       COUNT(DISTINCT c.id) as candidate_count,
                       COUNT(DISTINCT voters.id) as voter_count,
                       COUNT(DISTINCT v.id) as votes_cast
                FROM elections e
                LEFT JOIN election_specific_positions esp ON e.id = esp.election_id
                LEFT JOIN candidates c ON esp.id = c.election_specific_position_id AND c.is_approved = 1
                LEFT JOIN voters ON 1=1
                LEFT JOIN votes v ON e.id = v.election_id
                WHERE e.id = ?
                GROUP BY e.id, e.election_title, e.description, e.start_date, e.end_date, e.is_active, e.created_at, e.updated_at
            ");
            $stmt->execute([$election_id]);
            $election = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$election) {
                echo json_encode(['success' => false, 'message' => 'Election not found']);
                return;
            }
            
            $election['start_date_formatted'] = date('M j, Y g:i A', strtotime($election['start_date']));
            $election['end_date_formatted'] = date('M j, Y g:i A', strtotime($election['end_date']));
            $election['status'] = getElectionStatus($election);
            
            echo json_encode(['success' => true, 'data' => $election]);
        } else {
            // Get all elections
            $stmt = $pdo->query("
                SELECT e.id, e.election_title as title, e.description, e.start_date, e.end_date, 
                       e.is_active, e.created_at, e.updated_at,
                       COUNT(DISTINCT esp.id) as position_count,
                       COUNT(DISTINCT c.id) as candidate_count,
                       COUNT(DISTINCT voters.id) as voter_count,
                       COUNT(DISTINCT v.id) as votes_cast
                FROM elections e
                LEFT JOIN election_specific_positions esp ON e.id = esp.election_id
                LEFT JOIN candidates c ON esp.id = c.election_specific_position_id AND c.is_approved = 1
                LEFT JOIN voters ON 1=1
                LEFT JOIN votes v ON e.id = v.election_id
                GROUP BY e.id, e.election_title, e.description, e.start_date, e.end_date, e.is_active, e.created_at, e.updated_at
                ORDER BY e.created_at DESC
            ");
            
            $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format dates and status
            foreach ($elections as &$election) {
                $election['start_date_formatted'] = date('M j, Y g:i A', strtotime($election['start_date']));
                $election['end_date_formatted'] = date('M j, Y g:i A', strtotime($election['end_date']));
                $election['status'] = getElectionStatus($election);
            }
            
            echo json_encode(['success' => true, 'data' => $elections]);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

function handleGetPositions($pdo) {
    try {
        $election_id = $_GET['election_id'] ?? null;
        
        if ($election_id) {
            // Get positions assigned to specific election
            $stmt = $pdo->prepare("
                SELECT p.id, p.position_title as title, p.description, 
                       p.display_order, p.max_votes as max_candidates
                FROM positions p
                WHERE p.election_id = ?
                ORDER BY p.display_order, p.position_title
            ");
            $stmt->execute([$election_id]);
        } else {
            // Get all positions
            $stmt = $pdo->query("
                SELECT id, position_title as title, description, display_order
                FROM positions
                ORDER BY display_order, position_title
            ");
        }
        
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $positions]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleCreateElection($pdo) {
    try {
        // Check if admin is logged in
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['title', 'description', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Use title directly for database
        
        // Convert status to is_active boolean
        $is_active = false;
        if (isset($input['status'])) {
            // Only 'active' and 'upcoming' statuses should set is_active to true
            // 'inactive', 'ended', and 'completed' should set is_active to false
            $is_active = in_array($input['status'], ['active', 'upcoming']);
        } elseif (isset($input['is_active'])) {
            $is_active = (bool)$input['is_active'];
        }
        
        // Validate dates
        try {
            $start_date = new DateTime($input['start_date']);
            $end_date = new DateTime($input['end_date']);
            $now = new DateTime();
            
            if ($start_date >= $end_date) {
                echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
                return;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid date format: ' . $e->getMessage()]);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO elections (election_title, description, start_date, end_date, is_active, allow_live_results, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['title'],
            $input['description'],
            $input['start_date'],
            $input['end_date'],
            $is_active,
            isset($input['allow_live_results']) ? (bool)$input['allow_live_results'] : false,
            $_SESSION['admin_id']
        ]);
        
        $election_id = $pdo->lastInsertId();
        
        // Log the action
        if (isset($_SESSION['admin_id'])) {
            logAdminAction($_SESSION['admin_id'], 'CREATE', 'elections', $election_id, null, $input, $pdo);
        }
        
        echo json_encode(['success' => true, 'message' => 'Election created successfully', 'id' => $election_id]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdateElection($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $election_id = $_GET['id'] ?? null;
        
        if (empty($election_id)) {
            echo json_encode(['success' => false, 'message' => 'Election ID is required']);
            return;
        }
        
        // Get current election data for logging
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$old_data) {
            echo json_encode(['success' => false, 'message' => 'Election not found']);
            return;
        }
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        // Convert status to is_active boolean
        if (isset($input['status'])) {
            // Only 'active' and 'upcoming' statuses should set is_active to true
            // 'inactive', 'ended', and 'completed' should set is_active to false
            $input['is_active'] = in_array($input['status'], ['active', 'upcoming']);
            unset($input['status']); // Remove status field to avoid confusion
        }
        
        $allowed_fields = ['title', 'description', 'start_date', 'end_date', 'is_active', 'allow_live_results'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            return;
        }
        
        $params[] = $election_id;
        
        $stmt = $pdo->prepare("UPDATE elections SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);
        
        // Log the action
        if (isset($_SESSION['admin_id'])) {
            logAdminAction($_SESSION['admin_id'], 'UPDATE', 'elections', $election_id, $old_data, $input, $pdo);
        }
        
        echo json_encode(['success' => true, 'message' => 'Election updated successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleDeleteElection($pdo) {
    try {
        // Get election ID from URL parameter
        $election_id = $_GET['id'] ?? null;
        
        if (empty($election_id)) {
            echo json_encode(['success' => false, 'message' => 'Election ID is required']);
            return;
        }
        
        // Get election data for logging
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$election_data) {
            echo json_encode(['success' => false, 'message' => 'Election not found']);
            return;
        }
        
        // Check if election has votes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ?");
        $stmt->execute([$election_id]);
        $vote_count = $stmt->fetchColumn();
        
        if ($vote_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete election with existing votes']);
            return;
        }
        
        // Delete election (cascade will handle related records)
        $stmt = $pdo->prepare("DELETE FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        
        // Log the action
        if (isset($_SESSION['admin_id'])) {
            logAdminAction($_SESSION['admin_id'], 'DELETE', 'elections', $election_id, $election_data, null, $pdo);
        }
        
        echo json_encode(['success' => true, 'message' => 'Election deleted successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function getElectionStatus($election) {
    $now = new DateTime();
    $start = new DateTime($election['start_date']);
    $end = new DateTime($election['end_date']);
    
    if (!$election['is_active']) {
        return 'inactive';
    } elseif ($now < $start) {
        return 'upcoming';
    } elseif ($now >= $start && $now <= $end) {
        return 'active';
    } else {
        return 'ended';
    }
}

function handleAddPosition($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $election_id = $input['election_id'] ?? null;
        $position_id = $input['position_id'] ?? null;
        $display_order = $input['display_order'] ?? 0;
        $max_candidates = $input['max_candidates'] ?? 1;
        
        if (!$election_id || !$position_id) {
            echo json_encode(['success' => false, 'message' => 'Election ID and Position ID are required']);
            return;
        }
        
        // Check if position is already assigned to this election
        $stmt = $pdo->prepare("SELECT id FROM election_positions WHERE election_id = ? AND position_id = ?");
        $stmt->execute([$election_id, $position_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Position already assigned to this election']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO election_positions (election_id, position_id, display_order, max_candidates, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([$election_id, $position_id, $display_order, $max_candidates]);
        
        echo json_encode(['success' => true, 'message' => 'Position added to election successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleRemovePosition($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $election_id = $input['election_id'] ?? null;
        $position_id = $input['position_id'] ?? null;
        
        if (!$election_id || !$position_id) {
            echo json_encode(['success' => false, 'message' => 'Election ID and Position ID are required']);
            return;
        }
        
        // Check if there are candidates for this position in this election
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM candidates c
            INNER JOIN positions p ON c.position_id = p.id
            INNER JOIN election_positions ep ON p.id = ep.position_id
            WHERE ep.election_id = ? AND ep.position_id = ?
        ");
        $stmt->execute([$election_id, $position_id]);
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot remove position with existing candidates']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM election_positions WHERE election_id = ? AND position_id = ?");
        $stmt->execute([$election_id, $position_id]);
        
        echo json_encode(['success' => true, 'message' => 'Position removed from election successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdatePosition($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $election_id = $input['election_id'] ?? null;
        $position_id = $input['position_id'] ?? null;
        
        if (!$election_id || !$position_id) {
            echo json_encode(['success' => false, 'message' => 'Election ID and Position ID are required']);
            return;
        }
        
        $updates = [];
        $params = [];
        
        $allowed_fields = ['display_order', 'max_candidates', 'is_active'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            return;
        }
        
        $params[] = $election_id;
        $params[] = $position_id;
        
        $stmt = $pdo->prepare("UPDATE election_positions SET " . implode(', ', $updates) . " WHERE election_id = ? AND position_id = ?");
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Position updated successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function logAdminAction($admin_id, $action, $table, $record_id, $old_values, $new_values, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, old_values, new_values, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'admin',
            $admin_id,
            $action,
            $table,
            $record_id,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        if (!$result) {
            error_log("Failed to execute audit log insert: " . print_r($stmt->errorInfo(), true));
        }
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}
?>