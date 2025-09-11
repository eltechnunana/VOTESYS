<?php
/**
 * Heritage Christian University Online Voting System
 * Voter Registration Page
 */

require_once '../config/voter_config.php';
require_once '../config/database.php';

$db = VoterDatabase::getInstance()->getConnection();
$error_message = '';
$success_message = '';

// Get registration parameters from URL
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
$type = $_GET['type'] ?? '';
$required_fields = isset($_GET['required']) ? explode(',', $_GET['required']) : [];
$optional_fields = isset($_GET['optional']) ? explode(',', $_GET['optional']) : [];
$expiry = $_GET['expiry'] ?? '';
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';

// Validate registration link
if (!$election_id || $type !== 'registration') {
    $error_message = 'Invalid registration link.';
}

// Get election information first
$election = null;
if ($election_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();
        
        if (!$election) {
            $error_message = 'Election not found.';
        } else {
            // Check if election has already started AND is active - prevent registration after start date only for active elections
            $current_time = time();
            $election_start_time = strtotime($election['start_date']);
            
            if ($current_time >= $election_start_time && $election['is_active']) {
                $error_message = 'Voter registration is closed. Registration is only allowed before the election start date and time.';
            }
        }
    } catch (PDOException $e) {
        error_log("Election fetch error: " . $e->getMessage());
        $error_message = 'Unable to load election information.';
    }
}

// Check if registration link has expired
if ($expiry) {
    // Set expiry to end of day (23:59:59) instead of beginning of day
    $expiry_timestamp = strtotime($expiry . ' 23:59:59');
    $current_timestamp = time();
    
    if ($expiry_timestamp < $current_timestamp) {
        $error_message = 'This registration link has expired.';
    }
}

