<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Direct access allowed - no login check required

$page_title = 'Dashboard';
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    
    <style>
        :root {
            --primary-color: #4A6B8A;
            --secondary-color: #3a5a7a;
            --success-color: #22C55E;
            --warning-color: #F59E0B;
            --danger-color: #DC2626;
            --light-bg: #F9FAFB;
            --neutral-light: #D1D5DB;
            --neutral-medium: #9CA3AF;
            --dark-text: #374151;
            --darker-text: #1F2937;
            --white: #FFFFFF;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            box-shadow: 2px 0 15px rgba(37, 99, 235, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 14px 20px;
            margin: 3px 0;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--white);
            background-color: rgba(255,255,255,0.15);
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(255,255,255,0.1);
        }

        .main-content {
            padding: 20px;
        }

        .stats-card {
            background: var(--white);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.12);
            border-color: rgba(37, 99, 235, 0.2);
        }

        .stats-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: var(--white);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .chart-container {
            background: var(--white);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
            margin-bottom: 24px;
            height: 400px;
        }

        .chart-container.small {
            height: 300px;
        }

        .page-header {
            background: var(--white);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
        }

        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--success-color);
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin-right: 10px;
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.4);
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .recent-activity {
            background: var(--white);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
            height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 14px 0;
            border-bottom: 1px solid var(--neutral-light);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
        }

        .quick-stats {
            background: var(--white);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
            margin-bottom: 24px;
        }

        .stat-item {
            text-align: center;
            padding: 18px;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            color: var(--neutral-medium);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .refresh-btn {
             position: absolute;
             top: 18px;
             right: 18px;
             border: 1px solid var(--neutral-light);
             background: var(--white);
             color: var(--primary-color);
             border-radius: 10px;
             padding: 10px 14px;
             transition: all 0.3s ease;
             font-weight: 500;
         }

         .refresh-btn:hover {
             background: var(--primary-color);
             color: var(--white);
             border-color: var(--primary-color);
             box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
         }

         /* Notification Styles */
         .notification-container {
             position: fixed;
             top: 20px;
             right: 20px;
             z-index: 1060;
             max-width: 350px;
         }

         .notification {
             background: var(--white);
             border-radius: 12px;
             padding: 16px;
             margin-bottom: 10px;
             box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
             border-left: 4px solid var(--success-color);
             animation: slideInRight 0.3s ease;
             cursor: pointer;
         }

         .notification.warning {
             border-left-color: var(--warning-color);
         }

         .notification.error {
             border-left-color: var(--danger-color);
         }

         @keyframes slideInRight {
             from {
                 transform: translateX(100%);
                 opacity: 0;
             }
             to {
                 transform: translateX(0);
                 opacity: 1;
             }
         }

         /* Filter Container */
         .filter-container {
             background: var(--white);
             border-radius: 16px;
             padding: 20px;
             box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
             border: 1px solid var(--neutral-light);
         }

         /* Dark Mode Styles */
         .dark-mode {
             --light-bg: #1a1a1a;
             --white: #2d2d2d;
             --dark-text: #e5e5e5;
             --darker-text: #ffffff;
             --neutral-light: #404040;
             --neutral-medium: #666666;
         }

         .dark-mode body {
             background-color: var(--light-bg);
             color: var(--dark-text);
         }

         .dark-mode .stats-card,
         .dark-mode .chart-container,
         .dark-mode .page-header,
         .dark-mode .recent-activity,
         .dark-mode .quick-stats,
         .dark-mode .election-card,
         .dark-mode .filter-container {
             background: var(--white);
             color: var(--dark-text);
         }

         /* Enhanced Animation */
         .stats-card {
             animation: fadeInUp 0.6s ease;
         }

         .chart-container {
             animation: fadeInUp 0.8s ease;
         }

         @keyframes fadeInUp {
             from {
                 opacity: 0;
                 transform: translateY(30px);
             }
             to {
                 opacity: 1;
                 transform: translateY(0);
             }
         }

        .election-card {
            background: var(--white);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
            margin-bottom: 18px;
            border-left: 4px solid var(--primary-color);
        }

        .election-status {
            padding: 6px 14px;
            border-radius: 24px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active { 
            background-color: rgba(34, 197, 94, 0.1); 
            color: var(--success-color);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        .status-upcoming { 
            background-color: rgba(245, 158, 11, 0.1); 
            color: var(--warning-color);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        .status-completed { 
             background-color: var(--neutral-light); 
             color: var(--dark-text);
             border: 1px solid var(--neutral-medium);
         }

         /* Button Styling */
         .btn {
             border-radius: 10px;
             font-weight: 500;
             padding: 10px 20px;
             transition: all 0.3s ease;
             border: 1px solid transparent;
         }

         .btn-primary {
             background-color: var(--primary-color);
             border-color: var(--primary-color);
             color: var(--white);
         }

         .btn-primary:hover {
             background-color: #3a5a7a;
             border-color: #3a5a7a;
             box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
         }

         .btn-success {
             background-color: var(--success-color);
             border-color: var(--success-color);
             color: var(--white);
         }

         .btn-success:hover {
             background-color: #16a34a;
             border-color: #16a34a;
             box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
         }

         .btn-danger {
             background-color: var(--error-color);
             border-color: var(--error-color);
             color: var(--white);
         }

         .btn-danger:hover {
             background-color: #b91c1c;
             border-color: #b91c1c;
             box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
         }

         .btn-outline-primary {
             background-color: transparent;
             border-color: var(--primary-color);
             color: var(--primary-color);
         }

         .btn-outline-primary:hover {
             background-color: var(--primary-color);
             border-color: var(--primary-color);
             color: var(--white);
         }

         /* Mobile Navigation */
         .mobile-nav-toggle {
             display: none;
             position: fixed;
             top: 20px;
             left: 20px;
             z-index: 1070;
             background: var(--primary-color);
             color: var(--white);
             border: none;
             border-radius: 12px;
             padding: 12px;
             box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
             transition: all 0.3s ease;
         }

         .mobile-nav-toggle:hover {
             background: var(--secondary-color);
             transform: scale(1.05);
         }

         .sidebar-overlay {
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

         .sidebar-overlay.show {
             opacity: 1;
         }

         /* Responsive Design */
         @media (max-width: 1200px) {
             .main-content {
                 padding: 15px;
             }

             .stats-card {
                 padding: 20px;
             }

             .chart-container {
                 padding: 20px;
                 height: 350px;
             }

             .chart-container.small {
                 height: 280px;
             }
         }

         @media (max-width: 992px) {
             .sidebar {
                 position: fixed;
                 top: 0;
                 left: -280px;
                 width: 280px;
                 z-index: 1050;
                 transition: left 0.3s ease;
             }

             .sidebar.show {
                 left: 0;
             }

             .mobile-nav-toggle {
                 display: block;
             }

             .main-content {
                 margin-left: 0;
                 padding: 80px 15px 15px;
             }

             .stats-card {
                 margin-bottom: 20px;
                 padding: 18px;
             }

             .chart-container {
                 height: 320px;
                 padding: 18px;
                 margin-bottom: 20px;
             }

             .chart-container.small {
                 height: 260px;
             }

             .page-header {
                 padding: 20px;
                 margin-bottom: 20px;
             }

             .recent-activity {
                 height: 350px;
                 padding: 20px;
             }

             .quick-stats {
                 padding: 18px;
             }

             .stat-number {
                 font-size: 1.8rem;
             }

             .notification-container {
                 top: 80px;
                 right: 15px;
                 left: 15px;
                 max-width: none;
             }

             .filter-container {
                 padding: 15px;
             }
         }

         @media (max-width: 768px) {
             .main-content {
                 padding: 70px 10px 10px;
             }

             .stats-card {
                 padding: 15px;
                 margin-bottom: 15px;
             }

             .chart-container {
                 height: 280px;
                 padding: 15px;
                 margin-bottom: 15px;
             }

             .chart-container.small {
                 height: 240px;
             }

             .page-header {
                 padding: 15px;
                 margin-bottom: 15px;
             }

             .page-header h1 {
                 font-size: 1.5rem;
             }

             .recent-activity {
                 height: 300px;
                 padding: 15px;
             }

             .quick-stats {
                 padding: 15px;
             }

             .stat-item {
                 padding: 12px;
             }

             .stat-number {
                 font-size: 1.5rem;
             }

             .stat-label {
                 font-size: 0.8rem;
             }

             .election-card {
                 padding: 18px;
                 margin-bottom: 15px;
             }

             .activity-item {
                 padding: 10px 0;
             }

             .activity-icon {
                 width: 30px;
                 height: 30px;
                 font-size: 12px;
             }

             .refresh-btn {
                 padding: 8px 12px;
                 font-size: 0.9rem;
             }

             .filter-container {
                 padding: 12px;
             }

             .btn {
                 padding: 8px 16px;
                 font-size: 0.9rem;
             }
         }

         @media (max-width: 576px) {
             .mobile-nav-toggle {
                 top: 15px;
                 left: 15px;
                 padding: 10px;
             }

             .main-content {
                 padding: 65px 8px 8px;
             }

             .stats-card {
                 padding: 12px;
                 margin-bottom: 12px;
             }

             .chart-container {
                 height: 250px;
                 padding: 12px;
                 margin-bottom: 12px;
             }

             .chart-container.small {
                 height: 220px;
             }

             .page-header {
                 padding: 12px;
                 margin-bottom: 12px;
             }

             .page-header h1 {
                 font-size: 1.3rem;
             }

             .recent-activity {
                 height: 280px;
                 padding: 12px;
             }

             .quick-stats {
                 padding: 12px;
             }

             .stat-item {
                 padding: 8px;
             }

             .stat-number {
                 font-size: 1.3rem;
             }

             .stat-label {
                 font-size: 0.75rem;
             }

             .election-card {
                 padding: 15px;
                 margin-bottom: 12px;
             }

             .activity-item {
                 padding: 8px 0;
             }

             .activity-icon {
                 width: 28px;
                 height: 28px;
                 font-size: 11px;
             }

             .refresh-btn {
                 padding: 6px 10px;
                 font-size: 0.8rem;
                 top: 12px;
                 right: 12px;
             }

             .filter-container {
                 padding: 10px;
             }

             .btn {
                 padding: 6px 12px;
                 font-size: 0.8rem;
             }

             .notification-container {
                 top: 70px;
                 right: 8px;
                 left: 8px;
             }

             .notification {
                 padding: 12px;
                 font-size: 0.9rem;
             }

             .stats-icon {
                 width: 48px;
                 height: 48px;
                 font-size: 20px;
             }

             .sidebar {
                 width: 260px;
                 left: -260px;
             }

             .sidebar .nav-link {
                 padding: 12px 16px;
                 font-size: 0.9rem;
             }
         }

         /* Touch-friendly improvements */
         @media (hover: none) and (pointer: coarse) {
             .stats-card:hover {
                 transform: none;
             }

             .btn {
                 min-height: 44px;
             }

             .refresh-btn {
                 min-height: 40px;
                 min-width: 40px;
             }

             .mobile-nav-toggle {
                 min-height: 44px;
                 min-width: 44px;
             }

             .sidebar .nav-link {
                 min-height: 48px;
             }
         }

         /* Print styles */
         @media print {
             .sidebar,
             .mobile-nav-toggle,
             .refresh-btn,
             .notification-container {
                 display: none !important;
             }

             .main-content {
                 margin-left: 0 !important;
                 padding: 0 !important;
             }

             .stats-card,
             .chart-container,
             .page-header {
                 box-shadow: none !important;
                 border: 1px solid #ddd !important;
                 break-inside: avoid;
             }
         }

         /* Page header - Reduce padding */
         @media (max-width: 768px) {
             .page-header {
                 padding: 20px;
             }

             .page-header h2 {
                 font-size: 1.5rem;
             }

             /* Quick stats - Stack items */
             .quick-stats .row {
                 flex-direction: column;
             }

             .quick-stats .stat-item {
                 padding: 12px;
                 border-bottom: 1px solid var(--neutral-light);
             }

             .quick-stats .stat-item:last-child {
                 border-bottom: none;
             }

             /* Recent activity - Reduce height */
             .recent-activity {
                 height: auto;
                 max-height: 400px;
             }

             /* Refresh buttons - Smaller on mobile */
             .refresh-btn {
                 padding: 8px 10px;
                 font-size: 0.9rem;
             }
         }

         @media (max-width: 576px) {
             .main-content {
                 padding: 80px 10px 15px;
             }

             .chart-container {
                 height: 250px;
                 padding: 15px;
             }

             .chart-container.small {
                 height: 200px;
             }

             .stats-card {
                 padding: 20px;
             }

             .stats-icon {
                 width: 50px;
                 height: 50px;
                 font-size: 20px;
             }

             .page-header {
                 padding: 15px;
             }

             .page-header h2 {
                 font-size: 1.3rem;
             }

             .stat-number {
                 font-size: 1.8rem;
             }

             /* Hide some elements on very small screens */
             .live-indicator + small {
                 display: none;
             }
         }
     </style>
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle" id="mobileNavToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-vote-yea me-2"></i>VoteSystem
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="elections.php">
                            <i class="fas fa-calendar-alt me-2"></i>Elections
                        </a>
                        <a class="nav-link" href="candidates.php">
                            <i class="fas fa-users me-2"></i>Candidates
                        </a>
                        <a class="nav-link" href="positions.php">
                            <i class="fas fa-list me-2"></i>Positions
                        </a>
                        <a class="nav-link" href="voters.php">
                            <i class="fas fa-user-check me-2"></i>Voters
                        </a>
                        <a class="nav-link" href="reset_voter_passwords.php" style="padding-left: 3rem; font-size: 0.9rem;">
                            <i class="fas fa-key me-2"></i>Reset Passwords
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-user-cog me-2"></i>User Management
                        </a>
                        <a class="nav-link" href="monitoring.php">
                            <i class="fas fa-chart-line me-2"></i>Live Monitoring
                        </a>
                        <a class="nav-link" href="results.php">
                            <i class="fas fa-poll me-2"></i>Results
                        </a>
                        <a class="nav-link" href="audit.php">
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
                                <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
                                <span class="live-indicator"></span>
                                <small class="text-muted">Live</small>
                            </h2>
                            <p class="text-muted mb-0">Welcome back, <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin'; ?>!</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="totalElections">-</h3>
                                    <p class="text-muted mb-0">Total Elections</p>
                                    <small class="text-success" id="activeElections">- Active</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="totalCandidates">-</h3>
                                    <p class="text-muted mb-0">Total Candidates</p>
                                    <small class="text-info" id="activeCandidates">- Active</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, var(--success-color), #16a34a);">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="totalVoters">-</h3>
                                    <p class="text-muted mb-0">Registered Voters</p>
                                    <small class="text-warning" id="votedToday">- Voted Today</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                                    <i class="fas fa-poll"></i>
                                </div>
                                <div class="ms-3">
                                    <h3 class="mb-0" id="totalVotes">-</h3>
                                    <p class="text-muted mb-0">Total Votes</p>
                                    <small class="text-primary" id="votesToday">- Today</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Real-time Notifications -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div id="notificationContainer" class="notification-container">
                            <!-- Real-time notifications will appear here -->
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="filter-container">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">Dashboard Analytics</h5>
                                    <small class="text-muted">Filter data by date range</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <select id="dateRangeFilter" class="form-select form-select-sm" style="width: auto;">
                                            <option value="today">Today</option>
                                            <option value="week" selected>This Week</option>
                                            <option value="month">This Month</option>
                                            <option value="year">This Year</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" onclick="exportDashboardData()">
                                            <i class="fas fa-download me-1"></i>Export
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" id="darkModeToggle">
                                            <i class="fas fa-moon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container position-relative">
                            <button class="refresh-btn" onclick="loadVotingActivity()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <h5 class="mb-3">Real-Time Voting Activity</h5>
                            <canvas id="votingActivityChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="chart-container small position-relative">
                            <button class="refresh-btn" onclick="loadTurnoutStats()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <h5 class="mb-3">Voter Turnout</h5>
                            <canvas id="turnoutChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Live Vote Tallies and Recent Activity -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="chart-container position-relative">
                            <button class="refresh-btn" onclick="loadLiveVoteTallies()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <h5 class="mb-3">
                                <span class="live-indicator"></span>
                                Live Vote Tallies by Election
                            </h5>
                            <canvas id="liveTalliesChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="recent-activity">
                            <h5 class="mb-3">Recent Activity</h5>
                            <div id="recentActivityList">
                                <!-- Activity items will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Charts Row - Election Performance & Demographics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container position-relative">
                            <button class="refresh-btn" onclick="loadElectionPerformance()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <h5 class="mb-3">Election Performance Comparison</h5>
                            <canvas id="electionPerformanceChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container position-relative">
                            <button class="refresh-btn" onclick="loadVoterDemographics()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <h5 class="mb-3">Voter Demographics</h5>
                            <canvas id="voterDemographicsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Position-wise Vote Distribution -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="chart-container position-relative">
                            <button class="refresh-btn" onclick="loadPositionVoteDistribution()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <h5 class="mb-3">Position-wise Vote Distribution</h5>
                            <canvas id="positionVoteChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Election Status and Quick Stats -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="quick-stats">
                            <h5 class="mb-3">Quick Statistics</h5>
                            <div class="row">
                                <div class="col-4 stat-item">
                                    <div class="stat-number" id="avgTurnout">-</div>
                                    <div class="stat-label">Avg Turnout</div>
                                </div>
                                <div class="col-4 stat-item">
                                    <div class="stat-number" id="peakHour">-</div>
                                    <div class="stat-label">Peak Hour</div>
                                </div>
                                <div class="col-4 stat-item">
                                    <div class="stat-number" id="activeNow">-</div>
                                    <div class="stat-label">Active Now</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chart-container small position-relative">
                            <button class="refresh-btn" onclick="loadHourlyActivity()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <h5 class="mb-3">Hourly Voting Pattern</h5>
                            <canvas id="hourlyChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="recent-activity">
                            <h5 class="mb-3">Active Elections</h5>
                            <div id="activeElectionsList">
                                <!-- Election cards will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <script>
        let votingActivityChart;
        let turnoutChart;
        let liveTalliesChart;
        let hourlyChart;
        let electionPerformanceChart;
        let voterDemographicsChart;
        let positionVoteChart;
        let refreshInterval;
        let notificationQueue = [];
        let lastVoteCount = 0;

        $(document).ready(function() {
            initializeDashboard();
            startAutoRefresh();
        });

        function initializeDashboard() {
            loadDashboardStats();
            loadVotingActivity();
            loadTurnoutStats();
            loadLiveVoteTallies();
            loadHourlyActivity();
            loadRecentActivity();
            loadActiveElections();
            loadQuickStats();
            loadElectionPerformance();
            loadVoterDemographics();
            loadPositionVoteDistribution();
            
            // Initialize interactive features
            initializeDarkMode();
            initializeDateRangeFilter();
            initializeNotifications();
        }

        function startAutoRefresh() {
            // Refresh every 30 seconds
            refreshInterval = setInterval(function() {
                loadDashboardStats();
                loadLiveVoteTallies();
                loadRecentActivity();
                loadQuickStats();
                loadElectionPerformance();
                loadVoterDemographics();
                loadPositionVoteDistribution();
                checkForNewVotes();
            }, 30000);
        }

        function refreshDashboard() {
            initializeDashboard();
            showAlert('Dashboard refreshed successfully', 'success');
        }

        function loadDashboardStats() {
            $.get('api/monitoring.php?action=get_live_stats')
                .done(function(response) {
                    if (response.success) {
                        const data = response.data;
                        $('#totalElections').text(data.total_elections || 0);
                        $('#activeElections').text((data.active_elections || 0) + ' Active');
                        $('#totalCandidates').text(data.total_candidates || 0);
                        $('#activeCandidates').text((data.active_candidates || 0) + ' Active');
                        $('#totalVoters').text(data.total_voters || 0);
                        $('#votedToday').text((data.voted_today || 0) + ' Voted Today');
                        $('#totalVotes').text(data.total_votes || 0);
                        $('#votesToday').text((data.votes_today || 0) + ' Today');
                    }
                })
                .fail(function() {
                    console.error('Failed to load dashboard stats');
                });
        }

        function loadVotingActivity() {
            $.get('api/monitoring.php?action=get_hourly_activity')
                .done(function(response) {
                    if (response.success) {
                        updateVotingActivityChart(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load voting activity');
                });
        }

        function updateVotingActivityChart(data) {
            const ctx = document.getElementById('votingActivityChart').getContext('2d');
            
            if (votingActivityChart) {
                votingActivityChart.destroy();
            }
            
            const labels = data.map(item => {
                const hour = parseInt(item.hour);
                return hour + ':00';
            });
            const votes = data.map(item => item.vote_count);
            
            votingActivityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes per Hour',
                        data: votes,
                        borderColor: '#4A6B8A',
                        backgroundColor: 'rgba(74, 107, 138, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#4A6B8A',
                        pointBorderColor: '#3a5a7a',
                        pointRadius: 4,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#22C55E',
                        pointHoverBorderColor: '#16A34A',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#6B7280'
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6B7280'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.9)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: '#4A6B8A',
                            borderWidth: 2,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return 'Hour: ' + context[0].label;
                                },
                                label: function(context) {
                                    return 'Votes: ' + context.parsed.y + ' votes cast';
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1500,
                        easing: 'easeInOutQuart',
                        delay: (context) => {
                            let delay = 0;
                            if (context.type === 'data' && context.mode === 'default') {
                                delay = context.dataIndex * 100;
                            }
                            return delay;
                        }
                    },
                    onHover: (event, activeElements) => {
                        event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                    }
                }
            });
        }

        function loadTurnoutStats() {
            $.get('api/monitoring.php?action=get_turnout_stats')
                .done(function(response) {
                    if (response.success) {
                        updateTurnoutChart(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load turnout stats');
                });
        }

        function updateTurnoutChart(data) {
            const ctx = document.getElementById('turnoutChart').getContext('2d');
            
            if (turnoutChart) {
                turnoutChart.destroy();
            }
            
            const totalVoters = data.reduce((sum, item) => sum + item.total_voters, 0);
            const totalVoted = data.reduce((sum, item) => sum + item.votes_cast, 0);
            const notVoted = totalVoters - totalVoted;
            
            turnoutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Voted', 'Not Voted'],
                    datasets: [{
                        data: [totalVoted, notVoted],
                        backgroundColor: ['#22C55E', '#F3F4F6'],
                        borderWidth: 3,
                        borderColor: '#FFFFFF',
                        hoverBackgroundColor: ['#16A34A', '#E5E7EB'],
                        hoverBorderWidth: 4,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    weight: '500'
                                },
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.9)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: '#22C55E',
                            borderWidth: 2,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                title: function(context) {
                                    return 'Voter Turnout';
                                },
                                label: function(context) {
                                    const percentage = ((context.parsed / totalVoters) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed.toLocaleString() + ' voters (' + percentage + '%)';
                                },
                                afterLabel: function(context) {
                                    if (context.dataIndex === 0) {
                                        return 'Total eligible: ' + totalVoters.toLocaleString();
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    },
                    onHover: (event, activeElements) => {
                        event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                    }
                }
            });
        }

        function loadLiveVoteTallies() {
            $.get('api/monitoring.php?action=get_live_stats')
                .done(function(response) {
                    if (response.success) {
                        loadElectionVotes();
                    }
                })
                .fail(function() {
                    console.error('Failed to load live vote tallies');
                });
        }

        function loadElectionVotes() {
            $.get('api/monitoring.php?action=get_election_votes')
                .done(function(response) {
                    if (response.success) {
                        updateLiveTalliesChart(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load election votes');
                });
        }

        function updateLiveTalliesChart(data) {
            const ctx = document.getElementById('liveTalliesChart').getContext('2d');
            
            if (liveTalliesChart) {
                liveTalliesChart.destroy();
            }
            
            if (!data || data.length === 0) {
                // Show empty state
                ctx.font = '16px Arial';
                ctx.fillStyle = '#374151';
                ctx.textAlign = 'center';
                ctx.fillText('No active elections with votes', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }
            
            const elections = [...new Set(data.map(item => item.election_title))];
            const candidates = [...new Set(data.map(item => item.candidate_name))];
            
            const datasets = elections.map((election, index) => {
                const electionData = data.filter(item => item.election_title === election);
                const votes = candidates.map(candidate => {
                    const candidateData = electionData.find(item => item.candidate_name === candidate);
                    return candidateData ? candidateData.vote_count : 0;
                });
                
                const colors = [
                    '#4A6B8A', '#22C55E', '#F59E0B', '#DC2626', '#8B5CF6',
                    '#06B6D4', '#EF4444', '#10B981', '#3B82F6', '#F97316'
                ];
                
                return {
                    label: election,
                    data: votes,
                    backgroundColor: colors[index % colors.length],
                    borderColor: colors[index % colors.length],
                    borderWidth: 2
                };
            });
            
            liveTalliesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: candidates,
                    datasets: datasets.map(dataset => ({
                        ...dataset,
                        borderRadius: 4,
                        borderSkipped: false,
                        hoverBackgroundColor: dataset.backgroundColor + 'CC',
                        hoverBorderColor: dataset.borderColor,
                        hoverBorderWidth: 3
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#6B7280',
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.1)',
                                drawBorder: false
                            },
                            title: {
                                display: true,
                                text: 'Number of Votes',
                                color: '#374151',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                color: '#6B7280',
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Candidates',
                                color: '#374151',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    weight: '500'
                                },
                                color: '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.9)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: '#4A6B8A',
                            borderWidth: 2,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                title: function(context) {
                                    return 'Candidate: ' + context[0].label;
                                },
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : '0.0';
                                    return context.dataset.label + ': ' + context.parsed.y + ' votes (' + percentage + '%)';
                                },
                                afterBody: function(context) {
                                    const total = context[0].dataset.data.reduce((a, b) => a + b, 0);
                                    return 'Total votes in election: ' + total;
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart',
                        delay: (context) => {
                            let delay = 0;
                            if (context.type === 'data' && context.mode === 'default') {
                                delay = context.dataIndex * 150 + context.datasetIndex * 100;
                            }
                            return delay;
                        }
                    },
                    onHover: (event, activeElements) => {
                        event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                    }
                }
            });
        }

        function loadHourlyActivity() {
            $.get('api/monitoring.php?action=get_hourly_activity')
                .done(function(response) {
                    if (response.success) {
                        updateHourlyChart(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load hourly activity');
                });
        }

        function updateHourlyChart(data) {
            const ctx = document.getElementById('hourlyChart').getContext('2d');
            
            if (hourlyChart) {
                hourlyChart.destroy();
            }
            
            const labels = data.map(item => item.hour + ':00');
            const votes = data.map(item => item.vote_count);
            
            hourlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes per Hour',
                        data: votes,
                        backgroundColor: 'rgba(74, 107, 138, 0.8)',
                        borderColor: '#4A6B8A',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(34, 197, 94, 0.8)',
                        hoverBorderColor: '#16A34A',
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#6B7280',
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.1)',
                                drawBorder: false
                            },
                            title: {
                                display: true,
                                text: 'Number of Votes',
                                color: '#374151',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        x: {
                            ticks: {
                                color: '#6B7280',
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Time (24 Hours)',
                                color: '#374151',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.9)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: '#4A6B8A',
                            borderWidth: 2,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return 'Time: ' + context[0].label;
                                },
                                label: function(context) {
                                    const total = votes.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : '0.0';
                                    return 'Votes: ' + context.parsed.y + ' (' + percentage + '% of daily total)';
                                },
                                afterLabel: function(context) {
                                    const total = votes.reduce((a, b) => a + b, 0);
                                    return 'Total daily votes: ' + total;
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1800,
                        easing: 'easeInOutQuart',
                        delay: (context) => {
                            let delay = 0;
                            if (context.type === 'data' && context.mode === 'default') {
                                delay = context.dataIndex * 80;
                            }
                            return delay;
                        }
                    },
                    onHover: (event, activeElements) => {
                        event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                    }
                }
            });
        }

        function loadRecentActivity() {
            $.get('api/audit.php?action=get_logs&limit=10')
                .done(function(response) {
                    if (response.success) {
                        updateRecentActivity(response.data.logs);
                    }
                })
                .fail(function() {
                    console.error('Failed to load recent activity');
                });
        }

        function updateRecentActivity(logs) {
            const container = $('#recentActivityList');
            container.empty();
            
            if (!logs || logs.length === 0) {
                container.html('<p class="text-muted text-center">No recent activity</p>');
                return;
            }
            
            logs.forEach(function(log) {
                const timeAgo = getTimeAgo(new Date(log.created_at));
                let iconClass = 'fas fa-info-circle';
                let iconColor = '#5a6c7d';
                
                if (log.action.toLowerCase().includes('create')) {
                    iconClass = 'fas fa-plus-circle';
                    iconColor = '#5a6c7d';
                } else if (log.action.toLowerCase().includes('update')) {
                    iconClass = 'fas fa-edit';
                    iconColor = '#6b7c93';
                } else if (log.action.toLowerCase().includes('delete')) {
                    iconClass = 'fas fa-trash';
                    iconColor = '#4a5568';
                }
                
                const activityHtml = `
                    <div class="activity-item">
                        <div class="d-flex align-items-start">
                            <div class="activity-icon" style="background-color: ${iconColor};">
                                <i class="${iconClass}"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <div class="fw-bold">${log.action}</div>
                                <div class="text-muted small">${log.details}</div>
                                <div class="text-muted small">
                                    <i class="fas fa-user me-1"></i>${log.admin_name || 'Unknown'} • ${timeAgo}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.append(activityHtml);
            });
        }

        function loadActiveElections() {
            $.get('api/elections.php?action=get_elections')
                .done(function(response) {
                    if (response.success) {
                        updateActiveElections(response.data.filter(election => election.status === 'active'));
                    }
                })
                .fail(function() {
                    console.error('Failed to load active elections');
                });
        }

        function updateActiveElections(elections) {
            const container = $('#activeElectionsList');
            container.empty();
            
            if (!elections || elections.length === 0) {
                container.html('<p class="text-muted text-center">No active elections</p>');
                return;
            }
            
            elections.forEach(function(election) {
                const startDate = new Date(election.start_date).toLocaleDateString('en-US', {timeZone: 'UTC'}) + ' UTC';
                const endDate = new Date(election.end_date).toLocaleDateString('en-US', {timeZone: 'UTC'}) + ' UTC';
                
                const electionHtml = `
                    <div class="election-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">${election.title}</h6>
                            <span class="election-status status-active">Active</span>
                        </div>
                        <p class="text-muted small mb-2">${election.description}</p>
                        <div class="small text-muted">
                            <i class="fas fa-calendar me-1"></i>${startDate} - ${endDate}
                        </div>
                    </div>
                `;
                
                container.append(electionHtml);
            });
        }

        function loadQuickStats() {
            $.get('api/monitoring.php?action=get_turnout_stats')
                .done(function(response) {
                    if (response.success) {
                        updateQuickStats(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load quick stats');
                });
        }

        function updateQuickStats(data) {
            if (!data || data.length === 0) {
                $('#avgTurnout').text('0%');
                $('#peakHour').text('-');
                $('#activeNow').text('0');
                return;
            }
            
            const totalVoters = data.reduce((sum, item) => sum + item.total_voters, 0);
            const totalVoted = data.reduce((sum, item) => sum + item.votes_cast, 0);
            const avgTurnout = totalVoters > 0 ? ((totalVoted / totalVoters) * 100).toFixed(1) : 0;
            
            $('#avgTurnout').text(avgTurnout + '%');
            
            // Load hourly data for peak hour
            $.get('api/monitoring.php?action=get_hourly_activity')
                .done(function(response) {
                    if (response.success && response.data.length > 0) {
                        const peakHourData = response.data.reduce((max, item) => 
                            item.vote_count > max.vote_count ? item : max
                        );
                        $('#peakHour').text(peakHourData.hour + ':00');
                    }
                });
            
            // Simulate active users (in a real app, this would come from session data)
            const activeNow = Math.floor(Math.random() * 50) + 10;
            $('#activeNow').text(activeNow);
        }

        function getTimeAgo(date) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) {
                return 'Just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
            } else {
                const days = Math.floor(diffInSeconds / 86400);
                return days + ' day' + (days > 1 ? 's' : '') + ' ago';
            }
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

        // New Chart Functions
        function loadElectionPerformance() {
            $.get('api/monitoring.php?action=get_election_votes')
                .done(function(response) {
                    if (response.success && response.data.length > 0) {
                        updateElectionPerformanceChart(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load election performance data');
                });
        }

        function updateElectionPerformanceChart(data) {
            const ctx = document.getElementById('electionPerformanceChart').getContext('2d');
            
            if (electionPerformanceChart) {
                electionPerformanceChart.destroy();
            }
            
            // Calculate performance metrics for each election
            const chartData = data.slice(0, 5).map((election, index) => {
                const participationRate = Math.min(100, (election.vote_count / 100) * 100); // Simulated
                const candidateScore = Math.min(100, election.vote_count / 10); // Based on votes
                const distributionScore = Math.random() * 40 + 60; // Simulated distribution
                const completionRate = Math.random() * 20 + 80; // Simulated completion
                const engagementScore = Math.min(100, election.vote_count / 5); // Based on activity
                
                return {
                    label: election.election_title,
                    data: [participationRate, candidateScore, distributionScore, completionRate, engagementScore],
                    backgroundColor: `rgba(${37 + index * 50}, ${99 + index * 30}, 235, 0.2)`,
                    borderColor: `rgba(${37 + index * 50}, ${99 + index * 30}, 235, 1)`,
                    borderWidth: 2,
                    pointBackgroundColor: `rgba(${37 + index * 50}, ${99 + index * 30}, 235, 1)`
                };
            });
            
            electionPerformanceChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Participation', 'Activity', 'Distribution', 'Completion', 'Engagement'],
                    datasets: chartData
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20
                            }
                        }
                    }
                }
            });
        }

        function loadVoterDemographics() {
            $.get('api/monitoring.php?action=get_live_stats')
                .done(function(response) {
                    if (response.success) {
                        updateVoterDemographicsChart(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load voter demographics data');
                });
        }

        function updateVoterDemographicsChart(data) {
            const ctx = document.getElementById('voterDemographicsChart').getContext('2d');
            
            if (voterDemographicsChart) {
                voterDemographicsChart.destroy();
            }
            
            // Simulate demographic data based on available stats
            const totalVoters = data.total_voters || 100;
            const demographics = {
                'Active Voters': data.voted_today || Math.floor(totalVoters * 0.3),
                'Registered': totalVoters - (data.voted_today || Math.floor(totalVoters * 0.3)),
                'New Registrations': Math.floor(totalVoters * 0.1),
                'Pending Verification': Math.floor(totalVoters * 0.05)
            };
            
            voterDemographicsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(demographics),
                    datasets: [{
                        data: Object.values(demographics),
                        backgroundColor: [
                            'rgba(37, 99, 235, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderWidth: 0
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
        }

        function loadPositionVoteDistribution() {
            $.get('api/elections.php?action=get_elections')
                .done(function(response) {
                    if (response.success && response.data.length > 0) {
                        updatePositionVoteChart(response.data);
                    }
                })
                .fail(function() {
                    console.error('Failed to load position vote distribution data');
                });
        }

        function updatePositionVoteChart(elections) {
            const ctx = document.getElementById('positionVoteChart').getContext('2d');
            
            if (positionVoteChart) {
                positionVoteChart.destroy();
            }
            
            // Simulate position data from elections
            const positions = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor'];
            const totalVotes = positions.map(() => Math.floor(Math.random() * 200) + 50);
            const uniqueVoters = totalVotes.map(votes => Math.floor(votes * 0.8));
            
            positionVoteChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: positions,
                    datasets: [{
                        label: 'Total Votes',
                        data: totalVotes,
                        backgroundColor: 'rgba(37, 99, 235, 0.8)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Unique Voters',
                        data: uniqueVoters,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Interactive Features
        function initializeDarkMode() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            darkModeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                const isNowDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('darkMode', isNowDark);
                darkModeToggle.innerHTML = isNowDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
                showNotification('Dark mode ' + (isNowDark ? 'enabled' : 'disabled'), 'success');
            });
        }

        function initializeDateRangeFilter() {
            const dateFilter = document.getElementById('dateRangeFilter');
            dateFilter.addEventListener('change', function() {
                const selectedRange = this.value;
                // Reload all charts with new date range
                initializeDashboard();
                showNotification('Dashboard updated for ' + selectedRange.replace('_', ' '), 'success');
            });
        }

        function initializeNotifications() {
            // Check for notifications every 15 seconds
            setInterval(checkForNewVotes, 15000);
        }

        function checkForNewVotes() {
            $.get('api/monitoring.php?action=get_live_stats')
                .done(function(response) {
                    if (response.success && response.data.total_votes > lastVoteCount) {
                        const newVotes = response.data.total_votes - lastVoteCount;
                        if (lastVoteCount > 0) { // Don't show notification on first load
                            showNotification(`${newVotes} new vote(s) received!`, 'success');
                        }
                        lastVoteCount = response.data.total_votes;
                    }
                })
                .fail(function() {
                    console.error('Failed to check for new votes');
                });
        }

        function showNotification(message, type = 'success') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span>${message}</span>
                    <button type="button" class="btn-close btn-close-sm" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        function exportDashboardData() {
            showNotification('Preparing dashboard export...', 'info');
            
            // Collect all dashboard data
            const exportData = {
                timestamp: new Date().toISOString(),
                stats: {},
                elections: []
            };
            
            // Get current stats and export
            $.when(
                $.get('api/monitoring.php?action=get_live_stats'),
                $.get('api/elections.php?action=get_elections')
            ).done(function(statsResponse, electionsResponse) {
                exportData.stats = statsResponse[0].data;
                exportData.elections = electionsResponse[0].data;
                
                // Create and download JSON file
                const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `dashboard-export-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showNotification('Dashboard data exported successfully!', 'success');
            }).fail(function() {
                showNotification('Failed to export dashboard data', 'error');
            });
        }

        // Mobile Navigation Toggle
        $('#mobileNavToggle').on('click', function() {
            $('.sidebar').toggleClass('show');
            $('.sidebar-overlay').toggleClass('show');
        });

        // Close sidebar when overlay is clicked
        $('#sidebarOverlay').on('click', function() {
            $('.sidebar').removeClass('show');
            $('.sidebar-overlay').removeClass('show');
        });

        // Close sidebar when window is resized to desktop size
        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                $('.sidebar').removeClass('show');
                $('.sidebar-overlay').removeClass('show');
            }
        });

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>