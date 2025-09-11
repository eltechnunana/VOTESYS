<?php
/**
 * Heritage Christian University Online Voting System
 * Election-Specific Voter Login Page
 */

require_once '../config/voter_config.php';
require_once '../config/database.php';
require_once '../config/session.php';

$auth = new VoterAuth();
$error_message = '';
$success_message = '';

// Get URL parameters
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
$auth_method = isset($_GET['auth_method']) ? $_GET['auth_method'] : 'student_id';
$require_registration = isset($_GET['require_registration']) ? (bool)$_GET['require_registration'] : false;
$prevent_multiple = isset($_GET['prevent_multiple']) ? (bool)$_GET['prevent_multiple'] : true;
$log_activity = isset($_GET['log_activity']) ? (bool)$_GET['log_activity'] : true;
$expiry = isset($_GET['expiry']) ? (int)$_GET['expiry'] : 7;
$instructions = isset($_GET['instructions']) ? $_GET['instructions'] : '';

// Validate election ID
if ($election_id <= 0) {
    $error_message = 'Invalid election ID provided.';
}

// Check if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ../voter_page.php?election_id=' . $election_id);
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = VoterSecurity::sanitizeInput($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($student_id)) {
        $error_message = 'Please enter your Student ID.';
    } else {
        // Handle different authentication methods
        $login_success = false;
        
        switch ($auth_method) {
            case 'student_id':
                // Student ID only authentication
                $login_success = $auth->loginWithStudentId($student_id, $election_id);
                break;
                
            case 'id_password':
                // Student ID + Password authentication
                if (empty($password)) {
                    $error_message = 'Please enter your password.';
                } else {
                    $login_success = $auth->login($student_id, $password, $election_id);
                }
                break;
                
            case 'token':
                // Token-based authentication (if implemented)
                $token = $_POST['token'] ?? '';
                if (empty($token)) {
                    $error_message = 'Please enter your access token.';
                } else {
                    $login_success = $auth->loginWithToken($token, $election_id);
                }
                break;
                
            default:
                $error_message = 'Invalid authentication method.';
        }
        
        if ($login_success) {
            // Log activity if required
            if ($log_activity) {
                $auth->logVoterActivity($student_id, $election_id, 'login');
            }
            
            // Redirect to voting page
            header('Location: ../voter_page.php?election_id=' . $election_id);
            exit();
        } else if (empty($error_message)) {
            $error_message = 'Invalid credentials. Please try again.';
        }
    }
}

// Get error message from URL if redirected
if (isset($_GET['error'])) {
    $error_message = VoterSecurity::sanitizeInput($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = VoterSecurity::sanitizeInput($_GET['success']);
}

// Get election information
$election_info = null;
if ($election_id > 0) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT title, description, start_date, end_date FROM elections WHERE id = ? AND is_active = 1");
        $stmt->execute([$election_id]);
        $election_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching election info: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - Heritage Christian University</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/voter.css" rel="stylesheet">
    
    <style>
        :root {
            --hcu-blue: #2c3e50;
            --hcu-gold: #f39c12;
            --success-green: #27ae60;
            --error-red: #e74c3c;
            --neutral-light: #ecf0f1;
            --neutral-dark: #34495e;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--hcu-blue) 0%, var(--neutral-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 20px;
            display: flex;
            min-height: 600px;
        }

        .login-left {
            background: linear-gradient(135deg, var(--hcu-blue) 0%, var(--hcu-gold) 100%);
            color: white;
            padding: 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-right {
            padding: 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-title {
            color: var(--hcu-blue);
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .form-subtitle {
            color: var(--neutral-dark);
            margin-bottom: 30px;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--hcu-blue);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--hcu-blue);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }

        .input-group-text {
            background: var(--neutral-light);
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 12px 0 0 12px;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--hcu-blue) 0%, var(--hcu-gold) 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(44, 62, 80, 0.3);
            color: white;
        }

        .election-info {
            background: var(--neutral-light);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .election-info h5 {
            color: var(--hcu-blue);
            margin-bottom: 10px;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: var(--hcu-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            color: var(--hcu-gold);
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 10px;
            }
            
            .login-left {
                padding: 40px 20px;
            }
            
            .login-right {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <h1 class="login-title">Digital Voting Platform</h1>
            <p class="login-subtitle">Online Voting System</p>
            <img src="../assets/images/voting-illustration.svg" alt="Voting" class="voting-illustration" style="max-width: 200px; margin: 20px 0;">
            <p>Secure • Transparent • Democratic</p>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <h2 class="form-title">Voter Login</h2>
            <p class="form-subtitle">Enter your credentials to access the voting system</p>
            
            <?php if ($election_info): ?>
            <div class="election-info">
                <h5><i class="fas fa-vote-yea me-2"></i><?php echo htmlspecialchars($election_info['title']); ?></h5>
                <?php if ($election_info['description']): ?>
                    <p class="mb-2"><?php echo htmlspecialchars($election_info['description']); ?></p>
                <?php endif; ?>
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    <?php echo date('M j, Y g:i A', strtotime($election_info['start_date'])); ?> - 
                    <?php echo date('M j, Y g:i A', strtotime($election_info['end_date'])); ?>
                </small>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($instructions)): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo htmlspecialchars($instructions); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" novalidate>
                <div class="form-group">
                    <label for="student_id" class="form-label">
                        <i class="fas fa-id-card me-2"></i>Student ID
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="student_id" 
                               name="student_id" 
                               placeholder="Enter your Student ID"
                               value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>"
                               required>
                    </div>
                </div>
                
                <?php if ($auth_method === 'id_password'): ?>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <div class="input-group position-relative">
                        <span class="input-group-text">
                            <i class="fas fa-key"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required>
                        <button type="button" 
                                class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-2 p-0" 
                                id="togglePassword" 
                                style="border: none; background: none; color: var(--hcu-blue); z-index: 10;">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <?php elseif ($auth_method === 'token'): ?>
                <div class="form-group">
                    <label for="token" class="form-label">
                        <i class="fas fa-key me-2"></i>Access Token
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-ticket-alt"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               id="token" 
                               name="token" 
                               placeholder="Enter your access token"
                               required>
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Login to Vote
                </button>
            </form>
            
            <div class="back-link">
                <a href="../landing.php">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-focus on student ID field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('student_id').focus();
            
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (togglePassword && passwordInput && eyeIcon) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle eye icon
                    if (type === 'text') {
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    } else {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                });
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const studentId = document.getElementById('student_id').value.trim();
            
            if (!studentId) {
                e.preventDefault();
                alert('Please enter your Student ID.');
                return false;
            }
            
            <?php if ($auth_method === 'id_password'): ?>
            const password = document.getElementById('password').value;
            if (!password) {
                e.preventDefault();
                alert('Please enter your password.');
                return false;
            }
            <?php elseif ($auth_method === 'token'): ?>
            const token = document.getElementById('token').value.trim();
            if (!token) {
                e.preventDefault();
                alert('Please enter your access token.');
                return false;
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>