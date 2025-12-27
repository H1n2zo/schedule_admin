<?php
// Database Configuration
define('DB_HOST', 'localhost');        // Your MySQL host (usually localhost)
define('DB_USER', 'root');             // Your MySQL username
define('DB_PASS', '');                 // Your MySQL password (empty for XAMPP default)
define('DB_NAME', 'crcy_dispatch');   // CRCY Dispatch database name

// Site Configuration
define('SITE_URL', 'http://localhost/crcy_dispatch'); // UPDATE THIS FOR PRODUCTION
define('UPLOAD_DIR', __DIR__ . '/uploads/');


// CRCY Dispatch Configuration
define('MIN_ADVANCE_DAYS', 7); // Minimum days before event to submit request
define('MAX_ATTACHMENT_SIZE', 10485760); // 10MB for attachments

// Email Configuration (Gmail SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'crcy.evsu.oc@gmail.com');     // CRCY Gmail address
define('SMTP_PASS', 'pwai kmla xfks jsrq');        // Gmail App Password
define('FROM_EMAIL', 'noreply@evsu.edu.ph');       // Email address shown as sender
define('FROM_NAME', 'CRCY Dispatch System');       // Name shown as sender

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
session_start();

// Security Headers
function addSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com; " .
               "img-src 'self' data:; " .
               "font-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.gstatic.com; " .
               "connect-src 'self'";
        header("Content-Security-Policy: $csp");
    }
}

// Apply security headers
addSecurityHeaders();

