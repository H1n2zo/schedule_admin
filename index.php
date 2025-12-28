<?php
/**
 * EVSU Campus Event Request Portal
 * Landing Page
 */

session_start();

// If user is logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'CRCY Dispatch - Event Support Request System';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-background"></div>
    <div class="hero-animation-1"></div>
    <div class="hero-animation-2"></div>
    <div class="hero-particles"></div>
    
    <div class="container hero-content">
        <div class="row justify-content-center">
            <div class="col-8 text-center">
                <div class="hero-icon-wrapper mb-4">
                    <div class="icon-glow"></div>
                    <i class="fas fa-hands-helping hero-icon-main"></i>
                </div>
                
                <div class="university-badge">
                    <i class="fas fa-plus-circle"></i>
                    College Red Cross Youth - EVSU
                </div>
                
                <h1 class="hero-title">
                    <span class="title-word" data-delay="0">CRCY</span>
                    <span class="title-word" data-delay="200">DISPATCH</span>
                </h1>
                <p class="hero-subtitle">
                    <span class="typewriter"><em>Event Support Request with Scheduling Management System</em></span>
                </p>
                
                <div class="hero-divider"></div>
                
                <p class="hero-description">
                    Join our community of event organizers and request professional CRCY volunteer support for your campus events. 
                    <strong>Together, we make every event safer and more successful.</strong>
                </p>
                
                <div class="hero-cta-wrapper">
                    <a href="submit_request.php" class="btn btn-warning btn-xl px-5 hero-btn-primary">
                        <span class="btn-text">REQUEST SUPPORT NOW</span>
                        <div class="btn-ripple"></div>
                    </a>

                    
                </div>
                
                <div class="hero-features mt-4">
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="feature-icon-bg">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span>7-Day Advance Notice</span>
                    </div>
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="feature-icon-bg">
                            <i class="fas fa-bell"></i>
                        </div>
                        <span>Status Notifications</span>
                    </div>
                    <div class="feature-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="feature-icon-bg">
                            <i class="fas fa-users"></i>
                        </div>
                        <span>Volunteer Coordination</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="process-section">
    <div class="container">
        <div class="section-header">
            <h2>HOW IT WORKS</h2>
            <p class="section-subtitle">Three simple steps to get CRCY student volunteers for campus event</p>
        </div>
        
        <div class="process-grid">
            <div class="process-step">
                <div class="step-icon">
                    <span class="step-number">1</span>
                </div>
                <h3>Submit Request</h3>
                <p>Tell us about your event, volunteer needs, and timeline. Must be submitted 7 days in advance.</p>
                <div class="process-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>
            
            <div class="process-step">
                <div class="step-icon">
                    <span class="step-number">2</span>
                </div>
                <h3>Team Review</h3>
                <p>Our student officers review availability and confirm we can support your event.</p>
                <div class="process-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>
            
            <div class="process-step">
                <div class="step-icon">
                    <span class="step-number">3</span>
                </div>
                <h3>Volunteers Deployed</h3>
                <p>Trained CRCY students arrive ready to help make your event successful.</p>
            </div>
        </div>
    </div>
</section>

<!-- What We Bring Section -->
<section class="capabilities-section">
    <div class="container">
        <div class="section-header">
            <h2>WHAT CRCY STUDENTS BRING</h2>
            <p class="section-subtitle">Trained, enthusiastic EVSU students ready to support campus events</p>
        </div>
        <div class="capabilities-grid">
            <div class="capability-card">
                <div class="capability-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h4>Red Cross Training</h4>
                <p>Students certified in first aid, CPR, and emergency response protocols</p>
            </div>
            
            <div class="capability-card">
                <div class="capability-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h4>Campus Knowledge</h4>
                <p>EVSU students who understand campus culture and community needs</p>
            </div>
            
            <div class="capability-card">
                <div class="capability-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h4>Reliable Service</h4>
                <p>Committed volunteers who arrive on time and stay until the job is done</p>
            </div>
            
            <div class="capability-card">
                <div class="capability-icon">
                    <i class="fas fa-smile"></i>
                </div>
                <h4>Positive Energy</h4>
                <p>Enthusiastic students who bring great attitudes to every event</p>
            </div>
        </div>
    </div>
</section>


