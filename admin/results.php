<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'Election Results';
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
    <!-- Export libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
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
        
        .election-card {
            background: var(--base-white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid var(--neutral-medium);
            cursor: pointer;
        }
        
        .election-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 107, 138, 0.15);
            border-color: var(--primary-blue);
        }
        
        .election-card.selected {
            border: 2px solid var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 107, 138, 0.2);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.025em;
        }
        
        .status-active {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success-green);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .status-completed {
            background: rgba(74, 107, 138, 0.1);
            color: var(--primary-blue);
            border: 1px solid rgba(74, 107, 138, 0.2);
        }
        
        .status-upcoming {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .results-container {
            background: var(--base-white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 24px;
            border: 1px solid var(--neutral-medium);
        }
        
        .position-section {
            background: var(--neutral-light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--neutral-medium);
        }
        
        .candidate-item {
            background: var(--base-white);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid var(--neutral-medium);
            transition: all 0.3s ease;
            border: 1px solid var(--neutral-medium);
        }
        
        .candidate-item.winner {
            border-left-color: var(--success-green);
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(34, 197, 94, 0.1) 100%);
            border-color: rgba(34, 197, 94, 0.2);
        }
        
        .candidate-item.runner-up {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.1) 100%);
            border-color: rgba(245, 158, 11, 0.2);
        }
        
        .vote-bar {
            height: 8px;
            background: var(--neutral-medium);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .vote-progress {
            height: 100%;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a5a7a 100%);
            transition: width 0.5s ease;
        }
        
        .winner-badge {
            background: var(--success-green);
            color: var(--base-white);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);
        }
        
        .export-btn {
            background: var(--neutral-dark);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: var(--base-white);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .export-btn:hover {
            background: #1f2937;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(55, 65, 81, 0.3);
            color: var(--base-white);
        }
        
        .publish-btn {
            background: var(--success-green);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: var(--base-white);
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .publish-btn:hover {
            background: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
            color: var(--base-white);
        }
        
        .unpublish-btn {
            background: var(--error-red);
        }
        
        .unpublish-btn:hover {
            background: #b91c1c;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            color: var(--base-white);
        }
        
        .stats-card {
            background: var(--base-white);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px;
            text-align: center;
            border: 1px solid var(--neutral-medium);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }
        
        .chart-container {
            background: var(--base-white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 20px;
            margin-top: 20px;
            border: 1px solid var(--neutral-medium);
            height: 300px;
        }
        
        .chart-container canvas {
            max-height: 250px !important;
        }
        
        h2, h4, h5, h6 {
            color: var(--neutral-dark);
            font-weight: 600;
        }
        
        .text-muted {
            color: #6b7280 !important;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .col-md-9.col-lg-10 {
                padding: 0;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 15px;
            }
            
            .d-flex.justify-content-between .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .election-card {
                margin-bottom: 15px;
            }
            
            .stats-card {
                margin-bottom: 15px;
            }
            
            .chart-container {
                margin-bottom: 20px;
                height: 280px;
            }
            
            .results-container {
                padding: 15px;
            }
            
            .position-section {
                padding: 15px;
            }
            
            .candidate-item {
                padding: 12px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            h4 {
                font-size: 1.25rem;
            }
            
            h5 {
                font-size: 1.1rem;
            }
            
            .stats-number {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
            }
            
            .results-container {
                padding: 12px;
            }
            
            .position-section {
                padding: 12px;
            }
            
            .candidate-item {
                padding: 10px;
            }
            
            .stats-card {
                padding: 15px;
            }
            
            .chart-container {
                padding: 15px;
                height: 250px;
            }
            
            .chart-container canvas {
                max-height: 200px !important;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 14px;
            }
            
            .export-btn, .publish-btn {
                padding: 8px 16px;
            }
            
            h2 {
                font-size: 1.3rem;
            }
            
            .stats-number {
                font-size: 1.3rem;
            }
        }
        
        /* CESA Style Results */
        .cesa-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .cesa-title {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: white;
        }
        
        .cesa-subtitle {
            font-size: 1.1rem;
            font-weight: 500;
            letter-spacing: 1px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .cesa-info h6 {
            color: white;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .cesa-divider {
            height: 3px;
            background: white;
            width: 100px;
            margin-top: 10px;
        }
        
        .cesa-position-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        
        .cesa-position-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cesa-chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 20px;
        }
        
        .cesa-candidate {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 15px;
            position: relative;
        }
        
        .cesa-candidate-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e5e7eb;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .cesa-candidate.winner .cesa-candidate-photo {
            border-color: #22c55e;
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.3);
        }
        
        .cesa-candidate.runner-up .cesa-candidate-photo {
            border-color: #f59e0b;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.3);
        }
        
        .cesa-bar {
            width: 80px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-radius: 8px 8px 0 0;
            position: relative;
            transition: all 0.5s ease;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .cesa-candidate.winner .cesa-bar {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        
        .cesa-candidate.runner-up .cesa-bar {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .cesa-vote-count {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .cesa-candidate:hover .cesa-vote-count {
            opacity: 1;
        }
        
        .cesa-candidate-name {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            color: #374151;
            margin-top: 5px;
            line-height: 1.2;
        }
        
        .cesa-chart-area {
            display: flex;
            align-items: end;
            justify-content: center;
            height: 300px;
            padding: 20px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border-radius: 10px;
            border: 2px solid #e5e7eb;
        }
        
        .cesa-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .cesa-stat-item {
            text-align: center;
        }
        
        .cesa-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e40af;
        }
        
        .cesa-stat-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1060;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        
        /* CESA Mobile Responsive */
            @media (max-width: 768px) {
                .cesa-title {
                    font-size: 1.8rem;
                    letter-spacing: 1px;
                }
                
                .cesa-subtitle {
                    font-size: 1rem;
                }
                
                .cesa-header {
                    padding: 20px;
                }
                
                .cesa-position-section {
                    padding: 20px;
                }
                
                .cesa-chart-area {
                    height: 250px;
                    padding: 15px;
                }
                
                .cesa-candidate {
                    margin: 0 8px;
                }
                
                .cesa-candidate-photo {
                    width: 45px;
                    height: 45px;
                }
                
                .cesa-bar {
                    width: 60px;
                }
                
                .cesa-candidate-name {
                    font-size: 10px;
                }
                
                .cesa-stats {
                    flex-direction: column;
                    gap: 15px;
                }
                
                .mobile-menu-btn {
                    display: block;
                }
                
                .mobile-overlay.show {
                    display: block;
                }
                
                .main-content {
                    padding-top: 60px;
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
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users-cog me-2"></i> Users
                        </a>
                        <a class="nav-link" href="monitoring.php">
                            <i class="fas fa-chart-line me-2"></i> Live Monitoring
                        </a>
                        <a class="nav-link active" href="results.php">
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
                            <h2 class="mb-1">Election Results</h2>
                            <p class="text-muted mb-0">View and manage election results</p>
                        </div>
                    </div>
                    
                    <!-- Elections List -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="mb-3">Select an Election</h5>
                            <div class="row" id="electionsContainer">
                                <!-- Elections will be loaded here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results Section -->
                    <div id="resultsSection" style="display: none;">
                        <!-- Election Info -->
                        <div class="results-container mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h4 id="electionTitle">Election Title</h4>
                                    <p class="text-muted mb-0" id="electionDescription">Election Description</p>
                                </div>
                                <div>
                                    <button class="btn export-btn me-2" onclick="exportResults('csv')">
                                        <i class="fas fa-download me-2"></i> Export CSV
                                    </button>
                                    <button class="btn export-btn me-2" onclick="exportResults('json')">
                                        <i class="fas fa-file-code me-2"></i> Export JSON
                                    </button>
                                    <button class="btn publish-btn" id="publishBtn" onclick="togglePublishResults()">
                                        <i class="fas fa-eye me-2"></i> Publish Results
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number" id="totalVotes">0</div>
                                        <div class="text-muted">Total Votes</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number" id="uniqueVoters">0</div>
                                        <div class="text-muted">Unique Voters</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number" id="turnoutPercentage">0%</div>
                                        <div class="text-muted">Turnout Rate</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number" id="totalCandidates">0</div>
                                        <div class="text-muted">Candidates</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- View Toggle -->
                        <div class="results-container mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Election Results</h5>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="viewToggle" id="traditionalView" checked>
                                    <label class="btn btn-outline-primary" for="traditionalView">
                                        <i class="fas fa-list me-2"></i>Traditional View
                                    </label>
                                    <input type="radio" class="btn-check" name="viewToggle" id="cesaView">
                                    <label class="btn btn-outline-primary" for="cesaView">
                                        <i class="fas fa-chart-bar me-2"></i> Style View
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Traditional Results by Position -->
                        <div class="results-container" id="traditionalResults">
                            <h5 class="mb-3">Results by Position</h5>
                            <div id="positionResults">
                                <!-- Position results will be loaded here -->
                            </div>
                        </div>
                        
                        <!-- CESA Style Results -->
                        <div class="results-container" id="cesaResults" style="display: none;">
                            <div class="cesa-header mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h3 class="cesa-title mb-0">ELECTION RESULTS</h3>
                                        <p class="cesa-subtitle mb-0" id="cesaDate">DECEMBER 2030</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="cesa-info">
                                            <h6 class="mb-1">CANDIDATES & RATINGS</h6>
                                            <div class="cesa-divider"></div>
                                            <div class="cesa-export-buttons mt-2">
                                                <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="exportCesaResults('pdf')">
                                                    <i class="fas fa-file-pdf"></i> Export PDF
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportCesaResults('png')">
                                                    <i class="fas fa-image"></i> Export PNG
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="cesaPositionResults">
                                <!-- CESA style position results will be loaded here -->
                            </div>
                        </div>
                        
                        <!-- Charts -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h6 class="mb-3">Voting Pattern (Hourly)</h6>
                                    <canvas id="hourlyChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="chart-container">
                                    <h6 class="mb-3">Voting Pattern (Daily)</h6>
                                    <canvas id="dailyChart"></canvas>
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
        let selectedElectionId = null;
        let hourlyChart, dailyChart;
        
        $(document).ready(function() {
            loadElections();
            initializeCharts();
            initializeViewToggle();
        });
        
        // Export functionality for CESA results
        window.exportCesaResults = function(format) {
            const cesaContainer = document.getElementById('cesaResults');
            if (!cesaContainer || cesaContainer.style.display === 'none') {
                alert('Please switch to CESA view before exporting.');
                return;
            }
            
            if (format === 'pdf') {
                // Use html2pdf library for PDF export
                const opt = {
                    margin: 1,
                    filename: 'CESA_Election_Results.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
                };
                
                if (typeof html2pdf !== 'undefined') {
                    html2pdf().set(opt).from(cesaContainer).save();
                } else {
                    alert('PDF export library not loaded. Please refresh the page and try again.');
                }
            } else if (format === 'png') {
                // Use html2canvas for PNG export
                if (typeof html2canvas !== 'undefined') {
                    html2canvas(cesaContainer, {
                        scale: 2,
                        backgroundColor: '#ffffff'
                    }).then(canvas => {
                        const link = document.createElement('a');
                        link.download = 'CESA_Election_Results.png';
                        link.href = canvas.toDataURL();
                        link.click();
                    });
                } else {
                    alert('PNG export library not loaded. Please refresh the page and try again.');
                }
            }
        };
        
        function loadElections() {
            $.ajax({
                url: 'api/results.php?action=get_elections',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayElections(response.data);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Failed to load elections');
                }
            });
        }
        
        function displayElections(elections) {
            let html = '';
            elections.forEach(function(election) {
                const status = election.status || 'completed';
                const statusClass = getStatusClass(status);
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                
                html += `
                    <div class="col-md-4 mb-3">
                        <div class="election-card p-3" onclick="selectElection(${election.id})" data-election-id="${election.id}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1">${election.title}</h6>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </div>
                            <p class="text-muted small mb-2">${election.description || 'No description'}</p>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">Votes</small>
                                    <strong>${election.total_votes}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Candidates</small>
                                    <strong>${election.total_candidates}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Published</small>
                                    <strong>${election.results_published ? 'Yes' : 'No'}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#electionsContainer').html(html || '<div class="col-12"><p class="text-muted text-center py-4">No elections found</p></div>');
        }
        
        function selectElection(electionId) {
            selectedElectionId = electionId;
            
            // Update UI
            $('.election-card').removeClass('selected');
            $(`.election-card[data-election-id="${electionId}"]`).addClass('selected');
            
            // Load results
            loadResults(electionId);
            loadDetailedResults(electionId);
            
            $('#resultsSection').show();
        }
        
        function loadResults(electionId) {
            $.ajax({
                url: `api/results.php?action=get_results&election_id=${electionId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayResults(response.data);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Failed to load results');
                }
            });
        }
        
        function loadDetailedResults(electionId) {
            $.ajax({
                url: `api/results.php?action=get_detailed_results&election_id=${electionId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayDetailedResults(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load detailed results');
                }
            });
        }
        
        function displayResults(data) {
            const election = data.election;
            const results = data.results;
            
            // Update election info
            $('#electionTitle').text(election.title);
            $('#electionDescription').text(election.description || 'No description');
            
            // Update publish button
            const publishBtn = $('#publishBtn');
            if (election.results_published) {
                publishBtn.removeClass('publish-btn').addClass('unpublish-btn')
                    .html('<i class="fas fa-eye-slash me-2"></i> Unpublish Results');
            } else {
                publishBtn.removeClass('unpublish-btn').addClass('publish-btn')
                    .html('<i class="fas fa-eye me-2"></i> Publish Results');
            }
            
            // Display position results
            let html = '';
            results.forEach(function(position) {
                html += `
                    <div class="position-section">
                        <h6 class="mb-3">
                            <i class="fas fa-trophy me-2"></i>
                            ${position.position_title}
                            <small class="text-muted">(Max Winners: ${position.max_winners})</small>
                        </h6>
                `;
                
                if (position.candidates.length > 0) {
                    // Calculate max votes for percentage calculation
                    const maxVotes = Math.max(...position.candidates.map(c => c.vote_count));
                    
                    position.candidates.forEach(function(candidate, index) {
                        const isWinner = index < position.max_winners;
                        const isRunnerUp = index === position.max_winners;
                        const candidateClass = isWinner ? 'winner' : (isRunnerUp ? 'runner-up' : '');
                        const progressWidth = maxVotes > 0 ? (candidate.vote_count / maxVotes) * 100 : 0;
                        
                        html += `
                            <div class="candidate-item ${candidateClass}">
                                <div class="d-flex align-items-center">
                                    <img src="${candidate.photo || '../assets/images/default-avatar.png'}" 
                                         alt="${candidate.full_name}" 
                                         class="rounded-circle me-3" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1">
                                                ${candidate.full_name}
                                                ${isWinner ? '<span class="winner-badge ms-2">WINNER</span>' : ''}
                                            </h6>
                                            <div class="text-end">
                                                <strong class="text-primary">${candidate.vote_count} votes</strong>
                                                <small class="text-muted d-block">${candidate.vote_percentage}%</small>
                                            </div>
                                        </div>
                                        <div class="vote-bar">
                                            <div class="vote-progress" style="width: ${progressWidth}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html += '<p class="text-muted text-center py-3">No candidates for this position</p>';
                }
                
                html += '</div>';
            });
            
            $('#positionResults').html(html);
        }
        
        function displayDetailedResults(data) {
            const stats = data.statistics;
            
            // Update statistics
            $('#totalVotes').text(stats.total_votes || 0);
            $('#uniqueVoters').text(stats.unique_voters || 0);
            $('#turnoutPercentage').text((stats.turnout_percentage || 0) + '%');
            $('#totalCandidates').text(stats.total_registered_voters || 0);
            
            // Update charts
            updateHourlyChart(data.hourly_pattern);
            updateDailyChart(data.daily_pattern);
        }
        
        function initializeCharts() {
            // Hourly Chart
            const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
            hourlyChart = new Chart(hourlyCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Votes per Hour',
                        data: [],
                        backgroundColor: 'rgba(74, 107, 138, 0.8)',
                        borderColor: '#4A6B8A',
                        borderWidth: 1
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
            
            // Daily Chart
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            dailyChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Votes per Day',
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
        }
        
        function updateHourlyChart(data) {
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
            
            hourlyChart.data.labels = labels;
            hourlyChart.data.datasets[0].data = votes;
            hourlyChart.update();
        }
        
        function updateDailyChart(data) {
            const labels = data.map(item => item.vote_date);
            const votes = data.map(item => item.vote_count);
            
            dailyChart.data.labels = labels;
            dailyChart.data.datasets[0].data = votes;
            dailyChart.update();
        }
        
        function exportResults(format) {
            if (!selectedElectionId) {
                alert('Please select an election first');
                return;
            }
            
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Exporting...';
            btn.disabled = true;
            
            $.ajax({
                url: `api/results.php?action=export_results&election_id=${selectedElectionId}&format=${format}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (format === 'csv') {
                            // Download CSV file
                            const link = document.createElement('a');
                            link.href = response.data.download_url;
                            link.download = response.data.filename;
                            link.click();
                            alert('Results exported successfully!');
                        } else {
                            // Download JSON data
                            const dataStr = JSON.stringify(response.data, null, 2);
                            const dataBlob = new Blob([dataStr], {type: 'application/json'});
                            const url = URL.createObjectURL(dataBlob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = 'election_results.json';
                            link.click();
                            alert('Results exported successfully!');
                        }
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Failed to export results');
                },
                complete: function() {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }
        
        function togglePublishResults() {
            if (!selectedElectionId) {
                alert('Please select an election first.');
                return;
            }
            
            const btn = $('#publishBtn');
            const isPublished = btn.hasClass('unpublish-btn');
            const action = isPublished ? 'unpublish' : 'publish';
            
            if (!isPublished) {
                // Show email confirmation dialog for publishing
                const sendEmail = confirm('Do you want to send email notifications to voters about the published results?');
                
                if (confirm(`Are you sure you want to ${action} the results for this election?`)) {
                    $.ajax({
                        url: 'api/results.php?action=publish_results',
                        method: 'POST',
                        data: {
                            election_id: selectedElectionId,
                            publish: !isPublished,
                            send_email: sendEmail ? 1 : 0
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                updatePublishButton(!isPublished);
                                if (sendEmail) {
                                    alert('Results published successfully! Email notifications are being sent to voters.');
                                } else {
                                    alert('Results published successfully!');
                                }
                                loadElections(); // Refresh elections list
                                loadResults(selectedElectionId); // Refresh results
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('Failed to update publication status');
                        }
                    });
                }
            } else {
                // Unpublish without email
                if (confirm(`Are you sure you want to ${action} the results for this election?`)) {
                    $.ajax({
                        url: 'api/results.php?action=publish_results',
                        method: 'POST',
                        data: {
                            election_id: selectedElectionId,
                            publish: !isPublished
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                updatePublishButton(!isPublished);
                                alert('Results unpublished successfully!');
                                loadElections(); // Refresh elections list
                                loadResults(selectedElectionId); // Refresh results
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('Failed to update publication status');
                        }
                    });
                }
            }
        }
        
        // Update publish button state
        function updatePublishButton(isPublished) {
            const btn = $('#publishBtn');
            if (isPublished) {
                btn.removeClass('publish-btn').addClass('unpublish-btn');
                btn.html('<i class="fas fa-eye-slash me-2"></i> Unpublish Results');
            } else {
                btn.removeClass('unpublish-btn').addClass('publish-btn');
                btn.html('<i class="fas fa-eye me-2"></i> Publish Results');
            }
        }
        
        function getStatusClass(status) {
            switch (status) {
                case 'active': return 'status-active';
                case 'completed': return 'status-completed';
                case 'upcoming': return 'status-upcoming';
                default: return 'status-completed';
            }
        }
        
        // Initialize view toggle functionality
        function initializeViewToggle() {
            $('input[name="viewToggle"]').change(function() {
                if ($(this).attr('id') === 'traditionalView') {
                    $('#traditionalResults').show();
                    $('#cesaResults').hide();
                } else {
                    $('#traditionalResults').hide();
                    $('#cesaResults').show();
                    if (selectedElectionId) {
                        renderCesaResults();
                    }
                }
            });
        }
        
        // Render CESA-style results
        function renderCesaResults() {
            if (!selectedElectionId) return;
            
            $.ajax({
                url: 'api/results.php',
                method: 'GET',
                data: { 
                    action: 'get_results',
                    election_id: selectedElectionId 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayCesaResults(response.data);
                    }
                },
                error: function() {
                    console.error('Failed to load CESA results');
                }
            });
        }
        
        // Display CESA-style results
        function displayCesaResults(data) {
            // Update CESA header date
            const currentDate = new Date();
            const monthNames = ["JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE",
                "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"];
            $('#cesaDate').text(monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear());
            
            let cesaHtml = '';
            
            data.results.forEach(function(position) {
                if (position.candidates && position.candidates.length > 0) {
                    cesaHtml += `
                        <div class="cesa-position-section">
                            <h4 class="cesa-position-title">${position.position_title}</h4>
                            <div class="cesa-chart-container">
                                <div class="cesa-chart-area">
                                    ${renderCesaCandidates(position.candidates)}
                                </div>
                                <div class="cesa-stats">
                                    <div class="cesa-stat-item">
                                        <div class="cesa-stat-number">${position.candidates.reduce((sum, c) => sum + c.vote_count, 0)}</div>
                                        <div class="cesa-stat-label">Total Votes</div>
                                    </div>
                                    <div class="cesa-stat-item">
                                        <div class="cesa-stat-number">${position.candidates.length}</div>
                                        <div class="cesa-stat-label">Candidates</div>
                                    </div>
                                    <div class="cesa-stat-item">
                                        <div class="cesa-stat-number">${position.candidates.length > 0 ? Math.max(...position.candidates.map(c => c.vote_count)) : 0}</div>
                                        <div class="cesa-stat-label">Highest Votes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });
            
            $('#cesaPositionResults').html(cesaHtml);
        }
        
        // Render CESA candidates with bars
        function renderCesaCandidates(candidates) {
            if (!candidates || candidates.length === 0) return '';
            
            // Sort candidates by vote count
            candidates.sort((a, b) => b.vote_count - a.vote_count);
            
            const maxVotes = Math.max(...candidates.map(c => c.vote_count));
            let candidatesHtml = '';
            
            candidates.forEach(function(candidate, index) {
                const percentage = maxVotes > 0 ? (candidate.vote_count / maxVotes) * 100 : 0;
                const barHeight = Math.max((percentage / 100) * 250, 20); // Minimum height of 20px
                
                let candidateClass = 'cesa-candidate';
                if (index === 0) candidateClass += ' winner';
                else if (index === 1) candidateClass += ' runner-up';
                
                const photoUrl = candidate.photo ? `../uploads/candidates/${candidate.photo}` : '../assets/images/default-avatar.svg';
                
                candidatesHtml += `
                    <div class="${candidateClass}">
                        <img src="${photoUrl}" alt="${candidate.candidate_name}" class="cesa-candidate-photo" 
                             onerror="this.src='../assets/images/default-avatar.svg'">
                        <div class="cesa-bar" style="height: ${barHeight}px;">
                            <div class="cesa-vote-count">${candidate.vote_count} votes</div>
                        </div>
                        <div class="cesa-candidate-name">${candidate.candidate_name}</div>
                    </div>
                `;
            });
            
            return candidatesHtml;
        }
        
        // Mobile Menu Functionality
        $(document).ready(function() {
            $('#mobileMenuBtn').click(function() {
                $('.sidebar').toggleClass('show');
                $('#mobileOverlay').toggleClass('show');
            });
            
            $('#mobileOverlay').click(function() {
                $('.sidebar').removeClass('show');
                $('#mobileOverlay').removeClass('show');
            });
            
            $('.sidebar .nav-link').click(function() {
                if (window.innerWidth <= 768) {
                    $('.sidebar').removeClass('show');
                    $('#mobileOverlay').removeClass('show');
                }
            });
            
            $(window).resize(function() {
                if (window.innerWidth > 768) {
                    $('.sidebar').removeClass('show');
                    $('#mobileOverlay').removeClass('show');
                }
            });
        });
    </script>
</body>
</html>