// Continue with form processing

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message) {
    $student_id = VoterSecurity::sanitizeInput($_POST['student_id'] ?? '');
    $first_name = VoterSecurity::sanitizeInput($_POST['first_name'] ?? '');
    $last_name = VoterSecurity::sanitizeInput($_POST['last_name'] ?? '');
    $full_name = trim($first_name . ' ' . $last_name); // Combine for database storage
    $year_level = VoterSecurity::sanitizeInput($_POST['level'] ?? '');
    $course = VoterSecurity::sanitizeInput($_POST['department'] ?? '');
    $email = VoterSecurity::sanitizeInput($_POST['email'] ?? '');
    // Password will be auto-generated, no user input needed
    
    // Validate required fields
    $validation_errors = [];
    
    if (in_array('student_id', $required_fields) && empty($student_id)) {
        $validation_errors[] = 'Student ID is required.';
    }
    
    if (in_array('full_name', $required_fields) && (empty($first_name) || empty($last_name))) {
        $validation_errors[] = 'First Name and Last Name are required.';
    }
    
    if (in_array('level', $required_fields) && empty($year_level)) {
        $validation_errors[] = 'Level is required.';
    }
    
    if (in_array('department', $required_fields) && empty($course)) {
        $validation_errors[] = 'Department is required.';
    }
    
    if (empty($email)) {
        $validation_errors[] = 'Email is required.';
    }
    
    // Password validation removed - auto-generated passwords are used
    
    // Check if student ID already exists
    if (!empty($student_id) && empty($validation_errors)) {
        try {
            $stmt = $db->prepare("SELECT id FROM voters WHERE student_id = ?");
            $stmt->execute([$student_id]);
            if ($stmt->fetch()) {
                $validation_errors[] = 'Student ID already registered.';
            }
        } catch (PDOException $e) {
            error_log("Student ID check error: " . $e->getMessage());
            $validation_errors[] = 'Registration error. Please try again.';
        }
    }
    
    if (empty($validation_errors)) {
        // Generate secure password and hash it
        require_once '../config/email.php';
        $auto_password = EmailUtility::generateSecurePassword(12);
        $hashed_password = VoterSecurity::hashPassword($auto_password);
        
        try {
            // Insert new voter
            $stmt = $db->prepare("
                INSERT INTO voters (student_id, first_name, last_name, email, password, year_level, course, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $student_id,
                $first_name,
                $last_name,
                $email,
                $hashed_password,
                $year_level,
                $course
            ]);
            
            // Database insertion successful, now try to send email
            $registration_successful = true;
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            
            // Check for specific constraint violations
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), "for key 'email'") !== false) {
                    $error_message = 'Registration failed. This email address is already registered. Please use a different email address or contact support if you believe this is an error.';
                } elseif (strpos($e->getMessage(), "for key 'student_id'") !== false) {
                    $error_message = 'Registration failed. This student ID is already registered. Please check your student ID or contact support if you believe this is an error.';
                } else {
                    $error_message = 'Registration failed. Some of the information you provided is already in use. Please check your details and try again.';
                }
            } else {
                $error_message = 'Registration failed. Please try again.';
            }
            
            $registration_successful = false;
        }
        
        // Handle email sending separately from database operations
        if ($registration_successful) {
            try {
                // Send password via email
                $emailUtility = new EmailUtility();
                $emailSent = $emailUtility->sendPasswordEmail(
                    $email,
                    $full_name,
                    $student_id,
                    $auto_password,
                    $election ? $election['election_title'] : ''
                );
                
                if ($emailSent) {
                    $success_message = 'Registration successful! Your login password has been sent to your email address. Please check your email to get your login credentials.';
                } else {
                    $success_message = 'Registration successful! However, there was an issue sending your password via email.<br><br>';
                    $success_message .= '<strong>Your login credentials:</strong><br>';
                    $success_message .= 'Student ID: ' . htmlspecialchars($student_id) . '<br>';
                    $success_message .= 'Password: <span style="background-color: #ffffcc; padding: 2px 4px; font-family: monospace; font-weight: bold;">' . htmlspecialchars($auto_password) . '</span><br><br>';
                    $success_message .= '<em>Please save these credentials securely. You can change your password after logging in.</em>';
                    error_log("Failed to send password email for student ID: {$student_id}, email: {$email}. Password provided directly to user.");
                }
            } catch (Exception $e) {
                // Email sending failed, but registration was successful
                error_log("Email sending error: " . $e->getMessage());
                $success_message = 'Registration successful! However, there was an issue sending your password via email.<br><br>';
                $success_message .= '<strong>Your login credentials:</strong><br>';
                $success_message .= 'Student ID: ' . htmlspecialchars($student_id) . '<br>';
                $success_message .= 'Password: <span style="background-color: #ffffcc; padding: 2px 4px; font-family: monospace; font-weight: bold;">' . htmlspecialchars($auto_password) . '</span><br><br>';
                $success_message .= '<em>Please save these credentials securely. You can change your password after logging in.</em>';
            }
            
            // Clear form data on success
            $student_id = $first_name = $last_name = $year_level = $course = $email = $phone = $gender = '';
        }
    } else {
        $error_message = implode('<br>', $validation_errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration - Heritage Christian University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/voter.css" rel="stylesheet">
    <style>
        .registration-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .required-field::after {
            content: ' *';
            color: #dc3545;
        }
        
        .alert {
            border-radius: var(--border-radius-sm);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="voter-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="university-branding">
                        <img src="../assets/images/hcu-logo.svg" alt="HCU Logo" class="university-logo">
                        <div>
                            <h1 class="university-name">Heritage Christian University</h1>
                            <p class="system-name">Online Voting System</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <h2 class="election-name">Voter Registration</h2>
                    <?php if ($election): ?>
                        <p class="text-muted">For: <?php echo htmlspecialchars($election['election_title']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../voter_login.php" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php if ($error_message && !$election): ?>
                <div class="registration-container">
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                    </div>
                    <div class="text-center">
                        <a href="../landing.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Return to Home
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="registration-container">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                        </div>
                        <div class="text-center">
                            <a href="../voter_login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Now
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="mb-4"><i class="fas fa-user-plus me-2"></i>Register to Vote</h3>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo VoterSecurity::generateCSRFToken(); ?>">
                            
                            <?php if (in_array('student_id', $required_fields)): ?>
                                <div class="form-group">
                                    <label for="student_id" class="form-label required-field">Student ID</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" 
                                           value="<?php echo htmlspecialchars($student_id ?? ''); ?>" required>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('full_name', $required_fields)): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="first_name" class="form-label required-field">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="last_name" class="form-label required-field">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('level', $required_fields)): ?>
                                <div class="form-group">
                                    <label for="level" class="form-label required-field">Year Level</label>
                                    <select class="form-control" id="level" name="level" required>
                                        <option value="">Select Year Level</option>
                                        <option value="1st" <?php echo (isset($year_level) && $year_level === '1st') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd" <?php echo (isset($year_level) && $year_level === '2nd') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd" <?php echo (isset($year_level) && $year_level === '3rd') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th" <?php echo (isset($year_level) && $year_level === '4th') ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="Graduate" <?php echo (isset($year_level) && $year_level === 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('department', $required_fields)): ?>
                                <div class="form-group">
                                    <label for="department" class="form-label required-field">Course</label>
                                    <input type="text" class="form-control" id="department" name="department" 
                                           value="<?php echo htmlspecialchars($course ?? ''); ?>" required>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="email" class="form-label required-field">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                            
                            <?php if (in_array('phone', $optional_fields)): ?>
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone (Optional)</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('gender', $optional_fields)): ?>
                                <div class="form-group">
                                    <label for="gender" class="form-label">Gender (Optional)</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo (isset($gender) && $gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($gender) && $gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Security Notice:</strong> For your security, a strong password will be automatically generated and sent to your email address after registration.
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Register
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p class="text-muted">Already registered? <a href="../voter_login.php">Login here</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation (password fields removed - auto-generated passwords used)
        // Additional form validation can be added here if needed
    </script>
</body>
</html>