<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'Live Vote Monitoring';
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
        }
        
        .stats-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.95) 100%);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            background-clip: padding-box;
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
            font-size: 28px;
            color: var(--base-white);
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
        
        .icon-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); 
            animation: pulse 2s ease-in-out infinite alternate;
        }
        .icon-success { 
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 50%, #7fcdcd 100%); 
            animation: pulse 2s ease-in-out infinite alternate 0.5s;
        }
        .icon-warning { 
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%); 
            animation: pulse 2s ease-in-out infinite alternate 1s;
        }
        .icon-info { 
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 50%, #d299c2 100%); 
            animation: pulse 2s ease-in-out infinite alternate 1.5s;
        }
        
        .chart-container {
            background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.98) 100%);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1), 0 4px 16px rgba(0,0,0,0.05);
            padding: 32px;
            margin-bottom: 32px;
            display: flex;
            flex-direction: column;
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .chart-container:hover::before {
            opacity: 0.08;
        }
        
        .chart-container:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 8px 24px rgba(0,0,0,0.1);
            border-color: rgba(74, 107, 138, 0.2);
        }
        
        .chart-container canvas {
            flex: 1;
            min-height: 250px;
        }
        
        .charts-row {
            display: flex;
            align-items: stretch;
        }
        
        .charts-row .col-md-8,
        .charts-row .col-md-4 {
            display: flex;
        }
        
        .charts-row .chart-container {
            height: 350px;
        }
        
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #5a6c7d;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .election-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.95) 100%);
            border-radius: 16px;
            box-shadow: 0 6px 25px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            background-clip: padding-box;
            position: relative;
            overflow: hidden;
        }
        
        .election-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c, #4facfe, #00f2fe);
            background-size: 400% 400%;
            animation: gradientShift 6s ease infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .election-card:hover::before {
            opacity: 0.12;
        }
        
        .election-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2), 0 6px 16px rgba(0,0,0,0.12);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .status-badge:hover::before {
            left: 100%;
        }
        
        .status-active {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: var(--base-white);
            animation: pulse 2s ease-in-out infinite alternate;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--base-white);
            animation: pulse 2s ease-in-out infinite alternate 0.5s;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: var(--base-white);
            animation: pulse 2s ease-in-out infinite alternate 1s;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);
            color: var(--base-white);
            animation: pulse 2s ease-in-out infinite alternate 1.5s;
        }
        
        .alert-custom {
            border-radius: 12px;
            border: 1px solid var(--neutral-medium);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            background: var(--base-white);
        }
        
        .alert-success {
            background: #f0fdf4;
            border-color: var(--success-green);
            color: #166534;
        }
        
        .alert-danger {
            background: #fef2f2;
            border-color: var(--error-red);
            color: #991b1b;
        }
        
        .alert-info {
            background: #eff6ff;
            border-color: var(--primary-blue);
            color: #1e40af;
        }
        
        .refresh-btn {
            background: var(--primary-blue);
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            color: var(--base-white);
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .refresh-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn-custom {
            background: var(--primary-blue);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            color: var(--base-white);
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .btn-custom:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary-custom {
            background: var(--primary-blue);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            color: var(--base-white);
            transition: all 0.2s ease;
        }
        
        .btn-primary-custom:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            background: transparent;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: var(--base-white);
        }
        
        .btn-outline-danger {
            border: 2px solid var(--error-red);
            color: var(--error-red);
            background: transparent;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-outline-danger:hover {
             background: var(--error-red);
             color: var(--base-white);
         }

         /* Mobile Navigation */
         .mobile-menu-btn {
             display: none;
             position: fixed;
             top: 20px;
             left: 20px;
             z-index: 1001;
             background: var(--primary-blue);
             color: var(--base-white);
             border: none;
             border-radius: 8px;
             padding: 12px;
             box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
         }

         .mobile-overlay {
             display: none;
             position: fixed;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             background: rgba(0, 0, 0, 0.5);
             z-index: 999;
         }

         /* Responsive Design */
         @media (max-width: 768px) {
             .mobile-menu-btn {
                 display: block;
             }

             .sidebar {
                 position: fixed;
                 left: -100%;
                 top: 0;
                 width: 280px;
                 height: 100vh;
                 z-index: 1000;
                 transition: left 0.3s ease;
             }

             .sidebar.show {
                 left: 0;
             }

             .main-content {
                 margin-left: 0;
                 padding: 80px 15px 20px;
             }

             .stats-card {
                 margin-bottom: 20px;
                 padding: 20px;
             }

             .stats-icon {
                 width: 48px;
                 height: 48px;
                 font-size: 20px;
                 margin-bottom: 12px;
             }

             .chart-container {
                 padding: 20px;
                 margin-bottom: 20px;
             }

             .table-container {
                 padding: 15px;
                 overflow-x: auto;
             }

             .table {
                 font-size: 14px;
             }

             .table th,
             .table td {
                 padding: 12px 8px;
             }

             .btn-custom,
             .btn-primary-custom {
                 padding: 10px 20px;
                 font-size: 14px;
             }

             .refresh-btn {
                 padding: 8px 16px;
                 font-size: 14px;
             }

             .alert-custom {
                 padding: 16px;
                 margin-bottom: 16px;
             }
         }

         @media (max-width: 576px) {
             .main-content {
                 padding: 80px 10px 15px;
             }

             .stats-card {
                 padding: 16px;
                 text-align: center;
             }

             .stats-icon {
                 width: 40px;
                 height: 40px;
                 font-size: 18px;
                 margin: 0 auto 12px;
             }

             .chart-container {
                 padding: 16px;
             }

             .table-container {
                 padding: 12px;
             }

             .table {
                 font-size: 12px;
             }

             .table th,
             .table td {
                 padding: 8px 6px;
             }

             .btn-custom,
             .btn-primary-custom {
                 padding: 8px 16px;
                 font-size: 13px;
                 width: 100%;
                 margin-bottom: 10px;
             }

             .status-badge {
                 font-size: 10px;
                 padding: 4px 8px;
             }
         }

         @media (min-width: 769px) and (max-width: 1024px) {
             .main-content {
                 padding: 20px 15px;
             }

             .stats-card {
                 padding: 20px;
             }

             .chart-container,
             .table-container {
                 padding: 20px;
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
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4">
                        <h4 class="text-white mb-4">
                            <i class="fas fa-vote-yea me-2"></i>
                            VoteSystem
                        </h4>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="elections.php">
                            <i class="fas fa-calendar-alt me-2"></i> Elections
                        </a>
                        <a class="nav-link" href="candidates.php">
                            <i class="fas fa-users me-2"></i> Candidates
                        </a>
                        <a class="nav-link" href="voters.php">
                            <i class="fas fa-user-check me-2"></i> Voters
                        </a>
                        <a class="nav-link active" href="monitoring.php">
                            <i class="fas fa-chart-line me-2"></i> Live Monitoring
                        </a>
                        <a class="nav-link" href="results.php">
                            <i class="fas fa-poll me-2"></i> Results
                        </a>
                        <a class="nav-link" href="audit.php">
                            <i class="fas fa-clipboard-list me-2"></i> Audit Logs
                        </a>
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">
                                <span class="live-indicator me-2"></span>
                                Live Vote Monitoring
                            </h2>
                            <p class="text-muted mb-0">Real-time voting statistics and activity</p>
                        </div>
                        <button class="btn refresh-btn" onclick="refreshAllData()">
                            <i class="fas fa-sync-alt me-2"></i> Refresh
                        </button>
                    </div>
                    
                    <!-- Live Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon icon-primary me-3">
                                        <i class="fas fa-vote-yea"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1" id="votesToday">0</h3>
                                        <p class="text-muted mb-0">Votes Today</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon icon-success me-3">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1" id="activeElections">0</h3>
                                        <p class="text-muted mb-0">Active Elections</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon icon-warning me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1" id="totalVoters">0</h3>
                                        <p class="text-muted mb-0">Registered Voters</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon icon-info me-3">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1" id="turnoutPercentage">0%</h3>
                                        <p class="text-muted mb-0">Turnout Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4 charts-row">
                        <div class="col-md-8">
                            <div class="chart-container">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Voting Activity (Last 24 Hours)
                                </h5>
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="chart-container">
                                <h5 class="mb-3">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Election Turnout
                                </h5>
                                <canvas id="turnoutChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Elections Overview -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="mb-3">
                                    <i class="fas fa-poll me-2"></i>
                                    Active Elections
                                </h5>
                                <div id="electionsContainer">
                                    <!-- Elections will be loaded here -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="mb-3">
                                    <i class="fas fa-trophy me-2"></i>
                                    Leading Candidates
                                    <select class="form-select form-select-sm d-inline-block w-auto ms-2" id="electionSelect">
                                        <option value="">Select Election</option>
                                    </select>
                                </h5>
                                <div id="candidatesContainer">
                                    <p class="text-muted text-center py-4">Select an election to view candidates</p>
                                </div>
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
        let activityChart, turnoutChart;
        let refreshInterval;
        
        $(document).ready(function() {
            initializeCharts();
            loadAllData();
            
            // Set up auto-refresh every 30 seconds
            refreshInterval = setInterval(function() {
                loadAllData();
            }, 30000);
            
            // Election selection change
            $('#electionSelect').change(function() {
                const electionId = $(this).val();
                if (electionId) {
                    loadCandidateVotes(electionId);
                } else {
                    $('#candidatesContainer').html('<p class="text-muted text-center py-4">Select an election to view candidates</p>');
                }
            });
        });
        
        function initializeCharts() {
            // Activity Chart
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            activityChart = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Votes per Hour',
                        data: [],
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
                                stepSize: 1
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
            
            // Turnout Chart
            const turnoutCtx = document.getElementById('turnoutChart').getContext('2d');
            turnoutChart = new Chart(turnoutCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
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
        }
        
        function loadAllData() {
            loadLiveStats();
            loadElectionVotes();
            loadVotingActivity();
            loadTurnoutStats();
        }
        
        function loadLiveStats() {
            $.ajax({
                url: 'api/monitoring.php?action=get_live_stats',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        $('#votesToday').text(data.votes_today);
                        $('#activeElections').text(data.active_elections);
                        $('#totalVoters').text(data.total_voters);
                        $('#turnoutPercentage').text(data.turnout_percentage + '%');
                    }
                },
                error: function() {
                    console.error('Failed to load live stats');
                }
            });
        }
        
        function loadElectionVotes() {
            $.ajax({
                url: 'api/monitoring.php?action=get_election_votes',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayElections(response.data);
                        updateElectionSelect(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load election votes');
                }
            });
        }
        
        function loadVotingActivity() {
            $.ajax({
                url: 'api/monitoring.php?action=get_voting_activity',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateActivityChart(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load voting activity');
                }
            });
        }
        
        function loadTurnoutStats() {
            $.ajax({
                url: 'api/monitoring.php?action=get_turnout_stats',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateTurnoutChart(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load turnout stats');
                }
            });
        }
        
        function loadCandidateVotes(electionId) {
            $.ajax({
                url: 'api/monitoring.php?action=get_candidate_votes&election_id=' + electionId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayCandidates(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load candidate votes');
                }
            });
        }
        
        function displayElections(elections) {
            let html = '';
            elections.forEach(function(election) {
                const statusClass = election.status === 'active' ? 'status-active' : 'status-completed';
                html += `
                    <div class="election-card p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${election.title}</h6>
                                <small class="text-muted">${election.vote_count} votes</small>
                            </div>
                            <span class="status-badge ${statusClass}">${election.status}</span>
                        </div>
                    </div>
                `;
            });
            $('#electionsContainer').html(html || '<p class="text-muted text-center py-4">No elections found</p>');
        }
        
        function displayCandidates(candidates) {
            let html = '';
            candidates.forEach(function(candidate) {
                html += `
                    <div class="d-flex align-items-center mb-3 p-2 border rounded">
                        <div class="me-3">
                            <img src="${candidate.photo || '../assets/images/default-avatar.png'}" 
                                 alt="${candidate.full_name}" 
                                 class="rounded-circle" 
                                 style="width: 40px; height: 40px; object-fit: cover;">
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${candidate.full_name}</h6>
                            <small class="text-muted">${candidate.position}</small>
                        </div>
                        <div class="text-end">
                            <strong class="text-primary">${candidate.vote_count}</strong>
                            <small class="text-muted d-block">votes</small>
                        </div>
                    </div>
                `;
            });
            $('#candidatesContainer').html(html || '<p class="text-muted text-center py-4">No candidates found</p>');
        }
        
        function updateElectionSelect(elections) {
            let options = '<option value="">Select Election</option>';
            elections.forEach(function(election) {
                options += `<option value="${election.id}">${election.title}</option>`;
            });
            $('#electionSelect').html(options);
        }
        
        function updateActivityChart(data) {
            const labels = [];
            const votes = [];
            
            // Create 24-hour labels
            for (let i = 0; i < 24; i++) {
                labels.push(i + ':00');
                votes.push(0);
            }
            
            // Fill in actual data
            data.forEach(function(item) {
                votes[item.hour] = item.vote_count;
            });
            
            activityChart.data.labels = labels;
            activityChart.data.datasets[0].data = votes;
            activityChart.update();
        }
        
        function updateTurnoutChart(data) {
            const labels = [];
            const percentages = [];
            
            data.forEach(function(item) {
                labels.push(item.title);
                percentages.push(item.turnout_percentage);
            });
            
            turnoutChart.data.labels = labels;
            turnoutChart.data.datasets[0].data = percentages;
            turnoutChart.update();
        }
        
        function refreshAllData() {
            const btn = $('.refresh-btn');
            const icon = btn.find('i');
            
            icon.addClass('fa-spin');
            btn.prop('disabled', true);
            
            loadAllData();
            
            setTimeout(function() {
                icon.removeClass('fa-spin');
                btn.prop('disabled', false);
            }, 1000);
        }
        
        // Clean up interval on page unload
        $(window).on('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });

        // Mobile Menu Functionality
        $(document).ready(function() {
            const mobileMenuBtn = $('#mobileMenuBtn');
            const sidebar = $('.sidebar');
            const overlay = $('#mobileOverlay');

            // Toggle mobile menu
            mobileMenuBtn.on('click', function() {
                sidebar.toggleClass('show');
                overlay.toggle();
            });

            // Close menu when overlay is clicked
            overlay.on('click', function() {
                sidebar.removeClass('show');
                overlay.hide();
            });

            // Close menu when navigation link is clicked (mobile)
            $('.sidebar .nav-link').on('click', function() {
                if ($(window).width() <= 768) {
                    sidebar.removeClass('show');
                    overlay.hide();
                }
            });

            // Handle window resize
            $(window).on('resize', function() {
                if ($(window).width() > 768) {
                    sidebar.removeClass('show');
                    overlay.hide();
                }
            });
        });
    </script>
</body>
</html>