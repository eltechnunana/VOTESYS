<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'Position Management';
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
    
    <style>
        :root {
            --primary-color: #2563EB;
            --secondary-color: #1D4ED8;
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

        .page-header {
            background: var(--white);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
        }

        .content-card {
            background: var(--white);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            border: 1px solid var(--neutral-light);
        }

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
            background-color: #1d4ed8;
            border-color: #1d4ed8;
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
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #b91c1c;
            border-color: #b91c1c;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 40px rgba(37, 99, 235, 0.15);
        }

        .modal-header {
            border-bottom: 1px solid var(--neutral-light);
            padding: 20px 24px;
        }

        .modal-body {
            padding: 24px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--neutral-light);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
        }

        .table thead th {
            background-color: var(--light-bg);
            border-bottom: 2px solid var(--neutral-light);
            font-weight: 600;
            color: var(--darker-text);
        }

        /* Mobile Navigation Toggle */
        .mobile-nav-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
        }

        /* Responsive table wrapper */
        .table-responsive {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Mobile-friendly action buttons */
        .btn-group-mobile {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .page-header .row {
                flex-direction: column;
                gap: 15px;
            }

            .page-header .col-auto {
                align-self: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .mobile-nav-toggle {
                display: block;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100vh;
                z-index: 1045;
                transition: left 0.3s ease;
            }

            .sidebar.show {
                left: 0;
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 80px 15px 20px;
            }

            .col-md-2 {
                width: 280px;
            }

            .col-md-10 {
                width: 100%;
                padding-left: 0;
            }

            .page-header {
                padding: 20px;
                margin-bottom: 20px;
            }

            .content-card {
                padding: 20px;
            }

            .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }

            .modal-body {
                padding: 20px;
            }

            /* Stack action buttons vertically on mobile */
            .table td:last-child {
                min-width: 120px;
            }

            .table td:last-child .btn {
                display: block;
                width: 100%;
                margin-bottom: 5px;
            }

            .table td:last-child .btn:last-child {
                margin-bottom: 0;
            }

            /* Hide less important columns on small screens */
            .table th:nth-child(1),
            .table td:nth-child(1) {
                display: none;
            }

            .table th:nth-child(3),
            .table td:nth-child(3) {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .page-header h2 {
                font-size: 1.5rem;
            }

            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .form-control, .form-select {
                padding: 10px 14px;
            }

            /* Further optimize table for very small screens */
            .table th:nth-child(4),
            .table td:nth-child(4) {
                display: none;
            }

            .table-responsive {
                font-size: 0.9rem;
            }

            /* Compact modal on very small screens */
            .modal-header {
                padding: 15px 20px;
            }

            .modal-body {
                padding: 15px 20px;
            }

            .modal-footer {
                padding: 15px 20px;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="elections.php">
                            <i class="fas fa-calendar-alt me-2"></i>Elections
                        </a>
                        <a class="nav-link" href="candidates.php">
                            <i class="fas fa-users me-2"></i>Candidates
                        </a>
                        <a class="nav-link active" href="positions.php">
                            <i class="fas fa-list me-2"></i>Positions
                        </a>
                        <a class="nav-link" href="voters.php">
                            <i class="fas fa-user-check me-2"></i>Voters
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
                                <i class="fas fa-list me-2 text-primary"></i>Position Management
                            </h2>
                            <p class="text-muted mb-0">Manage election positions and their settings</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                                <i class="fas fa-plus me-2"></i>Add Position
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alert Container -->
                <div id="alertContainer"></div>

                <!-- Positions Table -->
                <div class="content-card">
                    <div class="table-responsive">
                        <table class="table table-striped" id="positionsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Position Title</th>
                                    <th>Description</th>
                                    <th>Display Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Positions will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Position Modal -->
    <div class="modal fade" id="addPositionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPositionForm">
                        <div class="mb-3">
                            <label for="positionTitle" class="form-label">Position Title *</label>
                            <input type="text" class="form-control" id="positionTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="positionDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="positionDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="displayOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="displayOrder" value="1" min="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitAddPosition()">Add Position</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Position Modal -->
    <div class="modal fade" id="editPositionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPositionForm">
                        <input type="hidden" id="editPositionId">
                        <div class="mb-3">
                            <label for="editPositionTitle" class="form-label">Position Title *</label>
                            <input type="text" class="form-control" id="editPositionTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPositionDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editPositionDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editDisplayOrder" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="editDisplayOrder" min="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditPosition()">Update Position</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Position Elections Modal -->
    <div class="modal fade" id="positionElectionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Position Assignment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This position cannot be deleted because it is assigned to the following elections. Remove the position from these elections first.
                    </div>
                    <div id="electionsList">
                        <!-- Elections will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        let positionsTable;

        $(document).ready(function() {
            // Check if we're on mobile
            const isMobile = window.innerWidth <= 768;
            
            // Initialize DataTable with responsive configuration
            positionsTable = $('#positionsTable').DataTable({
                ajax: {
                    url: 'api/positions.php?action=get_positions',
                    dataSrc: 'data'
                },
                columns: [
                    { 
                        data: 'id',
                        title: 'ID',
                        className: 'never' // Hide on mobile
                    },
                    { 
                        data: 'title',
                        title: 'Position Title',
                        className: 'all' // Always show
                    },
                    { 
                        data: 'description',
                        title: 'Description',
                        className: 'min-tablet-l', // Hide on mobile
                        render: function(data, type, row) {
                            if (type === 'display') {
                                const desc = data || 'No description';
                                return desc.length > 50 ? desc.substring(0, 50) + '...' : desc;
                            }
                            return data || 'No description';
                        }
                    },
                    { 
                        data: 'display_order',
                        title: 'Order',
                        className: 'min-tablet-p' // Hide on small mobile
                    },
                    {
                        data: null,
                        title: 'Actions',
                        orderable: false,
                        className: 'all',
                        render: function(data, type, row) {
                            if (isMobile) {
                                return `
                                    <div class="btn-group-mobile">
                                        <button class="btn btn-sm btn-primary" onclick="editPosition(${row.id})" title="Edit">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deletePosition(${row.id})" title="Delete">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                `;
                            } else {
                                return `
                                    <button class="btn btn-sm btn-primary me-1" onclick="editPosition(${row.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePosition(${row.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                `;
                            }
                        }
                    }
                ],
                responsive: {
                    details: {
                        type: 'inline',
                        target: 'tr'
                    }
                },
                pageLength: isMobile ? 5 : 10,
                lengthMenu: isMobile ? [[5, 10, 25], [5, 10, 25]] : [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[3, 'asc']], // Order by display_order
                language: {
                    search: "Search positions:",
                    lengthMenu: "Show _MENU_ positions",
                    info: "Showing _START_ to _END_ of _TOTAL_ positions",
                    infoEmpty: "No positions found",
                    infoFiltered: "(filtered from _MAX_ total positions)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    }
                },
                dom: isMobile ? 
                    "<'row'<'col-sm-12'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>" :
                    "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                initComplete: function() {
                    // Table is fully initialized
                    console.log('Positions table initialized successfully');
                },
                drawCallback: function() {
                    // Ensure responsive features are available after each draw
                    if (this.api().responsive && this.api().responsive.recalc) {
                        this.api().responsive.recalc();
                    }
                }
            });

            // Mobile navigation
            $('#mobileNavToggle').click(function() {
                $('.sidebar').toggleClass('show');
                $('.sidebar-overlay').toggleClass('show');
            });

            $('#sidebarOverlay').click(function() {
                $('.sidebar').removeClass('show');
                $('.sidebar-overlay').removeClass('show');
            });

            // Handle window resize for responsive behavior
            let resizeTimer;
            $(window).resize(function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    // Redraw table to handle responsive changes
                    if (positionsTable && positionsTable.responsive) {
                        try {
                            positionsTable.columns.adjust();
                            if (positionsTable.responsive.recalc) {
                                positionsTable.responsive.recalc();
                            }
                        } catch (error) {
                            console.log('Table resize adjustment skipped:', error.message);
                        }
                    }
                }, 250);
            });

            // Close mobile sidebar when clicking on nav links
            $('.sidebar .nav-link').click(function() {
                if (window.innerWidth <= 768) {
                    $('.sidebar').removeClass('show');
                    $('.sidebar-overlay').removeClass('show');
                }
            });

            // Prevent modal backdrop from interfering with mobile navigation
            $('.modal').on('show.bs.modal', function() {
                if (window.innerWidth <= 768) {
                    $('.sidebar').removeClass('show');
                    $('.sidebar-overlay').removeClass('show');
                }
            });
        });

        function showAlert(message, type = 'info') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('#alertContainer').html(alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        }

        function submitAddPosition() {
            const title = $('#positionTitle').val().trim();
            const description = $('#positionDescription').val().trim();
            const displayOrder = $('#displayOrder').val();

            if (!title) {
                showAlert('Position title is required', 'warning');
                return;
            }

            $.ajax({
                url: 'api/positions.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'create_position',
                    name: title,
                    description: description,
                    display_order: parseInt(displayOrder)
                }),
                success: function(response) {
                    if (response.success) {
                        showAlert('Position added successfully!', 'success');
                        $('#addPositionModal').modal('hide');
                        $('#addPositionForm')[0].reset();
                        positionsTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while adding the position.', 'danger');
                }
            });
        }

        function editPosition(id) {
            // Get position data from the table
            const row = positionsTable.row(function(idx, data) {
                return data.id == id;
            }).data();

            if (row) {
                $('#editPositionId').val(row.id);
                $('#editPositionTitle').val(row.name);
                $('#editPositionDescription').val(row.description || '');
                $('#editDisplayOrder').val(row.display_order);
                $('#editPositionModal').modal('show');
            }
        }

        function submitEditPosition() {
            const id = $('#editPositionId').val();
            const title = $('#editPositionTitle').val().trim();
            const description = $('#editPositionDescription').val().trim();
            const displayOrder = $('#editDisplayOrder').val();

            if (!title) {
                showAlert('Position title is required', 'warning');
                return;
            }

            $.ajax({
                url: 'api/positions.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'update_position',
                    id: parseInt(id),
                    name: title,
                    description: description,
                    display_order: parseInt(displayOrder)
                }),
                success: function(response) {
                    if (response.success) {
                        showAlert('Position updated successfully!', 'success');
                        $('#editPositionModal').modal('hide');
                        positionsTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while updating the position.', 'danger');
                }
            });
        }

        function deletePosition(id) {
            if (confirm('Are you sure you want to delete this position? This action cannot be undone and may affect existing elections.')) {
                $.ajax({
                    url: 'api/positions.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'delete_position',
                        id: parseInt(id)
                    }),
                    success: function(response) {
                        if (response.success) {
                            showAlert('Position deleted successfully!', 'success');
                            positionsTable.ajax.reload();
                        } else {
                            // Check if the error is due to election assignments
                            if (response.assigned_elections && response.assigned_elections.length > 0) {
                                showPositionElections(id);
                            } else {
                                showAlert('Error: ' + response.message, 'danger');
                            }
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while deleting the position.', 'danger');
                    }
                });
            }
        }

        function showPositionElections(positionId) {
            $.ajax({
                url: 'api/positions.php?action=get_position_elections&position_id=' + positionId,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        let electionsHtml = '';
                        if (response.elections.length > 0) {
                            electionsHtml = '<div class="list-group">';
                            response.elections.forEach(function(election) {
                                const statusBadge = election.is_active ? 
                                    '<span class="badge bg-success ms-2">Active</span>' : 
                                    '<span class="badge bg-secondary ms-2">Inactive</span>';
                                
                                electionsHtml += `
                                    <div class="list-group-item d-flex justify-content-between align-items-start">
                                        <div class="ms-2 me-auto">
                                            <div class="fw-bold">${election.election_title}${statusBadge}</div>
                                            <p class="mb-1">${election.description || 'No description'}</p>
                                            <small class="text-muted">Start: ${new Date(election.start_date).toLocaleDateString('en-US', {timeZone: 'UTC'})} UTC | End: ${new Date(election.end_date).toLocaleDateString('en-US', {timeZone: 'UTC'})} UTC</small>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger" onclick="removePositionFromElection(${positionId}, ${election.id}, '${election.election_title}')">
                                            <i class="fas fa-unlink me-1"></i>Remove
                                        </button>
                                    </div>
                                `;
                            });
                            electionsHtml += '</div>';
                        } else {
                            electionsHtml = '<p class="text-muted">No elections found for this position.</p>';
                        }
                        
                        $('#electionsList').html(electionsHtml);
                        $('#positionElectionsModal').modal('show');
                    } else {
                        showAlert('Error loading election assignments: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while loading election assignments.', 'danger');
                }
            });
        }

        function removePositionFromElection(positionId, electionId, electionTitle) {
            if (confirm(`Are you sure you want to remove this position from "${electionTitle}"?`)) {
                $.ajax({
                    url: 'api/positions.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'remove_position_from_election',
                        position_id: parseInt(positionId),
                        election_id: parseInt(electionId)
                    }),
                    success: function(response) {
                        if (response.success) {
                            showAlert('Position removed from election successfully!', 'success');
                            // Refresh the elections list
                            showPositionElections(positionId);
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while removing the position from election.', 'danger');
                    }
                });
            }
        }
    </script>
</body>
</html>