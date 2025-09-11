<?php
// Enable error logging for debugging
ini_set('log_errors', 1);
ini_set('error_log', 'C:\\xampp\\apache\\logs\\error.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Session check removed for development
// Set temporary admin session for development
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1; // Temporary admin ID for development
}

// Only log PUT requests for debugging candidate updates
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    error_log("[CANDIDATES API] PUT request to: " . $_SERVER['REQUEST_URI']);
}

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    

    
    switch ($method) {
        case 'GET':
            handleGetCandidates($pdo);
            break;
        case 'POST':
            handleCreateCandidate($pdo);
            break;
        case 'PUT':
            handleUpdateCandidate($pdo);
            break;
        case 'DELETE':
            handleDeleteCandidate($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }

function handleGetCandidates($pdo) {
    try {
        $candidate_id = $_GET['id'] ?? null;
        $election_id = $_GET['election_id'] ?? null;
        
        if ($candidate_id) {
            // Get single candidate for editing
            $sql = "
                SELECT c.id, c.election_specific_position_id as position_id, c.full_name, c.motto as platform, '' as email,
                       c.photo, c.is_approved as status, c.created_at,
                       e.id as election_id, e.election_title as election_name, esp.position_title,
                       COUNT(v.id) as vote_count
                FROM candidates c
                LEFT JOIN election_specific_positions esp ON c.election_specific_position_id = esp.id
                LEFT JOIN elections e ON esp.election_id = e.id
                LEFT JOIN votes v ON c.id = v.candidate_id
                WHERE c.id = ?
                GROUP BY c.id
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$candidate_id]);
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$candidate) {
                echo json_encode(['success' => false, 'message' => 'Candidate not found']);
                return;
            }
            
            // Process photo data
            if ($candidate['photo']) {
                $candidate['photo'] = base64_encode($candidate['photo']);
            }
            
            echo json_encode(['success' => true, 'data' => $candidate]);
            return;
        }
        
        // Get multiple candidates (existing logic)
        $sql = "
            SELECT c.id, c.election_specific_position_id as position_id, c.full_name, c.motto as platform, 
                   c.photo, c.is_approved as status, c.created_at,
                   e.election_title as election_name, esp.position_title,
                   COUNT(v.id) as vote_count
            FROM candidates c
            LEFT JOIN election_specific_positions esp ON c.election_specific_position_id = esp.id
            LEFT JOIN elections e ON esp.election_id = e.id
            LEFT JOIN votes v ON c.id = v.candidate_id
        ";
        
        $params = [];
        
        if ($election_id) {
            $sql .= " WHERE esp.election_id = ?";
            $params[] = $election_id;
        }
        
        $sql .= " GROUP BY c.id ORDER BY e.election_title, esp.display_order, c.id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process photo data
        foreach ($candidates as &$candidate) {
            if ($candidate['photo']) {
                $candidate['photo'] = base64_encode($candidate['photo']);
            } else {
                $candidate['photo'] = null;
            }
        }
        
        echo json_encode(['success' => true, 'data' => $candidates]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleCreateCandidate($pdo) {
    try {
        // Check if admin is logged in
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Admin not logged in']);
            return;
        }
        
        // Handle both form data and JSON input
        if (isset($_POST['full_name'])) {
            $input = $_POST;
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
        }
        
        // Validate required fields
        $required = ['full_name', 'position_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                return;
            }
        }
        
        // Validate position exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM election_specific_positions WHERE id = ?");
        $stmt->execute([$input['position_id']]);
        if ($stmt->fetchColumn() == 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid position ID']);
            return;
        }
        
        // Handle photo upload
        $photo_data = null;
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handlePhotoUpload($_FILES['photo']);
            if ($upload_result['success']) {
                $photo_data = $upload_result['blob'];
            } else {
                echo json_encode(['success' => false, 'message' => $upload_result['message']]);
                return;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO candidates (election_specific_position_id, full_name, platform, photo, is_approved)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['position_id'],
            $input['full_name'],
            $input['platform'] ?? null,
            $photo_data,
            isset($input['status']) ? (bool)$input['status'] : true
        ]);
        
        $candidate_id = $pdo->lastInsertId();
        
        // Log the action
        logAdminAction($_SESSION['admin_id'], 'CREATE', 'candidates', $candidate_id, null, $input, $pdo);
        
        echo json_encode(['success' => true, 'message' => 'Candidate created successfully', 'id' => $candidate_id]);
    } catch (Exception $e) {
        throw $e;
    }
}

