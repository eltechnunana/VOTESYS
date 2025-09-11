<?php
/**
 * Heritage Christian University Online Voting System
 * Main Voter Page
 */

// Timezone is set in constants.php to UTC (GMT+00:00)

require_once 'config/voter_config.php';

// Require authentication
requireVoterAuth();

$auth = new VoterAuth();
$db = VoterDatabase::getInstance()->getConnection();
$voter = $auth->getCurrentVoter();

// Get election ID from URL parameter, fallback to constant
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : CURRENT_ELECTION_ID;

// Validate election ID
if ($election_id <= 0) {
    $election_id = CURRENT_ELECTION_ID;
}

// Check if voter has already voted
$hasVoted = $voter ? $auth->hasVotedInElection($voter['id']) : false;

// Get current election information
try {
    $stmt = $db->prepare("SELECT id, election_title as title, description, start_date, end_date, is_active FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch();
    
    if (!$election) {
        $error_message = "No election found.";
        $election = ['title' => 'No Election Found', 'start_date' => date('Y-m-d H:i:s'), 'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')), 'is_active' => 0];
    }
} catch (PDOException $e) {
    error_log("Election fetch error: " . $e->getMessage());
    $error_message = "Unable to load election information.";
    $election = ['title' => 'Election System', 'start_date' => date('Y-m-d H:i:s'), 'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')), 'is_active' => 0];
}

// Check if voting is active
$voting_is_active = isset($election['is_active']) && $election['is_active'] == 1;

// Fetch positions assigned to current election and candidates (only if voting is active)
$positions_query = "SELECT esp.id, esp.position_title as name, esp.description, esp.display_order 
                   FROM election_specific_positions esp 
                   WHERE esp.election_id = ? 
                   ORDER BY esp.display_order, esp.position_title";
$positions_stmt = $db->prepare($positions_query);
$positions_stmt->execute([$election_id]);
$positions_result = $positions_stmt->fetchAll();

$candidates_by_position = [];
foreach ($positions_result as $position) {
    $candidates = [];
    
    // Only fetch candidates if voting is active
    if ($voting_is_active) {
        $candidates_query = "SELECT c.id, c.full_name as name, c.course, c.department, c.motto, c.photo 
                            FROM candidates c 
                            WHERE c.election_specific_position_id = ? AND c.is_approved = 1";
        $candidates_stmt = $db->prepare($candidates_query);
        $candidates_stmt->execute([$position['id']]);
        $candidates_result = $candidates_stmt->fetchAll();
        
        foreach ($candidates_result as $candidate) {
            // Include photo data for display (keep photo for template rendering)
            $clean_candidate = [
                'id' => $candidate['id'],
                'name' => $candidate['name'],
                'course' => $candidate['course'],
                'department' => $candidate['department'],
                'motto' => $candidate['motto'],
                'photo' => $candidate['photo'] // Include photo for template
            ];
            $candidates[] = $clean_candidate;
        }
    }
    
    $candidates_by_position[] = [
        'position' => $position,
        'candidates' => $candidates
    ];
}

// Convert to the format expected by the template
$positions = [];
foreach ($candidates_by_position as $item) {
    $position_id = $item['position']['id'];
    $positions[$position_id] = [
        'id' => $item['position']['id'],
        'name' => $item['position']['name'] ?? '',
        'description' => $item['position']['description'] ?? '',
        'order_priority' => $item['position']['display_order'] ?? 1,
        'candidates' => $item['candidates'] ?? []
    ];
}

// Ensure positions is always an array (not null)
if (!is_array($positions)) {
    $positions = [];
}

// Create a separate positions array for JavaScript (without binary photo data)
$positions_for_js = [];
foreach ($positions as $position_id => $position) {
    $js_candidates = [];
    foreach ($position['candidates'] as $candidate) {
        $js_candidates[] = [
            'id' => $candidate['id'],
            'name' => $candidate['name'],
            'course' => $candidate['course'],
            'department' => $candidate['department'],
            'motto' => $candidate['motto']
            // Exclude photo field to prevent JSON encoding issues
        ];
    }
    
    // Use array_push to create indexed array instead of associative
    $positions_for_js[] = [
        'id' => $position['id'],
        'name' => $position['name'],
        'description' => $position['description'],
        'order_priority' => $position['order_priority'],
        'candidates' => $js_candidates
    ];
}

