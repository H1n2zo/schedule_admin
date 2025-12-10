<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVSU Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --evsu-maroon: #800000;
            --evsu-gold: #FFD700;
            --maroon-dark: #5c0000;
            --gold-dark: #d4af37;
            --maroon-light: #fff5f5;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,215,0,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease;
        }
        
        .hero-subtitle {
            font-size: 1.4rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease 0.2s backwards;
        }
        
        .hero-buttons {
            animation: fadeInUp 1s ease 0.4s backwards;
        }
        
        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            margin: 10px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-hero-primary {
            background: var(--evsu-gold);
            color: var(--maroon-dark);
            border: 3px solid var(--evsu-gold);
        }
        
        .btn-hero-primary:hover {
            background: white;
            color: var(--maroon-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,215,0,0.4);
        }
        
        .btn-hero-secondary {
            background: transparent;
            color: white;
            border: 3px solid white;
        }
        
        .btn-hero-secondary:hover {
            background: white;
            color: var(--maroon-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,255,255,0.3);
        }
        
        .hero-image {
            animation: float 3s ease-in-out infinite;
        }
        
        /* Features Section */
        .features-section {
            padding: 100px 0;
            background: linear-gradient(to bottom, #f8f9fa 0%, white 100%);
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--evsu-maroon);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .section-subtitle {
            font-size: 1.2rem;
            color: #6c757d;
            text-align: center;
            margin-bottom: 60px;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .feature-card:hover {
            border-color: var(--evsu-gold);
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(128,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2rem;
            color: var(--evsu-gold);
            transform: rotate(-5deg);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: rotate(0deg) scale(1.1);
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--evsu-maroon);
            margin-bottom: 15px;
        }
        
        .feature-text {
            color: #6c757d;
            line-height: 1.8;
        }
        
        /* How It Works Section */
        .how-it-works {
            padding: 100px 0;
            background: var(--maroon-light);
        }
        
        .step-container {
            display: flex;
            align-items: center;
            margin-bottom: 60px;
            position: relative;
        }
        
        .step-number {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--evsu-gold);
            margin-right: 30px;
            flex-shrink: 0;
            box-shadow: 0 10px 30px rgba(128,0,0,0.3);
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--evsu-maroon);
            margin-bottom: 10px;
        }
        
        .step-description {
            font-size: 1.1rem;
            color: #6c757d;
            line-height: 1.8;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            color: white;
            text-align: center;
        }
        
        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        .cta-text {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }
        
        /* Footer */
        .footer {
            background: var(--maroon-dark);
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        
        .footer a {
            color: var(--evsu-gold);
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .btn-hero {
                padding: 12px 30px;
                font-size: 1rem;
            }
            
            .step-container {
                flex-direction: column;
                text-align: center;
            }
            
            .step-number {
                margin-right: 0;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">
                        🎓 EVSU Event<br>Management System
                    </h1>
                    <p class="hero-subtitle">
                        Streamline your event requests, manage approvals, and coordinate volunteers all in one powerful platform.
                    </p>
                    <div class="hero-buttons">
                        <a href="submit_request.php" class="btn btn-hero btn-hero-primary">
                            <i class="fas fa-calendar-plus"></i> Submit Event Request
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center hero-image">
                    <i class="fas fa-calendar-check" style="font-size: 20rem; color: rgba(255,215,0,0.2);"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Why Choose EVSU EMS?</h2>
            <p class="section-subtitle">Everything you need to manage campus events efficiently</p>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="feature-title">Easy Scheduling</h3>
                        <p class="feature-text">
                            Visual calendar interface makes it simple to view, schedule, and manage all campus events in one place.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="feature-title">Quick Approvals</h3>
                        <p class="feature-text">
                            Streamlined approval workflow allows administrators to review and approve requests with just a few clicks.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="feature-title">Instant Notifications</h3>
                        <p class="feature-text">
                            Automated email notifications keep everyone informed about request status and upcoming events.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Volunteer Management</h3>
                        <p class="feature-text">
                            Track volunteer requirements and assignments to ensure every event is properly staffed.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="feature-title">Document Attachments</h3>
                        <p class="feature-text">
                            Upload and manage event-related documents, proposals, and materials securely.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Activity Reports</h3>
                        <p class="feature-text">
                            Comprehensive audit trails and reporting to track all event activities and decisions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Submit your event request in three simple steps</p>
            
            <div class="step-container">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3 class="step-title">Submit Your Request</h3>
                    <p class="step-description">
                        Fill out the event request form with details about your event including date, time, venue, and volunteer requirements. Attach any supporting documents.
                    </p>
                </div>
            </div>
            
            <div class="step-container">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3 class="step-title">Admin Review & Approval</h3>
                    <p class="step-description">
                        Council administrators review your request, checking for conflicts and resource availability. You'll receive email updates on the status.
                    </p>
                </div>
            </div>
            
            <div class="step-container">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3 class="step-title">Get Confirmed & Organized</h3>
                    <p class="step-description">
                        Once approved, your event appears on the official calendar. The system helps coordinate volunteers and ensures everything runs smoothly.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="cta-text">
                Submit your event request today and let us help make your event a success!
            </p>
            <a href="submit_request.php" class="btn btn-hero btn-hero-primary" style="border-color: white;">
                <i class="fas fa-rocket"></i> Submit Event Request Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Eastern Visayas State University. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>