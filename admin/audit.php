<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check admin role for UI restrictions
$current_admin_role = $_SESSION['admin_role'] ?? 'not set';
$is_super_admin = (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin');

$page_title = 'Audit Logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - VoteSystem Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Color Palette Variables */
        :root {
            --primary-blue: #4A6B8A;
            --success-green: #22C55E;
            --error-red: #DC2626;
            --neutral-light: #F9FAFB;
            --neutral-medium: #D1D5DB;
            --neutral-dark: #374151;
            --base-white: #FFFFFF;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--neutral-light);
            color: var(--neutral-dark);
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a5a7a 100%);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: var(--base-white);
            transform: translateX(3px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .main-content {
            background-color: var(--neutral-light);
            min-height: 100vh;
            padding: 24px;
        }

        .stats-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.95) 100%);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.1);
            border: 2px solid transparent;
            background-clip: padding-box;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3, #54a0ff);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .stats-card:hover::before {
            opacity: 0.1;
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 8px 16px rgba(0,0,0,0.1);
            border-color: rgba(74, 107, 138, 0.3);
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .stats-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--base-white);
            font-size: 28px;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }

        .stats-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s ease-in-out infinite;
        }

        .stats-card:hover .stats-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 30px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.4);
        }

        @keyframes shimmer {
             0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
             50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
             100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
         }

         @keyframes pulse {
             0% { 
                 box-shadow: 0 8px 20px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.3), 0 0 0 0 rgba(255,255,255,0.7);
             }
             100% { 
                 box-shadow: 0 8px 20px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.3), 0 0 0 10px rgba(255,255,255,0);
             }
         }

        .btn-custom {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary-custom {
            background: var(--primary-blue);
            color: var(--base-white);
        }

        .btn-primary-custom:hover {
            background: #3a5a7a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 107, 138, 0.3);
            color: var(--base-white);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: var(--base-white);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 107, 138, 0.3);
        }
        
        .btn-outline-danger {
            border-color: var(--error-red);
            color: var(--error-red);
        }
        
        .btn-outline-danger:hover {
            background: var(--error-red);
            border-color: var(--error-red);
            color: var(--base-white);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .table-container {
            background: var(--base-white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--neutral-medium);
        }

        .filter-section {
            background: var(--base-white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--neutral-medium);
        }

        .log-action {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.025em;
        }

        .log-create { 
            background: rgba(34, 197, 94, 0.1); 
            color: var(--success-green);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        .log-update { 
            background: rgba(74, 107, 138, 0.1); 
            color: var(--primary-blue);
            border: 1px solid rgba(74, 107, 138, 0.2);
        }
        .log-delete { 
            background: rgba(220, 38, 38, 0.1); 
            color: var(--error-red);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        .log-login { 
            background: rgba(245, 158, 11, 0.1); 
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        .log-export { 
            background: rgba(55, 65, 81, 0.1); 
            color: var(--neutral-dark);
            border: 1px solid rgba(55, 65, 81, 0.2);
        }

        .chart-container {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.98) 50%, rgba(240,245,251,0.95) 100%);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1), 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 24px;
            min-height: 400px;
            border: 3px solid transparent;
            background-clip: padding-box;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3, #54a0ff);
            background-size: 400% 400%;
            animation: gradientShift 10s ease infinite;
            opacity: 0.05;
            z-index: -1;
        }

        .chart-container:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.15), 0 4px 12px rgba(0,0,0,0.1);
            border-color: rgba(74, 107, 138, 0.2);
        }

        .chart-container:hover::before {
            opacity: 0.08;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            min-height: 300px;
        }

        .chart-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .modal-content {
            border-radius: 12px;
            border: 1px solid var(--neutral-medium);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .modal-header {
            border-bottom: 1px solid var(--neutral-medium);
            border-radius: 12px 12px 0 0;
            background: var(--base-white);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--neutral-medium);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .page-header {
            background: var(--base-white);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--neutral-medium);
        }

        .export-btn {
            margin-left: 5px;
        }

        .log-details {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .log-details:hover {
            overflow: visible;
            white-space: normal;
            word-wrap: break-word;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
            background: var(--primary-blue);
            color: var(--base-white);
            border: none;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: #1d4ed8;
            transform: scale(1.05);
        }

        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-overlay.show {
            opacity: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1045;
                transition: left 0.3s ease;
                overflow-y: auto;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 80px 15px 20px;
            }

            .page-header {
                padding: 20px;
                margin-bottom: 15px;
            }

            .page-header .row {
                flex-direction: column;
                gap: 15px;
            }

            .page-header .col-auto {
                align-self: stretch;
            }

            .page-header .btn {
                width: 100%;
                margin-bottom: 8px;
            }

            .export-btn {
                margin-left: 0;
            }

            .stats-card {
                margin-bottom: 15px;
                padding: 20px;
            }

            .stats-icon {
                width: 48px;
                height: 48px;
                font-size: 18px;
            }

            .chart-container {
                padding: 20px;
                margin-bottom: 15px;
            }

            .filter-section {
                padding: 15px;
            }

            .filter-section .row {
                gap: 10px;
            }

            .filter-section .col-md-3,
            .filter-section .col-md-2,
            .filter-section .col-md-1 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .table-container {
                padding: 15px;
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
            }

            .modal-dialog {
                margin: 10px;
            }

            .modal-content {
                border-radius: 8px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 70px 10px 15px;
            }

            .page-header {
                padding: 15px;
            }

            .page-header h2 {
                font-size: 1.5rem;
            }

            .stats-card {
                padding: 15px;
            }

            .stats-card h3 {
                font-size: 1.5rem;
            }

            .chart-container {
                padding: 15px;
            }

            .chart-container h5 {
                font-size: 1rem;
            }

            .filter-section {
                padding: 12px;
            }

            .table-container {
                padding: 12px;
            }

            .btn-custom {
                padding: 10px 16px;
                font-size: 14px;
            }

            .form-control,
            .form-select {
                padding: 10px 12px;
                font-size: 14px;
            }
        }

        /* Tablet Responsive */
        @media (min-width: 769px) and (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                padding: 20px;
            }

            .stats-card {
                padding: 20px;
            }

            .chart-container {
                padding: 20px;
            }

            .page-header {
                padding: 20px;
            }

            .filter-section .col-md-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .filter-section .col-md-2 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-vote-yea me-2"></i>VoteSystem
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="elections.php">
                            <i class="fas fa-calendar-alt me-2"></i>Elections
                        </a>
                        <a class="nav-link" href="candidates.php">
                            <i class="fas fa-users me-2"></i>Candidates
                        </a>
                        <a class="nav-link" href="voters.php">
                            <i class="fas fa-user-check me-2"></i>Voters
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users-cog me-2"></i>Users
                        </a>
                        <a class="nav-link" href="monitoring.php">
                            <i class="fas fa-chart-line me-2"></i>Live Monitoring
                        </a>
                        <a class="nav-link" href="results.php">
                            <i class="fas fa-poll me-2"></i>Results
                        </a>
                        <a class="nav-link active" href="audit.php">
                            <i class="fas fa-clipboard-list me-2"></i>Audit Logs
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-0">
                                <i class="fas fa-clipboard-list me-2 text-primary"></i>Audit Logs
                            </h2>
                            <p class="text-muted mb-0">Monitor and track all administrative activities</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary-custom btn-custom" onclick="exportLogs('csv')">
                                <i class="fas fa-download me-2"></i>Export CSV
                            </button>
                            <button class="btn btn-outline-primary btn-custom export-btn" onclick="exportLogs('json')">
                                <i class="fas fa-file-code me-2"></i>Export JSON
                            </button>
                            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin'): ?>
                            <button class="btn btn-outline-danger btn-custom export-btn" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                                <i class="fas fa-trash me-2"></i>Clear Old Logs
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>



                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); animation: pulse 2s ease-in-out infinite alternate;">
                                    <i class="fas fa-list"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="totalLogs">-</h3>
                                    <p class="text-muted mb-0">Total Logs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 50%, #7fcdcd 100%); animation: pulse 2s ease-in-out infinite alternate 0.5s;">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="logsToday">-</h3>
                                    <p class="text-muted mb-0">Today's Logs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%); animation: pulse 2s ease-in-out infinite alternate 1s;">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="uniqueAdmins">-</h3>
                                    <p class="text-muted mb-0">Active Admins</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 50%, #d299c2 100%); animation: pulse 2s ease-in-out infinite alternate 1.5s;">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="topAction">-</h3>
                                    <p class="text-muted mb-0">Top Action</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container">
                            <h5 class="mb-3">Activity Timeline (Last 7 Days)</h5>
                            <div class="chart-wrapper" style="position: relative; height: 300px;">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container">
                            <h5 class="mb-3">Action Distribution</h5>
                            <div class="chart-wrapper" style="position: relative; height: 300px;">
                                <canvas id="actionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search logs...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action Filter</label>
                            <select class="form-select" id="actionFilter">
                                <option value="">All Actions</option>
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="delete">Delete</option>
                                <option value="login">Login</option>
                                <option value="export">Export</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Admin Filter</label>
                            <select class="form-select" id="adminFilter">
                                <option value="">All Admins</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" id="dateFrom">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" id="dateTo">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary-custom btn-custom w-100" onclick="applyFilters()">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Audit Logs</h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshLogs()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="auditTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                    <th>Date/Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin'): ?>
    <!-- Clear Old Logs Modal -->
    <div class="modal fade" id="clearLogsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2 text-danger"></i>Clear Old Logs
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action will permanently delete old audit logs and cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delete logs older than:</label>
                        <select class="form-select" id="clearDays">
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90" selected>90 days</option>
                            <option value="180">180 days</option>
                            <option value="365">1 year</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="clearOldLogs()">Clear Logs</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let auditTable;
        let activityChart;
        let actionChart;

        $(document).ready(function() {
            initializeDataTable();
            loadLogStats();
            loadAdminList();
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                refreshLogs();
                loadLogStats();
            }, 30000);
        });

        function initializeDataTable() {
            auditTable = $('#auditTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'api/audit.php?action=get_logs',
                    type: 'GET',
                    data: function(d) {
                        d.search = $('#searchInput').val();
                        d.action_filter = $('#actionFilter').val();
                        d.admin_filter = $('#adminFilter').val();
                        d.date_from = $('#dateFrom').val();
                        d.date_to = $('#dateTo').val();
                        d.page = Math.floor(d.start / d.length) + 1;
                        d.limit = d.length;
                    },
                    dataSrc: function(json) {
                        if (json.success) {
                            return json.data.logs;
                        } else {
                            showAlert('Error loading logs: ' + json.message, 'danger');
                            return [];
                        }
                    }
                },
                columns: [
                    { data: 'id' },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return `<div>
                                <strong>${row.admin_name || 'Unknown'}</strong><br>
                                <small class="text-muted">${row.admin_email || 'Unknown'}</small>
                            </div>`;
                        }
                    },
                    { 
                        data: 'action',
                        render: function(data, type, row) {
                            let className = 'log-action';
                            if (data.toLowerCase().includes('create')) className += ' log-create';
                            else if (data.toLowerCase().includes('update')) className += ' log-update';
                            else if (data.toLowerCase().includes('delete')) className += ' log-delete';
                            else if (data.toLowerCase().includes('login')) className += ' log-login';
                            else if (data.toLowerCase().includes('export')) className += ' log-export';
                            
                            return `<span class="${className}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'details',
                        render: function(data, type, row) {
                            return `<div class="log-details" title="${data}">${data}</div>`;
                        }
                    },
                    { data: 'ip_address' },
                    { 
                        data: 'created_at',
                        render: function(data, type, row) {
                            return new Date(data).toLocaleString('en-US', {timeZone: 'UTC'}) + ' UTC';
                        }
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true,
                language: {
                    processing: '<i class="fas fa-spinner fa-spin"></i> Loading logs...'
                }
            });
        }

        function loadLogStats() {
            $.get('api/audit.php?action=get_log_stats')
                .done(function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        $('#totalLogs').text(data.total_logs.toLocaleString());
                        $('#logsToday').text(data.logs_today.toLocaleString());
                        $('#uniqueAdmins').text(data.unique_admins.toLocaleString());
                        
                        if (data.common_actions.length > 0) {
                            $('#topAction').text(data.common_actions[0].action);
                        }
                        
                        updateCharts(data);
                    }
                })
                .fail(function() {
                    showAlert('Failed to load statistics', 'danger');
                });
        }

        function updateCharts(data) {
            // Activity Timeline Chart
            const activityCanvas = document.getElementById('activityChart');
            if (!activityCanvas) {
                console.error('Activity chart canvas not found');
                return;
            }
            
            const activityCtx = activityCanvas.getContext('2d');
            
            if (activityChart) {
                activityChart.destroy();
            }
            
            // Handle empty data gracefully
            let activityLabels = [];
            let activityData = [];
            
            if (data.recent_activity && data.recent_activity.length > 0) {
                activityLabels = data.recent_activity.map(item => 
                    new Date(item.log_date).toLocaleDateString()
                ).reverse();
                activityData = data.recent_activity.map(item => item.count).reverse();
            } else {
                // Provide default data for empty state
                activityLabels = ['No Data'];
                activityData = [0];
            }
            
            try {
                activityChart = new Chart(activityCtx, {
                    type: 'line',
                    data: {
                        labels: activityLabels,
                        datasets: [{
                            label: 'Daily Activity',
                            data: activityData,
                            borderColor: '#4A6B8A',
                            backgroundColor: 'rgba(74, 107, 138, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating activity chart:', error);
                showAlert('Failed to create activity chart', 'warning');
            }
            
            // Action Distribution Chart
            const actionCanvas = document.getElementById('actionChart');
            if (!actionCanvas) {
                console.error('Action chart canvas not found');
                return;
            }
            
            const actionCtx = actionCanvas.getContext('2d');
            
            if (actionChart) {
                actionChart.destroy();
            }
            
            // Handle empty data gracefully
            let actionLabels = [];
            let actionCounts = [];
            
            if (data.common_actions && data.common_actions.length > 0) {
                actionLabels = data.common_actions.map(item => item.action);
                actionCounts = data.common_actions.map(item => item.count);
            } else {
                // Provide default data for empty state
                actionLabels = ['No Data'];
                actionCounts = [1];
            }
            
            try {
                actionChart = new Chart(actionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: actionLabels,
                        datasets: [{
                            data: actionCounts,
                            backgroundColor: [
                                '#4A6B8A',
                                '#3a5a7a',
                                '#5a7a9a',
                                '#6a8aaa',
                                '#7a9aba'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating action chart:', error);
                showAlert('Failed to create action chart', 'warning');
            }
        }

        function loadAdminList() {
            $.get('api/audit.php?action=get_log_stats')
                .done(function(response) {
                    if (response.success && response.data.admin_activity) {
                        const adminSelect = $('#adminFilter');
                        adminSelect.find('option:not(:first)').remove();
                        
                        response.data.admin_activity.forEach(function(admin) {
                            adminSelect.append(`<option value="${admin.admin_id}">${admin.full_name}</option>`);
                        });
                    }
                });
        }

        function applyFilters() {
            auditTable.ajax.reload();
        }

        function refreshLogs() {
            auditTable.ajax.reload(null, false);
        }

        function exportLogs(format) {
            const dateFrom = $('#dateFrom').val();
            const dateTo = $('#dateTo').val();
            
            let url = `api/audit.php?action=export_logs&format=${format}`;
            if (dateFrom) url += `&date_from=${dateFrom}`;
            if (dateTo) url += `&date_to=${dateTo}`;
            
            $.get(url)
                .done(function(response) {
                    if (response.success) {
                        if (format === 'csv') {
                            // Download CSV file
                            const link = document.createElement('a');
                            link.href = response.data.download_url;
                            link.download = response.data.filename;
                            link.click();
                            
                            showAlert(`Successfully exported ${response.data.record_count} records`, 'success');
                        } else {
                            // Download JSON data
                            const dataStr = JSON.stringify(response.data, null, 2);
                            const dataBlob = new Blob([dataStr], {type: 'application/json'});
                            const url = URL.createObjectURL(dataBlob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = `audit_logs_${new Date().toISOString().split('T')[0]}.json`;
                            link.click();
                            
                            showAlert(`Successfully exported ${response.data.record_count} records`, 'success');
                        }
                    } else {
                        showAlert('Export failed: ' + response.message, 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Export failed', 'danger');
                });
        }

        function clearOldLogs() {
            const days = $('#clearDays').val();
            
            if (!confirm(`Are you sure you want to delete all logs older than ${days} days? This action cannot be undone.`)) {
                return;
            }
            
            $.post('api/audit.php?action=clear_old_logs', { days: days })
                .done(function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        $('#clearLogsModal').modal('hide');
                        refreshLogs();
                        loadLogStats();
                    } else {
                        showAlert('Failed to clear logs: ' + response.message, 'danger');
                    }
                })
                .fail(function() {
                    showAlert('Failed to clear logs', 'danger');
                });
        }

        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('.main-content').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }

        // Filter inputs event handlers
        $('#searchInput').on('keyup', function() {
            clearTimeout(this.delay);
            this.delay = setTimeout(function() {
                applyFilters();
            }, 500);
        });

        $('#actionFilter, #adminFilter, #dateFrom, #dateTo').on('change', function() {
            applyFilters();
        });

        // Mobile Menu Functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.querySelector('.sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const navLinks = document.querySelectorAll('.sidebar .nav-link');

        // Toggle mobile menu
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mobileOverlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
            if (sidebar.classList.contains('show')) {
                setTimeout(() => mobileOverlay.classList.add('show'), 10);
            } else {
                mobileOverlay.classList.remove('show');
            }
        });

        // Close menu when overlay is clicked
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            mobileOverlay.classList.remove('show');
            setTimeout(() => mobileOverlay.style.display = 'none', 300);
        });

        // Close menu when navigation link is clicked (mobile)
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                    mobileOverlay.classList.remove('show');
                    setTimeout(() => mobileOverlay.style.display = 'none', 300);
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                mobileOverlay.classList.remove('show');
                mobileOverlay.style.display = 'none';
            }
        });
    </script>
</body>
</html>