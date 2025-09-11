<?php
/**
 * Heritage Christian University Online Voting System
 * Main Logout Handler
 */

// Start session
session_start();

// Check if confirmation is provided
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    // Show confirmation page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Logout - HCU Voting System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    </head>
    <body class="bg-light">
        <div class="container-fluid vh-100 d-flex align-items-center justify-content-center">
            <div class="card shadow-lg" style="max-width: 400px; width: 100%;">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <div class="mb-3"></div>
                        <h4 class="card-title text-primary">Confirm Logout</h4>
                    </div>
                    
                    <p class="text-muted mb-4">Are you sure you want to log out of the voting system?</p>
                    
                    <div class="d-grid gap-2">
                        <a href="logout.php?confirm=yes" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Yes, Log Out
                        </a>
                        <button onclick="history.back()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    </body>
    </html>
    <?php
    exit();
}

// If confirmed, proceed with logout
// Destroy all session data
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to landing page
header('Location: landing.php');
exit();
?>