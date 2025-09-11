<?php
define('SECURE_ACCESS', true);
require_once '../../config/database.php';
require_once '../../config/session.php';

// Direct access allowed - no login check required

$pdo = getDBConnection();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_logs':
            getAuditLogs();
            break;
        case 'get_log_stats':
            getLogStats();
            break;
        case 'export_logs':
            exportLogs();
            break;
        case 'clear_old_logs':
            clearOldLogs();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getAuditLogs() {
    global $pdo;
    
    // Get pagination parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(10, intval($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $action_filter = $_GET['action_filter'] ?? '';
    $admin_filter = $_GET['admin_filter'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    try {
        // Build WHERE clause
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(al.action LIKE ? OR al.details LIKE ? OR a.name LIKE ?)";
            $search_param = '%' . $search . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        if (!empty($action_filter)) {
            $where_conditions[] = "al.action LIKE ?";
            $params[] = '%' . $action_filter . '%';
        }
        
        if (!empty($admin_filter)) {
            $where_conditions[] = "al.admin_id = ?";
            $params[] = $admin_filter;
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(al.created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(al.created_at) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get total count
        $count_sql = "
            SELECT COUNT(*) as total
            FROM audit_logs al
            LEFT JOIN admin a ON al.admin_id = a.id
            $where_clause
        ";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetch()['total'];
        
        // Get logs with pagination
        $sql = "
            SELECT 
                al.id,
                al.action,
                CONCAT(COALESCE(al.old_values, ''), ' -> ', COALESCE(al.new_values, '')) as details,
                al.ip_address,
                al.created_at,
                a.username as admin_name,
                a.email as admin_email
            FROM audit_logs al
            LEFT JOIN admin a ON al.admin_id = a.id
            $where_clause
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        // Calculate pagination info
        $total_pages = ceil($total_records / $limit);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'logs' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_records' => $total_records,
                    'per_page' => $limit,
                    'has_next' => $page < $total_pages,
                    'has_prev' => $page > 1
                ]
            ]
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getLogStats() {
    global $pdo;
    
    try {
        // Get total logs count
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_logs FROM audit_logs");
        $stmt->execute();
        $total_logs = $stmt->fetch()['total_logs'];
        
        // Get logs today
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as logs_today 
            FROM audit_logs 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        $logs_today = $stmt->fetch()['logs_today'];
        
        // Get unique admins
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT admin_id) as unique_admins 
            FROM audit_logs
        ");
        $stmt->execute();
        $unique_admins = $stmt->fetch()['unique_admins'];
        
        // Get most common actions
        $stmt = $pdo->prepare("
            SELECT 
                action,
                COUNT(*) as count
            FROM audit_logs 
            GROUP BY action 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $common_actions = $stmt->fetchAll();
        
        // Get recent activity (last 7 days)
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as log_date,
                COUNT(*) as count
            FROM audit_logs 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY log_date DESC
        ");
        $stmt->execute();
        $recent_activity = $stmt->fetchAll();
        
        // Get admin activity
        $stmt = $pdo->prepare("
            SELECT 
                a.username,
                COUNT(al.id) as log_count,
                MAX(al.created_at) as last_activity
            FROM audit_logs al
            LEFT JOIN admin a ON al.admin_id = a.id
            GROUP BY al.admin_id, a.username
            ORDER BY log_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $admin_activity = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_logs' => $total_logs,
                'logs_today' => $logs_today,
                'unique_admins' => $unique_admins,
                'common_actions' => $common_actions,
                'recent_activity' => $recent_activity,
                'admin_activity' => $admin_activity
            ]
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function exportLogs() {
    global $pdo;
    
    $format = $_GET['format'] ?? 'csv';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    try {
        // Build WHERE clause for date filtering
        $where_conditions = [];
        $params = [];
        
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(al.created_at) >= ?";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(al.created_at) <= ?";
            $params[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get logs data
        $sql = "
            SELECT 
                al.id,
                al.action,
                CONCAT(COALESCE(al.old_values, ''), ' -> ', COALESCE(al.new_values, '')) as details,
                al.ip_address,
                al.created_at,
                a.username as admin_name,
                a.email as admin_email
            FROM audit_logs al
            LEFT JOIN admin a ON al.admin_id = a.id
            $where_clause
            ORDER BY al.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        if ($format === 'csv') {
            // Generate CSV
            $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = '../../uploads/exports/' . $filename;
            
            // Create exports directory if it doesn't exist
            if (!file_exists('../../uploads/exports/')) {
                mkdir('../../uploads/exports/', 0755, true);
            }
            
            $file = fopen($filepath, 'w');
            
            // Write header
            fputcsv($file, ['ID', 'Admin Name', 'Admin Email', 'Action', 'Details', 'IP Address', 'Date/Time']);
            
            // Write data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log['id'],
                    $log['admin_name'] ?: 'Unknown',
                    $log['admin_email'] ?: 'Unknown',
                    $log['action'],
                    $log['details'],
                    $log['ip_address'],
                    $log['created_at']
                ]);
            }
            
            fclose($file);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'download_url' => '../uploads/exports/' . $filename,
                    'record_count' => count($logs)
                ]
            ]);
        } else {
            // Return JSON data
            echo json_encode([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'exported_at' => date('Y-m-d H:i:s'),
                    'record_count' => count($logs)
                ]
            ]);
        }
        
        // Log admin action
        logAdminAction('Export Audit Logs', 'Exported ' . count($logs) . ' audit log records');
        
    } catch (Exception $e) {
        throw $e;
    }
}

function clearOldLogs() {
    global $pdo;
    
    // Check if user is logged in and has super_admin role
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Only super administrators can clear audit logs.']);
        return;
    }
    
    $days = intval($_POST['days'] ?? 90); // Default to 90 days
    
    if ($days < 30) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete logs newer than 30 days']);
        return;
    }
    
    try {
        // Count logs to be deleted
        $count_stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM audit_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $count_stmt->execute([$days]);
        $count = $count_stmt->fetch()['count'];
        
        if ($count > 0) {
            // Delete old logs
            $delete_stmt = $pdo->prepare("
                DELETE FROM audit_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $delete_stmt->execute([$days]);
            
            // Log admin action
            logAdminAction('Clear Old Audit Logs', "Deleted $count audit log records older than $days days");
            
            echo json_encode([
                'success' => true,
                'message' => "Successfully deleted $count old audit log records",
                'deleted_count' => $count
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'No old logs found to delete',
                'deleted_count' => 0
            ]);
        }
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
            $_SESSION['admin_id'] ?? null,
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