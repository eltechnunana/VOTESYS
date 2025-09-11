<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Define available courses
$courses = [
    ['id' => 1, 'name' => 'Computer Science', 'category' => 'Technology'],
    ['id' => 2, 'name' => 'Information Technology', 'category' => 'Technology'],
    ['id' => 3, 'name' => 'Software Engineering', 'category' => 'Technology'],
    ['id' => 4, 'name' => 'Business Administration', 'category' => 'Business'],
    ['id' => 5, 'name' => 'Marketing', 'category' => 'Business'],
    ['id' => 6, 'name' => 'Accounting', 'category' => 'Business'],
    ['id' => 7, 'name' => 'Civil Engineering', 'category' => 'Engineering'],
    ['id' => 8, 'name' => 'Mechanical Engineering', 'category' => 'Engineering'],
    ['id' => 9, 'name' => 'Electrical Engineering', 'category' => 'Engineering'],
    ['id' => 10, 'name' => 'Elementary Education', 'category' => 'Education'],
    ['id' => 11, 'name' => 'Secondary Education', 'category' => 'Education'],
    ['id' => 12, 'name' => 'Special Education', 'category' => 'Education'],
    ['id' => 13, 'name' => 'Nursing', 'category' => 'Health Sciences'],
    ['id' => 14, 'name' => 'Physical Therapy', 'category' => 'Health Sciences'],
    ['id' => 15, 'name' => 'Medical Technology', 'category' => 'Health Sciences'],
    ['id' => 16, 'name' => 'Psychology', 'category' => 'Social Sciences'],
    ['id' => 17, 'name' => 'Sociology', 'category' => 'Social Sciences'],
    ['id' => 18, 'name' => 'Political Science', 'category' => 'Social Sciences']
];

