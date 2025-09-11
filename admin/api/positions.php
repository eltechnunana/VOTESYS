<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
    exit;
}

$pdo = getDBConnection();
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? null;
    }
    
    switch ($action) {
        case 'get_positions':
            handleGetPositions($pdo);
            break;
        case 'create_position':
            handleCreatePosition($pdo);
            break;
        case 'update_position':
            handleUpdatePosition($pdo);
            break;
        case 'delete_position':
            handleDeletePosition($pdo);
            break;
        case 'get_position_elections':
            handleGetPositionElections($pdo);
            break;
        case 'remove_position_from_election':
            handleRemovePositionFromElection($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log('Positions API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetPositions($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, position_title as title, description, display_order
            FROM positions
            ORDER BY display_order, position_title
        ");
        
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $positions]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleCreatePosition($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $position_title = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $display_order = $input['display_order'] ?? 1;
        
        if (empty($position_title)) {
            echo json_encode(['success' => false, 'message' => 'Position title is required']);
            return;
        }
        
        // Check if position title already exists
        $stmt = $pdo->prepare("SELECT id FROM positions WHERE position_title = ?");
        $stmt->execute([$position_title]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Position title already exists']);
            return;
        }
        
        // Insert new position
        $stmt = $pdo->prepare("
            INSERT INTO positions (position_title, description, display_order)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$position_title, $description, $display_order]);
        
        $position_id = $pdo->lastInsertId();
        
        // Log the action
        logAuditAction($pdo, $_SESSION['admin_id'], 'CREATE', 'positions', $position_id, 
                      "Created position: {$position_title}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Position created successfully',
            'position_id' => $position_id
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdatePosition($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        $position_title = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $display_order = $input['display_order'] ?? 1;
        
        if (!$id || empty($position_title)) {
            echo json_encode(['success' => false, 'message' => 'Position ID and title are required']);
            return;
        }
        
        // Check if position exists
        $stmt = $pdo->prepare("SELECT position_title FROM positions WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Position not found']);
            return;
        }
        
        // Check if new title conflicts with another position
        $stmt = $pdo->prepare("SELECT id FROM positions WHERE position_title = ? AND id != ?");
        $stmt->execute([$position_title, $id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Position title already exists']);
            return;
        }
        
        // Update position
        $stmt = $pdo->prepare("
            UPDATE positions 
            SET position_title = ?, description = ?, display_order = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$position_title, $description, $display_order, $id]);
        
        // Log the action
        logAuditAction($pdo, $_SESSION['admin_id'], 'UPDATE', 'positions', $id, 
                      "Updated position from '{$existing['position_title']}' to '{$position_title}'");
        
        echo json_encode(['success' => true, 'message' => 'Position updated successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleGetPositionElections($pdo) {
    try {
        $position_id = $_GET['position_id'] ?? null;
        
        if (!$position_id) {
            echo json_encode(['success' => false, 'message' => 'Position ID is required']);
            return;
        }
        
        // Get elections that this position is assigned to
        $stmt = $pdo->prepare("
            SELECT e.id, e.election_title, e.description, e.start_date, e.end_date, e.is_active
            FROM elections e
            INNER JOIN election_positions ep ON e.id = ep.election_id
            WHERE ep.position_id = ?
            ORDER BY e.election_title
        ");
        $stmt->execute([$position_id]);
        $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'elections' => $elections]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleRemovePositionFromElection($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $position_id = $input['position_id'] ?? null;
        $election_id = $input['election_id'] ?? null;
        
        if (!$position_id || !$election_id) {
            echo json_encode(['success' => false, 'message' => 'Position ID and Election ID are required']);
            return;
        }
        
        // Check if there are candidates for this position in this election
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM candidates c
            INNER JOIN positions p ON c.position_id = p.id
            WHERE p.id = ? AND p.election_id = ?
        ");
        $stmt->execute([$position_id, $election_id]);
        $candidate_count = $stmt->fetchColumn();
        
        if ($candidate_count > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot remove position from election. Remove candidates first.'
            ]);
            return;
        }
        
        // Remove position from election
        $stmt = $pdo->prepare("DELETE FROM election_positions WHERE position_id = ? AND election_id = ?");
        $stmt->execute([$position_id, $election_id]);
        
        // Log the action
        logAuditAction($pdo, $_SESSION['admin_id'], 'DELETE', 'election_positions', null, 
                      "Removed position {$position_id} from election {$election_id}");
        
        echo json_encode(['success' => true, 'message' => 'Position removed from election successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleDeletePosition($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Position ID is required']);
            return;
        }
        
        // Check if position exists
        $stmt = $pdo->prepare("SELECT position_title FROM positions WHERE id = ?");
        $stmt->execute([$id]);
        $position = $stmt->fetch();
        
        if (!$position) {
            echo json_encode(['success' => false, 'message' => 'Position not found']);
            return;
        }
        
        // Check if position is used in any elections
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM election_positions WHERE position_id = ?");
        $stmt->execute([$id]);
        $election_count = $stmt->fetchColumn();
        
        if ($election_count > 0) {
            // Get the elections this position is assigned to
            $stmt = $pdo->prepare("
                SELECT e.election_title
                FROM elections e
                INNER JOIN election_positions ep ON e.id = ep.election_id
                WHERE ep.position_id = ?
                ORDER BY e.election_title
            ");
            $stmt->execute([$id]);
            $elections = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $election_list = implode(', ', $elections);
            echo json_encode([
                'success' => false, 
                'message' => "Cannot delete position that is assigned to elections: {$election_list}. Remove from elections first.",
                'assigned_elections' => $elections
            ]);
            return;
        }
        
        // Check if position has candidates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE position_id = ?");
        $stmt->execute([$id]);
        $candidate_count = $stmt->fetchColumn();
        
        if ($candidate_count > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot delete position that has candidates. Remove candidates first.'
            ]);
            return;
        }
        
        // Delete position
        $stmt = $pdo->prepare("DELETE FROM positions WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log the action
        logAuditAction($pdo, $_SESSION['admin_id'], 'DELETE', 'positions', $id, 
                      "Deleted position: {$position['position_title']}");
        
        echo json_encode(['success' => true, 'message' => 'Position deleted successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function logAuditAction($pdo, $admin_id, $action, $table_name, $record_id, $details) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (admin_id, action, table_name, record_id, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->execute([$admin_id, $action, $table_name, $record_id, $details, $ip_address, $user_agent]);
    } catch (Exception $e) {
        // Log audit errors but don't fail the main operation
        error_log('Audit log error: ' . $e->getMessage());
    }
}
?>