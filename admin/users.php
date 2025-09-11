<?php
define('SECURE_ACCESS', true);
require_once '../config/database.php';
require_once '../config/session.php';

// Direct access allowed - no login check required

$page_title = 'User Management';

// Initialize database connection
$pdo = getDBConnection();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create':
                $stmt = $pdo->prepare("INSERT INTO admin (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute([$_POST['username'], $_POST['email'], $hashedPassword, $_POST['role']]);
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
                break;
                
            case 'update':
                if (!empty($_POST['password'])) {
                    $stmt = $pdo->prepare("UPDATE admin SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt->execute([$_POST['username'], $_POST['email'], $hashedPassword, $_POST['role'], $_POST['id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE admin SET username = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$_POST['username'], $_POST['email'], $_POST['role'], $_POST['id']]);
                }
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM admin WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                break;
                
            case 'get_users':
                $search = $_POST['search'] ?? '';
                $role = $_POST['role'] ?? '';
                
                $sql = "SELECT id, username, email, role, created_at, last_login FROM admin WHERE 1=1";
                $params = [];
                
                if (!empty($search)) {
                    $sql .= " AND (username LIKE ? OR email LIKE ?)";
                    $searchTerm = "%$search%";
                    $params = array_merge($params, [$searchTerm, $searchTerm]);
                }
                
                if (!empty($role)) {
                    $sql .= " AND role = ?";
                    $params[] = $role;
                }
                
                $sql .= " ORDER BY created_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'users' => $users]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
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
            box-shadow: 2px 0 15px rgba(74, 107, 138, 0.1);
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
            box-shadow: 0 4px 20px rgba(74, 107, 138, 0.08);
            border: 1px solid var(--neutral-light);
        }

        .content-card {
            background: var(--white);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(74, 107, 138, 0.08);
            border: 1px solid var(--neutral-light);
            margin-bottom: 24px;
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
            background-color: #3a5a7a;
            border-color: #3a5a7a;
            box-shadow: 0 4px 12px rgba(74, 107, 138, 0.3);
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

        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(74, 107, 138, 0.05);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            padding: 16px;
        }

        .table tbody td {
            padding: 16px;
            border-color: var(--neutral-light);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(74, 107, 138, 0.02);
        }

        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-warning {
            background-color: var(--warning-color) !important;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--neutral-light);
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 107, 138, 0.25);
        }

        .search-filters {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(74, 107, 138, 0.05);
            border: 1px solid var(--neutral-light);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons .btn {
            padding: 6px 12px;
            font-size: 0.875rem;
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
            box-shadow: 0 4px 12px rgba(74, 107, 138, 0.3);
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

        /* Responsive Design */
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
            }

            .page-header h2 {
                font-size: 1.5rem;
            }

            .content-card {
                padding: 20px;
            }

            .search-filters {
                padding: 15px;
            }

            .table-responsive {
                border-radius: 12px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }

            .action-buttons .btn {
                font-size: 0.8rem;
                padding: 4px 8px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 80px 10px 15px;
            }

            .page-header {
                padding: 15px;
            }

            .page-header h2 {
                font-size: 1.3rem;
            }

            .content-card {
                padding: 15px;
            }

            .search-filters {
                padding: 12px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
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
                        <a class="nav-link" href="voters.php">
                            <i class="fas fa-user-check me-2"></i>Voters
                        </a>
                        <a class="nav-link active" href="users.php">
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
                                <i class="fas fa-user-cog me-2 text-primary"></i>User Management
                            </h2>
                            <p class="text-muted mb-0">Manage admin users and their permissions</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal()">
                                <i class="fas fa-plus me-2"></i>Add User
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search users...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="roleFilter">
                                <option value="">All Roles</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-primary w-100" onclick="loadUsers()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="content-card">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Loading users...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="userForm">
                    <div class="modal-body">
                        <input type="hidden" id="userId" name="id">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text" id="passwordHelp">Leave blank to keep current password (when editing)</div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveUserBtn">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentEditingId = null;

        // Mobile navigation
        document.getElementById('mobileNavToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Auto-close sidebar on desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function() {
                setTimeout(loadUsers, 300); // Debounce search
            });
            
            document.getElementById('roleFilter').addEventListener('change', loadUsers);
        });

        // Load users function
        function loadUsers() {
            const search = document.getElementById('searchInput').value;
            const role = document.getElementById('roleFilter').value;
            
            fetch('users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_users&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUsers(data.users);
                } else {
                    showAlert('Error loading users: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error loading users', 'danger');
            });
        }

        // Display users in table
        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            
            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="fas fa-users me-2 text-muted"></i>No users found
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = users.map(user => {
                const roleClass = {
                    'super_admin': 'bg-primary',
                    'admin': 'bg-success'
                }[user.role] || 'bg-secondary';
                
                const createdDate = new Date(user.created_at).toLocaleDateString('en-US', {timeZone: 'UTC'}) + ' UTC';
                const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString('en-US', {timeZone: 'UTC'}) + ' UTC' : 'Never';
                
                return `
                    <tr>
                        <td>
                            <div>
                                <strong>@${escapeHtml(user.username)}</strong>
                            </div>
                        </td>
                        <td>${escapeHtml(user.email)}</td>
                        <td><span class="badge ${roleClass}">${escapeHtml(user.role.replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' '))}</span></td>
                        <td>${createdDate}</td>
                        <td>${lastLogin}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-primary" onclick="editUser(${user.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id}, '${escapeHtml(user.username)}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Open user modal
        function openUserModal(user = null) {
            const modal = document.getElementById('userModal');
            const title = document.getElementById('userModalTitle');
            const form = document.getElementById('userForm');
            const passwordHelp = document.getElementById('passwordHelp');
            
            form.reset();
            currentEditingId = null;
            
            if (user) {
                title.textContent = 'Edit User';
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username;
                document.getElementById('email').value = user.email;
                document.getElementById('role').value = user.role;
                document.getElementById('password').required = false;
                passwordHelp.style.display = 'block';
                currentEditingId = user.id;
            } else {
                title.textContent = 'Add User';
                document.getElementById('password').required = true;
                passwordHelp.style.display = 'none';
            }
        }

        // Reset form function
         function resetForm() {
             const title = document.getElementById('modalTitle');
             const passwordHelp = document.getElementById('passwordHelp');
             
             title.textContent = 'Add User';
             document.getElementById('userForm').reset();
             document.getElementById('userId').value = '';
             document.getElementById('password').required = true;
             passwordHelp.style.display = 'none';
             currentEditingId = null;
         }

        // Edit user
        function editUser(userId) {
            // Find user data from current table
            const rows = document.querySelectorAll('#usersTableBody tr');
            let userData = null;
            
            rows.forEach(row => {
                const editBtn = row.querySelector(`button[onclick="editUser(${userId})"]`);
                if (editBtn) {
                    const cells = row.querySelectorAll('td');
                    const username = cells[0].querySelector('strong').textContent.replace('@', '');
                    const email = cells[1].textContent;
                    const role = cells[2].querySelector('.badge').textContent.toLowerCase().replace(' ', '_');
                    
                    userData = {
                        id: userId,
                        username: username,
                        email: email,
                        role: role
                    };
                }
            });
            
            if (userData) {
                openUserModal(userData);
                new bootstrap.Modal(document.getElementById('userModal')).show();
            }
        }

        // Delete user
        function deleteUser(userId, username) {
            if (confirm(`Are you sure you want to delete user "${username}"?`)) {
                fetch('users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadUsers();
                    } else {
                        showAlert('Error: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error deleting user', 'danger');
                });
            }
        }

        // Handle form submission
        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = currentEditingId ? 'update' : 'create';
            formData.append('action', action);
            
            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
                    loadUsers();
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error saving user', 'danger');
            });
        });

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.main-content');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>