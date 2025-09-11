<?php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
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

// Function to validate course against predefined list
function validateCourse($course_name) {
    if (empty($course_name)) {
        return false;
    }
    
    // List of valid courses - can be moved to config later
    $validCourses = [
        'Computer Science', 'Information Technology', 'Software Engineering',
        'Business Administration', 'Marketing', 'Accounting',
        'Civil Engineering', 'Mechanical Engineering', 'Electrical Engineering',
        'Elementary Education', 'Secondary Education', 'Special Education',
        'Nursing', 'Physical Therapy', 'Medical Technology',
        'Psychology', 'Sociology', 'Political Science'
    ];
    
    return in_array($course_name, $validCourses);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            handleGetVoters();
            break;
        case 'POST':
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'import_csv':
                        handleCSVImport();
                        break;
                    case 'bulk_delete':
                        handleBulkDelete();
                        break;
                    default:
                        handleAddVoter();
                        break;
                }
            } else {
                handleAddVoter();
            }
            break;
        case 'PUT':
            handleUpdateVoter();
            break;
        case 'DELETE':
            handleDeleteVoter();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Voters API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}



function handleGetVoters() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    $election_id = $_GET['election_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    
    if ($id) {
        // Get single voter
        $stmt = $pdo->prepare("
            SELECT v.* 
            FROM voters v 
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($voter) {
            echo json_encode(['success' => true, 'data' => $voter]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Voter not found']);
        }
        return;
    }
    
    // Build query for multiple voters
    $query = "
        SELECT v.*,
               CASE WHEN vt.voter_id IS NOT NULL THEN 'voted' ELSE 'not_voted' END as vote_status
        FROM voters v 
        LEFT JOIN votes vt ON v.id = vt.voter_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Election ID filtering removed - voters table doesn't have election_id column
    
    if ($status) {
        if ($status === 'voted') {
            $query .= " AND vt.voter_id IS NOT NULL";
        } elseif ($status === 'not_voted') {
            $query .= " AND vt.voter_id IS NULL";
        } else {
            $query .= " AND v.status = ?";
            $params[] = $status;
        }
    }
    
    if ($search) {
        $query .= " AND (v.first_name LIKE ? OR v.last_name LIKE ? OR v.email LIKE ? OR v.student_id LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $query .= " GROUP BY v.id ORDER BY v.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $voters]);
}

function handleAddVoter() {
    global $pdo;
    
    // Include EmailUtility
    require_once '../../config/email.php';
    
    $required_fields = ['first_name', 'last_name', 'email', 'student_id'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
            return;
        }
    }
    
    // Check if email or student_id already exists
    $stmt = $pdo->prepare("SELECT id FROM voters WHERE (email = ? OR student_id = ?)");
    $stmt->execute([$_POST['email'], $_POST['student_id']]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Voter with this email or student ID already exists']);
        return;
    }
    
    // Validate course if provided
    $course = $_POST['course'] ?? null;
    if ($course && !validateCourse($course)) {
        echo json_encode(['success' => false, 'message' => 'Invalid course selection']);
        return;
    }
    
    // Generate secure password and hash it
    $generated_password = EmailUtility::generateSecurePassword();
    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
    $year_level = $_POST['year_level'] ?? '1st';
    
    $stmt = $pdo->prepare("
        INSERT INTO voters (first_name, last_name, email, student_id, password, year_level, course, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    if ($stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['student_id'],
        $hashed_password,
        $year_level,
        $course
    ])) {
        $voter_id = $pdo->lastInsertId();
        
        // Send password via email
         $emailUtility = new EmailUtility();
         $email_sent = $emailUtility->sendPasswordEmail(
             $_POST['email'],
             $_POST['first_name'] . ' ' . $_POST['last_name'],
             $_POST['student_id'],
             $generated_password
         );
        
        // Log admin action
        logAdminAction($_SESSION['admin_id'] ?? 1, 'add_voter', "Added voter: {$_POST['first_name']} {$_POST['last_name']}");
        
        $message = 'Voter added successfully';
        if (!$email_sent) {
            $message .= ', but email could not be sent. Please provide the password manually: ' . $generated_password;
        } else {
            $message .= ' and password has been sent to their email address.';
        }
        
        echo json_encode(['success' => true, 'message' => $message, 'voter_id' => $voter_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add voter']);
    }
}

function handleUpdateVoter() {
    global $pdo, $input;
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Voter ID is required']);
        return;
    }
    
    try {
        $required_fields = ['first_name', 'last_name', 'email', 'student_id'];
        
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
                return;
            }
        }
        
        // Validate course if provided
        $course = $input['course'] ?? null;
        if ($course && !validateCourse($course)) {
            echo json_encode(['success' => false, 'message' => 'Invalid course selection']);
            return;
        }
    
    // Check if email or student_id already exists for another voter
    $stmt = $pdo->prepare("
        SELECT id FROM voters 
        WHERE (email = ? OR student_id = ?) 
        AND id != ?
    ");
    $stmt->execute([
        $input['email'], 
        $input['student_id'], 
        $id
    ]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Another voter with this email or student ID already exists']);
        return;
    }
    
    // Handle password update if provided
    $password_update = "";
    $params = [
        $input['first_name'],
        $input['last_name'],
        $input['email'],
        $input['student_id'],
        $input['year_level'] ?? '1st',
        $course,
        isset($input['status']) ? ($input['status'] === 'active' ? 1 : 0) : 1
    ];
    
    // Check if password reset is requested
    $generated_password = null;
    if (!empty($input['reset_password']) && $input['reset_password'] == '1') {
        // Include EmailUtility
        require_once '../../config/email.php';
        
        // Generate secure password
        $generated_password = EmailUtility::generateSecurePassword();
        $password_update = ", password = ?";
        $params[] = password_hash($generated_password, PASSWORD_DEFAULT);
    }
    
    $params[] = $id; // Add ID at the end
    
    $stmt = $pdo->prepare("
        UPDATE voters 
        SET first_name = ?, last_name = ?, email = ?, student_id = ?, year_level = ?, course = ?, is_active = ?, updated_at = NOW() $password_update
        WHERE id = ?
    ");
    
    if ($stmt->execute($params)) {
        // Log admin action with password reset info
        $action_details = "Updated voter: {$input['first_name']} {$input['last_name']}";
        $message = 'Voter updated successfully';
        
        if ($generated_password) {
            $action_details .= " (Password reset)";
            
            // Send password via email
             $emailUtility = new EmailUtility();
             $email_sent = $emailUtility->sendPasswordEmail(
                 $input['email'],
                 $input['first_name'] . ' ' . $input['last_name'],
                 $input['student_id'],
                 $generated_password
             );
            
            if (!$email_sent) {
                $message .= ', but email could not be sent. New password: ' . $generated_password;
            } else {
                $message .= ' and new password has been sent to their email address.';
            }
        }
        
        logAdminAction($_SESSION['admin_id'] ?? 1, 'update_voter', $action_details);
        
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        error_log("handleUpdateVoter - UPDATE query failed");
        echo json_encode(['success' => false, 'message' => 'Failed to update voter']);
    }
    
    } catch (Exception $e) {
        error_log("handleUpdateVoter - Exception: " . $e->getMessage());
        error_log("handleUpdateVoter - Stack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'An error occurred while updating the voter']);
    }
}

function handleDeleteVoter() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Voter ID is required']);
        return;
    }
    
    // Get voter info for logging
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM voters WHERE id = ?");
    $stmt->execute([$id]);
    $voter = $stmt->fetch();
    
    if (!$voter) {
        echo json_encode(['success' => false, 'message' => 'Voter not found']);
        return;
    }
    
    // Check if voter has already voted
    $stmt = $pdo->prepare("SELECT id FROM votes WHERE voter_id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete voter who has already voted']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM voters WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        // Log admin action
        logAdminAction($_SESSION['admin_id'] ?? 1, 'delete_voter', "Deleted voter: {$voter['first_name']} {$voter['last_name']}");
        
        echo json_encode(['success' => true, 'message' => 'Voter deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete voter']);
    }
}