// Positions data is ready for JavaScript

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: voter_login.php?success=You have been logged out successfully.');
    exit();
}

// Generate CSRF token
$csrf_token = VoterSecurity::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Now - Heritage Christian University</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="alternate icon" href="assets/images/favicon.svg">
    
    <!-- Custom CSS -->
    <link href="assets/css/voter.css" rel="stylesheet">
    
    <style>
        :root {
            /* Professional Color Palette */
            --primary-blue: #4A6B8A;
            --success-green: #22C55E;
            --error-red: #DC2626;
            --neutral-light: #F9FAFB;
            --neutral-medium: #D1D5DB;
            --neutral-dark: #374151;
            --base-white: #FFFFFF;
            --text-primary: #000000;
            --text-secondary: #1F2937;
            --text-tertiary: #4B5563;
            --border-color: #E5E7EB;
            --shadow-light: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-medium: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-large: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--neutral-light);
            color: var(--text-primary);
            font-weight: 500;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        /* Header Styles */
        .voter-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a5a7a 100%);
            color: var(--base-white);
            padding: 1.5rem 0;
            box-shadow: var(--shadow-medium);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .university-branding .university-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--base-white);
        }
        
        .university-branding .system-name {
            font-size: 0.875rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 800;
            color: #000000;
        }
        
        .election-info .election-name {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--base-white);
        }
        
        .countdown-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem;
            backdrop-filter: blur(10px);
        }
        
        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .countdown-item {
            text-align: center;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            min-width: 60px;
        }
        
        .countdown-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--base-white);
        }
        
        .countdown-label {
            font-size: 0.75rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 800;
            color: #000000;
        }
        
        .countdown-text {
            text-align: center;
            margin: 0;
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 800;
            color: #000000;
        }
        
        .voter-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .voter-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-photo, .default-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .voter-info .voter-name {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            color: var(--base-white);
        }
        
        .voter-info .voter-id {
            font-size: 0.875rem;
            margin: 0;
            opacity: 0.9;
            font-weight: 800;
            color: #000000;
        }
        
        .logout-btn {
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--base-white);
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
            color: var(--base-white);
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }
        
        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-light);
        }
        
        .alert-danger {
            background-color: #FEF2F2;
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }
        
        /* Voted Container */
        .voted-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
        }
        
        .voted-card {
            background: var(--base-white);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: var(--shadow-large);
            max-width: 500px;
            width: 100%;
        }
        
        .voted-icon {
            font-size: 4rem;
            color: var(--success-green);
            margin-bottom: 1.5rem;
        }
        
        .voted-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .voted-message {
            font-size: 1.125rem;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        
        .voted-details {
            background: var(--neutral-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .voted-details p {
            margin: 0.5rem 0;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        /* Voting Container */
        .voting-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .voting-instructions {
            background: var(--base-white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
        }
        
        .voting-instructions h2 {
            color: var(--primary-blue);
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .voting-instructions .lead {
            color: var(--text-secondary);
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .progress-indicator {
            background: var(--neutral-light);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
        }
        
        .progress-text {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        /* Position Section */
        .position-section {
            background: var(--base-white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--border-color);
        }
        
        .position-header {
            border-bottom: 2px solid var(--neutral-light);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .position-title {
            color: var(--primary-blue);
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .position-description {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .selection-indicator {
            background: var(--primary-blue);
            color: var(--base-white);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }
        
        /* Candidate Cards */
        .candidates-grid {
            gap: 0.5cm; /* 0.5cm spacing between cards */
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        
        .candidate-wrapper {
            flex-shrink: 0;
        }
        
        .candidate-card {
            background: var(--base-white);
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: default;
            position: relative;
            overflow: hidden;
            width: 6cm;
            height: 27cm;
            flex-shrink: 0;
        }
        
        .candidate-card:hover {
            border-color: var(--primary-blue);
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
        }
        
        .candidate-card.selected {
            border-color: var(--success-green);
            background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        .candidate-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 1rem;
            border: 3px solid var(--border-color);
        }
        
        .candidate-image, .default-candidate-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: var(--neutral-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--text-secondary);
        }
        
        .candidate-info {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .candidate-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .candidate-program {
            color: var(--primary-blue);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .candidate-manifesto {
            text-align: left;
        }
        
        .candidate-manifesto strong {
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 700;
        }
        
        .manifesto-preview {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
            line-height: 1.5;
            margin-top: 0.5rem;
        }
        
        .read-more, .read-less {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
        }
        
        .read-more:hover, .read-less:hover {
            text-decoration: underline;
        }
        
        /* Candidate Actions */
        .candidate-actions {
            text-align: center;
        }
        
        .candidate-radio {
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .vote-btn {
            background: var(--primary-blue);
            color: var(--base-white);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .vote-btn:hover {
            background: #3a5a7a;
            transform: translateY(-1px);
            box-shadow: var(--shadow-medium);
            color: var(--base-white);
        }
        
        .candidate-radio:checked + .vote-btn {
            background: var(--success-green);
            color: var(--base-white);
        }
        
        .candidate-radio:checked + .vote-btn:hover {
            background: #16A34A;
        }
        
        /* Voting Actions */
        .voting-actions {
            background: var(--base-white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-light);
        }
        
        #submitVoteBtn {
            background: var(--success-green);
            border: none;
            border-radius: 12px;
            color: var(--base-white);
            font-weight: 600;
            font-size: 1.125rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-medium);
        }
        
        #submitVoteBtn:hover {
            background: #16A34A;
            transform: translateY(-2px);
            box-shadow: var(--shadow-large);
        }
        
        #submitVoteBtn:disabled {
            background: var(--neutral-medium);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-large);
        }
        
        .modal-header {
            background: var(--primary-blue);
            color: var(--base-white);
            border-radius: 16px 16px 0 0;
            padding: 1.5rem 2rem;
        }
        
        .modal-title {
            font-weight: 600;
            margin: 0;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .confirmation-message .lead {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .vote-review {
            background: var(--neutral-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .alert-warning {
            background-color: #FFFBEB;
            color: #92400E;
            border-left: 4px solid #F59E0B;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .btn-secondary {
            background: var(--neutral-medium);
            border: none;
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-secondary:hover {
            background: #9CA3AF;
            color: var(--text-primary);
        }
        
        #confirmSubmitBtn {
            background: var(--success-green);
            border: none;
            color: var(--base-white);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        
        #confirmSubmitBtn:hover {
            background: #16A34A;
        }
        
        /* Enhanced Responsive Design */
         
         /* Large tablets and small desktops */
         @media (max-width: 1024px) {
             .voting-container {
                 max-width: 100%;
                 padding: 0 1rem;
             }
             
             .candidates-grid {
                 justify-content: flex-start;
                 overflow-x: auto;
             }
             
             .candidate-card {
                 width: 6cm;
                 height: 27cm;
             }
             
             .position-title {
                 font-size: 1.375rem;
             }
             
             .candidate-name {
                 font-size: 1rem;
                 font-weight: 700;
             }
             
             .voting-instructions h2 {
                 font-size: 1.5rem;
             }
         }
         
         /* Tablets */
         @media (max-width: 768px) {
             .voter-header {
                 padding: 1rem 0;
             }
             
             .voter-header .container-fluid {
                 padding: 0 1rem;
             }
             
             .university-branding .university-name {
                 font-size: 1.25rem;
                 font-weight: 800;
             }
             
             .university-branding .system-name {
                 font-size: 0.75rem;
                 font-weight: 500;
             }
             
             .election-info .election-name {
                 font-size: 1.5rem;
                 font-weight: 700;
                 margin-bottom: 0.75rem;
             }
             
             .countdown-container {
                 padding: 0.75rem;
             }
             
             .countdown-timer {
                 gap: 0.5rem;
                 flex-wrap: wrap;
             }
             
             .countdown-item {
                 min-width: 50px;
                 padding: 0.5rem 0.25rem;
                 flex: 1;
             }
             
             .countdown-number {
                 font-size: 1.25rem;
             }
             
             .countdown-label {
                 font-size: 0.7rem;
             }
             
             .voter-profile {
                 justify-content: center;
                 margin-top: 1rem;
                 flex-direction: column;
                 text-align: center;
             }
             
             .voter-avatar {
                 margin-bottom: 0.5rem;
             }
             
             .main-content {
                 padding: 1rem 0;
             }
             
             .container-fluid {
                 padding: 0 1rem;
             }
             
             .voting-instructions,
             .position-section,
             .voting-actions {
                 padding: 1.5rem;
                 margin-bottom: 1.5rem;
                 border-radius: 12px;
             }
             
             .voting-instructions h2 {
                 font-size: 1.375rem;
                 text-align: center;
             }
             
             .voting-instructions .lead {
                 font-size: 1rem;
                 text-align: center;
             }
             
             .progress-indicator {
                 text-align: center;
             }
             
             .position-header {
                 text-align: center;
                 padding-bottom: 1rem;
                 margin-bottom: 1.5rem;
             }
             
             .position-title {
                 font-size: 1.375rem;
                 font-weight: 800;
             }
             
             .position-description {
                 font-size: 0.9rem;
                 font-weight: 600;
             }
             
             .candidates-grid {
                 gap: 0.5cm;
                 overflow-x: auto;
                 justify-content: flex-start;
             }
             
             .candidate-card {
                  width: 6cm;
                  height: 27cm;
              }
             
             .candidate-card {
                 padding: 1rem;
                 margin-bottom: 1rem;
             }
             
             .candidate-photo {
                 width: 100px;
                 height: 100px;
             }
             
             .candidate-name {
                 font-size: 1rem;
                 font-weight: 700;
             }
             
             .candidate-program {
                 font-size: 0.8rem;
                 font-weight: 600;
             }
             
             .vote-btn {
                 padding: 0.625rem 1.25rem;
                 font-size: 0.875rem;
                 width: 100%;
             }
             
             .voted-card {
                 padding: 2rem 1.5rem;
                 margin: 1rem;
             }
             
             .voted-title {
                 font-size: 1.5rem;
             }
             
             .voted-icon {
                 font-size: 3rem;
             }
             
             .modal-dialog {
                 margin: 1rem;
                 max-width: calc(100% - 2rem);
             }
         }
         
         /* Mobile phones */
         @media (max-width: 576px) {
             .voter-header {
                 padding: 0.75rem 0;
             }
             
             .voter-header .container-fluid {
                 padding: 0 0.75rem;
             }
             
             .university-branding .university-name {
                 font-size: 1.125rem;
                 font-weight: 800;
             }
             
             .university-branding .system-name {
                 font-size: 0.7rem;
                 font-weight: 500;
             }
             
             .election-info .election-name {
                 font-size: 1.25rem;
                 font-weight: 700;
                 margin-bottom: 0.5rem;
             }
             
             .countdown-container {
                 padding: 0.5rem;
             }
             
             .countdown-timer {
                 gap: 0.25rem;
             }
             
             .countdown-item {
                 min-width: 40px;
                 padding: 0.375rem 0.125rem;
             }
             
             .countdown-number {
                 font-size: 1rem;
             }
             
             .countdown-label {
                 font-size: 0.625rem;
             }
             
             .countdown-text {
                 font-size: 0.75rem;
             }
             
             .voter-profile {
                 gap: 0.5rem;
             }
             
             .voter-avatar {
                 width: 40px;
                 height: 40px;
             }
             
             .voter-info .voter-name {
                 font-size: 0.875rem;
             }
             
             .voter-info .voter-id {
                 font-size: 0.75rem;
             }
             
             .logout-btn {
                 font-size: 0.7rem;
                 padding: 0.25rem 0.5rem;
             }
             
             .main-content {
                 padding: 0.75rem 0;
             }
             
             .container-fluid {
                 padding: 0 0.75rem;
             }
             
             .voting-instructions,
             .position-section,
             .voting-actions {
                 padding: 1rem;
                 margin-bottom: 1rem;
                 border-radius: 8px;
             }
             
             .voting-instructions h2 {
                 font-size: 1.125rem;
             }
             
             .voting-instructions .lead {
                 font-size: 0.875rem;
             }
             
             .position-title {
                 font-size: 1.125rem;
                 font-weight: 800;
             }
             
             .position-description {
                 font-size: 0.875rem;
                 font-weight: 600;
             }
             
             .selection-indicator {
                 font-size: 0.75rem;
                 padding: 0.375rem 0.75rem;
             }
             
             .candidate-card {
                 padding: 0.75rem;
             }
             
             .candidate-photo {
                 width: 90px;
                 height: 90px;
             }
             
             .candidate-name {
                 font-size: 0.875rem;
                 font-weight: 700;
             }
             
             .candidate-program {
                 font-size: 0.75rem;
                 font-weight: 600;
             }
             
             .candidate-manifesto strong {
                 font-size: 0.75rem;
                 font-weight: 700;
             }
             
             .manifesto-preview {
                 font-size: 0.75rem;
                 font-weight: 500;
             }
             
             .vote-btn {
                 padding: 0.5rem 1rem;
                 font-size: 0.75rem;
             }
             
             #submitVoteBtn {
                 font-size: 1rem;
                 padding: 0.875rem 1.5rem;
             }
             
             .voted-card {
                 padding: 1.5rem 1rem;
                 margin: 0.75rem;
             }
             
             .voted-title {
                 font-size: 1.25rem;
             }
             
             .voted-message {
                 font-size: 1rem;
             }
             
             .voted-icon {
                 font-size: 2.5rem;
             }
             
             .modal-dialog {
                 margin: 0.5rem;
                 max-width: calc(100% - 1rem);
             }
             
             .modal-header {
                 padding: 1rem 1.5rem;
             }
             
             .modal-body,
             .modal-footer {
                 padding: 1rem 1.5rem;
             }
             
             .modal-title {
                 font-size: 1rem;
             }
             
             .confirmation-message .lead {
                 font-size: 0.875rem;
             }
         }
         
         /* Extra small devices */
         @media (max-width: 480px) {
             .countdown-timer {
                 flex-direction: row;
                 justify-content: space-between;
             }
             
             .countdown-item {
                 min-width: 35px;
                 padding: 0.25rem 0.125rem;
             }
             
             .countdown-number {
                 font-size: 0.875rem;
                 font-weight: 700;
             }
             
             .countdown-label {
                 font-size: 0.5rem;
                 font-weight: 600;
             }
             
             .voting-instructions h2 {
                 font-size: 1rem;
                 font-weight: 800;
             }
             
             .position-title {
                 font-size: 1rem;
                 font-weight: 800;
             }
             
             .position-description {
                 font-size: 0.8rem;
                 font-weight: 600;
             }
             
             .candidate-photo {
                 width: 80px;
                 height: 80px;
             }
             
             .candidate-name {
                 font-size: 0.8rem;
                 font-weight: 700;
             }
             
             .candidate-program {
                 font-size: 0.7rem;
                 font-weight: 600;
             }
             
             .vote-btn {
                 padding: 0.375rem 0.75rem;
                 font-size: 0.7rem;
                 font-weight: 600;
             }
             
             .voting-instructions,
             .position-section,
             .voting-actions {
                 margin-bottom: 0.75rem;
             }
         }
         
         /* Touch device optimizations */
         @media (hover: none) and (pointer: coarse) {
             .candidate-card {
                 padding: 1.25rem;
                 margin-bottom: 1.25rem;
             }
             
             .vote-btn {
                 min-height: 44px;
                 padding: 0.75rem 1.5rem;
                 font-weight: 600;
             }
             
             .logout-btn {
                 min-height: 44px;
                 padding: 0.5rem 1rem;
                 font-weight: 600;
             }
             
             #submitVoteBtn {
                 min-height: 56px;
                 font-weight: 700;
             }
             
             .btn-close {
                 min-width: 44px;
                 min-height: 44px;
             }
             
             .position-section {
                 padding: 1.5rem;
             }
             
             .voting-instructions {
                 padding: 1.5rem;
             }
         }
         
         /* Landscape orientation on mobile */
         @media (max-width: 768px) and (orientation: landscape) {
             .voter-header {
                 padding: 0.5rem 0;
             }
             
             .countdown-container {
                 padding: 0.5rem;
             }
             
             .countdown-item {
                 padding: 0.375rem 0.25rem;
             }
             
             .main-content {
                 padding: 0.5rem 0;
             }
             
             .voting-instructions,
             .position-section,
             .voting-actions {
                 padding: 1rem;
                 margin-bottom: 1rem;
             }
         }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="voter-header">
        <div class="container-fluid">
            <div class="row align-items-center g-3">
                <div class="col-lg-3 col-md-12 order-1 order-lg-1">
                    <div class="university-branding">
                        <div class="university-info">
                            <h1 class="university-name">Online Voting System</h1>
                            <p class="system-name">Secure Digital Elections</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 col-md-12 order-3 order-lg-2 text-center">
                    <div class="election-info">
                        <h2 class="election-name"><?php echo htmlspecialchars($election['title']); ?></h2>
                        <div class="countdown-container">
                            <div class="countdown-timer" id="countdown">
                                <div class="countdown-item">
                                    <span class="countdown-number" id="days">00</span>
                                    <span class="countdown-label">Days</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number" id="hours">00</span>
                                    <span class="countdown-label">Hours</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number" id="minutes">00</span>
                                    <span class="countdown-label">Minutes</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number" id="seconds">00</span>
                                    <span class="countdown-label">Seconds</span>
                                </div>
                            </div>
                            <p class="countdown-text countdown-label">Time remaining to vote</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-12 order-2 order-lg-3">
                    <div class="voter-profile">
                        <div class="voter-avatar">
                            <?php if (isset($voter['photo']) && $voter['photo']): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($voter['photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($voter['name']); ?>" 
                                     class="profile-photo">
                            <?php else: ?>
                                <div class="default-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="voter-info">
                            <h3 class="voter-name"><?php echo htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']); ?></h3>
                            <p class="voter-id">ID: <?php echo htmlspecialchars($voter['student_id']); ?></p>
                            <a href="?logout=1" class="btn btn-outline-light btn-sm logout-btn">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid px-3 px-md-4">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($hasVoted): ?>
                <!-- Already Voted Message -->
                <div class="voted-container">
                    <div class="voted-card">
                        <div class="voted-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="voted-title">Thank You for Voting!</h2>
                        <p class="voted-message">
                            You have successfully cast your vote in the <?php echo htmlspecialchars($election['title']); ?>.
                            Your participation in the democratic process is greatly appreciated.
                        </p>
                        <div class="voted-details">
                            <p><strong>Voter:</strong> <?php echo htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']); ?></p>
                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($voter['student_id']); ?></p>
                            <p><strong>Election:</strong> <?php echo htmlspecialchars($election['title']); ?></p>
                        </div>
                        <a href="landing.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Return to Home
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($voting_is_active): ?>
                    <!-- Voting Interface -->
                    <div class="voting-container">
                        <div class="voting-instructions text-center text-md-start">
                            <h2 class="mb-3"><i class="fas fa-vote-yea me-2"></i>Cast Your Vote</h2>
                            <p class="lead mb-4">Select one candidate for each position. Review your choices carefully before submitting.</p>
                            <div class="progress-indicator d-none d-md-block">
                                <span class="progress-text">Progress: <span id="votingProgress">0</span> of <?php echo count($positions); ?> positions selected</span>
                            </div>
                        </div>
                    
                    <form id="votingForm" method="POST" action="process_vote.php">
                        <input type="hidden" name="csrf_token" value="<?php echo VoterSecurity::generateCSRFToken(); ?>">
                        <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
                        
                        <?php foreach ($positions as $position): ?>
                            <div class="position-section" data-position-id="<?php echo $position['id']; ?>">
                                <div class="position-header">
                        <h3 class="position-title"><?php echo htmlspecialchars($position['name']); ?></h3>
                        <p class="position-description"><?php echo htmlspecialchars($position['description']); ?></p>
                        <span class="selection-indicator">Select one candidate</span>
                    </div>
                                
                                <div class="candidates-grid">
                                    <?php foreach ($position['candidates'] as $candidate): ?>
                                        <div class="candidate-wrapper">
                                            <div class="candidate-card h-100" data-candidate-id="<?php echo $candidate['id']; ?>">
                                                <div class="candidate-photo">
                                                    <?php if ($candidate['photo']): ?>
                                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($candidate['photo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($candidate['name']); ?>" 
                                                             class="candidate-image">
                                                    <?php else: ?>
                                                        <div class="default-candidate-photo">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            
                                            <div class="candidate-info">
                                <h4 class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></h4>
                                <p class="candidate-course text-primary"><?php echo htmlspecialchars($candidate['course']); ?></p>
                                <p class="candidate-department text-secondary"><?php echo htmlspecialchars($candidate['department']); ?></p>
                                <div class="candidate-motto">
                                    <p class="text-muted motto-preview">
                                        <?php 
                                        $motto = htmlspecialchars($candidate['motto']);
                                        $preview = strlen($motto) > 150 ? substr($motto, 0, 150) . '...' : $motto;
                                        echo $preview;
                                        ?>
                                        <?php if (strlen($candidate['motto']) > 150): ?>
                                            <a href="#" class="read-more" data-full-text="<?php echo htmlspecialchars($candidate['motto']); ?>">Read More</a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                                            
                                                <div class="candidate-actions">
                                                    <input type="radio" 
                                                           name="position_<?php echo $position['id']; ?>" 
                                                           value="<?php echo $candidate['id']; ?>" 
                                                           id="candidate_<?php echo $candidate['id']; ?>" 
                                                           class="candidate-radio">
                                                    <label for="candidate_<?php echo $candidate['id']; ?>" class="vote-btn w-100">
                                                        <i class="fas fa-vote-yea me-2"></i>Vote
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="voting-actions text-center mt-5">
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-6 col-lg-4">
                                    <button type="button" class="btn btn-success btn-lg w-100 py-3" id="submitVoteBtn">
                                        <i class="fas fa-paper-plane me-2"></i>Submit My Vote
                                    </button>
                                    <p class="text-muted mt-2 small">Make sure you have selected candidates for all positions</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                    <!-- Voting Not Active Message -->
                    <div class="voting-container">
                        <div class="text-center">
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h4 class="alert-heading">Voting Not Started</h4>
                                <p class="mb-0">The administrator has not yet started the voting process for this election.</p>
                                <hr>
                                <p class="mb-0">Please wait for the voting to begin. You will be able to see the candidates and cast your vote once the administrator starts the voting process.</p>
                            </div>
                            <div class="mt-4">
                                <a href="landing.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-home me-2"></i>Return to Home
                                </a>
                                <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="location.reload()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh Page
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Vote Confirmation Modal -->
    <div class="modal fade" id="confirmVoteModal" tabindex="-1" aria-labelledby="confirmVoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmVoteModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Confirm Your Vote
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="confirmation-message">
                        <p class="lead">Please review your selections before submitting your vote:</p>
                    </div>
                    <div id="voteReview" class="vote-review">
                        <!-- Vote selections will be populated here -->
                    </div>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Once you submit your vote, you cannot change your selections.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-2"></i>Review Again
                    </button>
                    <button type="button" class="btn btn-success" id="confirmSubmitBtn">
                        <i class="fas fa-check me-2"></i>Confirm & Submit Vote
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Pass data to JavaScript -->
    <script>
        window.voterPageData = {
            electionStartDate: <?php echo (int)(strtotime($election['start_date']) * 1000); ?>,
            electionEndDate: <?php echo (int)(strtotime($election['end_date']) * 1000); ?>,
            hasVoted: <?php echo $hasVoted ? 'true' : 'false'; ?>,
            positions: <?php echo json_encode($positions_for_js ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
            electionName: <?php echo json_encode($election['title'] ?? 'Election', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
            voterName: <?php echo json_encode($voter ? trim(($voter['first_name'] ?? '') . ' ' . ($voter['last_name'] ?? '')) : 'Guest', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
        };
        
        // Read More functionality
        document.addEventListener('DOMContentLoaded', function() {
            function attachReadMoreListener(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const fullText = this.getAttribute('data-full-text');
                    const preview = this.parentElement;
                    preview.innerHTML = fullText + ' <a href="#" class="read-less">Read Less</a>';
                    
                    const readLessLink = preview.querySelector('.read-less');
                    readLessLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        const shortText = fullText.length > 150 ? fullText.substring(0, 150) + '...' : fullText;
                        preview.innerHTML = shortText + ' <a href="#" class="read-more" data-full-text="' + fullText + '">Read More</a>';
                        
                        // Re-attach event listener
                        const newReadMoreLink = preview.querySelector('.read-more');
                        attachReadMoreListener(newReadMoreLink);
                    });
                });
            }
            
            const readMoreLinks = document.querySelectorAll('.read-more');
            readMoreLinks.forEach(link => {
                attachReadMoreListener(link);
            });
            
            // Let voter.js handle all candidate selection and form interactions
            // No duplicate event handlers needed here
        });
    </script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/voter.js?v=<?php echo time() . '_' . rand(1000, 9999); ?>"></script>
</body>
</html>