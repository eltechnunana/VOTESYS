<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System - Secure Digital Elections</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/landing.css" rel="stylesheet">
    
    <style>
        /* Professional Color Palette Variables */
        :root {
            --primary-blue: #4A6B8A;
            --success-green: #10b981;
            --error-red: #DC2626;
            --neutral-light: #F9FAFB;
            --neutral-medium: #D1D5DB;
            --neutral-dark: #374151;
            --base-white: #FFFFFF;
        }

        /* Override existing styles with new color palette */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--base-white);
            color: var(--neutral-dark);
        }

        /* Navigation */
        .navbar {
            background: var(--primary-blue) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            color: var(--base-white) !important;
            font-weight: 700;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .navbar-nav .nav-link:hover {
            color: var(--base-white) !important;
        }

        .btn-outline-gold {
            border: 2px solid var(--base-white);
            color: var(--base-white);
            background: transparent;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-outline-gold:hover {
            background: var(--base-white);
            color: var(--primary-blue);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a5a7a 100%);
            color: var(--base-white);
        }

        .text-gold {
            color: var(--success-green) !important;
        }

        .btn-primary {
            background: var(--success-green);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-light {
            border: 2px solid var(--base-white);
            color: var(--base-white);
            background: transparent;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-outline-light:hover {
            background: var(--base-white);
            color: var(--primary-blue);
        }

        /* Countdown Section */
        .countdown-section {
            background: var(--neutral-light);
        }

        .countdown-container {
            background: var(--base-white);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--neutral-medium);
        }

        .countdown-title {
            color: var(--neutral-dark);
        }

        .countdown-number {
            color: var(--primary-blue);
            font-weight: 700;
        }

        .countdown-label {
            color: var(--neutral-dark);
        }

        /* About Section */
        .about-section {
            background: var(--base-white);
        }

        .section-title {
            color: var(--neutral-dark);
        }

        .text-primary {
            color: var(--primary-blue) !important;
        }

        .benefit-item i {
            color: var(--primary-blue) !important;
        }

        /* How It Works Section */
        .how-it-works-section {
            background: var(--neutral-light) !important;
        }

        .step-card {
            background: var(--base-white);
            border-radius: 12px;
            padding: 30px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--neutral-medium);
            transition: all 0.2s ease;
        }

        .step-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .step-icon {
            background: var(--primary-blue);
            color: var(--base-white);
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 20px;
        }

        .step-number {
            background: var(--success-green);
            color: var(--base-white);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 15px;
        }

        .step-title {
            color: var(--neutral-dark);
        }

        .step-description {
            color: var(--neutral-dark);
        }

        /* Features Section */
        .features-section {
            background: var(--base-white);
        }

        .feature-card {
            background: var(--base-white);
            border-radius: 12px;
            padding: 30px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--neutral-medium);
            transition: all 0.2s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            background: var(--primary-blue);
            color: var(--base-white);
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 20px;
        }

        .feature-title {
            color: var(--neutral-dark);
        }

        .feature-description {
            color: var(--neutral-dark);
        }

        /* CTA Section */
        .cta-section {
            background: var(--primary-blue) !important;
        }

        .btn-light {
            background: var(--base-white);
            color: var(--primary-blue);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-light:hover {
            background: var(--neutral-light);
            color: var(--primary-blue);
            transform: translateY(-1px);
        }

        /* Footer */
        .footer {
            background: var(--neutral-dark) !important;
        }

        .footer-links a:hover {
            color: var(--primary-blue) !important;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            background: var(--base-white);
            border: 1px solid var(--neutral-medium);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dropdown-item {
            color: var(--neutral-dark);
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: var(--neutral-light);
            color: var(--primary-blue);
        }

        /* Responsive Design Enhancements */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .countdown-container {
                padding: 30px 20px;
            }

            .step-card,
            .feature-card {
                margin-bottom: 20px;
            }

            .btn-lg {
                padding: 12px 20px;
                font-size: 16px;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }

            .countdown-container {
                padding: 20px 15px;
            }

            .countdown-timer {
                flex-wrap: wrap;
                gap: 10px;
            }

            .countdown-item {
                min-width: 80px;
            }

            .step-card,
            .feature-card {
                padding: 20px 15px;
            }
        }
    </style>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Online Voting System - Secure, Transparent, and Fair Digital Elections">
    <meta name="keywords" content="online voting, student elections, secure voting, digital democracy">
    <meta name="author" content="Digital Voting Solutions">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Online Voting System - Secure Digital Elections">
    <meta property="og:description" content="Secure, Transparent, and Fair Elections for Our University">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="alternate icon" href="assets/images/favicon.svg">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <span class="fw-bold">VoteSystem</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-outline-gold ms-2 px-3" href="#" id="loginDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Login
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="loginDropdown">
                            <li><a class="dropdown-item" href="voter_login.php"><i class="fas fa-user me-2"></i>Voter Login</a></li>
                            <li><a class="dropdown-item" href="admin/login.php"><i class="fas fa-cog me-2"></i>Admin Login</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section d-flex align-items-center">
        <div class="hero-overlay"></div>
        <div class="container position-relative">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <div class="hero-content animate-fade-in">
                        <h1 class="hero-title mb-4">
                            Digital Voting Platform<br>
                            <span class="text-gold">Secure Elections</span>
                        </h1>
                        <p class="hero-subtitle mb-5">
                            Secure, Transparent, and Fair Digital Elections
                        </p>
                        <div class="hero-buttons">
                            <a href="voter_login.php" class="btn btn-primary btn-lg me-3 mb-3">
                                <i class="fas fa-vote-yea me-2"></i>Get Started
                            </a>
                            <a href="#about" class="btn btn-outline-light btn-lg mb-3">
                                <i class="fas fa-info-circle me-2"></i>Learn More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-scroll-indicator">
            <a href="#countdown" class="scroll-down">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </section>

    <!-- Election Countdown Timer -->
    <section id="countdown" class="countdown-section py-5">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <div class="countdown-container">
                        <h2 class="countdown-title mb-4 animate-slide-up">
                            <i class="fas fa-clock me-3"></i>Voting starts soon!
                        </h2>
                        <div id="countdown-timer" class="countdown-timer animate-fade-in">
                            <div class="countdown-item">
                                <span class="countdown-number" id="days">00</span>
                                <span class="countdown-label">Days</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-number" id="hours">00</span>
                                <span class="countdown-label">Hours</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-number" id="minutes">00</span>
                                <span class="countdown-label">Minutes</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-number" id="seconds">00</span>
                                <span class="countdown-label">Seconds</span>
                            </div>
                        </div>
                        <div id="countdown-message" class="countdown-message mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="about-content animate-slide-left">
                        <h2 class="section-title mb-4">
                            About Our <span class="text-primary">Voting System</span>
                        </h2>
                        <p class="lead mb-4">
                            Our Digital Voting Platform revolutionizes governance by providing a secure, transparent, and accessible platform for all elections.
                        </p>
                        <div class="about-benefits">
                            <div class="benefit-item mb-3">
                                <i class="fas fa-shield-alt text-primary me-3"></i>
                                <span><strong>Enhanced Security:</strong> Advanced encryption and authentication protocols</span>
                            </div>
                            <div class="benefit-item mb-3">
                                <i class="fas fa-eye text-primary me-3"></i>
                                <span><strong>Full Transparency:</strong> Real-time monitoring and audit trails</span>
                            </div>
                            <div class="benefit-item mb-3">
                                <i class="fas fa-mobile-alt text-primary me-3"></i>
                                <span><strong>Easy Access:</strong> Vote from anywhere, anytime during election period</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-users text-primary me-3"></i>
                                <span><strong>Student Governance:</strong> Empowering student voice in university decisions</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-image animate-slide-right">
                        <img src="assets/images/voting-illustration.svg" alt="Online Voting Illustration" class="img-fluid rounded-3 shadow">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works-section py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title animate-fade-in">
                        How It <span class="text-primary">Works</span>
                    </h2>
                    <p class="section-subtitle animate-fade-in">
                        Simple, secure, and straightforward voting process
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center animate-slide-up" data-delay="0.1s">
                        <div class="step-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div class="step-number">1</div>
                        <h4 class="step-title">Login</h4>
                        <p class="step-description">
                            Sign in securely using your student ID and password
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center animate-slide-up" data-delay="0.2s">
                        <div class="step-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="step-number">2</div>
                        <h4 class="step-title">View Candidates</h4>
                        <p class="step-description">
                            Browse through candidates and their platforms for each position
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center animate-slide-up" data-delay="0.3s">
                        <div class="step-icon">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                        <div class="step-number">3</div>
                        <h4 class="step-title">Cast Your Vote</h4>
                        <p class="step-description">
                            Make your selections and submit your secure ballot
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center animate-slide-up" data-delay="0.4s">
                        <div class="step-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="step-number">4</div>
                        <h4 class="step-title">View Results</h4>
                        <p class="step-description">
                            Access real-time results when voting period ends
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Features Section -->
    <section id="features" class="features-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="section-title animate-fade-in">
                        Key <span class="text-primary">Features</span>
                    </h2>
                    <p class="section-subtitle animate-fade-in">
                        Built with cutting-edge technology for the best voting experience
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card animate-slide-up" data-delay="0.1s">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h4 class="feature-title">Advanced Security</h4>
                        <p class="feature-description">
                            End-to-end encryption, secure authentication, and tamper-proof ballot storage ensure your vote is protected.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card animate-slide-up" data-delay="0.2s">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="feature-title">Live Results</h4>
                        <p class="feature-description">
                            Real-time vote counting and instant result updates provide transparency throughout the election process.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card animate-slide-up" data-delay="0.3s">
                        <div class="feature-icon">
                            <i class="fas fa-universal-access"></i>
                        </div>
                        <h4 class="feature-title">Easy Access</h4>
                        <p class="feature-description">
                            Intuitive interface designed for all users, with full accessibility compliance and mobile optimization.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card animate-slide-up" data-delay="0.4s">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 class="feature-title">Mobile Friendly</h4>
                        <p class="feature-description">
                            Fully responsive design works seamlessly on desktop, tablet, and mobile devices.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card animate-slide-up" data-delay="0.5s">
                        <div class="feature-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h4 class="feature-title">Audit Trail</h4>
                        <p class="feature-description">
                            Complete audit logs and verification systems ensure election integrity and accountability.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card animate-slide-up" data-delay="0.6s">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="feature-title">24/7 Availability</h4>
                        <p class="feature-description">
                            Vote anytime during the election period with reliable system uptime and technical support.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <div class="cta-content animate-fade-in">
                        <h2 class="cta-title mb-4">
                            Ready to Participate in Democracy?
                        </h2>
                        <p class="cta-subtitle mb-5">
                            Join thousands of users in shaping the future of digital democracy
                        </p>
                        <div class="cta-buttons">
                            <a href="voter_login.php" class="btn btn-light btn-lg me-3 mb-3">
                                <i class="fas fa-user me-2"></i>Login as Voter
                            </a>
                            <a href="admin/login.php" class="btn btn-outline-light btn-lg mb-3">
                                <i class="fas fa-cog me-2"></i>Login as Admin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="footer-brand">
                        <h5>Digital Voting Platform</h5>
                        <p class="text-muted">
                            Empowering students through democratic participation and transparent governance.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links list-unstyled">
                        <li><a href="#home" class="text-muted">Home</a></li>
                        <li><a href="#about" class="text-muted">About</a></li>
                        <li><a href="#how-it-works" class="text-muted">How It Works</a></li>
                        <li><a href="#features" class="text-muted">Features</a></li>
                        <li><a href="voter_login.php" class="text-muted">Login</a></li>
                        <!-- <li><a href="register.php" class="text-muted">Register</a></li> -->
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="footer-title">Contact Information</h5>
                    <div class="footer-contact">
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Olympic, Stadium Street, Amasaman
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-phone me-2"></i>
                            +233202298399
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            voting@hcu.edu
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-globe me-2"></i>
                            <a href="https://hcu.edu.gh/" class="text-muted">www.hcu.edu.gh</a>
                        </p>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright mb-0 text-muted">
                        &copy; ELTECH@2025. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/hccghana/posts/welcome-to-heritage-christian-university-join-us-in-january-2025-apply-at-wwwhcu/1037437485065412/" class="text-muted me-3" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-muted me-3" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-muted me-3" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-muted" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/landing.js"></script>
</body>
</html>