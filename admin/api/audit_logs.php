<?php
/**
 * Heritage Christian University Online Voting System
 * Audit Logs API Endpoint
 * Provides audit log data with DataTables server-side processing
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
    
    // DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    // Column definitions for DataTables
    $columns = [
        0 => 'id',
        1 => 'action',
        2 => 'user_type',
        3 => 'user_id',
        4 => 'details',
        5 => 'ip_address',
        6 => 'created_at'
    ];
    
    // Order parameters
    $orderColumn = isset($_GET['order'][0]['column']) ? $columns[$_GET['order'][0]['column']] : 'created_at';
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';
    
    // Base query
    $baseQuery = "FROM audit_logs al";
    
    // Search condition
    $searchCondition = "";
    if (!empty($searchValue)) {
        $searchCondition = " WHERE (
            al.action LIKE :search OR 
            al.user_type LIKE :search OR 
            al.details LIKE :search OR 
            al.ip_address LIKE :search OR
            al.user_id LIKE :search
        )";
    }
    
    // Count total records
    $totalQuery = "SELECT COUNT(*) as total " . $baseQuery;
    $stmt = $conn->prepare($totalQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Count filtered records
    $filteredQuery = "SELECT COUNT(*) as total " . $baseQuery . $searchCondition;
    $stmt = $conn->prepare($filteredQuery);
    if (!empty($searchValue)) {
        $stmt->bindValue(':search', '%' . $searchValue . '%', PDO::PARAM_STR);
    }
    $stmt->execute();
    $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Main data query
    $dataQuery = "
        SELECT 
            al.id,
            al.action,
            al.user_type,
            al.user_id,
            al.details,
            al.ip_address,
            al.created_at,
            CASE 
                WHEN al.user_type = 'admin' THEN 
                    COALESCE((SELECT username FROM admin WHERE id = al.user_id), 'Unknown Admin')
                WHEN al.user_type = 'voter' THEN 
                    COALESCE((SELECT CONCAT(first_name, ' ', last_name) FROM voters WHERE id = al.user_id), 'Unknown Voter')
                ELSE 'System'
            END as user_name
        " . $baseQuery . $searchCondition . "
        ORDER BY " . $orderColumn . " " . $orderDir . "
        LIMIT :start, :length
    ";
    
    $stmt = $conn->prepare($dataQuery);
    if (!empty($searchValue)) {
        $stmt->bindValue(':search', '%' . $searchValue . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for DataTables
    $formattedData = [];
    foreach ($data as $row) {
        // Format action with badge
        $actionBadge = '';
        switch (strtolower($row['action'])) {
            case 'login':
                $actionBadge = '<span class="badge bg-success">' . htmlspecialchars($row['action']) . '</span>';
                break;
            case 'logout':
                $actionBadge = '<span class="badge bg-secondary">' . htmlspecialchars($row['action']) . '</span>';
                break;
            case 'vote':
                $actionBadge = '<span class="badge bg-primary">' . htmlspecialchars($row['action']) . '</span>';
                break;
            case 'delete':
            case 'remove':
                $actionBadge = '<span class="badge bg-danger">' . htmlspecialchars($row['action']) . '</span>';
                break;
            case 'create':
            case 'add':
                $actionBadge = '<span class="badge bg-info">' . htmlspecialchars($row['action']) . '</span>';
                break;
            case 'update':
            case 'edit':
                $actionBadge = '<span class="badge bg-warning">' . htmlspecialchars($row['action']) . '</span>';
                break;
            default:
                $actionBadge = '<span class="badge bg-light text-dark">' . htmlspecialchars($row['action']) . '</span>';
        }
        
        // Format user type with icon
        $userTypeIcon = '';
        switch (strtolower($row['user_type'])) {
            case 'admin':
                $userTypeIcon = '<i class="fas fa-user-shield text-danger"></i> Admin';
                break;
            case 'voter':
                $userTypeIcon = '<i class="fas fa-user text-primary"></i> Voter';
                break;
            default:
                $userTypeIcon = '<i class="fas fa-cog text-secondary"></i> System';
        }
        
        // Format date
        $formattedDate = date('M d, Y H:i:s', strtotime($row['created_at']));
        
        // Truncate details if too long
        $details = strlen($row['details']) > 100 ? 
            substr(htmlspecialchars($row['details']), 0, 100) . '...' : 
            htmlspecialchars($row['details']);
        
        $formattedData[] = [
            $row['id'],
            $actionBadge,
            $userTypeIcon,
            htmlspecialchars($row['user_name']) . ' (ID: ' . $row['user_id'] . ')',
            $details,
            htmlspecialchars($row['ip_address']),
            $formattedDate
        ];
    }
    
    // Prepare DataTables response
    $response = [
        'draw' => $draw,
        'recordsTotal' => (int)$totalRecords,
        'recordsFiltered' => (int)$filteredRecords,
        'data' => $formattedData
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>