<?php
// Start output buffering to catch any unwanted output
ob_start();

define('SECURE_ACCESS', true);
require_once '../../config/database.php';
require_once '../../config/session.php';

// Initialize database connection
$pdo = getDBConnection();

// Clean any output that might have been generated
ob_clean();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

// Ensure clean JSON output
ob_clean();

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_position':
            getPosition();
            break;
        case 'get_candidate':
            getCandidate();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_election':
            updateElection();
            break;
        case 'add_position':
            addPosition();
            break;
        case 'update_position':
            updatePosition();
            break;
        case 'delete_position':
            deletePosition();
            break;
        case 'add_candidate':
            addCandidate();
            break;
        case 'update_candidate':
            updateCandidate();
            break;
        case 'delete_candidate':
            deleteCandidate();
            break;
        case 'update_position_order':
            updatePositionOrder();
            break;
        case 'update_candidate_order':
            updateCandidateOrder();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

function updateElection() {
    global $pdo;
    
    try {
        $election_id = (int)($_POST['election_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        // Enhanced debug logging
        error_log("Election Update Debug - ID: $election_id, Title: $title, Status: $status");
        error_log("Date Debug - Start: '$start_date', End: '$end_date'");
        
        // Validate inputs
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Election title is required']);
            return;
        }
        
        if (empty($election_id)) {
            echo json_encode(['success' => false, 'message' => 'Election ID is required']);
            return;
        }
        
        if (!in_array($status, ['inactive', 'active', 'completed'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            return;
        }
        
        if (empty($start_date) || empty($end_date)) {
            echo json_encode(['success' => false, 'message' => 'Start date and end date are required']);
            return;
        }
        
        // Validate dates with enhanced debugging
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        error_log("Timestamp Debug - Start: $start_timestamp (" . date('Y-m-d H:i:s', $start_timestamp ?: 0) . "), End: $end_timestamp (" . date('Y-m-d H:i:s', $end_timestamp ?: 0) . ")");
        
        if ($start_timestamp === false || $end_timestamp === false) {
            error_log("Date parsing failed - Start valid: " . ($start_timestamp !== false ? 'yes' : 'no') . ", End valid: " . ($end_timestamp !== false ? 'yes' : 'no'));
            echo json_encode(['success' => false, 'message' => 'Invalid date format']);
            return;
        }
        
        if ($start_timestamp >= $end_timestamp) {
            $time_diff = $end_timestamp - $start_timestamp;
            error_log("Date validation failed - Start: $start_timestamp, End: $end_timestamp, Difference: $time_diff seconds");
            echo json_encode([
                'success' => false, 
                'message' => 'End date must be after start date',
                'debug' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'start_timestamp' => $start_timestamp,
                    'end_timestamp' => $end_timestamp,
                    'time_difference' => $time_diff
                ]
            ]);
            return;
        }
        
        // Convert status to is_active boolean
        $is_active = ($status === 'active') ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE elections 
            SET election_title = ?, description = ?, is_active = ?, start_date = ?, end_date = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$title, $description, $is_active, $start_date, $end_date, $election_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            error_log("Election Update Success - Rows affected: " . $stmt->rowCount());
            echo json_encode(['success' => true, 'message' => 'Election updated successfully']);
        } else {
            error_log("Election Update Failed - No rows affected or election not found");
            echo json_encode(['success' => false, 'message' => 'No changes made or election not found']);
        }
        
    } catch (PDOException $e) {
        error_log("Election Update Database Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function addPosition() {
    global $pdo;
    
    try {
        $election_id = (int)$_POST['election_id'];
        $position_title = trim($_POST['position_title']);
        $description = trim($_POST['description']);
        $max_votes = (int)$_POST['max_winners'];
        $display_order = (int)$_POST['position_order'];
        
        // Validate inputs
        if (empty($position_title)) {
            echo json_encode(['success' => false, 'message' => 'Position title is required']);
            return;
        }
        
        if ($max_votes < 1) {
            echo json_encode(['success' => false, 'message' => 'Max winners must be at least 1']);
            return;
        }
        
        // Check if position title already exists for this election
        $stmt = $pdo->prepare("SELECT id FROM positions WHERE election_id = ? AND position_title = ?");
        $stmt->execute([$election_id, $position_title]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Position title already exists for this election']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO positions (election_id, position_title, description, max_votes, display_order)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$election_id, $position_title, $description, $max_votes, $display_order]);
        
        echo json_encode(['success' => true, 'message' => 'Position added successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updatePosition() {
    global $pdo;
    
    try {
        $position_id = (int)$_POST['position_id'];
        $election_id = (int)$_POST['election_id'];
        $position_title = trim($_POST['position_title']);
        $description = trim($_POST['description']);
        $max_votes = (int)$_POST['max_winners'];
        $display_order = (int)$_POST['position_order'];
        
        // Validate inputs
        if (empty($position_title)) {
            echo json_encode(['success' => false, 'message' => 'Position title is required']);
            return;
        }
        
        if ($max_votes < 1) {
            echo json_encode(['success' => false, 'message' => 'Max winners must be at least 1']);
            return;
        }
        
        // Check if position title already exists for this election (excluding current position)
        $stmt = $pdo->prepare("SELECT id FROM positions WHERE election_id = ? AND position_title = ? AND id != ?");
        $stmt->execute([$election_id, $position_title, $position_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Position title already exists for this election']);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE positions 
            SET position_title = ?, description = ?, max_votes = ?, display_order = ?
            WHERE id = ? AND election_id = ?
        ");
        
        $stmt->execute([$position_title, $description, $max_votes, $display_order, $position_id, $election_id]);
        
        echo json_encode(['success' => true, 'message' => 'Position updated successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deletePosition() {
    global $pdo;
    
    try {
        $position_id = (int)$_POST['position_id'];
        
        // Check if position is used in any elections
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM election_positions WHERE position_id = ?");
        $stmt->execute([$position_id]);
        $election_count = $stmt->fetchColumn();
        
        if ($election_count > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot delete position that is assigned to elections. Remove from elections first.'
            ]);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete all candidates for this position first
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE position_id = ?");
        $stmt->execute([$position_id]);
        
        // Delete the position
        $stmt = $pdo->prepare("DELETE FROM positions WHERE id = ?");
        $stmt->execute([$position_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Position deleted successfully']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getPosition() {
    global $pdo;
    
    try {
        $position_id = (int)$_GET['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = ?");
        $stmt->execute([$position_id]);
        $position = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($position) {
            echo json_encode(['success' => true, 'position' => $position]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Position not found']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function addCandidate() {
    global $pdo;
    
    try {
        $position_id = (int)($_POST['position_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $candidate_order = (int)($_POST['candidate_order'] ?? 1);
        
        // Validate inputs
        if (empty($full_name)) {
            echo json_encode(['success' => false, 'message' => 'Full name is required']);
            return;
        }
        
        if (empty($student_id)) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required']);
            return;
        }
        
        // Check if student ID already exists for this position
        $stmt = $pdo->prepare("SELECT id FROM candidates WHERE position_id = ? AND student_id = ?");
        $stmt->execute([$position_id, $student_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Student ID already exists for this position']);
            return;
        }
        
        // Handle photo upload
        $photo_filename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo_filename = handlePhotoUpload($_FILES['photo']);
            if (!$photo_filename) {
                echo json_encode(['success' => false, 'message' => 'Error uploading photo']);
                return;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO candidates (position_id, full_name, student_id, level, department, bio, photo, candidate_order, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())
        ");
        
        $stmt->execute([$position_id, $full_name, $student_id, $level, $department, $bio, $photo_filename, $candidate_order]);
        
        echo json_encode(['success' => true, 'message' => 'Candidate added successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateCandidate() {
    global $pdo;
    
    try {
        $candidate_id = (int)($_POST['candidate_id'] ?? 0);
        $position_id = (int)($_POST['position_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $candidate_order = (int)($_POST['candidate_order'] ?? 1);
        
        // Validate inputs
        if (empty($full_name)) {
            echo json_encode(['success' => false, 'message' => 'Full name is required']);
            return;
        }
        
        if (empty($student_id)) {
            echo json_encode(['success' => false, 'message' => 'Student ID is required']);
            return;
        }
        
        // Check if student ID already exists for this position (excluding current candidate)
        $stmt = $pdo->prepare("SELECT id FROM candidates WHERE position_id = ? AND student_id = ? AND id != ?");
        $stmt->execute([$position_id, $student_id, $candidate_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Student ID already exists for this position']);
            return;
        }
        
        // Get current photo
        $stmt = $pdo->prepare("SELECT photo FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        $current_photo = $stmt->fetchColumn();
        
        $photo_filename = $current_photo;
        
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $new_photo = handlePhotoUpload($_FILES['photo']);
            if ($new_photo) {
                // Delete old photo if it exists
                if ($current_photo && file_exists("../../uploads/candidates/" . $current_photo)) {
                    unlink("../../uploads/candidates/" . $current_photo);
                }
                $photo_filename = $new_photo;
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE candidates 
            SET full_name = ?, student_id = ?, level = ?, department = ?, bio = ?, photo = ?, candidate_order = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$full_name, $student_id, $level, $department, $bio, $photo_filename, $candidate_order, $candidate_id]);
        
        echo json_encode(['success' => true, 'message' => 'Candidate updated successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteCandidate() {
    global $pdo;
    
    try {
        $candidate_id = (int)$_POST['candidate_id'];
        
        // Get photo filename before deletion
        $stmt = $pdo->prepare("SELECT photo FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        $photo = $stmt->fetchColumn();
        
        // Delete candidate
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        
        // Delete photo file if it exists
        if ($photo && file_exists("../../uploads/candidates/" . $photo)) {
            unlink("../../uploads/candidates/" . $photo);
        }
        
        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getCandidate() {
    global $pdo;
    
    try {
        $candidate_id = (int)$_GET['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($candidate) {
            echo json_encode(['success' => true, 'candidate' => $candidate]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updatePositionOrder() {
    global $pdo;
    
    try {
        $order_data = json_decode($_POST['order_data'], true);
        
        if (!$order_data) {
            echo json_encode(['success' => false, 'message' => 'Invalid order data']);
            return;
        }
        
        $pdo->beginTransaction();
        
        foreach ($order_data as $item) {
            $stmt = $pdo->prepare("UPDATE positions SET display_order = ? WHERE id = ?");
            $stmt->execute([$item['order'], $item['id']]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Position order updated successfully']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateCandidateOrder() {
    global $pdo;
    
    try {
        $order_data = json_decode($_POST['order_data'], true);
        
        if (!$order_data) {
            echo json_encode(['success' => false, 'message' => 'Invalid order data']);
            return;
        }
        
        $pdo->beginTransaction();
        
        foreach ($order_data as $item) {
            $stmt = $pdo->prepare("UPDATE candidates SET candidate_order = ? WHERE id = ?");
            $stmt->execute([$item['order'], $item['id']]);
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Candidate order updated successfully']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePhotoUpload($file) {
    $upload_dir = '../../uploads/candidates/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('candidate_') . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}
?>