function handleUpdateCandidate($pdo) {
    try {
        // Get candidate ID from URL parameter
        $candidate_id = $_GET['id'] ?? null;
        
        if (empty($candidate_id)) {
            echo json_encode(['success' => false, 'message' => 'Candidate ID is required']);
            return;
        }
        
        // Handle both form data and JSON input
        // For PUT requests, try to parse multipart data or JSON
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            // First try to get data from php://input
            $raw_data = file_get_contents('php://input');
            
            // Try JSON first
            $input = json_decode($raw_data, true);
            
            // If not JSON, try to parse as query string (for simple form data)
            if (!$input) {
                parse_str($raw_data, $input);
            }
            
            // If still no data, initialize empty array
            if (!$input) {
                $input = [];
            }
        } else {
            // For POST requests, use $_POST
            $input = $_POST;
        }
        
        // Get current candidate data for logging
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$old_data) {
            echo json_encode(['success' => false, 'message' => 'Candidate not found']);
            return;
        }
        
        // Handle photo upload if provided
        $photo_updates = [];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handlePhotoUpload($_FILES['photo']);
            if ($upload_result['success']) {
                $photo_updates['photo'] = $upload_result['blob'];
            } else {
                echo json_encode(['success' => false, 'message' => $upload_result['message']]);
                return;
            }
        }
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        $allowed_fields = ['full_name', 'email', 'motto', 'is_approved', 'position_id', 'election_id'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                // Map frontend fields to database columns
                $field_mapping = [
                    'full_name' => 'full_name',
                    'motto' => 'motto',
                    'is_approved' => 'is_approved',
                    'email' => 'email', // Note: email field doesn't exist in current schema
                    'position_id' => 'election_specific_position_id',
                    'election_id' => 'election_id'
                ];
                
                $db_field = $field_mapping[$field] ?? $field;
                
                // Skip email and election_id fields since they don't exist in the database
                if ($field === 'email' || $field === 'election_id') {
                    continue;
                }
                
                // Validate position_id exists in election_specific_positions table
                if ($field === 'position_id') {
                    $check_stmt = $pdo->prepare("SELECT id FROM election_specific_positions WHERE id = ?");
                    $check_stmt->execute([$input[$field]]);
                    if (!$check_stmt->fetch()) {
                        echo json_encode(['success' => false, 'message' => 'Invalid position ID: Position does not exist']);
                        return;
                    }
                }
                
                $updates[] = "$db_field = ?";
                $params[] = $input[$field];
            }
        }
        
        // Add photo updates
        foreach ($photo_updates as $field => $value) {
            $updates[] = "$field = ?";
            $params[] = $value;
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            return;
        }
        
        $params[] = $candidate_id;
        
        // Update candidate
        $stmt = $pdo->prepare("UPDATE candidates SET " . implode(', ', $updates) . " WHERE id = ?");
        $result = $stmt->execute($params);
        
        // Log the action (exclude binary photo data from logging)
        $log_data = $input;
        if (!empty($photo_updates)) {
            $log_data['photo'] = '[PHOTO_UPDATED]';
        }
        logAdminAction($_SESSION['admin_id'], 'UPDATE', 'candidates', $candidate_id, $old_data, $log_data, $pdo);
        
        echo json_encode(['success' => true, 'message' => 'Candidate updated successfully']);
    } catch (Exception $e) {
        error_log("[UPDATE CANDIDATE] Exception caught: " . $e->getMessage());
        error_log("[UPDATE CANDIDATE] Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
}

function handleDeleteCandidate($pdo) {
    try {
        // Get candidate ID from URL parameter
        $candidate_id = $_GET['id'] ?? null;
        
        if (empty($candidate_id)) {
            echo json_encode(['success' => false, 'message' => 'Candidate ID is required']);
            return;
        }
        
        // Get candidate data for logging
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        $candidate_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidate_data) {
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
        
        // Photo data will be automatically deleted with the candidate record
        
        // Delete candidate
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$candidate_id]);
        
        // Log the action
        logAdminAction($_SESSION['admin_id'], 'DELETE', 'candidates', $candidate_id, $candidate_data, null, $pdo);
        
        echo json_encode(['success' => true, 'message' => 'Candidate deleted successfully']);
    } catch (Exception $e) {
        throw $e;
    }
}

function handlePhotoUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'];
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
    }
    
    // Read file content directly into blob
    $photo_blob = file_get_contents($file['tmp_name']);
    
    if ($photo_blob !== false) {
        return [
            'success' => true,
            'blob' => $photo_blob
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to read uploaded file'];
    }
}

function logAdminAction($admin_id, $action, $table, $record_id, $old_values, $new_values, $pdo) {
    try {
        // Remove binary data (like photos) before JSON encoding to avoid constraint violations
        $clean_old_values = $old_values;
        $clean_new_values = $new_values;
        
        if ($clean_old_values && isset($clean_old_values['photo'])) {
            $clean_old_values['photo'] = '[BINARY_DATA_REMOVED]';
        }
        
        if ($clean_new_values && isset($clean_new_values['photo'])) {
            $clean_new_values['photo'] = '[BINARY_DATA_REMOVED]';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, table_name, record_id, old_values, new_values, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'admin',
            $admin_id,
            $action,
            $table,
            $record_id,
            $clean_old_values ? json_encode($clean_old_values, JSON_UNESCAPED_UNICODE) : null,
            $clean_new_values ? json_encode($clean_new_values, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}
?>