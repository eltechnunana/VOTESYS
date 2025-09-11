<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'Election Management';
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
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
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
            background: var(--primary-blue);
            box-shadow: 2px 0 10px rgba(37, 99, 235, 0.1);
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
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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
        
        .btn-light {
            background: var(--base-white);
            border: 1px solid var(--neutral-medium);
            color: var(--neutral-dark);
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-light:hover {
            background: var(--neutral-light);
            border-color: var(--neutral-dark);
            color: var(--neutral-dark);
            transform: translateY(-1px);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.025em;
        }
        
        .badge.bg-warning {
            background-color: #f59e0b !important;
            color: white;
        }
        
        .badge.bg-success {
            background-color: var(--success-green) !important;
            color: white;
        }
        
        .badge.bg-secondary {
            background-color: var(--neutral-dark) !important;
            color: white;
        }
        
        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .table th {
            background: var(--primary-blue);
            color: white;
            border: none;
            font-weight: 600;
            padding: 16px;
            font-size: 0.875rem;
            letter-spacing: 0.025em;
        }
        
        .table td {
            padding: 16px;
            border-color: var(--neutral-medium);
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: var(--neutral-light);
        }
        
        .modal-header {
            background: var(--primary-blue);
            color: white;
            border-radius: 16px 16px 0 0;
            border-bottom: none;
            padding: 20px 24px;
        }
        
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--neutral-medium);
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
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.1);
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
        
        .text-muted {
            color: #6b7280 !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: var(--neutral-dark);
            font-weight: 700;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.875rem;
            border-radius: 8px;
        }
        
        .table-responsive {
            border-radius: 12px;
        }
        
        /* Mobile Navigation Toggle */
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
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .mobile-sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        
        /* Responsive Design */
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
            
            .mobile-sidebar-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .header-section {
                padding: 20px 0;
                margin-bottom: 20px;
                margin-left: -15px;
                margin-right: -15px;
            }
            
            .header-section h1 {
                font-size: 1.5rem;
                margin-bottom: 5px;
            }
            
            .header-section p {
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .card {
                margin-bottom: 1rem;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.875rem;
            }
            
            .btn-sm {
                padding: 6px 10px;
                font-size: 0.75rem;
            }
            
            .table th,
            .table td {
                padding: 8px;
                font-size: 0.875rem;
            }
        }
        
        /* Enhanced Positions Modal Styles */
        .sortable-container {
            min-height: 200px;
        }
        
        .position-item {
            background: white;
            border: 2px solid var(--neutral-medium);
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: move;
            position: relative;
        }
        
        .position-item:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }
        
        .position-item.sortable-ghost {
            opacity: 0.5;
            background: var(--neutral-light);
        }
        
        .position-item.sortable-chosen {
            border-color: var(--primary-blue);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.2);
        }
        
        .position-item.sortable-drag {
            transform: rotate(5deg);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
        }
        
        .position-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .position-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--neutral-dark);
            margin: 0;
        }
        
        .position-order {
            background: var(--primary-blue);
            color: white;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .position-description {
            color: var(--neutral-medium-dark);
            font-size: 0.875rem;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .position-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        
        .position-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
            color: var(--neutral-medium-dark);
        }
        
        .position-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .drag-handle {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--neutral-medium);
            font-size: 1.2rem;
            cursor: grab;
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .position-item:hover .drag-handle {
            color: var(--primary-blue);
        }
        
        /* Position Form Modal */
        .position-form-modal .modal-dialog {
            max-width: 600px;
        }
        
        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .candidate-limit-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
        }
        
        .candidate-limit-info small {
            color: #64748b;
            font-size: 0.8rem;
        }
        
        /* Animation for position updates */
        .position-updated {
            animation: positionPulse 0.6s ease-in-out;
        }
        
        @keyframes positionPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); box-shadow: 0 8px 24px rgba(34, 197, 94, 0.2); }
            100% { transform: scale(1); }
        }
            
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-body {
                padding: 15px;
            }
            
            .form-control,
            .form-select {
                padding: 10px 12px;
                font-size: 0.875rem;
            }
            
            .header-section {
                text-align: center;
            }
            
            .header-section .d-flex {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-section .row {
                gap: 10px;
            }
            
            .filter-section .col-md-4 {
                margin-bottom: 15px;
            }
    /* Mobile Responsive Styles */
    @media (max-width: 991.98px) {
        .modal-dialog {
            margin: 10px;
        }
        
        .modal-body {
            padding: 15px;
        }
        
        .form-control,
        .form-select {
            padding: 10px 12px;
            font-size: 0.875rem;
        }
        
        .header-section {
            text-align: center;
        }
        
        .header-section .d-flex {
            flex-direction: column;
            gap: 15px;
        }
        
        .filter-section .row {
            gap: 10px;
        }
        
        .filter-section .col-md-4 {
            margin-bottom: 15px;
        }
    }
        
        @media (max-width: 575.98px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .main-content {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .header-section h1 {
                font-size: 1.25rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .btn-group .btn {
                width: auto;
                margin-bottom: 0;
            }
            
            .table-responsive {
                font-size: 0.75rem;
            }
            
            .modal-dialog {
            margin: 5px;
        }
    }
    
    /* Touch-friendly styles */
    .touch-friendly {
        min-height: 44px;
        min-width: 44px;
        padding: 8px 12px;
    }
    
    @media (hover: none) and (pointer: coarse) {
        /* Touch device specific styles */
        .btn {
            min-height: 48px;
            font-size: 16px;
        }
        
        .form-control, .form-select {
            min-height: 48px;
            font-size: 16px;
        }
        
        .dropdown-item {
            padding: 12px 16px;
            font-size: 16px;
        }
        
        .table td, .table th {
            padding: 12px 8px;
        }
        
        .modal-header .btn-close {
            min-height: 48px;
            min-width: 48px;
        }
    }
    
    /* Improve scrolling on mobile */
    @media (max-width: 767.98px) {
        .table-responsive {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    }
        
        /* Enhanced Dashboard Responsive */
        @media (max-width: 991.98px) {
            #electionDashboard .col-md-3 {
                margin-bottom: 15px;
            }
            
            #electionDashboard .col-md-6 {
                margin-bottom: 15px;
            }
            
            #electionDashboard .d-flex.gap-2 {
                flex-direction: column;
                gap: 10px !important;
            }
            
            #electionDashboard .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 767.98px) {
            /* Dashboard Cards */
            #electionDashboard .card-body {
                padding: 15px;
            }
            
            #electionDashboard .fa-2x {
                font-size: 1.5em !important;
            }
            
            #electionDashboard h4 {
                font-size: 1.25rem;
            }
            
            /* Filter Section */
            .filter-section .col-md-4 {
                margin-bottom: 15px;
            }
            
            /* Table Responsive */
            .table-responsive {
                border: none;
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 6px 4px;
                font-size: 0.75rem;
                white-space: nowrap;
            }
            
            /* Action buttons in table */
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.7rem;
                margin: 1px;
            }
            
            /* Modal adjustments */
            .modal-lg {
                max-width: 95%;
            }
            
            .modal-body {
                padding: 15px;
            }
            
            /* Form adjustments */
            .form-control,
            .form-select {
                font-size: 0.875rem;
                padding: 8px 12px;
            }
        }
        
        @media (max-width: 575.98px) {
            /* Extra small devices */
            .header-section h1 {
                font-size: 1.1rem;
            }
            
            .header-section .btn {
                font-size: 0.8rem;
                padding: 8px 12px;
            }
            
            /* Dashboard specific */
            #electionDashboard .col-sm-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            /* Table even more compact */
            .table th,
            .table td {
                padding: 4px 2px;
                font-size: 0.7rem;
            }
            
            /* Hide less important columns on very small screens */
            .table th:nth-child(2),
            .table td:nth-child(2) {
                display: none; /* Hide description column */
            }
            
            .table th:nth-child(7),
            .table td:nth-child(7) {
                display: none; /* Hide candidates column */
            }
            
            /* Stack action buttons vertically */
            .btn-group-vertical .btn {
                width: 100%;
                margin-bottom: 2px;
            }
        }
        
        /* DataTables Responsive */
        @media (max-width: 767.98px) {
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: center;
                margin-bottom: 10px;
            }
            
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                text-align: center;
                margin-top: 10px;
            }
            
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 5px 8px;
                margin: 0 2px;
                font-size: 0.8rem;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                margin-left: 0 !important;
            }
            
            .dataTables_wrapper .dataTables_length select {
                width: auto;
            }
        }
        
        /* Responsive utilities */
        .d-mobile-none {
            display: block;
        }
        
        .d-mobile-block {
            display: none;
        }
        
        @media (max-width: 767.98px) {
            .d-mobile-none {
                display: none !important;
            }
            
            .d-mobile-block {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle" id="mobileNavToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Mobile Sidebar Overlay -->
    <div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>
    
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
                            <a class="nav-link active" href="elections.php">
                                <i class="fas fa-calendar-check me-2"></i>Elections
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="candidates.php">
                                <i class="fas fa-users me-2"></i>Candidates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="voters.php">
                                <i class="fas fa-user-check me-2"></i>Voters
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users-cog me-2"></i>Users
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
                                <h1><i class="fas fa-calendar-check me-2"></i>Election Management</h1>
                                <p class="mb-0">Create and manage elections, positions, and voting periods</p>
                            </div>
                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addElectionModal">
                                <i class="fas fa-plus me-2"></i>Add Election
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Election Dashboard -->
                <div class="row mb-4" id="electionDashboard" style="display: none;">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        <span id="dashboardElectionTitle">Election Dashboard</span>
                                    </h5>
                                    <button class="btn btn-sm btn-light" onclick="closeDashboard()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-info text-white h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-users fa-2x mb-2"></i>
                                                <h4 class="mb-1" id="dashboardRegisteredVoters">0</h4>
                                                <p class="mb-0 small">Registered Voters</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-success text-white h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-vote-yea fa-2x mb-2"></i>
                                                <h4 class="mb-1" id="dashboardVotesCast">0</h4>
                                                <p class="mb-0 small">Votes Cast</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-warning text-white h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-percentage fa-2x mb-2"></i>
                                                <h4 class="mb-1" id="dashboardTurnout">0%</h4>
                                                <p class="mb-0 small">Voter Turnout</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-secondary text-white h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-clock fa-2x mb-2"></i>
                                                <h4 class="mb-1" id="dashboardTimeRemaining">--</h4>
                                                <p class="mb-0 small">Time Remaining</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Voting Progress</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="progress mb-2" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar" id="votingProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                        <span id="votingProgressText">0%</span>
                                                    </div>
                                                </div>
                                                <small class="text-muted">Percentage of registered voters who have cast their votes</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Election Status</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>Status:</span>
                                                    <span class="badge" id="dashboardStatus">Unknown</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span>Start Date:</span>
                                                    <span id="dashboardStartDate">--</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>End Date:</span>
                                                    <span id="dashboardEndDate">--</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-primary" onclick="refreshDashboard()">
                                                <i class="fas fa-sync-alt me-2"></i>Refresh Data
                                            </button>
                                            <button class="btn btn-success" onclick="exportElectionData()">
                                                <i class="fas fa-download me-2"></i>Export Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="filterStatus" class="form-label">Filter by Status</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">All Status</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="ended">Ended</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filterYear" class="form-label">Filter by Year</label>
                                <select class="form-select" id="filterYear">
                                    <option value="">All Years</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="searchElection" class="form-label">Search Elections</label>
                                <input type="text" class="form-control" id="searchElection" placeholder="Search by title or description...">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Elections Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="electionsTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Positions</th>
                                        <th>Candidates</th>
                                        <th>Total Voters</th>
                                        <th>Votes Cast</th>
                                        <th>Voter Links</th>
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
    
    <!-- Add Election Modal -->
    <div class="modal fade" id="addElectionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Election</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addElectionForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="electionTitle" class="form-label">Election Title</label>
                                    <input type="text" class="form-control" id="electionTitle" name="title" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="electionDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="electionDescription" name="description" rows="3" placeholder="Describe the purpose and scope of this election..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="electionStartDate" class="form-label">Start Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="electionStartDate" name="start_date" step="60" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="electionEndDate" class="form-label">End Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="electionEndDate" name="end_date" step="60" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="electionStatus" class="form-label">Status</label>
                            <select class="form-select" id="electionStatus" name="status">
                                <option value="upcoming">Upcoming</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="ended">Ended</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Election</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Election Modal -->
    <div class="modal fade" id="editElectionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Election</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editElectionForm">
                    <input type="hidden" id="editElectionId" name="election_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="editElectionTitle" class="form-label">Election Title</label>
                                    <input type="text" class="form-control" id="editElectionTitle" name="title" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editElectionDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editElectionDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editElectionStartDate" class="form-label">Start Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="editElectionStartDate" name="start_date" step="60" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editElectionEndDate" class="form-label">End Date & Time</label>
                                    <input type="datetime-local" class="form-control" id="editElectionEndDate" name="end_date" step="60" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editElectionStatus" class="form-label">Status</label>
                            <select class="form-select" id="editElectionStatus" name="status">
                                <option value="upcoming">Upcoming</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="ended">Ended</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Election</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Positions Modal -->
    <div class="modal fade" id="positionsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-list-ol me-2"></i>Manage Election Positions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h6 class="text-primary"><i class="fas fa-vote-yea me-2"></i>Election: <span id="positionElectionTitle"></span></h6>
                            <p class="text-muted mb-0">Drag and drop positions to reorder them. The order determines how they appear on the ballot.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-success" onclick="addPosition()">
                                <i class="fas fa-plus me-1"></i>Add New Position
                            </button>
                        </div>
                    </div>
                    
                    <div class="alert alert-info" id="positionsHelpText">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Tips:</strong> Use descriptive titles and clear descriptions. Set appropriate candidate limits for each position.
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h6 class="mb-0"><i class="fas fa-grip-vertical me-2"></i>Positions List</h6>
                                        </div>
                                        <div class="col-auto">
                                            <span class="badge bg-secondary" id="positionsCount">0 positions</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div id="positionsContainer" class="sortable-container">
                                        <!-- Positions will be loaded here -->
                                    </div>
                                    <div id="noPositionsMessage" class="text-center py-5 text-muted" style="display: none;">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <h5>No positions added yet</h5>
                                        <p>Click "Add New Position" to get started.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="savePositionsOrder()">
                        <i class="fas fa-save me-1"></i>Save Order
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Voter Registration Modal -->
    <div class="modal fade" id="voterRegistrationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Voter Registration Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Election: <span id="regElectionTitle"></span></h6>
                        <p class="text-muted">Configure the voter registration form and generate a shareable link.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Required Fields</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reqStudentId" checked disabled>
                                <label class="form-check-label" for="reqStudentId">Student ID</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reqFullName" checked disabled>
                                <label class="form-check-label" for="reqFullName">Full Name</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reqLevel" checked>
                                <label class="form-check-label" for="reqLevel">Level/Year</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="reqDepartment" checked>
                                <label class="form-check-label" for="reqDepartment">Department</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Optional Fields</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="optEmail">
                                <label class="form-check-label" for="optEmail">Email Address</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="optPhone">
                                <label class="form-check-label" for="optPhone">Phone Number</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="optGender">
                                <label class="form-check-label" for="optGender">Gender</label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="regLinkExpiry" class="form-label">Link Expiry</label>
                        <select class="form-select" id="regLinkExpiry">
                            <option value="7">7 days</option>
                            <option value="14" selected>14 days</option>
                            <option value="30">30 days</option>
                            <option value="0">No expiry</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="regCustomMessage" class="form-label">Custom Message (Optional)</label>
                        <textarea class="form-control" id="regCustomMessage" rows="3" placeholder="Add a custom message for voters..."></textarea>
                    </div>
                    
                    <div class="alert alert-info" id="generatedRegLink" style="display: none;">
                        <h6>Generated Registration Link:</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="regLinkUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('regLinkUrl')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">Share this link with eligible voters to register for the election.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="generateRegistrationLink()">Generate Link</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Voter Login Modal -->
    <div class="modal fade" id="voterLoginModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Voter Login Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Election: <span id="loginElectionTitle"></span></h6>
                        <p class="text-muted">Configure voter authentication and generate a login link.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Authentication Method</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="authMethod" id="authStudentId" value="student_id" checked>
                                <label class="form-check-label" for="authStudentId">
                                    Student ID Only
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="authMethod" id="authIdPassword" value="id_password">
                                <label class="form-check-label" for="authIdPassword">
                                    Student ID + Generated Password
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="authMethod" id="authToken" value="token">
                                <label class="form-check-label" for="authToken">
                                    Unique Voter Tokens
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Access Control</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="requireRegistration" checked>
                                <label class="form-check-label" for="requireRegistration">
                                    Only registered voters can login
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="preventMultipleVotes" checked>
                                <label class="form-check-label" for="preventMultipleVotes">
                                    Prevent multiple votes
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logVoterActivity">
                                <label class="form-check-label" for="logVoterActivity">
                                    Log voter activity
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="loginLinkExpiry" class="form-label">Link Expiry</label>
                        <select class="form-select" id="loginLinkExpiry">
                            <option value="1">1 day</option>
                            <option value="3">3 days</option>
                            <option value="7" selected>7 days</option>
                            <option value="14">14 days</option>
                            <option value="0">Until election ends</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="loginCustomMessage" class="form-label">Voting Instructions (Optional)</label>
                        <textarea class="form-control" id="loginCustomMessage" rows="3" placeholder="Add instructions for voters..."></textarea>
                    </div>
                    
                    <div class="alert alert-info" id="generatedLoginLink" style="display: none;">
                        <h6>Generated Login Link:</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="loginLinkUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('loginLinkUrl')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">Share this link with registered voters to access the voting page.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="generateLoginLink()">Generate Link</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        let electionsTable;
        let positionsTable;
        let currentElectionId;
        
        $(document).ready(function() {
            // Initialize DataTable
            electionsTable = $('#electionsTable').DataTable({
                ajax: {
                    url: 'api/elections.php',
                    type: 'GET',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'title' },
                    { 
                        data: 'description',
                        render: function(data) {
                            return data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : '';
                        }
                    },
                    { 
                        data: 'start_date',
                        render: function(data) {
                            return new Date(data).toLocaleString('en-US', {timeZone: 'UTC'}) + ' UTC';
                        }
                    },
                    { 
                        data: 'end_date',
                        render: function(data) {
                            return new Date(data).toLocaleString('en-US', {timeZone: 'UTC'}) + ' UTC';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const now = new Date();
                            const startDate = new Date(row.start_date);
                            const endDate = new Date(row.end_date);
                            
                            let status = row.status;
                            let badgeClass = 'bg-secondary';
                            let statusText = 'Unknown';
                            let countdown = '';
                            
                            // Check if election is inactive first
                            if (!row.is_active || row.is_active == 0) {
                                status = 'inactive';
                                badgeClass = 'bg-secondary';
                                statusText = 'Inactive';
                                countdown = '<small class="text-muted d-block">Election disabled</small>';
                            }
                            // Auto-determine status based on dates only if election is active
                            else if (now < startDate) {
                                status = 'upcoming';
                                badgeClass = 'bg-warning';
                                statusText = 'Upcoming';
                                
                                // Calculate countdown to start
                                const timeDiff = startDate - now;
                                countdown = formatCountdown(timeDiff, 'Starts in');
                            } else if (now >= startDate && now <= endDate) {
                                status = 'active';
                                badgeClass = 'bg-success';
                                statusText = 'Active';
                                
                                // Calculate countdown to end
                                const timeDiff = endDate - now;
                                countdown = formatCountdown(timeDiff, 'Ends in');
                            } else if (now > endDate) {
                                status = 'ended';
                                badgeClass = 'bg-danger';
                                statusText = 'Ended';
                                countdown = '<small class="text-muted d-block">Election completed</small>';
                            }
                            
                            return `
                                <div class="status-container" data-election-id="${row.id}" data-start="${row.start_date}" data-end="${row.end_date}" data-is-active="${row.is_active}">
                                    <span class="badge ${badgeClass} status-badge">${statusText}</span>
                                    <div class="countdown-timer">${countdown}</div>
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'position_count',
                        render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-info" onclick="managePositions(${row.id || 0}, '${row.title || 'Election'}')" title="Manage Positions">${data || 0}</button>`;
                        }
                    },
                    { 
                        data: 'candidate_count',
                        render: function(data) {
                            return data || 0;
                        }
                    },
                    { 
                        data: 'voter_count',
                        render: function(data, type, row) {
                            return `<span class="badge bg-info">${data || 0}</span>`;
                        }
                    },
                    { 
                        data: 'votes_cast',
                        render: function(data, type, row) {
                            const percentage = row.voter_count > 0 ? Math.round((data || 0) / row.voter_count * 100) : 0;
                            return `<span class="badge bg-success">${data || 0}</span><br><small class="text-muted">${percentage}%</small>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group-vertical" role="group">
                                    <button class="btn btn-sm btn-outline-primary mb-1" onclick="generateVoterRegistrationLink(${row.id}, '${row.title}')" title="Generate Registration Link">
                                        <i class="fas fa-user-plus"></i> Registration
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="generateVoterLoginLink(${row.id}, '${row.title}')" title="Generate Login Link">
                                        <i class="fas fa-sign-in-alt"></i> Login
                                    </button>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-primary" onclick="editElection(${row.id})" title="Edit Election">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteElection(${row.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                responsive: {
                    details: {
                        type: 'inline'
                    }
                },
                columnDefs: [
                    { className: 'never', targets: [1] }, // Hide description on mobile
                    { className: 'min-tablet-l', targets: [2, 3] }, // Hide dates on small screens
                    { className: 'min-tablet-p', targets: [5, 6, 7, 8] }, // Hide counts on portrait tablets
                    { className: 'desktop', targets: [9] }, // Hide voter links on mobile
                    { className: 'all', targets: [0, 4, 10] } // Always show title, status, actions
                ],
                pageLength: window.innerWidth < 768 ? 5 : 10,
                order: [[2, 'desc']],
                language: {
                    paginate: {
                        previous: '<i class="fas fa-chevron-left"></i>',
                        next: '<i class="fas fa-chevron-right"></i>'
                    }
                },
                dom: window.innerWidth < 768 ? 'frtip' : 'Bfrtip'
            });
            
            // Positions are now managed with the enhanced modal structure
            
            // Populate year filter
            populateYearFilter();
            
            // Filter handlers
            $('#filterStatus, #filterYear').on('change', function() {
                electionsTable.ajax.reload();
            });
            
            $('#searchElection').on('keyup', function() {
                electionsTable.search(this.value).draw();
            });
            
            // Form submissions
            $('#addElectionForm').on('submit', handleAddElection);
            $('#editElectionForm').on('submit', handleEditElection);
            
            // Modal events
            $('#addElectionModal').on('hidden.bs.modal', function() {
                resetForm('#addElectionForm');
            });
            
            $('#editElectionModal').on('hidden.bs.modal', function() {
                resetForm('#editElectionForm');
            });
        });
        
        function populateYearFilter() {
            const currentYear = new Date().getFullYear();
            const $yearFilter = $('#filterYear');
            
            for (let year = currentYear + 2; year >= currentYear - 5; year--) {
                $yearFilter.append(`<option value="${year}">${year}</option>`);
            }
        }
        
        function handleAddElection(e) {
            e.preventDefault();
            
            const formData = {
                title: $('#electionTitle').val(),
                description: $('#electionDescription').val(),
                start_date: $('#electionStartDate').val(),
                end_date: $('#electionEndDate').val(),
                status: $('#electionStatus').val()
            };
            
            $.ajax({
                url: 'api/elections.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        showAlert('Election added successfully!', 'success');
                        $('#addElectionModal').modal('hide');
                        electionsTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while adding the election.', 'danger');
                }
            });
        }
        
        function handleEditElection(e) {
            e.preventDefault();
            
            const formData = {
                title: $('#editElectionTitle').val(),
                description: $('#editElectionDescription').val(),
                start_date: $('#editElectionStartDate').val(),
                end_date: $('#editElectionEndDate').val(),
                status: $('#editElectionStatus').val()
            };
            const electionId = $('#editElectionId').val();
            
            $.ajax({
                url: `api/elections.php?id=${electionId}`,
                type: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        showAlert('Election updated successfully!', 'success');
                        $('#editElectionModal').modal('hide');
                        electionsTable.ajax.reload();
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while updating the election.', 'danger');
                }
            });
        }
        
        function editElection(electionId) {
            // Redirect to the comprehensive edit election page
            window.location.href = `edit_election.php?id=${electionId}`;
        }
        
        function deleteElection(electionId) {
            if (confirm('Are you sure you want to delete this election? This will also delete all associated positions and candidates.')) {
                $.ajax({
                    url: `api/elections.php?id=${electionId}`,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            showAlert('Election deleted successfully!', 'success');
                            electionsTable.ajax.reload();
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while deleting the election.', 'danger');
                    }
                });
            }
        }
        
        function managePositions(electionId, electionTitle) {
            currentElectionId = electionId;
            $('#positionElectionTitle').text(electionTitle);
            
            // Load positions for this election
            $.get(`api/elections.php?action=get_positions&election_id=${electionId}`, function(response) {
                if (response.success) {
                    renderPositionsList(response.data);
                    $('#positionsModal').modal('show');
                } else {
                    showAlert('Error loading positions: ' + response.message, 'danger');
                }
            });
        }
        
        function renderPositionsList(positions) {
            const container = $('#positionsContainer');
            container.empty();
            
            if (positions.length === 0) {
                container.html('<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2"></i><br>No positions added yet</div>');
                return;
            }
            
            positions.forEach(function(position, index) {
                const positionItem = $(`
                    <div class="position-item" data-position-id="${position.id}">
                        <div class="position-drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </div>
                        <div class="position-content">
                            <div class="position-header">
                                <div class="position-title">${position.title}</div>
                                <div class="position-order">#${position.display_order || index + 1}</div>
                            </div>
                            <div class="position-description">${position.description || 'No description'}</div>
                            <div class="position-meta">
                                <span class="badge bg-primary">Max: ${position.max_candidates || 5}</span>
                                <span class="badge ${position.is_active ? 'bg-success' : 'bg-secondary'}">
                                    ${position.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                        <div class="position-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="editPositionInModal(${position.id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="removePositionFromElection(${position.id})" title="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `);
                container.append(positionItem);
            });
            
            // Initialize sortable
            if (window.sortableInstance) {
                window.sortableInstance.destroy();
            }
            
            window.sortableInstance = Sortable.create(container[0], {
                handle: '.position-drag-handle',
                animation: 150,
                ghostClass: 'position-ghost',
                chosenClass: 'position-chosen',
                dragClass: 'position-drag',
                onEnd: function(evt) {
                    updatePositionOrder();
                }
            });
        }
        
        function updatePositionOrder() {
            const positions = [];
            $('#positionsContainer .position-item').each(function(index) {
                positions.push({
                    position_id: $(this).data('position-id'),
                    display_order: index + 1
                });
                
                // Update the visual order number
                $(this).find('.position-order').text('#' + (index + 1));
            });
            
            // Show save button
            $('#savePositionOrder').removeClass('d-none');
            window.pendingPositionOrder = positions;
        }
        
        function savePositionOrder() {
            if (!window.pendingPositionOrder) return;
            
            $.ajax({
                url: 'api/elections.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'update_position_order',
                    election_id: currentElectionId,
                    positions: window.pendingPositionOrder
                }),
                success: function(response) {
                    if (response.success) {
                        showAlert('Position order updated successfully!', 'success');
                        $('#savePositionOrder').addClass('d-none');
                        window.pendingPositionOrder = null;
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while updating position order.', 'danger');
                }
            });
        }
        
        function addPosition() {
            // Get all available positions
            $.get('api/elections.php?action=get_all_positions', function(response) {
                if (response.success) {
                    let options = '<option value="">Select a position...</option>';
                    response.data.forEach(function(position) {
                        options += `<option value="${position.id}">${position.title} - ${position.description || 'No description'}</option>`;
                    });
                    
                    $('#addPositionSelect').html(options);
                    $('#addPositionModal').modal('show');
                } else {
                    showAlert('Error loading positions: ' + response.message, 'danger');
                }
            });
        }
        
        function editPositionInModal(positionId) {
            // Get current position data from the rendered list
            const positionItem = $(`.position-item[data-position-id="${positionId}"]`);
            const title = positionItem.find('.position-title').text();
            const description = positionItem.find('.position-description').text();
            const maxCandidates = positionItem.find('.badge:contains("Max:")').text().replace('Max: ', '');
            const isActive = positionItem.find('.badge.bg-success').length > 0;
            const displayOrder = positionItem.find('.position-order').text().replace('#', '');
            
            // Populate edit form
            $('#editPositionId').val(positionId);
            $('#editPositionTitle').val(title);
            $('#editPositionDescription').val(description === 'No description' ? '' : description);
            $('#editPositionMaxCandidates').val(maxCandidates);
            $('#editPositionDisplayOrder').val(displayOrder);
            $('#editPositionActive').prop('checked', isActive);
            
            $('#editPositionModal').modal('show');
        }
        
        function submitAddPosition() {
            const positionId = $('#addPositionSelect').val();
            const displayOrder = $('#addPositionDisplayOrder').val();
            const maxCandidates = $('#addPositionMaxCandidates').val();
            const isActive = $('#addPositionActive').is(':checked');
            
            if (!positionId) {
                showAlert('Please select a position', 'warning');
                return;
            }
            
            $.ajax({
                url: 'api/elections.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'add_position',
                    election_id: currentElectionId,
                    position_id: parseInt(positionId),
                    display_order: parseInt(displayOrder),
                    max_candidates: parseInt(maxCandidates),
                    is_active: isActive ? 1 : 0
                }),
                success: function(response) {
                    if (response.success) {
                        showAlert('Position added successfully!', 'success');
                        $('#addPositionModal').modal('hide');
                        managePositions(currentElectionId, $('#positionElectionTitle').text());
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while adding the position.', 'danger');
                }
            });
        }
        
        function submitEditPosition() {
            const positionId = $('#editPositionId').val();
            const displayOrder = $('#editPositionDisplayOrder').val();
            const maxCandidates = $('#editPositionMaxCandidates').val();
            const isActive = $('#editPositionActive').is(':checked');
            
            $.ajax({
                url: 'api/elections.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'update_position',
                    election_id: currentElectionId,
                    position_id: parseInt(positionId),
                    display_order: parseInt(displayOrder),
                    max_candidates: parseInt(maxCandidates),
                    is_active: isActive ? 1 : 0
                }),
                success: function(response) {
                    if (response.success) {
                        showAlert('Position updated successfully!', 'success');
                        $('#editPositionModal').modal('hide');
                        managePositions(currentElectionId, $('#positionElectionTitle').text());
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showAlert('An error occurred while updating the position.', 'danger');
                }
            });
        }
        
        function editElectionPosition(positionId) {
            // Get current position data
            const row = positionsTable.row(function(idx, data) {
                return data.id == positionId;
            }).data();
            
            const displayOrder = prompt('Enter display order:', row.display_order || 1);
            if (displayOrder !== null) {
                const maxCandidates = prompt('Maximum number of candidates:', row.max_candidates || 5) || 5;
                const isActive = confirm('Is this position active for this election?');
                
                $.ajax({
                    url: 'api/elections.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'update_position',
                        election_id: currentElectionId,
                        position_id: positionId,
                        display_order: parseInt(displayOrder),
                        max_candidates: parseInt(maxCandidates),
                        is_active: isActive ? 1 : 0
                    }),
                    success: function(response) {
                        if (response.success) {
                            showAlert('Position settings updated successfully!', 'success');
                            managePositions(currentElectionId, $('#positionElectionTitle').text());
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while updating the position.', 'danger');
                    }
                });
            }
        }
        
        function removePositionFromElection(positionId) {
            if (confirm('Are you sure you want to remove this position from the election? This cannot be undone if there are existing candidates.')) {
                $.ajax({
                    url: 'api/elections.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'remove_position',
                        election_id: currentElectionId,
                        position_id: positionId
                    }),
                    success: function(response) {
                        if (response.success) {
                            showAlert('Position removed from election successfully!', 'success');
                            managePositions(currentElectionId, $('#positionElectionTitle').text());
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('An error occurred while removing the position.', 'danger');
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
        
        // Mobile Navigation
        function initMobileNavigation() {
            const mobileToggle = document.getElementById('mobileNavToggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('mobileSidebarOverlay');
            
            if (mobileToggle && sidebar && overlay) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
                
                // Close sidebar when clicking nav links on mobile
                const navLinks = sidebar.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 992) {
                            sidebar.classList.remove('show');
                            overlay.classList.remove('show');
                        }
                    });
                });
            }
        }
        
        // Generate voter registration link
        function generateVoterRegistrationLink(electionId, electionTitle) {
            currentElectionId = electionId;
            $('#regElectionTitle').text(electionTitle);
            $('#voterRegistrationModal').modal('show');
        }
        
        // Generate voter login link
        function generateVoterLoginLink(electionId, electionTitle) {
            currentElectionId = electionId;
            $('#loginElectionTitle').text(electionTitle);
            $('#voterLoginModal').modal('show');
        }
        
        // Generate registration link
        function generateRegistrationLink() {
            const requiredFields = [];
            const optionalFields = [];
            
            // Collect required fields
            if ($('#reqStudentId').is(':checked')) requiredFields.push('student_id');
            if ($('#reqFullName').is(':checked')) requiredFields.push('full_name');
            if ($('#reqLevel').is(':checked')) requiredFields.push('level');
            if ($('#reqDepartment').is(':checked')) requiredFields.push('department');
            
            // Collect optional fields
            if ($('#optEmail').is(':checked')) optionalFields.push('email');
            if ($('#optPhone').is(':checked')) optionalFields.push('phone');
            if ($('#optGender').is(':checked')) optionalFields.push('gender');
            
            const expiryDays = $('#regLinkExpiry').val();
            const message = $('#regCustomMessage').val();
            
            // Calculate actual expiry date
            let expiryDate = '';
            if (expiryDays !== '0') {
                const today = new Date();
                const expiry = new Date(today.getTime() + (parseInt(expiryDays) * 24 * 60 * 60 * 1000));
                expiryDate = expiry.getFullYear() + '-' + 
                           String(expiry.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(expiry.getDate()).padStart(2, '0');
            }
            
            // Generate the link
            const baseUrl = window.location.origin + window.location.pathname.replace('/admin/elections.php', '');
            const params = new URLSearchParams({
                election_id: currentElectionId,
                type: 'registration',
                required: requiredFields.join(','),
                optional: optionalFields.join(','),
                expiry: expiryDate,
                message: encodeURIComponent(message)
            });
            
            const registrationUrl = `${baseUrl}/voter/register.php?${params.toString()}`;
            
            $('#regLinkUrl').val(registrationUrl);
            $('#generatedRegLink').show();
        }
        
        // Generate login link
        function generateLoginLink() {
            const authMethod = $('input[name="authMethod"]:checked').val();
            const requireRegistration = $('#requireRegistration').is(':checked');
            const preventMultipleVotes = $('#preventMultipleVotes').is(':checked');
            const logVoterActivity = $('#logVoterActivity').is(':checked');
            const expiry = $('#loginLinkExpiry').val();
            const instructions = $('#loginCustomMessage').val();
            
            // Generate the link
            const baseUrl = window.location.origin + window.location.pathname.replace('/admin/elections.php', '');
            const params = new URLSearchParams({
                election_id: currentElectionId,
                auth_method: authMethod,
                require_registration: requireRegistration ? '1' : '0',
                prevent_multiple: preventMultipleVotes ? '1' : '0',
                log_activity: logVoterActivity ? '1' : '0',
                expiry: expiry,
                instructions: encodeURIComponent(instructions)
            });
            
            const loginUrl = `${baseUrl}/voter/login.php?${params.toString()}`;
            
            $('#loginLinkUrl').val(loginUrl);
            $('#generatedLoginLink').show();
        }
        
        // Copy to clipboard function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            element.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                showAlert('Link copied to clipboard!', 'success');
            } catch (err) {
                // Fallback for modern browsers
                navigator.clipboard.writeText(element.value).then(function() {
                    showAlert('Link copied to clipboard!', 'success');
                }).catch(function() {
                    showAlert('Failed to copy link. Please copy manually.', 'warning');
                });
            }
        }
        
        // Dashboard functions
        let currentDashboardElectionId = null;
        let dashboardRefreshInterval = null;
        
        function refreshDashboard() {
            loadDashboardData();
            showAlert('Dashboard data refreshed', 'success');
        }
        
        function exportElectionData() {
            if (currentDashboardElectionId) {
                window.location.href = `api/elections.php?action=export_data&election_id=${currentDashboardElectionId}`;
            }
        }
        

         

         
         // Status and countdown functions
         function formatCountdown(timeDiff, prefix) {
             if (timeDiff <= 0) {
                 return '<small class="text-muted d-block">Time expired</small>';
             }
             
             const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
             const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
             const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
             const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);
             
             let timeString = '';
             
             if (days > 0) {
                 timeString = `${days}d ${hours}h ${minutes}m`;
             } else if (hours > 0) {
                 timeString = `${hours}h ${minutes}m ${seconds}s`;
             } else if (minutes > 0) {
                 timeString = `${minutes}m ${seconds}s`;
             } else {
                 timeString = `${seconds}s`;
             }
             
             return `<small class="text-muted d-block">${prefix} ${timeString}</small>`;
         }
         
         // Function to refresh elections table data
         function refreshElectionsTable() {
             if (typeof electionsTable !== 'undefined' && electionsTable) {
                 electionsTable.ajax.reload(null, false); // false = don't reset paging
             }
         }
         
         // Listen for storage events to detect voting status changes from other tabs/windows
         window.addEventListener('storage', function(e) {
             if (e.key === 'voting_status_changed') {
                 refreshElectionsTable();
                 localStorage.removeItem('voting_status_changed'); // Clean up
             }
         });
         
         // Global function to trigger table refresh (can be called from other pages)
         window.triggerElectionsTableRefresh = function() {
             refreshElectionsTable();
             // Also trigger for other tabs/windows
             localStorage.setItem('voting_status_changed', Date.now());
         };
         
         function updateElectionStatuses() {
             $('.status-container').each(function() {
                 const container = $(this);
                 const electionId = container.data('election-id');
                 const startDate = new Date(container.data('start'));
                 const endDate = new Date(container.data('end'));
                 const isActive = container.data('is-active');
                 const now = new Date();
                 
                 let badgeClass = 'bg-secondary';
                 let statusText = 'Unknown';
                 let countdown = '';
                 
                 // Check if election is inactive first
                 if (!isActive || isActive == 0) {
                     badgeClass = 'bg-secondary';
                     statusText = 'Inactive';
                     countdown = '<small class="text-muted d-block">Election disabled</small>';
                 }
                 // Auto-determine status based on dates only if election is active
                 else if (now < startDate) {
                     badgeClass = 'bg-warning';
                     statusText = 'Upcoming';
                     
                     // Calculate countdown to start
                     const timeDiff = startDate - now;
                     countdown = formatCountdown(timeDiff, 'Starts in');
                 } else if (now >= startDate && now <= endDate) {
                     badgeClass = 'bg-success';
                     statusText = 'Active';
                     
                     // Calculate countdown to end
                     const timeDiff = endDate - now;
                     countdown = formatCountdown(timeDiff, 'Ends in');
                 } else if (now > endDate) {
                     badgeClass = 'bg-danger';
                     statusText = 'Ended';
                     countdown = '<small class="text-muted d-block">Election completed</small>';
                 }
                 
                 // Update badge
                 const badge = container.find('.status-badge');
                 badge.removeClass('bg-warning bg-success bg-danger bg-secondary')
                      .addClass(badgeClass)
                      .text(statusText);
                 
                 // Update countdown
                 container.find('.countdown-timer').html(countdown);
             });
         }
         
         // Auto-update status every 30 seconds
         let statusUpdateInterval;
         
         function startStatusUpdates() {
             // Update immediately
             updateElectionStatuses();
             
             // Set up interval for updates
             if (statusUpdateInterval) {
                 clearInterval(statusUpdateInterval);
             }
             statusUpdateInterval = setInterval(updateElectionStatuses, 30000); // Update every 30 seconds
         }
         
         function stopStatusUpdates() {
             if (statusUpdateInterval) {
                 clearInterval(statusUpdateInterval);
                 statusUpdateInterval = null;
             }
         }
         
         // Window resize handler for responsive tables
        function handleWindowResize() {
            if (typeof electionsTable !== 'undefined' && electionsTable.responsive) {
                try {
                    electionsTable.responsive.recalc();
                } catch (e) {
                    console.log('Responsive recalc not available');
                }
            }
            
            // Adjust page length based on screen size
            if (typeof electionsTable !== 'undefined') {
                const newPageLength = window.innerWidth < 768 ? 5 : 10;
                if (electionsTable.page.len() !== newPageLength) {
                    electionsTable.page.len(newPageLength).draw();
                }
            }
            
            // Adjust modal sizes for mobile
            $('.modal-dialog').each(function() {
                if (window.innerWidth < 576) {
                    $(this).removeClass('modal-lg modal-xl').addClass('modal-fullscreen-sm-down');
                } else {
                    $(this).removeClass('modal-fullscreen-sm-down');
                }
            });
        }
        
        // Touch-friendly interactions for mobile
        function initTouchInteractions() {
            // Add touch-friendly classes to buttons
            $('.btn').addClass('touch-friendly');
            
            // Improve dropdown behavior on touch devices
            if ('ontouchstart' in window) {
                $('.dropdown-toggle').on('click', function(e) {
                    e.preventDefault();
                    $(this).dropdown('toggle');
                });
            }
        }
        
        // Initialize mobile navigation when document is ready
        $(document).ready(function() {
            initMobileNavigation();
            initTouchInteractions();
            
            // Start status updates after DataTable is initialized
            if (typeof electionsTable !== 'undefined') {
                electionsTable.on('draw', function() {
                    startStatusUpdates();
                });
            }
            
            // Start status updates on page load
            setTimeout(startStatusUpdates, 1000);
            
            // Handle window resize
            $(window).on('resize', function() {
                clearTimeout(window.resizeTimeout);
                window.resizeTimeout = setTimeout(handleWindowResize, 250);
            });
            
            // Close mobile sidebar when modal is shown
            $('.modal').on('show.bs.modal', function() {
                if (window.innerWidth < 992) {
                    $('.sidebar').removeClass('show');
                    $('#mobileSidebarOverlay').removeClass('show');
                }
            });
            
            // Stop status updates when page is unloaded
            $(window).on('beforeunload', function() {
                stopStatusUpdates();
            });
        });
    </script>
</body>
</html>