function handleCSVImport() {
    global $pdo;
    
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid CSV file']);
        return;
    }
    
    $election_id = $_POST['election_id'] ?? null;
    if (!$election_id) {
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        return;
    }
    
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    if (!$handle) {
        echo json_encode(['success' => false, 'message' => 'Failed to read CSV file']);
        return;
    }
    
    $header = fgetcsv($handle);
    $required_columns = ['first_name', 'last_name', 'email', 'student_id'];
    
    // Validate CSV headers
    $missing_columns = array_diff($required_columns, $header);
    if (!empty($missing_columns)) {
        fclose($handle);
        echo json_encode(['success' => false, 'message' => 'Missing required columns: ' . implode(', ', $missing_columns)]);
        return;
    }
    
    $imported = 0;
    $errors = [];
    $line = 1;
    
    $pdo->beginTransaction();
    
    try {
        while (($data = fgetcsv($handle)) !== FALSE) {
            $line++;
            
            if (count($data) < count($required_columns)) {
                $errors[] = "Line $line: Insufficient data";
                continue;
            }
            
            $row_data = array_combine($header, $data);
            
            // Validate required fields
            $missing_fields = [];
            foreach ($required_columns as $col) {
                if (empty(trim($row_data[$col]))) {
                    $missing_fields[] = $col;
                }
            }
            
            if (!empty($missing_fields)) {
                $errors[] = "Line $line: Missing " . implode(', ', $missing_fields);
                continue;
            }
            
            // Validate email format
            if (!filter_var(trim($row_data['email']), FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Line $line: Invalid email format";
                continue;
            }
            
            // Check for duplicates
            $stmt = $pdo->prepare("SELECT id FROM voters WHERE (email = ? OR student_id = ?) AND election_id = ?");
            $stmt->execute([trim($row_data['email']), trim($row_data['student_id']), $election_id]);
            
            if ($stmt->fetch()) {
                $errors[] = "Line $line: Duplicate email or student ID";
                continue;
            }
            
            // Generate voter key
            $voter_key = generateVoterKey();
            
            // Insert voter
            $stmt = $pdo->prepare("
                INSERT INTO voters (first_name, last_name, email, student_id, election_id, voter_key, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            
            if ($stmt->execute([
                trim($row_data['first_name']),
                trim($row_data['last_name']),
                trim($row_data['email']),
                trim($row_data['student_id']),
                $election_id,
                $voter_key
            ])) {
                $imported++;
            } else {
                $errors[] = "Line $line: Database error";
            }
        }
        
        $pdo->commit();
        fclose($handle);
        
        // Log admin action
        logAdminAction($_SESSION['admin_id'], 'import_voters', "Imported $imported voters via CSV");
        
        $response = [
            'success' => true,
            'message' => "Successfully imported $imported voters",
            'imported' => $imported,
            'errors' => $errors
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        fclose($handle);
        echo json_encode(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
    }
}

function handleBulkDelete() {
    global $pdo;
    
    $voter_ids = $_POST['voter_ids'] ?? [];
    
    if (empty($voter_ids) || !is_array($voter_ids)) {
        echo json_encode(['success' => false, 'message' => 'No voters selected']);
        return;
    }
    
    // Check if any selected voters have already voted
    $placeholders = str_repeat('?,', count($voter_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT voter_id FROM votes WHERE voter_id IN ($placeholders)");
    $stmt->execute($voter_ids);
    $voted_voters = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($voted_voters)) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete voters who have already voted']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM voters WHERE id IN ($placeholders)");
    
    if ($stmt->execute($voter_ids)) {
        $deleted_count = $stmt->rowCount();
        
        // Log admin action
        logAdminAction($_SESSION['admin_id'] ?? 1, 'bulk_delete_voters', "Bulk deleted $deleted_count voters");
        
        echo json_encode(['success' => true, 'message' => "Successfully deleted $deleted_count voters"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete voters']);
    }
}

function generateVoterKey() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function logAdminAction($admin_id, $action, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_type, admin_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'admin',
            $admin_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}
?>