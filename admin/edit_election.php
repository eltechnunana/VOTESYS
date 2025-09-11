<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get database connection
$pdo = getDBConnection();

// Get election ID from URL
$election_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$election_id) {
    header('Location: elections.php');
    exit();
}

// Fetch election details
try {
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$election) {
        header('Location: elections.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching election: " . $e->getMessage();
}

// Fetch all elections for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, election_title, description FROM elections ORDER BY election_title ASC");
    $stmt->execute();
    $all_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_elections = [];
}

// Fetch all unique position titles for dropdown
try {
    $stmt = $pdo->prepare("SELECT DISTINCT position_title, description FROM positions ORDER BY position_title ASC");
    $stmt->execute();
    $all_position_titles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_position_titles = [];
}

// Fetch election-specific positions for this election
try {
    $stmt = $pdo->prepare("SELECT * FROM election_specific_positions WHERE election_id = ? ORDER BY display_order ASC");
    $stmt->execute([$election_id]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $positions = [];
}

// Fetch candidates for each position
$candidates = [];
foreach ($positions as $position) {
    try {
        $stmt = $pdo->prepare("SELECT id, election_specific_position_id, full_name, motto as platform, photo, is_approved, created_at FROM candidates WHERE election_specific_position_id = ? ORDER BY id ASC");
        $stmt->execute([$position['id']]);
        $candidates[$position['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $candidates[$position['id']] = [];
    }
}

$page_title = 'Edit Election: ' . ($election['title'] ?? 'Unknown');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - VoteSystem Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SortableJS for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <style>
        :root {
            --primary-blue: #4A6B8A;
            --success-green: #22C55E;
            --error-red: #DC2626;
            --warning-orange: #F59E0B;
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
            background: var(--primary-blue);
            box-shadow: 2px 0 10px rgba(74, 107, 138, 0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(3px);
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .main-content {
            background-color: var(--neutral-light);
            min-height: 100vh;
        }
        
        .card {
            border: 1px solid var(--neutral-medium);
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            background: var(--base-white);
            margin-bottom: 24px;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        
        .btn-primary {
            background: var(--primary-blue);
            border: 1px solid var(--primary-blue);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #3a5a7a;
            border-color: #3a5a7a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 107, 138, 0.3);
        }
        
        .btn-success {
            background: var(--success-green);
            border: 1px solid var(--success-green);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background: #16a34a;
            border-color: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        
        .btn-danger {
            background: var(--error-red);
            border: 1px solid var(--error-red);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .btn-warning {
            background: var(--warning-orange);
            border: 1px solid var(--warning-orange);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
            border-color: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            color: white;
        }
        
        .form-control {
            border: 2px solid var(--neutral-medium);
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(74, 107, 138, 0.1);
            outline: none;
        }
        
        .form-select {
            border: 2px solid var(--neutral-medium);
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(74, 107, 138, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--neutral-dark);
            margin-bottom: 8px;
            font-size: 0.875rem;
        }
        
        .header-section {
            background: var(--primary-blue);
            color: white;
            padding: 32px 0;
            margin-bottom: 32px;
            border-radius: 0 0 24px 24px;
            box-shadow: 0 4px 16px rgba(74, 107, 138, 0.1);
        }
        
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border-left: 4px solid var(--success-green);
        }
        
        .position-item {
            background: white;
            border: 2px solid var(--neutral-medium);
            border-radius: 12px;
            margin-bottom: 16px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: move;
        }
        
        .position-item:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(74, 107, 138, 0.1);
        }
        
        .candidate-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            padding: 16px;
            transition: all 0.3s ease;
        }
        
        .candidate-item:hover {
            background: #f1f5f9;
            border-color: var(--primary-blue);
        }
        
        .drag-handle {
            color: var(--neutral-medium);
            cursor: grab;
            margin-right: 12px;
        }
        
        .drag-handle:hover {
            color: var(--primary-blue);
        }
        
        .section-header {
            background: linear-gradient(135deg, var(--primary-blue), #3b82f6);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            margin: -1px -1px 0 -1px;
        }
        
        .mobile-nav-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(74, 107, 138, 0.3);
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 991.98px) {
            .mobile-nav-toggle {
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
                margin-left: 0 !important;
                padding-left: 15px;
                padding-right: 15px;
                padding-top: 70px;
            }
            
            .card {
                margin-bottom: 20px;
            }
            
            .section-header {
                padding: 15px;
            }
            
            .section-header h3 {
                font-size: 1.25rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .main-content {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 0.9rem;
            }
            
            .btn-group-sm .btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .position-item {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .candidate-item {
                padding: 12px;
                margin-bottom: 10px;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }
            
            .d-flex.justify-content-between .btn-group {
                align-self: flex-end;
            }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .form-label {
                font-size: 0.9rem;
                font-weight: 600;
            }
            
            .form-control, .form-select {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .mobile-nav-toggle {
                top: 15px;
                left: 15px;
                padding: 8px;
            }
            
            .main-content {
                padding-left: 8px;
                padding-right: 8px;
                padding-top: 60px;
            }
            
            .card {
                border-radius: 12px;
                margin-bottom: 15px;
            }
            
            .section-header {
                padding: 12px;
                border-radius: 12px 12px 0 0;
            }
            
            .section-header h3 {
                font-size: 1.1rem;
            }
            
            .card-body {
                padding: 12px;
            }
            
            .btn {
                padding: 8px 14px;
                font-size: 0.85rem;
                width: 100%;
                margin-bottom: 8px;
            }
            
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                border-radius: 8px !important;
                margin-bottom: 5px;
            }
            
            .position-item, .candidate-item {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .drag-handle {
                display: none;
            }
            
            .modal-dialog {
                margin: 5px;
            }
            
            .modal-body {
                padding: 15px;
            }
            
            .row .col-md-6 {
                margin-bottom: 15px;
            }
            
            .d-flex.justify-content-end {
                justify-content: center !important;
            }
            
            .candidate-photo {
                width: 60px;
                height: 60px;
            }
        }
        
        /* Tablet Responsive Styles */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .main-content {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .position-item {
                padding: 20px;
            }
            
            .candidate-item {
                padding: 15px;
            }
        }
        
        /* Utility Classes for Responsive Design */
        .mobile-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        @media (min-width: 768px) {
            .mobile-stack {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }
        
        .responsive-text {
            font-size: 0.9rem;
        }
        
        @media (min-width: 768px) {
            .responsive-text {
                font-size: 1rem;
            }
        }
        
        /* Candidate item responsive styles */
        .candidate-item {
            margin-bottom: 0.75rem;
        }
        
        .candidate-photo {
            flex-shrink: 0;
        }
        
        .candidate-info {
            min-width: 0;
        }
        
        .candidate-actions {
            flex-shrink: 0;
        }
        
        @media (max-width: 575.98px) {
            .candidate-item .d-flex {
                flex-wrap: wrap;
            }
            
            .candidate-info {
                flex-basis: 100%;
                margin-top: 0.5rem;
                margin-bottom: 0.5rem;
            }
            
            .candidate-actions {
                flex-basis: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-lg-2 d-lg-block sidebar collapse" id="sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">VoteSystem</h4>
                        <small class="text-white-50">Admin Panel</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="elections.php">
                                <i class="fas fa-vote-yea me-2"></i>
                                Elections
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="positions.php">
                                <i class="fas fa-list me-2"></i>
                                Positions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="candidates.php">
                                <i class="fas fa-users me-2"></i>
                                Candidates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="voters.php">
                                <i class="fas fa-user-check me-2"></i>
                                Voters
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="results.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit.php">
                                <i class="fas fa-clipboard-list me-2"></i>
                                Audit Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-user-cog me-2"></i>
                                Admin Users
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-lg-10 ms-sm-auto px-md-4 main-content">
                <!-- Header -->
                <div class="header-section">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="mb-2">
                                    <i class="fas fa-edit me-3"></i>
                                    Edit Election
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Manage election details, positions, and candidates
                                </p>
                            </div>
                            <div>
                                <a href="elections.php" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Elections
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <div id="alertContainer"></div>

                <!-- Election Details and Positions Management Section -->
                <div class="card">
                    <div class="section-header">
                        <h3 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Election Details & Positions Management
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="electionDetailsForm">
                            <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
                            
                            <!-- Election Details Section -->
                            <div class="mb-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Election Information
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="electionTitle" class="form-label">Election Title</label>
                                            <input type="text" class="form-control" id="electionTitle" name="title" 
                                                   value="<?php echo htmlspecialchars($election['election_title']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="electionStatus" class="form-label">Status</label>
                                            <select class="form-select" id="electionStatus" name="status">
                                                <option value="inactive" <?php echo $election['is_active'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                                                <option value="active" <?php echo $election['is_active'] == 1 ? 'selected' : ''; ?>>Active</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="electionDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="electionDescription" name="description" rows="3"><?php echo htmlspecialchars($election['description']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="startDate" class="form-label">Start Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="startDate" name="start_date" 
                                                   value="<?php echo date('Y-m-d\TH:i', strtotime($election['start_date'])); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="endDate" class="form-label">End Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="endDate" name="end_date" 
                                                   value="<?php echo date('Y-m-d\TH:i', strtotime($election['end_date'])); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Positions Management Section -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list me-2"></i>
                                        Positions Management
                                    </h5>
                                    <button type="button" class="btn btn-success" onclick="addPosition()">
                                        <i class="fas fa-plus me-2"></i>
                                        <span class="d-none d-sm-inline">Add Position</span>
                                        <span class="d-sm-none">Add</span>
                                    </button>
                                </div>
                        <div id="positionsContainer">
                            <?php if (empty($positions)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-list fa-3x mb-3"></i>
                                    <p>No positions added yet. Click "Add Position" to get started.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($positions as $position): ?>
                                    <div class="position-item" data-position-id="<?php echo $position['id']; ?>">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-grip-vertical drag-handle d-none d-md-block"></i>
                                            <div class="flex-grow-1">
                                                <div class="mobile-stack mb-2">
                                                    <h5 class="mb-1 responsive-text"><?php echo htmlspecialchars($position['position_title']); ?></h5>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-warning" onclick="editPosition(<?php echo $position['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                            <span class="d-none d-sm-inline ms-1">Edit</span>
                                                        </button>
                                                        <button type="button" class="btn btn-danger" onclick="deletePosition(<?php echo $position['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                            <span class="d-none d-sm-inline ms-1">Delete</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="text-muted mb-2"><?php echo htmlspecialchars($position['description']); ?></p>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">
                                                            <i class="fas fa-users me-1"></i>
                                                            Max Winners: <?php echo $position['max_candidates']; ?>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted">
                                                            <i class="fas fa-sort-numeric-up me-1"></i>
                                                            Order: <?php echo $position['display_order']; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <!-- Candidates for this position -->
                                                <div class="mt-3">
                                                    <div class="mobile-stack mb-2">
                                                        <h6 class="mb-0 responsive-text">Candidates</h6>
                                                        <button type="button" class="btn btn-sm btn-success" onclick="addCandidate(<?php echo $position['id']; ?>)">
                                                            <i class="fas fa-plus me-1"></i>
                                                            <span class="d-none d-sm-inline">Add Candidate</span>
                                                            <span class="d-sm-none">Add</span>
                                                        </button>
                                                    </div>
                                                    <div class="candidates-container" data-position-id="<?php echo $position['id']; ?>">
                                                        <?php if (empty($candidates[$position['id']])): ?>
                                                            <div class="text-center py-2 text-muted">
                                                                <small>No candidates added yet.</small>
                                                            </div>
                                                        <?php else: ?>
                                                            <?php foreach ($candidates[$position['id']] as $candidate): ?>
                                                                <div class="candidate-item" data-candidate-id="<?php echo $candidate['id']; ?>">
                                                                    <div class="d-flex align-items-center flex-wrap">
                                                                        <i class="fas fa-grip-vertical drag-handle me-2 d-none d-md-block"></i>
                                                                        <?php if ($candidate['photo']): ?>
                                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($candidate['photo']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($candidate['full_name']); ?>" 
                                                                 class="rounded-circle me-3 candidate-photo" width="40" height="40">
                                                        <?php else: ?>
                                                                            <div class="bg-secondary rounded-circle me-3 d-flex align-items-center justify-content-center candidate-photo" 
                                                                                 style="width: 40px; height: 40px;">
                                                                                <i class="fas fa-user text-white"></i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div class="flex-grow-1 candidate-info">
                                                            <h6 class="mb-0 responsive-text"><?php echo htmlspecialchars($candidate['full_name']); ?></h6>
                                                            <small class="text-muted"><?php echo htmlspecialchars($position['position_title']); ?></small>
                                                        </div>
                                                                        <div class="btn-group btn-group-sm candidate-actions">
                                                                            <button type="button" class="btn btn-warning" onclick="editCandidate(<?php echo $candidate['id']; ?>)">
                                                                                <i class="fas fa-edit"></i>
                                                                                <span class="d-none d-sm-inline ms-1">Edit</span>
                                                                            </button>
                                                                            <button type="button" class="btn btn-danger" onclick="deleteCandidate(<?php echo $candidate['id']; ?>)">
                                                                                <i class="fas fa-trash"></i>
                                                                                <span class="d-none d-sm-inline ms-1">Delete</span>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                            </div>
                            
                            <!-- Save Button -->
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    Save Election and Position Details
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                    
                    <!-- Voting Control Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Voting Control</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-3 justify-content-center">
                                <button type="button" class="btn btn-success btn-lg" id="startVoteBtn" onclick="startVoting()">
                                    <i class="fas fa-play"></i> Start Vote
                                </button>
                                <button type="button" class="btn btn-danger btn-lg" id="stopVoteBtn" onclick="stopVoting()">
                                    <i class="fas fa-stop"></i> Stop/End Vote
                                </button>
                            </div>
                            <div class="mt-3 text-center">
                                <small class="text-muted">Current Status: <span id="votingStatus" class="fw-bold">Loading...</span></small>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Position Modal -->
    <div class="modal fade" id="positionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="positionModalTitle">Add Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="positionForm">
                        <input type="hidden" id="positionId" name="position_id">
                        <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
                        
                        <div class="mb-3">
                            <label for="positionTitle" class="form-label">Position Title</label>
                            <select class="form-select" id="positionTitle" name="position_title" required>
                                <option value="">Select a position title...</option>
                                <?php foreach ($all_position_titles as $pos): ?>
                                    <option value="<?php echo htmlspecialchars($pos['position_title']); ?>" 
                                            data-description="<?php echo htmlspecialchars($pos['description']); ?>">
                                        <?php echo htmlspecialchars($pos['position_title']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom">+ Add Custom Position Title</option>
                            </select>
                            <input type="text" class="form-control mt-2" id="customPositionTitle" name="custom_position_title" 
                                   placeholder="Enter custom position title" style="display: none;">
                        </div>
                        
                        <div class="mb-3">
                            <label for="positionDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="positionDescription" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="maxWinners" class="form-label">Max Winners</label>
                                    <input type="number" class="form-control" id="maxWinners" name="max_winners" min="1" value="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="positionOrder" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="positionOrder" name="position_order" min="1" value="1" required>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePosition()">Save Position</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Candidate Modal -->
    <div class="modal fade" id="candidateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="candidateModalTitle">Add Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="candidateForm" enctype="multipart/form-data">
                        <input type="hidden" id="candidateId" name="candidate_id">
                        <input type="hidden" id="candidatePositionId" name="position_id">
                        
                        <!-- Election and Position Info -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Election</label>
                                    <input type="text" class="form-control" id="candidateElectionTitle" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" id="candidatePositionTitle" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateFullName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="candidateFullName" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateStudentId" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="candidateStudentId" name="student_id" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateLevel" class="form-label">Level</label>
                                    <input type="text" class="form-control" id="candidateLevel" name="level">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateDepartment" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="candidateDepartment" name="department">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="candidateCourse" class="form-label">Course</label>
                            <input type="text" class="form-control" id="candidateCourse" name="course">
                        </div>
                        
                        <div class="mb-3">
                            <label for="candidateBio" class="form-label">Motto</label>
                            <textarea class="form-control" id="candidateBio" name="bio" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="candidatePhoto" class="form-label">Photo</label>
                            <input type="file" class="form-control" id="candidatePhoto" name="photo" accept="image/*">
                            <div class="form-text">Upload a photo for the candidate (optional)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="candidateOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="candidateOrder" name="candidate_order" min="1" value="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCandidate()">Save Candidate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <script>
        // Initialize sortable for positions
        let positionsSortable;
        let candidatesSortables = {};
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeSortables();
        });
        
        function initializeSortables() {
            // Initialize positions sortable
            const positionsContainer = document.getElementById('positionsContainer');
            if (positionsContainer) {
                positionsSortable = Sortable.create(positionsContainer, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        updatePositionOrder();
                    }
                });
            }
            
            // Initialize candidates sortables
            document.querySelectorAll('.candidates-container').forEach(container => {
                const positionId = container.dataset.positionId;
                candidatesSortables[positionId] = Sortable.create(container, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        updateCandidateOrder(positionId);
                    }
                });
            });
        }
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();
            
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" id="${alertId}" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            alertContainer.insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
        
        // Election Details Form - Modified to save both election details and positions
        document.getElementById('electionDetailsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Client-side date validation
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            if (startDateInput && endDateInput) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                
                if (startDate >= endDate) {
                    showAlert('Error: End date must be after start date', 'danger');
                    return;
                }
                
                if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                    showAlert('Error: Please enter valid start and end dates', 'danger');
                    return;
                }
            }
            
            // Show loading state
            const saveBtn = document.querySelector('#electionDetailsForm button[type="submit"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;
            
            // First, save election details
            const formData = new FormData(this);
            formData.append('action', 'update_election');
            
            fetch('api/edit_election.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Election details saved successfully, now save positions order
                    return updatePositionOrder(true); // Pass true to indicate this is part of unified save
                } else {
                    throw new Error(data.message);
                }
            })
            .then(() => {
                // Both election details and positions saved successfully
                showAlert('Election details and positions saved successfully!', 'success');
                
                // Reset button state
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
                console.error('Error:', error);
                
                // Reset button state
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        });
        
        // Position Management Functions
        function addPosition() {
            document.getElementById('positionModalTitle').textContent = 'Add Position';
            document.getElementById('positionForm').reset();
            document.getElementById('positionId').value = '';
            
            // Set next order number
            const positions = document.querySelectorAll('.position-item');
            document.getElementById('positionOrder').value = positions.length + 1;
            
            new bootstrap.Modal(document.getElementById('positionModal')).show();
        }
        
        function editPosition(positionId) {
            document.getElementById('positionModalTitle').textContent = 'Edit Position';
            document.getElementById('positionId').value = positionId;
            
            // Fetch position data
            fetch(`api/election_positions.php?action=get_position&id=${positionId}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const position = data.position;
                        document.getElementById('positionTitle').value = position.position_title;
                        document.getElementById('positionDescription').value = position.description || '';
                        document.getElementById('maxWinners').value = position.max_votes;
                        document.getElementById('positionOrder').value = position.display_order;
                        
                        new bootstrap.Modal(document.getElementById('positionModal')).show();
                    } else {
                        showAlert('Error loading position data', 'danger');
                    }
                })
                .catch(error => {
                    showAlert('Error loading position data', 'danger');
                    console.error('Error:', error);
                });
        }
        
        function savePosition() {
            const form = document.getElementById('positionForm');
            const formData = new FormData(form);
            
            // Handle custom position title
            const positionTitleSelect = document.getElementById('positionTitle');
            const customPositionTitle = document.getElementById('customPositionTitle');
            
            if (positionTitleSelect.value === 'custom') {
                formData.set('position_title', customPositionTitle.value);
            }
            
            const positionId = document.getElementById('positionId').value;
            formData.append('action', positionId ? 'update_position' : 'add_position');
            
            fetch('api/election_positions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(positionId ? 'Position updated successfully!' : 'Position added successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('positionModal')).hide();
                    location.reload(); // Reload to show updated positions
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error saving position', 'danger');
                console.error('Error:', error);
            });
        }
        
        function deletePosition(positionId) {
            if (confirm('Are you sure you want to delete this position? This will also delete all associated candidates.')) {
                const formData = new FormData();
                formData.append('action', 'delete_position');
                formData.append('position_id', positionId);
                
                fetch('api/election_positions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Position deleted successfully!', 'success');
                        location.reload();
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    showAlert('Error deleting position', 'danger');
                    console.error('Error:', error);
                });
            }
        }
        
        // Candidate Management Functions
        function addCandidate(positionId) {
            document.getElementById('candidateModalTitle').textContent = 'Add Candidate';
            document.getElementById('candidateForm').reset();
            document.getElementById('candidateId').value = '';
            document.getElementById('candidatePositionId').value = positionId;
            
            // Auto-populate election title
            document.getElementById('candidateElectionTitle').value = '<?php echo htmlspecialchars($election["election_title"] ?? $election["title"] ?? ""); ?>';
            
            // Find and populate position title
            const positions = <?php echo json_encode($positions); ?>;
            const position = positions.find(p => p.id == positionId);
            if (position) {
                document.getElementById('candidatePositionTitle').value = position.position_title;
            }
            
            // Set next order number
            const candidates = document.querySelectorAll(`[data-position-id="${positionId}"] .candidate-item`);
            document.getElementById('candidateOrder').value = candidates.length + 1;
            
            new bootstrap.Modal(document.getElementById('candidateModal')).show();
        }
        
        function editCandidate(candidateId) {
            document.getElementById('candidateModalTitle').textContent = 'Edit Candidate';
            document.getElementById('candidateId').value = candidateId;
            
            // Fetch candidate data
            fetch(`api/election_positions.php?action=get_candidate&id=${candidateId}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const candidate = data.candidate;
                        document.getElementById('candidateFullName').value = candidate.full_name;
                        document.getElementById('candidateStudentId').value = candidate.student_id;
                        document.getElementById('candidateLevel').value = candidate.level || '';
                        document.getElementById('candidateDepartment').value = candidate.department || '';
                        document.getElementById('candidateCourse').value = candidate.course || '';
                        document.getElementById('candidateBio').value = candidate.bio || '';
                        document.getElementById('candidateOrder').value = candidate.candidate_order;
                        document.getElementById('candidatePositionId').value = candidate.election_specific_position_id;
                        
                        // Auto-populate election title
                        document.getElementById('candidateElectionTitle').value = '<?php echo htmlspecialchars($election["election_title"] ?? $election["title"] ?? ""); ?>';
                        
                        // Find and populate position title
                        const positions = <?php echo json_encode($positions); ?>;
                        const position = positions.find(p => p.id == candidate.election_specific_position_id);
                        if (position) {
                            document.getElementById('candidatePositionTitle').value = position.position_title;
                        }
                        
                        new bootstrap.Modal(document.getElementById('candidateModal')).show();
                    } else {
                        showAlert('Error loading candidate data', 'danger');
                    }
                })
                .catch(error => {
                    showAlert('Error loading candidate data', 'danger');
                    console.error('Error:', error);
                });
        }
        
        function saveCandidate() {
            const form = document.getElementById('candidateForm');
            const formData = new FormData(form);
            
            const candidateId = document.getElementById('candidateId').value;
            formData.append('action', candidateId ? 'update_candidate' : 'add_candidate');
            
            fetch('api/election_positions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(candidateId ? 'Candidate updated successfully!' : 'Candidate added successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('candidateModal')).hide();
                    location.reload(); // Reload to show updated candidates
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error saving candidate', 'danger');
                console.error('Error:', error);
            });
        }
        
        function deleteCandidate(candidateId) {
            if (confirm('Are you sure you want to delete this candidate?')) {
                const formData = new FormData();
                formData.append('action', 'delete_candidate');
                formData.append('candidate_id', candidateId);
                
                fetch('api/election_positions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Candidate deleted successfully!', 'success');
                        location.reload();
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    showAlert('Error deleting candidate', 'danger');
                    console.error('Error:', error);
                });
            }
        }
        
        // Order Update Functions
        function updatePositionOrder(isUnifiedSave = false) {
            const positions = document.querySelectorAll('.position-item');
            const orderData = [];
            
            positions.forEach((position, index) => {
                orderData.push({
                    id: position.dataset.positionId,
                    order: index + 1
                });
            });
            
            const formData = new FormData();
            formData.append('action', 'update_position_order');
            formData.append('order_data', JSON.stringify(orderData));
            
            const promise = fetch('api/election_positions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (!isUnifiedSave) {
                        showAlert('Position order updated!', 'success');
                    }
                    return data;
                } else {
                    throw new Error(data.message || 'Error updating position order');
                }
            })
            .catch(error => {
                if (!isUnifiedSave) {
                    console.error('Error updating position order:', error);
                }
                throw error;
            });
            
            // Return promise for unified save, otherwise handle normally
            if (isUnifiedSave) {
                return promise;
            } else {
                promise.catch(error => {
                    showAlert('Error updating position order', 'danger');
                });
            }
        }
        
        function updateCandidateOrder(positionId) {
            const candidates = document.querySelectorAll(`[data-position-id="${positionId}"] .candidate-item`);
            const orderData = [];
            
            candidates.forEach((candidate, index) => {
                orderData.push({
                    id: candidate.dataset.candidateId,
                    order: index + 1
                });
            });
            
            const formData = new FormData();
            formData.append('action', 'update_candidate_order');
            formData.append('order_data', JSON.stringify(orderData));
            
            fetch('api/election_positions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Candidate order updated!', 'success');
                } else {
                    showAlert('Error updating candidate order', 'danger');
                }
            })
            .catch(error => {
                console.error('Error updating candidate order:', error);
            });
        }
        
        // Election data for dropdown change
        const electionData = <?php echo json_encode($all_elections); ?>;
        
        // Handle election title dropdown change
        document.getElementById('electionTitle').addEventListener('change', function() {
            const selectedTitle = this.value;
            const selectedElection = electionData.find(election => election.election_title === selectedTitle);
            
            if (selectedElection) {
                document.getElementById('electionDescription').value = selectedElection.description || '';
            }
        });
        
        // Handle position title dropdown change
        document.getElementById('positionTitle').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const customInput = document.getElementById('customPositionTitle');
            const descriptionField = document.getElementById('positionDescription');
            
            if (this.value === 'custom') {
                customInput.style.display = 'block';
                customInput.required = true;
                this.required = false;
                descriptionField.value = '';
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
                this.required = true;
                
                // Populate description from selected option
                if (selectedOption && selectedOption.dataset.description) {
                    descriptionField.value = selectedOption.dataset.description;
                } else {
                    descriptionField.value = '';
                }
            }
        });
        
        // Voting Control Functions
        function startVoting() {
            if (confirm('Are you sure you want to start voting for this election? Voters will be able to cast their votes.')) {
                updateVotingStatus('active');
            }
        }
        
        function stopVoting() {
            if (confirm('Are you sure you want to stop/end voting for this election? This will prevent voters from casting any more votes.')) {
                updateVotingStatus('inactive');
            }
        }
        
        function updateVotingStatus(status) {
            const formData = new FormData();
            formData.append('election_id', <?php echo $election_id; ?>);
            formData.append('voting_status', status);
            
            fetch('api/update_voting_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Voting status updated successfully!', 'success');
                    loadVotingStatus();
                    
                    // Trigger refresh of elections table in elections.php if it's open in another tab/window
                    if (window.opener && window.opener.triggerElectionsTableRefresh) {
                        window.opener.triggerElectionsTableRefresh();
                    }
                    
                    // Also trigger via localStorage for other tabs/windows
                    localStorage.setItem('voting_status_changed', Date.now());
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to update voting status'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error updating voting status', 'danger');
            });
        }
        
        function loadVotingStatus() {
            fetch(`api/get_voting_status.php?election_id=<?php echo $election_id; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const statusElement = document.getElementById('votingStatus');
                    const startBtn = document.getElementById('startVoteBtn');
                    const stopBtn = document.getElementById('stopVoteBtn');
                    
                    if (data.status === 'active') {
                        statusElement.textContent = 'Voting Active';
                        statusElement.className = 'fw-bold text-success';
                        startBtn.disabled = true;
                        stopBtn.disabled = false;
                    } else {
                        statusElement.textContent = 'Voting Inactive';
                        statusElement.className = 'fw-bold text-danger';
                        startBtn.disabled = false;
                        stopBtn.disabled = true;
                    }
                } else {
                    document.getElementById('votingStatus').textContent = 'Unknown';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('votingStatus').textContent = 'Error loading status';
            });
        }
        
        // Date validation function
        function validateDates() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            if (startDateInput && endDateInput && startDateInput.value && endDateInput.value) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                
                if (startDate >= endDate) {
                    endDateInput.setCustomValidity('End date must be after start date');
                    endDateInput.classList.add('is-invalid');
                    startDateInput.classList.add('is-invalid');
                } else {
                    endDateInput.setCustomValidity('');
                    endDateInput.classList.remove('is-invalid');
                    startDateInput.classList.remove('is-invalid');
                }
            }
        }
        
        // Load voting status on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadVotingStatus();
            
            // Add real-time date validation
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', validateDates);
                endDateInput.addEventListener('change', validateDates);
                
                // Initial validation
                validateDates();
            }
        });
    </script>
</body>
</html>