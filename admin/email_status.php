<?php
/**
 * Email Configuration Status Page for Administrators
 */

session_start();
require_once '../config/database.php';
require_once '../config/email.php';

// Check if user is admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Email Configuration Status';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - VOTESYS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-vote-yea me-2"></i>VOTESYS Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-envelope me-2"></i><?php echo $pageTitle; ?></h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Test email configuration
                        echo "<h5>Email Configuration Status</h5>";
                        
                        try {
                            $emailUtility = new EmailUtility();
                            $mailer = $emailUtility->getMailer();
                            
                            echo "<div class='row'>";
                            echo "<div class='col-md-6'>";
                            echo "<h6>Current Settings:</h6>";
                            echo "<ul class='list-group list-group-flush'>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>SMTP Host:</span><span class='badge bg-info'>" . getenv('MAIL_HOST') . "</span>";
                            echo "</li>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>SMTP Port:</span><span class='badge bg-info'>" . getenv('MAIL_PORT') . "</span>";
                            echo "</li>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>Username:</span><span class='badge bg-info'>" . getenv('MAIL_USERNAME') . "</span>";
                            echo "</li>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>Password:</span><span class='badge bg-" . (getenv('MAIL_PASSWORD') ? 'success' : 'danger') . "'>" . (getenv('MAIL_PASSWORD') ? 'Configured' : 'Not Set') . "</span>";
                            echo "</li>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>From Email:</span><span class='badge bg-info'>" . getenv('MAIL_FROM_EMAIL') . "</span>";
                            echo "</li>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>Encryption:</span><span class='badge bg-info'>" . getenv('MAIL_ENCRYPTION') . "</span>";
                            echo "</li>";
                            echo "</ul>";
                            echo "</div>";
                            
                            echo "<div class='col-md-6'>";
                            echo "<h6>Connection Test:</h6>";
                            
                            // Test SMTP connection
                            try {
                                $testMailer = new PHPMailer\PHPMailer\PHPMailer(true);
                                $testMailer->isSMTP();
                                $testMailer->Host = getenv('MAIL_HOST');
                                $testMailer->SMTPAuth = true;
                                $testMailer->Username = getenv('MAIL_USERNAME');
                                $testMailer->Password = getenv('MAIL_PASSWORD');
                                $testMailer->SMTPSecure = getenv('MAIL_ENCRYPTION');
                                $testMailer->Port = getenv('MAIL_PORT');
                                $testMailer->Timeout = 10;
                                
                                $testMailer->SMTPOptions = array(
                                    'ssl' => array(
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        'allow_self_signed' => true
                                    )
                                );
                                
                                $testMailer->smtpConnect();
                                $testMailer->smtpClose();
                                
                                echo "<div class='alert alert-success'>";
                                echo "<i class='fas fa-check-circle me-2'></i>SMTP Connection: <strong>Successful</strong>";
                                echo "</div>";
                                
                            } catch (Exception $e) {
                                echo "<div class='alert alert-danger'>";
                                echo "<i class='fas fa-times-circle me-2'></i>SMTP Connection: <strong>Failed</strong><br>";
                                echo "<small>Error: " . htmlspecialchars($e->getMessage()) . "</small>";
                                echo "</div>";
                            }
                            
                            echo "<h6>PHP Extensions:</h6>";
                            echo "<ul class='list-group list-group-flush'>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>OpenSSL:</span><span class='badge bg-" . (extension_loaded('openssl') ? 'success' : 'danger') . "'>" . (extension_loaded('openssl') ? 'Loaded' : 'Not Loaded') . "</span>";
                            echo "</li>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>cURL:</span><span class='badge bg-" . (extension_loaded('curl') ? 'success' : 'danger') . "'>" . (extension_loaded('curl') ? 'Loaded' : 'Not Loaded') . "</span>";
                            echo "</li>";
                            echo "<li class='list-group-item d-flex justify-content-between'>";
                            echo "<span>mail() function:</span><span class='badge bg-" . (function_exists('mail') ? 'success' : 'danger') . "'>" . (function_exists('mail') ? 'Available' : 'Not Available') . "</span>";
                            echo "</li>";
                            echo "</ul>";
                            echo "</div>";
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<i class='fas fa-exclamation-triangle me-2'></i>Error initializing email utility: " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                        }
                        ?>
                        
                        <hr>
                        
                        <h5>Troubleshooting Guide</h5>
                        <div class="accordion" id="troubleshootingAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        <i class="fas fa-key me-2"></i>SendGrid API Key Issues
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li>Verify the SendGrid API key is valid and hasn't expired</li>
                                            <li>Ensure the API key has 'Mail Send' permissions</li>
                                            <li>Check if the API key is correctly set in the .env file</li>
                                            <li>Verify the 'From' email address is verified in SendGrid</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                        <i class="fas fa-network-wired me-2"></i>Network & Firewall Issues
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li>Check if port 587 is open for outbound connections</li>
                                            <li>Verify network connectivity to smtp.sendgrid.net</li>
                                            <li>Check if your hosting provider blocks SMTP connections</li>
                                            <li>Try using port 25 or 465 if 587 doesn't work</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                        <i class="fas fa-cog me-2"></i>Configuration Issues
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li>Ensure the .env file exists and is readable</li>
                                            <li>Check that environment variables are loading correctly</li>
                                            <li>Verify all required PHP extensions are installed</li>
                                            <li>Check PHP error logs for detailed error messages</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> When email sending fails, the system will automatically display login credentials to users during registration, and administrators will see the password in success messages when adding voters.
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <a href="../debug_email.php" class="btn btn-primary" target="_blank">
                                <i class="fas fa-bug me-2"></i>Debug Email Test
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>