// Maintenance mode check
function checkMaintenanceMode() {
    $maintenanceFile = __DIR__ . '/.maintenance';
    if (file_exists($maintenanceFile) && !isAdmin()) {
        $message = file_get_contents($maintenanceFile) ?: 'System is under maintenance. Please try again later.';
        
        http_response_code(503);
        header('Retry-After: 3600'); // Retry after 1 hour
        
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance - CRCY Dispatch</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                :root {
                    --evsu-maroon: #8B0000;
                    --evsu-gold: #FFD700;
                }
                
                body {
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    min-height: 100vh;
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                }
                
                .maintenance-container {
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .maintenance-card {
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(139, 0, 0, 0.15);
                    padding: 3rem;
                    max-width: 600px;
                    text-align: center;
                    border: 1px solid rgba(139, 0, 0, 0.1);
                }
                
                .maintenance-icon {
                    width: 120px;
                    height: 120px;
                    background: linear-gradient(135deg, var(--evsu-maroon) 0%, #6B0000 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 2rem;
                    animation: pulse 2s infinite;
                }
                
                .maintenance-icon i {
                    font-size: 3rem;
                    color: white;
                }
                
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }
                
                .maintenance-title {
                    color: var(--evsu-maroon);
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin-bottom: 1rem;
                }
                
                .maintenance-message {
                    font-size: 1.2rem;
                    color: #6c757d;
                    margin-bottom: 2rem;
                    line-height: 1.6;
                }
                
                .maintenance-footer {
                    color: #adb5bd;
                    font-size: 0.95rem;
                }
                
                .brand-section {
                    margin-top: 2rem;
                    padding-top: 2rem;
                    border-top: 1px solid #e9ecef;
                }
                
                .brand-logo {
                    color: var(--evsu-maroon);
                    font-size: 1.5rem;
                    font-weight: 700;
                    margin-bottom: 0.5rem;
                }
                
                .brand-subtitle {
                    color: #6c757d;
                    font-size: 0.9rem;
                }
                
                @media (max-width: 768px) {
                    .maintenance-card {
                        margin: 1rem;
                        padding: 2rem;
                    }
                    
                    .maintenance-title {
                        font-size: 2rem;
                    }
                    
                    .maintenance-icon {
                        width: 100px;
                        height: 100px;
                    }
                    
                    .maintenance-icon i {
                        font-size: 2.5rem;
                    }
                }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <div class="maintenance-card">
                    <div class="maintenance-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    
                    <h1 class="maintenance-title">System Maintenance</h1>
                    
                    <p class="maintenance-message">' . htmlspecialchars($message) . '</p>
                    
                    <div class="maintenance-footer">
                        <p class="mb-2">
                            <i class="fas fa-clock me-2"></i>
                            We apologize for any inconvenience. Please check back later.
                        </p>
                        <p class="mb-0">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                If you need immediate assistance, please contact the CRCY office.
                            </small>
                        </p>
                    </div>
                    
                    <div class="brand-section">
                        <div class="brand-logo">
                            <i class="fas fa-hands-helping me-2"></i>
                            CRCY Dispatch
                        </div>
                        <div class="brand-subtitle">
                            College Red Cross Youth - EVSU
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
}

// Check maintenance mode (skip for admin login page)
if (!str_contains($_SERVER['REQUEST_URI'] ?? '', 'login.php')) {
    checkMaintenanceMode();
}

// Database Connection
function getDB() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Utility Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && 
           ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireValidAdmin();
    if (!isAdmin()) {
        logSecurityEvent('unauthorized_admin_access', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'requested_page' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        header('Location: dashboard.php');
        exit;
    }
}

function sanitizeInput($data) {
    if (!$data) return $data;
    
    // Remove null bytes and trim
    $data = str_replace("\x00", '', trim($data));
    
    // Strip tags and encode HTML entities
    $data = htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    
    return $data;
}

function sanitizeFilename($filename) {
    // Remove path traversal attempts
    $filename = basename($filename);
    
    // Remove dangerous characters
    $filename = preg_replace('/[^\w\s.-]/', '', $filename);
    
    // Limit length
    return substr($filename, 0, 255);
}

function validateEmail($email) {
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if (!$email) return false;
    
    // Additional validation for institutional emails if needed
    return $email;
}

function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
    // Simple file-based rate limiting (in production, use Redis or database)
    $rateLimitFile = sys_get_temp_dir() . '/crcy_rate_limit_' . md5($identifier);
    
    if (!file_exists($rateLimitFile)) {
        file_put_contents($rateLimitFile, json_encode(['count' => 0, 'first_attempt' => time()]));
        return true;
    }
    
    $data = json_decode(file_get_contents($rateLimitFile), true);
    $now = time();
    
    // Reset if time window has passed
    if (($now - $data['first_attempt']) > $timeWindow) {
        file_put_contents($rateLimitFile, json_encode(['count' => 0, 'first_attempt' => $now]));
        return true;
    }
    
    return $data['count'] < $maxAttempts;
}

function recordRateLimitAttempt($identifier) {
    $rateLimitFile = sys_get_temp_dir() . '/crcy_rate_limit_' . md5($identifier);
    
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        $data['count']++;
        file_put_contents($rateLimitFile, json_encode($data));
    }
}

function checkDuplicateRequest($requesterEmail, $eventName, $eventDate, $hoursWindow = 24) {
    try {
        $db = getDB();
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hoursWindow} hours"));
        
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM support_requests 
            WHERE requester_email = ? 
            AND event_name = ? 
            AND event_date = ? 
            AND submitted_at >= ?
        ");
        $stmt->execute([$requesterEmail, $eventName, $eventDate, $cutoffTime]);
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false; // Allow submission if check fails
    }
}

function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'details' => $details
    ];
    
    $logFile = __DIR__ . '/logs/security.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

function validateAdminSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check session timeout (4 hours)
    $sessionTimeout = 4 * 60 * 60; // 4 hours
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $sessionTimeout) {
        logSecurityEvent('session_timeout', [
            'user_id' => $_SESSION['user_id'],
            'login_time' => $_SESSION['login_time']
        ]);
        session_destroy();
        return false;
    }
    
    // Validate admin user still exists
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, locked_until 
            FROM admin_users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            logSecurityEvent('invalid_session_user', [
                'user_id' => $_SESSION['user_id']
            ]);
            session_destroy();
            return false;
        }
        
        // Check if account is locked
        if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            logSecurityEvent('session_account_locked', [
                'user_id' => $_SESSION['user_id'],
                'locked_until' => $admin['locked_until']
            ]);
            session_destroy();
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        logSecurityEvent('session_validation_error', [
            'user_id' => $_SESSION['user_id'],
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

function requireValidAdmin() {
    if (!validateAdminSession()) {
        header('Location: login.php');
        exit;
    }
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'declined' => '<span class="badge bg-danger">Declined</span>'
    ];
    return $badges[$status] ?? $status;
}

// CRCY Dispatch Validation Functions
function getNextAvailableDate() {
    try {
        $db = getDB();
        
        // Start checking from minimum advance days
        $checkDate = strtotime('+' . MIN_ADVANCE_DAYS . ' days');
        
        // Check up to 30 days ahead to find a date with availability
        for ($i = 0; $i < 30; $i++) {
            $dateToCheck = date('Y-m-d', $checkDate + ($i * 24 * 60 * 60));
            
            // Get all events on this date to check time availability
            $stmt = $db->prepare("
                SELECT event_time, event_end_time FROM support_requests 
                WHERE event_date = ? AND status = 'approved'
                ORDER BY event_time ASC
            ");
            $stmt->execute([$dateToCheck]);
            $events = $stmt->fetchAll();
            
            // If no events, this date is completely available
            if (empty($events)) {
                return $dateToCheck;
            }
            
            // If less than 4 events, there might be time slots available
            // (allowing up to 4 events per day with proper spacing)
            if (count($events) < 4) {
                return $dateToCheck;
            }
        }
        
        // If no available date found in 30 days, return the minimum date
        return date('Y-m-d', strtotime('+' . MIN_ADVANCE_DAYS . ' days'));
        
    } catch (PDOException $e) {
        // If error, return minimum date
        return date('Y-m-d', strtotime('+' . MIN_ADVANCE_DAYS . ' days'));
    }
}

function validateEventDate($eventDate, $eventTime) {
    $errors = [];
    
    // Check if date is in the past
    $eventDateTime = strtotime($eventDate . ' ' . $eventTime);
    $now = time();
    
    if ($eventDateTime <= $now) {
        $errors[] = 'Event date and time cannot be in the past';
    }
    
    // Check 7-day advance requirement (compare dates only, not time)
    $eventDateOnly = strtotime($eventDate);
    $minDateOnly = strtotime('+' . MIN_ADVANCE_DAYS . ' days', strtotime('today'));
    
    if ($eventDateOnly < $minDateOnly) {
        $nextAvailable = getNextAvailableDate();
        $nextAvailableFormatted = date('F j, Y', strtotime($nextAvailable));
        $errors[] = 'Please submit at least ' . MIN_ADVANCE_DAYS . ' days in advance. Next available date: ' . $nextAvailableFormatted;
    }
    
    return $errors;
}

function checkDateConflict($eventDate, $eventTime, $eventEndTime = null, $excludeId = null) {
    try {
        $db = getDB();
        
        // Get all approved events on the same date
        $sql = "SELECT id, event_name, event_time, event_end_time, venue, volunteers_needed
                FROM support_requests 
                WHERE event_date = ? 
                AND status = 'approved'";
        
        $params = [$eventDate];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $existingEvents = $stmt->fetchAll();
        
        if (empty($existingEvents)) {
            return []; // No conflicts
        }
        
        // Check for time overlaps
        $conflicts = [];
        $newEventStart = strtotime($eventTime);
        $newEventEnd = $eventEndTime ? strtotime($eventEndTime) : $newEventStart + (2 * 3600); // Default 2 hours if no end time
        
        foreach ($existingEvents as $event) {
            $existingStart = strtotime($event['event_time']);
            $existingEnd = $event['event_end_time'] ? strtotime($event['event_end_time']) : $existingStart + (2 * 3600);
            
            // Check if times overlap (with 1-hour buffer for volunteer travel/setup)
            $buffer = 1 * 3600; // 1 hour buffer
            $adjustedNewStart = $newEventStart - $buffer;
            $adjustedNewEnd = $newEventEnd + $buffer;
            
            if (($adjustedNewStart < $existingEnd) && ($adjustedNewEnd > $existingStart)) {
                $conflicts[] = $event;
            }
        }
        
        return $conflicts;
        
    } catch (PDOException $e) {
        return [];
    }
}



// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
?>