<!-- FAQ + CTA Combined Section -->
<div style="background: linear-gradient(180deg, var(--evsu-maroon) 0%, #6B0000 100%);">
    <!-- FAQ Section -->
    <section class="faq-section py-5 bg-white">
        <div class="container">
            <div class="section-header">
                <h2>FREQUENTLY ASKED QUESTIONS</h2>
                <p class="section-subtitle">Everything you need to know in requesting</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>How far in advance should I submit my request?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    All requests must be submitted at least 7 days before your event date. This allows our team sufficient time to validate your request, check volunteer availability, and coordinate assignments. Earlier submissions are encouraged for better planning.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>What information do I need to provide?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You'll need to provide event details (date, time, location), expected number of volunteers needed, event description, and any relevant documents. Make sure to include your institutional credentials and contact information for verification.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-question-circle me-2 text-primary"></i>How will I know if my request is approved?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You'll receive email notifications at each stage of the process. Once logged in, you can also track your request status in real-time through the dashboard. Approved requests will include volunteer contact details and coordination information.
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced CTA Section -->
    <section class="cta-enhanced">
        <div class="container">
            <h2>Ready to Get CRCY Student Support?</h2>
            <p>Join fellow EVSU organizations who trust our student volunteers to help make their events successful. Our trained Red Cross Youth members are ready to support your next campus event!</p>
            <a href="submit_request.php" class="cta-button">
                <i class="fas fa-hands-helping me-2"></i>Submit Your Request
            </a>
            <div style="margin-top: 2rem; opacity: 0.8;">
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Remember to submit at least 7 days in advance • Free for EVSU organizations
                </small>
            </div>
        </div>
    </section>
</div>

<style>
/* Modern Animations */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--evsu-maroon) 0%, #6B0000 50%, var(--maroon-dark) 100%);
    padding: 6rem 1rem;
    position: relative;
    overflow: hidden;
    min-height: 600px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(255, 215, 0, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(160, 0, 0, 0.3) 0%, transparent 50%);
    z-index: 0;
}

.hero-animation-1 {
    position: absolute;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
    top: -100px;
    right: -50px;
    animation: float 8s ease-in-out infinite;
    z-index: 1;
}

.hero-animation-2 {
    position: absolute;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
    border-radius: 50%;
    bottom: -50px;
    left: 5%;
    animation: float 10s ease-in-out infinite reverse;
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    animation: slideUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.hero-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 2rem;
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.hero-icon-main {
    font-size: 7rem;
    color: white;
    display: inline-block;
    animation: bounce 6s ease-in-out infinite;
    text-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.university-badge {
    background: rgba(255, 215, 0, 0.2);
    border: 2px solid var(--evsu-gold);
    color: var(--evsu-gold);
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 20px;
    animation: slideUp 0.8s ease-out 0.1s both;
}

.hero-title {
    font-size: 4rem;
    font-weight: 900;
    letter-spacing: 2px;
    color: white;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    margin-bottom: 0.5rem;
    animation: slideUp 0.8s ease-out 0.2s both;
}

.hero-subtitle {
    font-size: 1.4rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.95);
    letter-spacing: 1px;
    margin-bottom: 1.5rem;
    animation: slideUp 0.8s ease-out 0.3s both;
}

.hero-divider {
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, transparent, white, transparent);
    margin: 1.5rem auto;
    animation: slideUp 0.8s ease-out 0.4s both;
}

.hero-description {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 2.5rem;
    line-height: 1.6;
    animation: slideUp 0.8s ease-out 0.5s both;
}

.hero-description strong {
    color: var(--evsu-gold);
    font-weight: 700;
}

.hero-cta-wrapper {
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    flex-wrap: wrap;
    animation: slideUp 0.8s ease-out 0.6s both;
}

.btn-xl {
    font-size: 1.6rem;
    padding: 1.4rem 4rem !important;
}

.hero-btn-primary {
    background: #FFFFFF !important;
    color: #8B0000 !important;
    border: none !important;
    box-shadow: inset 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    position: relative;
    overflow: hidden;
}

.hero-btn-primary:hover,
.hero-btn-primary:active {
    background: #8B0000 !important;
    color: #FFFFFF !important;
    box-shadow: inset 0 4px 12px rgba(255, 255, 255, 0.15) !important;
    transform: translateY(-3px);
}

.hero-features {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    justify-content: center;
    animation: slideUp 0.8s ease-out 0.7s both;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    opacity: 0.9;
}

.feature-item i {
    color: var(--evsu-gold);
    font-size: 1.1rem;
}

/* Section Dividers */
.section-divider {
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--maroon-dark) 0%, var(--evsu-maroon) 50%, var(--evsu-gold) 100%);
    border: none;
    margin: 0;
}

/* Modern Card Styles */
.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(128, 0, 0, 0.15);
}

/* Feature Icons */
.feature-icon {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 2rem;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    margin: 0 auto 1rem;
}

.card:hover .feature-icon {
    transform: scale(1.2) rotate(10deg);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
}



/* Section Styles */
.features-section {
    background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
    padding: 4rem 2rem;
    border-radius: 0;
    margin: 0;
}

.response-stats-section {
    background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
    padding: 4rem 2rem;
    border-radius: 0;
    margin: 0;
}

