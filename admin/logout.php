<?php
/**
 * Heritage Christian University Online Voting System
 * Admin Logout
 */

// Start session
session_start();

// Destroy all session data
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>