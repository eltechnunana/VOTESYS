<?php
define('SECURE_ACCESS', true);
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Candidate Management';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
        
        .btn-secondary {
            background-color: var(--neutral-medium);
            border-color: var(--neutral-medium);
            color: var(--neutral-dark);
            border-radius: 8px;
            font-weight: 600;
        }
        
        .candidate-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--neutral-medium);
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
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-select {
            border: 1px solid var(--neutral-medium);
            border-radius: 8px;
            padding: 0.75rem;
        }
        
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .table {
            background-color: var(--base-white);
        }
        
        .table th {
            background-color: var(--neutral-light);
            color: var(--neutral-dark);
            font-weight: 600;
            border-bottom: 2px solid var(--neutral-medium);
            padding: 1rem 0.75rem;
        }
        
        .table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--neutral-medium);
            vertical-align: middle;
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
        
        .photo-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid var(--neutral-medium);
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
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
                margin-top: 4rem;
            }
            
            .btn-toolbar {
                width: 100%;
            }
            
            .btn-toolbar .btn {
                width: 100%;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .candidate-photo {
                width: 40px;
                height: 40px;
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
            
            .photo-preview {
                max-width: 150px;
                max-height: 150px;
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
            
            .d-flex.justify-content-between h1 {
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
            
            .candidate-photo {
                width: 35px;
                height: 35px;
            }
            
            .badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            .modal-body .row {
                margin-bottom: 0.5rem;
            }
            
            .form-control,
            .form-select {
                font-size: 0.875rem;
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
                        <h4 class="text-white"><i class="fas fa-vote-yea"></i> VoteSystem</h4>
                        <p class="text-white-50">Admin Panel</p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="elections.php">
                                <i class="fas fa-calendar-alt me-2"></i> Elections
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="candidates.php">
                                <i class="fas fa-users me-2"></i> Candidates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="voters.php">
                                <i class="fas fa-user-friends me-2"></i> Voters
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users-cog me-2"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="results.php">
                                <i class="fas fa-chart-bar me-2"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="audit.php">
                                <i class="fas fa-clipboard-list me-2"></i> Audit Logs
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-users me-2"></i><?php echo $page_title; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                            <i class="fas fa-plus me-2"></i>Add Candidate
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="filterElection" class="form-label">Filter by Election</label>
                                <select class="form-select" id="filterElection">
                                    <option value="">All Elections</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filterPosition" class="form-label">Filter by Position</label>
                                <select class="form-select" id="filterPosition">
                                    <option value="">All Positions</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filterStatus" class="form-label">Filter by Status</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">All Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Candidates Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="candidatesTable">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Election</th>
                                        <th>Position</th>
                                        <th>Votes</th>
                                        <th>Status</th>
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

    <!-- Add Candidate Modal -->
    <div class="modal fade" id="addCandidateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCandidateForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="candidateName" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="candidateEmail" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateElection" class="form-label">Election</label>
                                    <select class="form-select" id="candidateElection" name="election_id" required>
                                        <option value="">Select Election</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidatePosition" class="form-label">Position</label>
                                    <select class="form-select" id="candidatePosition" name="position_id" required>
                                        <option value="">Select Position</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidatePhoto" class="form-label">Photo</label>
                                    <input type="file" class="form-control" id="candidatePhoto" name="photo" accept="image/*">
                                    <div id="photoPreview" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="candidateStatus" class="form-label">Status</label>
                                    <select class="form-select" id="candidateStatus" name="status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="candidatePlatform" class="form-label">Motto</label>
                            <textarea class="form-control" id="candidatePlatform" name="platform" rows="4" placeholder="Describe the candidate's motto and goals..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Candidate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Candidate Modal -->
    <div class="modal fade" id="editCandidateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCandidateForm" enctype="multipart/form-data">
                    <input type="hidden" id="editCandidateId" name="candidate_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCandidateName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="editCandidateName" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCandidateEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="editCandidateEmail" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCandidateElection" class="form-label">Election</label>
                                    <select class="form-select" id="editCandidateElection" name="election_id" required>
                                        <option value="">Select Election</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCandidatePosition" class="form-label">Position</label>
                                    <select class="form-select" id="editCandidatePosition" name="position_id" required>
                                        <option value="">Select Position</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCandidatePhoto" class="form-label">Photo</label>
                                    <input type="file" class="form-control" id="editCandidatePhoto" name="photo" accept="image/*">
                                    <small class="text-muted">Leave empty to keep current photo</small>
                                    <div id="editPhotoPreview" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCandidateStatus" class="form-label">Status</label>
                                    <select class="form-select" id="editCandidateStatus" name="status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editCandidatePlatform" class="form-label">Motto</label>
                            <textarea class="form-control" id="editCandidatePlatform" name="platform" rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Candidate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        let candidatesTable;
        let isEditMode = false;
        
        $(document).ready(function() {
            // Disable DataTables error alerts
            $.fn.dataTable.ext.errMode = 'none';
            
            // Initialize DataTable
            candidatesTable = $('#candidatesTable').DataTable({
                ajax: {
                    url: 'api/candidates.php',
                    type: 'GET',
                    dataSrc: 'data',
                    error: function(xhr, error, code) {
                        console.log('Ajax error:', error, code);
                        // Show user-friendly message instead of alert
                        $('#candidatesTable tbody').html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-database me-2"></i>Database temporarily unavailable. Please ensure MySQL is running and try refreshing the page.</td></tr>');
                    }
                },
                columns: [
                    {
                        data: 'photo',
                        render: function(data, type, row) {
                            if (data) {
                                return `<img src="data:image/jpeg;base64,${data}" class="candidate-photo" alt="${row.full_name}">`;
                            } else {
                                return '<div class="candidate-photo bg-secondary d-flex align-items-center justify-content-center"><i class="fas fa-user text-white"></i></div>';
                            }
                        }
                    },
                    { data: 'full_name' },
                    { data: 'election_name' },
                    { data: 'position_title' },
                    { 
                        data: 'vote_count',
                        render: function(data) {
                            return data || 0;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            return data == 1 ? 
                                '<span class="badge bg-success">Active</span>' : 
                                '<span class="badge bg-danger">Inactive</span>';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-sm btn-primary me-1" onclick="editCandidate(${row.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteCandidate(${row.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                responsive: true,
                pageLength: 10,
                order: [[1, 'asc']]
            });
            
            // Load elections and positions
            loadElections();
            loadPositions();
            
            // Filter handlers
            $('#filterElection, #filterPosition, #filterStatus').on('change', function() {
                candidatesTable.ajax.reload();
            });
            
            // Cascading dropdown handlers
            $('#candidateElection').on('change', function() {
                const electionId = $(this).val();
                if (electionId) {
                    loadPositions(electionId, '#candidatePosition');
                } else {
                    $('#candidatePosition').find('option:not(:first)').remove();
                }
            });
            
            $('#editCandidateElection').on('change', function() {
                const electionId = $(this).val();
                if (electionId) {
                    loadPositions(electionId, '#editCandidatePosition');
                } else {
                    $('#editCandidatePosition').find('option:not(:first)').remove();
                }
            });
            
            // Photo preview handlers
            $('#candidatePhoto').on('change', function() {
                previewPhoto(this, '#photoPreview');
            });
            
            $('#editCandidatePhoto').on('change', function() {
                previewPhoto(this, '#editPhotoPreview');
            });
            
            // Form submissions
            $('#addCandidateForm').on('submit', handleAddCandidate);
            $('#editCandidateForm').on('submit', handleEditCandidate);
            
            // Modal events
            $('#addCandidateModal').on('hidden.bs.modal', function() {
                resetForm('#addCandidateForm');
                $('#photoPreview').empty();
            });
            
            $('#editCandidateModal').on('hidden.bs.modal', function() {
                resetForm('#editCandidateForm');
                $('#editPhotoPreview').empty();
            });
        });
        
        function loadElections() {
            $.get('api/elections.php', function(response) {
                if (response.success) {
                    const elections = response.data;
                    const selects = ['#candidateElection', '#editCandidateElection', '#filterElection'];
                    
                    selects.forEach(selector => {
                        const $select = $(selector);
                        $select.find('option:not(:first)').remove();
                        
                        elections.forEach(election => {
                            $select.append(`<option value="${election.id}">${election.title}</option>`);
                        });
                    });
                }
            }).fail(function() {
                console.error('Failed to load elections');
            });
        }
        
        function loadPositions(electionId = null, targetSelector = null) {
            let url = 'api/elections.php?action=positions';
            if (electionId) {
                url = `api/election_positions.php?action=get_positions&election_id=${electionId}`;
            }
            
            $.get(url, function(response) {
                if (response.success) {
                    const positions = response.data;
                    let selects = targetSelector ? [targetSelector] : ['#candidatePosition', '#editCandidatePosition', '#filterPosition'];
                    
                    selects.forEach(selector => {
                        const $select = $(selector);
                        $select.find('option:not(:first)').remove();
                        
                        positions.forEach(position => {
                            const title = position.position_title || position.title;
                            $select.append(`<option value="${position.id}">${title}</option>`);
                        });
                    });
                }
            }).fail(function() {
                console.error('Failed to load positions');
            });
        }
        
        function previewPhoto(input, previewSelector) {
            const file = input.files[0];
            const $preview = $(previewSelector);
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $preview.html(`<img src="${e.target.result}" class="photo-preview" alt="Preview">`);
                };
                reader.readAsDataURL(file);
            } else {
                $preview.empty();
            }
        }
        
        function handleAddCandidate(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: 'api/candidates.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                         showAlert('Candidate added successfully!', 'success');
                         $('#addCandidateModal').modal('hide');
                         candidatesTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while adding the candidate.', 'danger');
                }
            });
        }
        
        function handleEditCandidate(e) {
            e.preventDefault();
            
            const candidateId = $('#editCandidateId').val();
            
            if (!candidateId) {
                showAlert('Error: Candidate ID is missing. Please try again.', 'danger');
                return;
            }
            
            // Collect form data as JSON object
            const formData = {
                candidate_id: candidateId,
                full_name: $('#editCandidateName').val(),
                email: $('#editCandidateEmail').val(),
                election_id: $('#editCandidateElection').val(),
                position_id: $('#editCandidatePosition').val(),
                platform: $('#editCandidatePlatform').val(),
                is_approved: $('#editCandidateStatus').val()
            };
            
            $.ajax({
                url: `api/candidates.php?id=${candidateId}&_t=${Date.now()}`,
                type: 'PUT',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                cache: false,
                success: function(response) {
                    console.log('Update response:', response);
                    if (response.success) {
                        showAlert('Candidate updated successfully!', 'success');
                        $('#editCandidateModal').modal('hide');
                        candidatesTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    showAlert(`An error occurred while updating the candidate. Status: ${xhr.status} - ${xhr.statusText}`, 'danger');
                }
            });
        }
        
        function editCandidate(candidateId) {
            $.get(`api/candidates.php?id=${candidateId}`, function(response) {
                if (response.success) {
                    const candidate = response.data;
                    
                    $('#editCandidateId').val(candidate.id);
                    $('#editCandidateName').val(candidate.full_name);
                    $('#editCandidateEmail').val(candidate.email);
                    $('#editCandidateElection').val(candidate.election_id);
                    $('#editCandidatePosition').val(candidate.position_id);
                    $('#editCandidateStatus').val(candidate.status);
                    $('#editCandidatePlatform').val(candidate.platform);
                    
                    if (candidate.photo) {
                        $('#editPhotoPreview').html(`<img src="data:image/jpeg;base64,${candidate.photo}" class="photo-preview" alt="Current Photo">`);
                    }
                    
                    $('#editCandidateModal').modal('show');
                } else {
                    showAlert('Error: ' + response.message, 'danger');
                }
            });
        }
        
        function deleteCandidate(candidateId) {
            if (confirm('Are you sure you want to delete this candidate?')) {
                $.ajax({
                    url: `api/candidates.php?id=${candidateId}`,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            showAlert('Candidate deleted successfully!', 'success');
                            candidatesTable.ajax.reload();
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while deleting the candidate.', 'danger');
                    }
                });
            }
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
            const overlay = $('#mobileOverlay');
            
            // Toggle mobile menu
            mobileMenuBtn.on('click', function() {
                sidebar.toggleClass('show');
                overlay.toggleClass('show');
            });
            
            // Close menu when overlay is clicked
            overlay.on('click', function() {
                sidebar.removeClass('show');
                overlay.removeClass('show');
            });
            
            // Close menu when navigation link is clicked
            $('.sidebar .nav-link').on('click', function() {
                if ($(window).width() <= 768) {
                    sidebar.removeClass('show');
                    overlay.removeClass('show');
                }
            });
            
            // Handle window resize
            $(window).on('resize', function() {
                if ($(window).width() > 768) {
                    sidebar.removeClass('show');
                    overlay.removeClass('show');
                }
            });
        });
    </script>
</body>
</html>