.categories-section {
    background: linear-gradient(135deg, white 0%, #f8f9fa 100%);
    padding: 4rem 2rem;
    border-radius: 0;
    margin: 0;
}

/* Category Cards */
.category-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(239, 243, 246, 0.9) 100%);
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    cursor: pointer;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.8);
    padding: 2rem 1rem;
    font-weight: 600;
}

.category-card:hover {
    background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
    color: white;
    transform: translateY(-8px) scale(1.05);
    box-shadow: 0 15px 40px rgba(128, 0, 0, 0.3);
}

.category-card i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

/* Stat Cards */
.stat-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(239, 243, 246, 0.9) 100%);
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.15);
}

/* Accordion Styles */
.accordion-button {
    background-color: transparent;
    border: 1px solid rgba(128, 0, 0, 0.1);
    border-radius: 10px;
    font-weight: 700;
    color: var(--gray-700);
    transition: all 0.3s ease;
}

.accordion-button:hover {
    background-color: rgba(128, 0, 0, 0.05);
    color: var(--evsu-maroon);
}

.accordion-button:not(.collapsed) {
    background-color: rgba(128, 0, 0, 0.1);
    color: var(--evsu-maroon);
    box-shadow: none;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
    color: white;
}

.cta-visual i {
    animation: pulse 3s ease-in-out infinite;
}

/* Text Colors */
.text-primary {
    color: var(--evsu-maroon) !important;
}

/* ===== IMPROVED DESIGN SYSTEM ===== */

/* Typography System */
.section-header {
    text-align: center;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--evsu-maroon);
    margin-bottom: 1rem;
    line-height: 1.2;
}

.section-header .section-subtitle {
    font-size: 1.2rem;
    color: #6c757d;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* Process Section - Clean & Modern */
.process-section {
    padding: 5rem 0;
    background: #ffffff;
}

.process-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 3rem;
    margin-top: 3rem;
}

.process-step {
    text-align: center;
    padding: 2rem;
    position: relative;
}

.step-icon {
    width: 80px;
    height: 80px;
    background: var(--evsu-maroon);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    box-shadow: 0 4px 20px rgba(139, 0, 0, 0.15);
}

.step-number {
    color: white;
    font-size: 2rem;
    font-weight: 700;
}

.process-step h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--evsu-maroon);
    margin-bottom: 1rem;
}

.process-step p {
    font-size: 1.1rem;
    color: #6c757d;
    line-height: 1.6;
    max-width: 280px;
    margin: 0 auto;
}

/* Process Arrows */
.process-arrow {
    position: absolute;
    right: -1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--evsu-maroon);
    font-size: 1.5rem;
    opacity: 0.7;
    z-index: 5;
}

.process-step:last-child .process-arrow {
    display: none;
}

/* Responsive arrows */
@media (max-width: 768px) {
    .process-arrow {
        position: static;
        transform: none;
        margin: 1rem 0;
        font-size: 1.2rem;
    }
    
    .process-arrow i {
        transform: rotate(90deg);
    }
}

/* Capabilities Section - Simplified */
.capabilities-section {
    padding: 5rem 0;
    background: #f8f9fa;
}

.capabilities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.capability-card {
    background: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.capability-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.capability-icon {
    width: 60px;
    height: 60px;
    background: var(--evsu-maroon);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.capability-icon i {
    color: white;
    font-size: 1.5rem;
}

.capability-card h4 {
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--evsu-maroon);
    margin-bottom: 1rem;
}

.capability-card p {
    color: #6c757d;
    line-height: 1.6;
    margin: 0;
}

/* Event Types - Cleaner Grid */
.event-types-section {
    padding: 5rem 0;
    background: white;
}

.event-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 3rem;
}

.event-type-card {
    background: #f8f9fa;
    padding: 2rem 1.5rem;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.event-type-card:hover {
    background: var(--evsu-maroon);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(139, 0, 0, 0.2);
}

.event-type-card i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    transition: color 0.3s ease;
}

.event-type-card p {
    font-weight: 600;
    margin: 0;
    font-size: 0.95rem;
}

.event-type-card:hover i,
.event-type-card:hover p {
    color: white;
}

/* CTA Section Enhancement */
.cta-enhanced {
    background: linear-gradient(135deg, var(--evsu-maroon) 0%, #6B0000 100%);
    padding: 4rem 0;
    text-align: center;
    color: white;
}

.cta-enhanced h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-enhanced p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-button {
    background: white;
    color: var(--evsu-maroon);
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    color: var(--evsu-maroon);
}


</style>

<script>
// Hidden admin shortcut: Ctrl+Shift+A
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && (e.key === 'A' || e.key === 'a')) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Go to login page
            window.location.href = 'login.php';
            
            return false;
        }
    }, true); // Use capture phase to intercept before browser
});
</script>

<?php include 'includes/footer.php'; ?>