$page_title = 'Voter Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - VoteSystem Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css" rel="stylesheet">
    
    <style>
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
            background-color: var(--primary-blue);
            box-shadow: 2px 0 10px rgba(37, 99, 235, 0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 0.875rem 1.25rem;
            margin: 0.25rem 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.15);
            transform: translateX(3px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
            font-weight: 600;
        }
        
        .main-content {
            background-color: var(--neutral-light);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .card {
            border: 1px solid var(--neutral-medium);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background-color: var(--base-white);
            transition: all 0.2s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            border-radius: 8px;
            padding: 0.625rem 1.25rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #3a5a7a;
            border-color: #3a5a7a;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: var(--success-green);
            border-color: var(--success-green);
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-success:hover {
            background-color: #16a34a;
            border-color: #16a34a;
        }
        
        .btn-danger {
            background-color: var(--error-red);
            border-color: var(--error-red);
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-danger:hover {
            background-color: #b91c1c;
            border-color: #b91c1c;
        }
        
        .btn-warning {
            background-color: #f59e0b;
            border-color: #f59e0b;
            border-radius: 8px;
            font-weight: 600;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #d97706;
            border-color: #d97706;
            color: white;
        }
        
        .btn-secondary {
            background-color: var(--neutral-medium);
            border-color: var(--neutral-medium);
            color: var(--neutral-dark);
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-light {
            background-color: var(--base-white);
            border-color: var(--neutral-medium);
            color: var(--primary-blue);
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-light:hover {
            background-color: var(--neutral-light);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .table {
            background-color: var(--base-white);
        }
        
        .table th {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem 0.75rem;
        }
        
        .table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--neutral-medium);
            vertical-align: middle;
        }
        
        .modal-header {
            background-color: var(--primary-blue);
            color: white;
            border-radius: 12px 12px 0 0;
            border-bottom: none;
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .form-control {
            border: 1px solid var(--neutral-medium);
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(74, 107, 138, 0.1);
        }
        
        .form-select {
            border: 1px solid var(--neutral-medium);
            border-radius: 8px;
            padding: 0.75rem;
        }
        
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(74, 107, 138, 0.1);
        }
        
        .header-section {
            background-color: var(--primary-blue);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .csv-template {
            background-color: var(--neutral-light);
            border: 2px dashed var(--neutral-medium);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .file-upload-area {
            border: 2px dashed var(--primary-blue);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background-color: var(--neutral-light);
            transition: all 0.2s ease;
        }
        
        .file-upload-area:hover {
            background-color: rgba(37, 99, 235, 0.05);
            border-color: #1d4ed8;
        }
        
        .file-upload-area.dragover {
            background-color: rgba(37, 99, 235, 0.1);
            border-color: var(--primary-blue);
        }
        
        .bulk-actions {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: none;
        }
        
        .badge {
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
        }
        
        .bg-success {
            background-color: var(--success-green) !important;
        }
        
        .bg-danger {
            background-color: var(--error-red) !important;
        }
        
        .border-bottom {
            border-bottom: 2px solid var(--neutral-medium) !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: var(--neutral-dark);
            font-weight: 700;
        }
        
        .text-muted {
            color: #6b7280 !important;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }
        
        .btn-outline-secondary {
            border-color: var(--neutral-medium);
            color: var(--neutral-dark);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--neutral-medium);
            border-color: var(--neutral-medium);
            color: var(--neutral-dark);
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
                padding: 1rem;
            }
            
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1060;
                background-color: var(--primary-blue);
                color: white;
                border: none;
                border-radius: 8px;
                padding: 0.75rem;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
            }
            
            .mobile-overlay.show {
                display: block;
            }
            
            .header-section {
                margin-top: 4rem;
                padding: 1.5rem 0;
            }
            
            .header-section .d-flex {
                flex-direction: column;
                gap: 1rem;
            }
            
            .header-section .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                border-radius: 6px !important;
                margin-bottom: 0.25rem;
            }
            
            .modal-dialog {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .bulk-actions {
                padding: 0.75rem;
            }
            
            .file-upload-area {
                padding: 1.5rem 1rem;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .header-section h1 {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }
            
            .status-badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn d-md-none" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white"><i class="fas fa-vote-yea me-2"></i>VoteSystem</h4>
                        <small class="text-white-50">Admin Panel</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="elections.php">
                                <i class="fas fa-calendar-check me-2"></i>Elections
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="candidates.php">
                                <i class="fas fa-users me-2"></i>Candidates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="voters.php">
                                <i class="fas fa-user-check me-2"></i>Voters
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reset_voter_passwords.php" style="padding-left: 3rem; font-size: 0.9rem;">
                                <i class="fas fa-key me-2"></i>Reset Passwords
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-user-cog me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="results.php">
                                <i class="fas fa-chart-bar me-2"></i>Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit.php">
                                <i class="fas fa-clipboard-list me-2"></i>Audit Logs
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="header-section">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1><i class="fas fa-user-check me-2"></i>Voter Management</h1>
                                <p class="mb-0">Manage registered voters and import voter lists</p>
                            </div>
                            <div>
                                <a href="reset_voter_passwords.php" class="btn btn-warning me-2">
                                    <i class="fas fa-key me-2"></i>Reset Passwords
                                </a>
                                <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="fas fa-upload me-2"></i>Import CSV
                                </button>
                                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addVoterModal">
                                    <i class="fas fa-plus me-2"></i>Add Voter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="filterElection" class="form-label">Filter by Election</label>
                                <select class="form-select" id="filterElection">
                                    <option value="">All Elections</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterStatus" class="form-label">Filter by Status</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="voted">Voted</option>
                                    <option value="not_voted">Not Voted</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="searchVoter" class="form-label">Search Voters</label>
                                <input type="text" class="form-control" id="searchVoter" placeholder="Search by name, email, or student ID...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-outline-secondary d-block w-100" onclick="clearFilters()">
                                    <i class="fas fa-times me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><strong id="selectedCount">0</strong> voters selected</span>
                        <div>
                            <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
                                <i class="fas fa-trash me-1"></i>Delete Selected
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="clearSelection()">
                                <i class="fas fa-times me-1"></i>Clear Selection
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Voters Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="votersTable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Student ID</th>
                                        <th>Course</th>
                                        <th>Status</th>
                                        <th>Vote Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Voter Modal -->
    <div class="modal fade" id="addVoterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addVoterForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="voterFirstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="voterFirstName" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="voterLastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="voterLastName" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="voterEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="voterEmail" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="voterStudentId" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="voterStudentId" name="student_id" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="voterYearLevel" class="form-label">Year Level</label>
                                    <select class="form-select" id="voterYearLevel" name="year_level" required>
                                        <option value="">Select Year Level</option>
                                        <option value="1st">1st Year</option>
                                        <option value="2nd">2nd Year</option>
                                        <option value="3rd">3rd Year</option>
                                        <option value="4th">4th Year</option>
                                        <option value="Graduate">Graduate</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="voterCourse" class="form-label">Course</label>
                                    <select class="form-select" id="voterCourse" name="course" required>
                                        <option value="">Select Course</option>
                                        <?php 
                                        $current_category = '';
                                        foreach ($courses as $course): 
                                            if ($course['category'] !== $current_category) {
                                                if ($current_category !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . htmlspecialchars($course['category']) . '">';
                                                $current_category = $course['category'];
                                            }
                                        ?>
                                            <option value="<?php echo htmlspecialchars($course['name']); ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                                        <?php endforeach; ?>
                                        <?php if ($current_category !== '') echo '</optgroup>'; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Password Security:</strong> A secure password will be automatically generated and sent to the voter's email address after registration.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Voter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Voter Modal -->
    <div class="modal fade" id="editVoterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editVoterForm">
                    <input type="hidden" id="editVoterId" name="voter_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editVoterFirstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="editVoterFirstName" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editVoterLastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="editVoterLastName" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editVoterEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="editVoterEmail" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editVoterStudentId" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="editVoterStudentId" name="student_id" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editVoterYearLevel" class="form-label">Year Level</label>
                                    <select class="form-select" id="editVoterYearLevel" name="year_level" required>
                                        <option value="">Select Year Level</option>
                                        <option value="1st">1st Year</option>
                                        <option value="2nd">2nd Year</option>
                                        <option value="3rd">3rd Year</option>
                                        <option value="4th">4th Year</option>
                                        <option value="Graduate">Graduate</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editVoterCourse" class="form-label">Course</label>
                                    <select class="form-select" id="editVoterCourse" name="course" required>
                                        <option value="">Select Course</option>
                                        <?php 
                                        $current_category = '';
                                        foreach ($courses as $course): 
                                            if ($course['category'] !== $current_category) {
                                                if ($current_category !== '') echo '</optgroup>';
                                                echo '<optgroup label="' . htmlspecialchars($course['category']) . '">';
                                                $current_category = $course['category'];
                                            }
                                        ?>
                                            <option value="<?php echo htmlspecialchars($course['name']); ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                                        <?php endforeach; ?>
                                        <?php if ($current_category !== '') echo '</optgroup>'; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editVoterStatus" class="form-label">Status</label>
                            <select class="form-select" id="editVoterStatus" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="resetVoterPassword" name="reset_password" value="1">
                                <label class="form-check-label" for="resetVoterPassword">
                                    Reset Password
                                </label>
                            </div>
                            <div class="form-text">Check this box to generate a new secure password and send it to the voter's email address.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Voter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Import CSV Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Voters from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="csv-template">
                        <h6><i class="fas fa-info-circle me-2"></i>CSV Format Requirements</h6>
                        <p class="mb-2">Your CSV file must include the following columns (in any order):</p>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><strong>first_name</strong> - Voter's first name</li>
                                    <li><strong>last_name</strong> - Voter's last name</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><strong>email</strong> - Valid email address</li>
                                    <li><strong>student_id</strong> - Unique student identifier</li>
                                </ul>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="downloadTemplate()">
                            <i class="fas fa-download me-1"></i>Download Template
                        </button>
                    </div>
                    
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="importElection" class="form-label">Select Election</label>
                            <select class="form-select" id="importElection" name="election_id" required>
                                <option value="">Choose election for imported voters</option>
                            </select>
                        </div>
                        
                        <div class="file-upload-area" id="fileUploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h5>Drag and drop your CSV file here</h5>
                            <p class="text-muted">or click to browse files</p>
                            <input type="file" class="d-none" id="csvFile" name="csv_file" accept=".csv" required>
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('csvFile').click()">
                                <i class="fas fa-folder-open me-1"></i>Choose File
                            </button>
                        </div>
                        
                        <div id="fileInfo" class="mt-3" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-file-csv me-2"></i>
                                <span id="fileName"></span>
                                <span class="float-end">
                                    <span id="fileSize"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        
                        <div id="importProgress" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted">Importing voters...</small>
                        </div>
                        
                        <div id="importResults" class="mt-3" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="startImport()" id="importBtn">
                        <i class="fas fa-upload me-1"></i>Import Voters
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
    
    <script>
        let votersTable;
        let selectedVoters = [];
        
        $(document).ready(function() {
            // Initialize DataTable
            votersTable = $('#votersTable').DataTable({
                ajax: {
                    url: 'api/voters.php',
                    type: 'GET',
                    data: function(d) {
                        d.election_id = $('#filterElection').val();
                        d.status = $('#filterStatus').val();
                        d.search = $('#searchVoter').val();
                    },
                    dataSrc: 'data'
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `<input type="checkbox" class="voter-checkbox" value="${row.id}">`;
                        }
                    },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return `${row.first_name} ${row.last_name}`;
                        }
                    },
                    { data: 'email' },
                    { data: 'student_id' },
                    { data: 'course' },
                    {
                        data: 'is_active',
                        render: function(data) {
                            return data == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                        }
                    },
                    {
                        data: 'vote_status',
                        render: function(data) {
                            const badges = {
                                'voted': '<span class="badge bg-primary">Voted</span>',
                                'not_voted': '<span class="badge bg-warning">Not Voted</span>'
                            };
                            return badges[data] || '<span class="badge bg-light">Unknown</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-sm btn-primary me-1" onclick="editVoter(${row.id})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteVoter(${row.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                responsive: true,
                pageLength: 25,
                order: [[1, 'asc']]
            });
            
            // Load elections for filters and forms
            loadElections();
            
            // Filter handlers
            $('#filterElection, #filterStatus').on('change', function() {
                votersTable.ajax.reload();
            });
            
            $('#searchVoter').on('keyup', function() {
                votersTable.ajax.reload();
            });
            
            // Form submissions
            $('#addVoterForm').on('submit', handleAddVoter);
            $('#editVoterForm').on('submit', handleEditVoter);
            
            // Modal events
            $('#addVoterModal').on('hidden.bs.modal', function() {
                resetForm('#addVoterForm');
            });
            
            $('#editVoterModal').on('hidden.bs.modal', function() {
                resetForm('#editVoterForm');
            });
            
            // Checkbox handlers
            $('#selectAll').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.voter-checkbox').prop('checked', isChecked);
                updateSelectedVoters();
            });
            
            $(document).on('change', '.voter-checkbox', function() {
                updateSelectedVoters();
            });
            
            // File upload handlers
            setupFileUpload();
        });
        
        function loadElections() {
            $.get('api/elections.php', function(response) {
                if (response.success) {
                    const elections = response.data;
                    const $filterElection = $('#filterElection');
                    const $voterElection = $('#voterElection');
                    const $importElection = $('#importElection');
                    
                    elections.forEach(election => {
                        const option = `<option value="${election.id}">${election.title}</option>`;
                        $filterElection.append(option);
                        $voterElection.append(option);
                        $importElection.append(option);
                    });
                }
            });
        }
        
        function handleAddVoter(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'api/voters.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Voter added successfully!', 'success');
                        $('#addVoterModal').modal('hide');
                        votersTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('XHR Status:', xhr.status);
                    console.log('Response Text:', xhr.responseText);
                    console.log('Error:', error);
                    showAlert('An error occurred while adding the voter. Check console for details.', 'danger');
                }
            });
        }
        
        function handleEditVoter(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            const voterId = $('#editVoterId').val();
            const data = {};
            
            formData.forEach(item => {
                data[item.name] = item.value;
            });
            
            $.ajax({
                url: `api/voters.php?id=${voterId}`,
                type: 'PUT',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        showAlert('Voter updated successfully!', 'success');
                        $('#editVoterModal').modal('hide');
                        votersTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while updating the voter.', 'danger');
                }
            });
        }
        
        function editVoter(voterId) {
            $.get(`api/voters.php?id=${voterId}`, function(response) {
                if (response.success) {
                    const voter = response.data;
                    
                    $('#editVoterId').val(voter.id);
                    $('#editVoterFirstName').val(voter.first_name);
                    $('#editVoterLastName').val(voter.last_name);
                    $('#editVoterEmail').val(voter.email);
                    $('#editVoterStudentId').val(voter.student_id);
                    $('#editVoterYearLevel').val(voter.year_level);
                    $('#editVoterCourse').val(voter.course);
                    $('#editVoterStatus').val(voter.status);
                    
                    $('#editVoterModal').modal('show');
                } else {
                    showAlert('Error: ' + response.message, 'danger');
                }
            });
        }
        
        function deleteVoter(voterId) {
            if (confirm('Are you sure you want to delete this voter?')) {
                $.ajax({
                    url: `api/voters.php?id=${voterId}`,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            showAlert('Voter deleted successfully!', 'success');
                            votersTable.ajax.reload();
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while deleting the voter.', 'danger');
                    }
                });
            }
        }
        
        function updateSelectedVoters() {
            selectedVoters = [];
            $('.voter-checkbox:checked').each(function() {
                selectedVoters.push($(this).val());
            });
            
            $('#selectedCount').text(selectedVoters.length);
            
            if (selectedVoters.length > 0) {
                $('#bulkActions').show();
            } else {
                $('#bulkActions').hide();
            }
            
            // Update select all checkbox
            const totalCheckboxes = $('.voter-checkbox').length;
            const checkedCheckboxes = $('.voter-checkbox:checked').length;
            
            if (checkedCheckboxes === 0) {
                $('#selectAll').prop('indeterminate', false).prop('checked', false);
            } else if (checkedCheckboxes === totalCheckboxes) {
                $('#selectAll').prop('indeterminate', false).prop('checked', true);
            } else {
                $('#selectAll').prop('indeterminate', true);
            }
        }
        
        function clearSelection() {
            $('.voter-checkbox').prop('checked', false);
            $('#selectAll').prop('checked', false);
            updateSelectedVoters();
        }
        
        function bulkDelete() {
            if (selectedVoters.length === 0) {
                showAlert('No voters selected.', 'warning');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${selectedVoters.length} selected voters?`)) {
                $.ajax({
                    url: 'api/voters.php',
                    type: 'POST',
                    data: {
                        action: 'bulk_delete',
                        voter_ids: selectedVoters
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            votersTable.ajax.reload();
                            clearSelection();
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while deleting voters.', 'danger');
                    }
                });
            }
        }
        
        function clearFilters() {
            $('#filterElection').val('');
            $('#filterStatus').val('');
            $('#searchVoter').val('');
            votersTable.ajax.reload();
        }
        
        function setupFileUpload() {
            const $fileUploadArea = $('#fileUploadArea');
            const $csvFile = $('#csvFile');
            
            // Drag and drop handlers
            $fileUploadArea.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            $fileUploadArea.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            $fileUploadArea.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $csvFile[0].files = files;
                    handleFileSelect(files[0]);
                }
            });
            
            // File input change handler
            $csvFile.on('change', function() {
                if (this.files.length > 0) {
                    handleFileSelect(this.files[0]);
                }
            });
        }
        
        function handleFileSelect(file) {
            if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                showAlert('Please select a valid CSV file.', 'warning');
                return;
            }
            
            $('#fileName').text(file.name);
            $('#fileSize').text(formatFileSize(file.size));
            $('#fileInfo').show();
        }
        
        function clearFile() {
            $('#csvFile').val('');
            $('#fileInfo').hide();
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function startImport() {
            const formData = new FormData($('#importForm')[0]);
            formData.append('action', 'import_csv');
            
            if (!$('#importElection').val()) {
                showAlert('Please select an election.', 'warning');
                return;
            }
            
            if (!$('#csvFile')[0].files.length) {
                showAlert('Please select a CSV file.', 'warning');
                return;
            }
            
            $('#importProgress').show();
            $('#importBtn').prop('disabled', true);
            
            $.ajax({
                url: 'api/voters.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#importProgress').hide();
                    $('#importBtn').prop('disabled', false);
                    
                    if (response.success) {
                        let resultHtml = `
                            <div class="alert alert-success">
                                <h6><i class="fas fa-check-circle me-2"></i>Import Completed</h6>
                                <p class="mb-1"><strong>${response.imported}</strong> voters imported successfully.</p>
                        `;
                        
                        if (response.errors && response.errors.length > 0) {
                            resultHtml += `
                                <p class="mb-1"><strong>${response.errors.length}</strong> errors encountered:</p>
                                <ul class="mb-0">
                            `;
                            response.errors.slice(0, 10).forEach(error => {
                                resultHtml += `<li>${error}</li>`;
                            });
                            if (response.errors.length > 10) {
                                resultHtml += `<li>... and ${response.errors.length - 10} more errors</li>`;
                            }
                            resultHtml += '</ul>';
                        }
                        
                        resultHtml += '</div>';
                        $('#importResults').html(resultHtml).show();
                        
                        votersTable.ajax.reload();
                        clearFile();
                    } else {
                        $('#importResults').html(`
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-circle me-2"></i>Import Failed</h6>
                                <p class="mb-0">${response.message}</p>
                            </div>
                        `).show();
                    }
                },
                error: function() {
                    $('#importProgress').hide();
                    $('#importBtn').prop('disabled', false);
                    showAlert('An error occurred during import.', 'danger');
                }
            });
        }
        
        function downloadTemplate() {
            const csvContent = 'first_name,last_name,email,student_id\nJohn,Doe,john.doe@example.com,STU001\nJane,Smith,jane.smith@example.com,STU002';
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'voters_template.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function resetForm(formSelector) {
            $(formSelector)[0].reset();
            $(formSelector).find('.is-invalid').removeClass('is-invalid');
            $(formSelector).find('.invalid-feedback').remove();
        }
        
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('.main-content').prepend(alertHtml);
            
            setTimeout(() => {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        // Mobile Menu Functionality
        $(document).ready(function() {
            const mobileMenuBtn = $('#mobileMenuBtn');
            const sidebar = $('.sidebar');
            const mobileOverlay = $('#mobileOverlay');
            
            // Toggle mobile menu
            mobileMenuBtn.on('click', function() {
                sidebar.toggleClass('show');
                mobileOverlay.toggleClass('show');
                $(this).find('i').toggleClass('fa-bars fa-times');
            });
            
            // Close menu when clicking overlay
            mobileOverlay.on('click', function() {
                sidebar.removeClass('show');
                mobileOverlay.removeClass('show');
                mobileMenuBtn.find('i').removeClass('fa-times').addClass('fa-bars');
            });
            
            // Close menu when clicking a nav link on mobile
            $('.sidebar .nav-link').on('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.removeClass('show');
                    mobileOverlay.removeClass('show');
                    mobileMenuBtn.find('i').removeClass('fa-times').addClass('fa-bars');
                }
            });
            
            // Handle window resize
            $(window).on('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.removeClass('show');
                    mobileOverlay.removeClass('show');
                    mobileMenuBtn.find('i').removeClass('fa-times').addClass('fa-bars');
                }
            });
        });
    </script>
</body>
</html>