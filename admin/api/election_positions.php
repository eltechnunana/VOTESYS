<?php
define('SECURE_ACCESS', true);
require_once '../../config/database.php';
require_once '../../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set content type
header('Content-Type: application/json');

// Get database connection
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle different actions
try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_positions':
            handleGetPositions($pdo);
            break;
        case 'get_position':
            handleGetPosition($pdo);
            break;
        case 'add_position':
            handleAddPosition($pdo);
            break;
        case 'update_position':
            handleUpdatePosition($pdo);
            break;
        case 'delete_position':
            handleDeletePosition($pdo);
            break;
        case 'update_order':
            handleUpdateOrder($pdo);
            break;
        case 'update_position_order':
            handleUpdatePositionOrder($pdo);
            break;
        case 'get_candidate':
            handleGetCandidate($pdo);
            break;
        case 'add_candidate':
            handleAddCandidate($pdo);
            break;
        case 'update_candidate':
            handleUpdateCandidate($pdo);
            break;
        case 'delete_candidate':
            handleDeleteCandidate($pdo);
            break;
        case 'update_candidate_order':
            handleUpdateCandidateOrder($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Election Positions API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

function handleGetPositions($pdo) {
    try {
        $election_id = $_GET['election_id'] ?? null;
        
        if (!$election_id) {
            echo json_encode(['success' => false, 'message' => 'Election ID is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT id, position_title, description, display_order, max_candidates, is_active
            FROM election_specific_positions 
            WHERE election_id = ? 
            ORDER BY display_order ASC, position_title ASC
        ");
        $stmt->execute([$election_id]);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $positions]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleGetPosition($pdo) {
    try {
        $position_id = $_GET['id'] ?? null;
        
        if (!$position_id) {
            echo json_encode(['success' => false, 'message' => 'Position ID is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT id, position_title, description, display_order, max_candidates, is_active, election_id
            FROM election_specific_positions 
            WHERE id = ?
        ");
        $stmt->execute([$position_id]);
        $position = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($position) {
            // Rename max_candidates to max_votes for compatibility with frontend
            $position['max_votes'] = $position['max_candidates'];
            echo json_encode(['success' => true, 'position' => $position]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Position not found']);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

function handleAddPosition($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        
        $election_id = $input['election_id'] ?? null;
        $position_title = trim($input['position_title'] ?? '');
        $description = trim($input['description'] ?? '');
        $max_candidates = (int)($input['max_votes'] ?? $input['max_candidates'] ?? 1);
        
        if (!$election_id || !$position_title) {
            echo json_encode(['success' => false, 'message' => 'Election ID and position title are required']);
            return;
        }
        
        // Check if position title already exists for this election
        $stmt = $pdo->prepare("SELECT id FROM election_specific_positions WHERE election_id = ? AND position_title = ?");
        $stmt->execute([$election_id, $position_title]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Position title already exists for this election']);
            return;
        }
        
        // Get next display order
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM election_specific_positions WHERE election_id = ?");
        $stmt->execute([$election_id]);
        $next_order = $stmt->fetchColumn();
        
        // Insert new position
        $stmt = $pdo->prepare("
            INSERT INTO election_specific_positions (election_id, position_title, description, display_order, max_candidates) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$election_id, $position_title, $description, $next_order, $max_candidates]);
        
        $position_id = $pdo->lastInsertId();
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'CREATE', 'election_specific_positions', ?, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], $position_id, "Added position: $position_title for election ID: $election_id"]);
        
        echo json_encode(['success' => true, 'message' => 'Position added successfully', 'id' => $position_id]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdatePosition($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        
        $id = (int)($input['position_id'] ?? $input['id'] ?? 0);
        $position_title = trim($input['position_title'] ?? '');
        $description = trim($input['description'] ?? '');
        $max_candidates = (int)($input['max_votes'] ?? $input['max_candidates'] ?? 1);
        
        if (!$id || !$position_title) {
            echo json_encode(['success' => false, 'message' => 'Position ID and title are required']);
            return;
        }
        
        // Get current position data
        $stmt = $pdo->prepare("SELECT * FROM election_specific_positions WHERE id = ?");
        $stmt->execute([$id]);
        $current_position = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_position) {
            echo json_encode(['success' => false, 'message' => 'Position not found']);
            return;
        }
        
        // Check if position title already exists for this election (excluding current position)
        $stmt = $pdo->prepare("SELECT id FROM election_specific_positions WHERE election_id = ? AND position_title = ? AND id != ?");
        $stmt->execute([$current_position['election_id'], $position_title, $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Position title already exists for this election']);
            return;
        }
        
        // Update position
        $stmt = $pdo->prepare("
            UPDATE election_specific_positions 
            SET position_title = ?, description = ?, max_candidates = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$position_title, $description, $max_candidates, $id]);
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'UPDATE', 'election_specific_positions', ?, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], $id, "Updated position: $position_title"]);
        
        echo json_encode(['success' => true, 'message' => 'Position updated successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleDeletePosition($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        $id = (int)($input['position_id'] ?? $input['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Position ID is required']);
            return;
        }
        
        // Get position details
        $stmt = $pdo->prepare("SELECT * FROM election_specific_positions WHERE id = ?");
        $stmt->execute([$id]);
        $position = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$position) {
            echo json_encode(['success' => false, 'message' => 'Position not found']);
            return;
        }
        
        // Check if position has candidates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE election_specific_position_id = ?");
        $stmt->execute([$id]);
        $candidate_count = $stmt->fetchColumn();
        
        if ($candidate_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete position with existing candidates. Remove candidates first.']);
            return;
        }
        
        // Delete position
        $stmt = $pdo->prepare("DELETE FROM election_specific_positions WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'DELETE', 'election_specific_positions', ?, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], $id, "Deleted position: {$position['position_title']}"]);
        
        echo json_encode(['success' => true, 'message' => 'Position deleted successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdateOrder($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $positions = $input['positions'] ?? [];
        
        if (empty($positions)) {
            echo json_encode(['success' => false, 'message' => 'No positions provided']);
            return;
        }
        
        $pdo->beginTransaction();
        
        foreach ($positions as $index => $position_id) {
            $stmt = $pdo->prepare("UPDATE election_specific_positions SET display_order = ? WHERE id = ?");
            $stmt->execute([$index + 1, $position_id]);
        }
        
        $pdo->commit();
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'UPDATE', 'election_specific_positions', NULL, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], "Updated position display order"]);
        
        echo json_encode(['success' => true, 'message' => 'Position order updated successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleUpdatePositionOrder($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        
        $order_data = $input['order_data'] ?? '';
        if (is_string($order_data)) {
            $order_data = json_decode($order_data, true);
        }
        
        if (empty($order_data)) {
            echo json_encode(['success' => false, 'message' => 'No order data provided']);
            return;
        }
        
        $pdo->beginTransaction();
        
        foreach ($order_data as $item) {
            $stmt = $pdo->prepare("UPDATE election_specific_positions SET display_order = ? WHERE id = ?");
            $stmt->execute([$item['order'], $item['id']]);
        }
        
        $pdo->commit();
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'UPDATE', 'election_specific_positions', NULL, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], "Updated position display order"]);
        
        echo json_encode(['success' => true, 'message' => 'Position order updated successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleGetCandidate($pdo) {
    try {
        $candidate_id = $_GET['id'] ?? null;
        
        if (!$candidate_id) {
            echo json_encode(['success' => false, 'message' => 'Candidate ID is required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT c.id, c.position_id, c.election_specific_position_id, c.full_name, c.student_id, c.level, c.department, c.motto, c.course, c.is_approved, c.created_at, esp.position_title 
            FROM candidates c 
            LEFT JOIN election_specific_positions esp ON c.election_specific_position_id = esp.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$candidate_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($candidate) {
            // Map motto to bio for frontend compatibility
            $candidate['bio'] = $candidate['motto'] ?? '';
            $candidate['course'] = $candidate['course'] ?? '';
            $candidate['student_id'] = $candidate['student_id'] ?? '';
            $candidate['level'] = $candidate['level'] ?? '';
            $candidate['department'] = $candidate['department'] ?? '';
            $candidate['candidate_order'] = 1;
            echo json_encode(['success' => true, 'candidate' => $candidate]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

function handleAddCandidate($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        
        $position_id = $input['candidatePositionId'] ?? $input['position_id'] ?? null;
        $full_name = trim($input['candidateFullName'] ?? $input['full_name'] ?? '');
        $motto = trim($input['candidateBio'] ?? $input['bio'] ?? $input['motto'] ?? '');
        $course = trim($input['candidateCourse'] ?? $input['course'] ?? '');
        $student_id = trim($input['candidateStudentId'] ?? $input['student_id'] ?? '');
        $level = trim($input['candidateLevel'] ?? $input['level'] ?? '');
        $department = trim($input['candidateDepartment'] ?? $input['department'] ?? '');
        
        if (!$position_id || !$full_name) {
            echo json_encode(['success' => false, 'message' => 'Position ID and candidate name are required']);
            return;
        }
        
        // Check if candidate name already exists for this position
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM candidates c 
            WHERE c.full_name = ? AND c.election_specific_position_id = ?
        ");
        $stmt->execute([$full_name, $position_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Candidate name already exists for this position']);
            return;
        }
        
        // Handle photo upload
        $photo_blob = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo_blob = file_get_contents($_FILES['photo']['tmp_name']);
        }
        
        // Insert candidate
        $stmt = $pdo->prepare("
            INSERT INTO candidates (election_specific_position_id, full_name, student_id, level, department, motto, course, photo, is_approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$position_id, $full_name, $student_id, $level, $department, $motto, $course, $photo_blob]);
        
        $candidate_id = $pdo->lastInsertId();
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'INSERT', 'candidates', ?, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], $candidate_id, "Added candidate: {$full_name}"]);
        
        echo json_encode(['success' => true, 'message' => 'Candidate added successfully', 'candidate_id' => $candidate_id]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdateCandidate($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        
        $candidate_id = $input['candidateId'] ?? $input['candidate_id'] ?? $input['id'] ?? null;
        $position_id = $input['candidatePositionId'] ?? $input['position_id'] ?? null;
        $full_name = trim($input['candidateFullName'] ?? $input['full_name'] ?? '');
        $motto = trim($input['candidateBio'] ?? $input['bio'] ?? $input['motto'] ?? '');
        $course = trim($input['candidateCourse'] ?? $input['course'] ?? '');
        $student_id = trim($input['candidateStudentId'] ?? $input['student_id'] ?? '');
        $level = trim($input['candidateLevel'] ?? $input['level'] ?? '');
        $department = trim($input['candidateDepartment'] ?? $input['department'] ?? '');
        
        if (!$candidate_id || !$position_id || !$full_name) {
            echo json_encode(['success' => false, 'message' => 'Candidate ID, position ID, and candidate name are required']);
            return;
        }
        
        // Check if candidate name already exists for this position (excluding current candidate)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM candidates c 
            WHERE c.full_name = ? AND c.election_specific_position_id = ? AND c.id != ?
        ");
        $stmt->execute([$full_name, $position_id, $candidate_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Candidate name already exists for this position']);
            return;
        }
        
        // Handle photo upload
        $photo_update = '';
        $params = [$position_id, $full_name, $student_id, $level, $department, $motto, $course];
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo_blob = file_get_contents($_FILES['photo']['tmp_name']);
            $photo_update = ', photo = ?';
            $params[] = $photo_blob;
        }
        
        $params[] = $candidate_id;
        
        // Update candidate
        $stmt = $pdo->prepare("
            UPDATE candidates 
            SET election_specific_position_id = ?, full_name = ?, student_id = ?, level = ?, department = ?, motto = ?, course = ?{$photo_update} 
            WHERE id = ?
        ");
        $stmt->execute($params);
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'UPDATE', 'candidates', ?, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], $candidate_id, "Updated candidate: {$full_name}"]);
        
        echo json_encode(['success' => true, 'message' => 'Candidate updated successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleDeleteCandidate($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        
        $candidate_id = $input['candidate_id'] ?? $input['id'] ?? null;
        
        if (!$candidate_id) {
            echo json_encode(['success' => false, 'message' => 'Candidate ID is required']);
            return;
        }
        
        // Get candidate details for logging
        $stmt = $pdo->prepare("SELECT full_name FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidate) {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
            return;
        }
        
        // Check if candidate has votes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ?");
        $stmt->execute([$candidate_id]);
        $vote_count = $stmt->fetchColumn();
        
        if ($vote_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete candidate with existing votes']);
            return;
        }
        
        // Delete candidate
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'DELETE', 'candidates', ?, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], $candidate_id, "Deleted candidate: {$candidate['full_name']}"]);
        
        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdateCandidateOrder($pdo) {
    try {
        // Handle both JSON and FormData input
        if ($_SERVER['CONTENT_TYPE'] === 'application/json' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
        } else {
            $input = $_POST;
        }
        
        $order_data = $input['order_data'] ?? '';
        if (is_string($order_data)) {
            $order_data = json_decode($order_data, true);
        }
        
        if (empty($order_data)) {
            echo json_encode(['success' => false, 'message' => 'No order data provided']);
            return;
        }
        
        $pdo->beginTransaction();
        
        foreach ($order_data as $item) {
            $stmt = $pdo->prepare("UPDATE candidates SET candidate_order = ? WHERE id = ?");
            $stmt->execute([$item['order'], $item['id']]);
        }
        
        $pdo->commit();
        
        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, details) 
            VALUES ('admin', ?, 'UPDATE', 'candidates', NULL, ?)
        ");
        $stmt->execute([$_SESSION['admin_id'], "Updated candidate display order"]);
        
        echo json_encode(['success' => true, 'message' => 'Candidate order updated successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>