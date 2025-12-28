<?php
/**
 * CRCY Dispatch System
 * CRCY Admin Dashboard - Support Request Management with Statistics
 * File: dashboard.php
 */

require_once 'config.php';
requireAdmin();

// Get database connection
$db = getDB();

// Handle password change from modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (strlen($newPassword) < 8) {
            throw new Exception('New password must be at least 8 characters long');
        }
        
        if ($newPassword !== $confirmPassword) {
            throw new Exception('New passwords do not match');
        }
        
        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = 1");
        $stmt->execute();
        $currentHash = $stmt->fetchColumn();
        
        if (!password_verify($currentPassword, $currentHash)) {
            throw new Exception('Current password is incorrect');
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE id = 1");
        $stmt->execute([$newHash]);
        
        // Log password change
        logSecurityEvent('password_changed', ['admin_id' => 1]);
        
        $response['success'] = true;
        $response['message'] = 'Password changed successfully!';
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        logSecurityEvent('password_change_failed', [
            'admin_id' => 1,
            'error' => $e->getMessage()
        ]);
    }
    
    // Return JSON response for AJAX or redirect with message
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        // Redirect with message
        $_SESSION['password_message'] = $response['message'];
        $_SESSION['password_success'] = $response['success'];
        header('Location: dashboard.php');
        exit;
    }
}

// Handle maintenance mode toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_maintenance') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $maintenanceFile = __DIR__ . '/.maintenance';
        $message = trim($_POST['message'] ?? 'System is under maintenance. Please try again later.');
        
        if (file_exists($maintenanceFile)) {
            // Disable maintenance mode
            unlink($maintenanceFile);
            logSecurityEvent('maintenance_disabled', ['admin_id' => 1]);
            $response['success'] = true;
            $response['message'] = 'Maintenance mode disabled. System is now online.';
        } else {
            // Enable maintenance mode
            file_put_contents($maintenanceFile, $message);
            logSecurityEvent('maintenance_enabled', [
                'admin_id' => 1,
                'message' => $message
            ]);
            $response['success'] = true;
            $response['message'] = 'Maintenance mode enabled. Users will see the maintenance page.';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating maintenance mode: ' . $e->getMessage();
        logSecurityEvent('maintenance_error', [
            'admin_id' => 1,
            'error' => $e->getMessage()
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle maintenance status check
if (isset($_GET['check_maintenance'])) {
    $maintenanceFile = __DIR__ . '/.maintenance';
    $response = [
        'enabled' => file_exists($maintenanceFile),
        'message' => file_exists($maintenanceFile) ? file_get_contents($maintenanceFile) : ''
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Set page configuration
$pageTitle = 'CRCY Admin Dashboard - CRCY Dispatch';
$customCSS = ['dashboard'];
$customJS = ['dashboard'];
$bodyClass = 'dashboard-page';

// Helper function for pagination URLs
function buildPaginationUrl($pageNum, $params) {
    $params['page'] = $pageNum;
    return 'dashboard.php?' . http_build_query($params);
}

// Get current month and year
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selectedDate = isset($_GET['date']) ? $_GET['date'] : null;

// CHANGED: Handle filter parameter instead of status
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : null;
$showAllPending = $filterStatus === 'pending';
$showAllApproved = $filterStatus === 'approved';
$showAllDeclined = $filterStatus === 'declined';
$showAllRequests = $filterStatus === 'all';

// Get ALL requests for the current month (for calendar display)
$stmt = $db->prepare("
    SELECT 
        id, event_name, organization, event_date, event_time, event_end_time,
        volunteers_needed, status, requester_email, requester_name, venue, event_description
    FROM support_requests 
    WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?
    ORDER BY event_date, event_time
");
$stmt->execute([$currentMonth, $currentYear]);
$monthRequests = $stmt->fetchAll();

// Get stats for sidebar (month-specific)
$stmt = $db->prepare("SELECT COUNT(*) FROM support_requests WHERE status = 'pending' AND MONTH(event_date) = ? AND YEAR(event_date) = ?");
$stmt->execute([$currentMonth, $currentYear]);
$totalPending = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM support_requests WHERE status = 'approved' AND MONTH(event_date) = ? AND YEAR(event_date) = ?");
$stmt->execute([$currentMonth, $currentYear]);
$totalApproved = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM support_requests WHERE status = 'declined' AND MONTH(event_date) = ? AND YEAR(event_date) = ?");
$stmt->execute([$currentMonth, $currentYear]);
$totalDeclined = $stmt->fetchColumn();

// Get requests for selected date or filter
$displayRequests = [];
if ($selectedDate) {
    // Get all requests for selected date
    $stmt = $db->prepare("
        SELECT 
            id, event_name, organization, event_date, event_time, event_end_time,
            volunteers_needed, status, requester_email, requester_name, venue, event_description
        FROM support_requests 
        WHERE event_date = ?
        ORDER BY event_time
    ");
    $stmt->execute([$selectedDate]);
    $displayRequests = $stmt->fetchAll();
} elseif ($filterStatus) {
    // Get requests by filter status
    $stmt = $db->prepare("
        SELECT 
            id, event_name, organization, event_date, event_time, event_end_time,
            volunteers_needed, status, requester_email, requester_name, venue, event_description
        FROM support_requests 
        WHERE status = ?
        ORDER BY event_date DESC, event_time
        LIMIT 50
    ");
    $stmt->execute([$filterStatus]);
    $displayRequests = $stmt->fetchAll();
} else {
    // Default: Get pending requests for current month
    $stmt = $db->prepare("
        SELECT 
            id, event_name, organization, event_date, event_time, event_end_time,
            volunteers_needed, status, requester_email, requester_name, venue, event_description
        FROM support_requests 
        WHERE status = 'pending' AND MONTH(event_date) = ? AND YEAR(event_date) = ?
        ORDER BY event_date, event_time
        LIMIT 20
    ");
    $stmt->execute([$currentMonth, $currentYear]);
    $displayRequests = $stmt->fetchAll();
}

// ==================== STATISTICS SECTION ====================

// Get overall statistics (all time)
$stmt = $db->prepare("SELECT COUNT(*) FROM support_requests WHERE status = 'pending'");
$stmt->execute();
$overallPending = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM support_requests WHERE status = 'approved'");
$stmt->execute();
$overallApproved = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM support_requests WHERE status = 'declined'");
$stmt->execute();
$overallDeclined = $stmt->fetchColumn();

$overallTotal = $overallPending + $overallApproved + $overallDeclined;

// Calculate percentages for overall stats
$overallApprovedPercent = $overallTotal > 0 ? round(($overallApproved / $overallTotal) * 100, 1) : 0;
$overallDeclinedPercent = $overallTotal > 0 ? round(($overallDeclined / $overallTotal) * 100, 1) : 0;
$overallPendingPercent = $overallTotal > 0 ? round(($overallPending / $overallTotal) * 100, 1) : 0;

// Calculate percentages for current month
$monthTotal = $totalPending + $totalApproved + $totalDeclined;
$monthApprovedPercent = $monthTotal > 0 ? round(($totalApproved / $monthTotal) * 100, 1) : 0;
$monthDeclinedPercent = $monthTotal > 0 ? round(($totalDeclined / $monthTotal) * 100, 1) : 0;
$monthPendingPercent = $monthTotal > 0 ? round(($totalPending / $monthTotal) * 100, 1) : 0;

// ==================== END STATISTICS SECTION ====================

// Calendar helper functions
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<!-- Flash Messages - Positioned Fixed -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show dashboard-alert" role="alert">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show dashboard-alert" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Password Change Messages -->
<?php if (isset($_SESSION['password_message'])): ?>
    <div class="alert <?= $_SESSION['password_success'] ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show dashboard-alert" role="alert">
        <i class="fas <?= $_SESSION['password_success'] ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i> 
        <?= htmlspecialchars($_SESSION['password_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    unset($_SESSION['password_message']); 
    unset($_SESSION['password_success']); 
    ?>
<?php endif; ?>

<div class="gmail-calendar-container">
    <!-- Left Sidebar Panel -->
    <div class="left-sidebar">
        <!-- Mini Calendar -->
        <div class="mini-calendar-section">
            <div class="mini-calendar-header">
                <button class="nav-btn" onclick="navigateMonth(-1)"></button>
                <h6 class="month-title"><?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></h6>
                <button class="nav-btn" onclick="navigateMonth(1)"></button>
            </div>
            <div class="mini-calendar-grid">
                <div class="day-header">S</div>
                <div class="day-header">M</div>
                <div class="day-header">T</div>
                <div class="day-header">W</div>
                <div class="day-header">T</div>
                <div class="day-header">F</div>
                <div class="day-header">S</div>
                
                <?php
                $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                $daysInMonth = date('t', $firstDay);
                $dayOfWeek = date('w', $firstDay);
                
                // Empty cells before first day
                for ($i = 0; $i < $dayOfWeek; $i++) {
                    echo '<div class="mini-day empty"></div>';
                }
                
                // Days of the month
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                    $isToday = ($date === date('Y-m-d')) ? 'today' : '';
                    $isSelected = ($date === $selectedDate) ? 'selected' : '';
                    
                    // Check if this date has events
                    $hasEvents = false;
                    foreach ($monthRequests as $req) {
                        if ($req['event_date'] === $date) {
                            $hasEvents = true;
                            break;
                        }
                    }
                    
                    $eventClass = $hasEvents ? 'has-events' : '';
                    
                    echo "<div class='mini-day $isToday $isSelected $eventClass' data-date='$date' onclick='selectDate(\"$date\")'>$day</div>";
                }
                ?>
            </div>
        </div>

        <!-- Status Categories -->
        <div class="status-categories">
            <h6 class="categories-title">Event Status</h6>
            
            <div class="status-item <?= !$filterStatus ? 'active' : '' ?>" onclick="filterByStatus('all')">
                <div class="status-indicator all"></div>
                <span class="status-label">All Events</span>
                <span class="status-count"><?= $totalPending + $totalApproved + $totalDeclined ?></span>
            </div>
            
            <div class="status-item <?= $filterStatus === 'pending' ? 'active' : '' ?>" onclick="filterByStatus('pending')">
                <div class="status-indicator pending"></div>
                <span class="status-label">Under Review</span>
                <span class="status-count"><?= $totalPending ?></span>
            </div>
            
            <div class="status-item <?= $filterStatus === 'approved' ? 'active' : '' ?>" onclick="filterByStatus('approved')">
                <div class="status-indicator approved"></div>
                <span class="status-label">Approved</span>
                <span class="status-count"><?= $totalApproved ?></span>
            </div>
            
            <div class="status-item <?= $filterStatus === 'declined' ? 'active' : '' ?>" onclick="filterByStatus('declined')">
                <div class="status-indicator declined"></div>
                <span class="status-label">Declined</span>
                <span class="status-count"><?= $totalDeclined ?></span>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="statistics-section">
            <h6 class="statistics-title">Statistics</h6>
            
            <!-- Overall Stats -->
            <div class="stat-card overall-stats">
                <div class="stat-header">
                    <i class="fas fa-chart-pie"></i>
                    <span>Overall (All Time)</span>
                </div>
                <div class="stat-total">
                    <div class="stat-number"><?= number_format($overallTotal) ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-breakdown">
                    <div class="stat-row approved">
                        <div class="stat-bar-container">
                            <div class="stat-bar" style="width: <?= $overallApprovedPercent ?>%"></div>
                        </div>
                        <div class="stat-info">
                            <span class="stat-count"><?= $overallApproved ?></span>
                            <span class="stat-percent"><?= $overallApprovedPercent ?>%</span>
                        </div>
                    </div>
                    <div class="stat-row declined">
                        <div class="stat-bar-container">
                            <div class="stat-bar" style="width: <?= $overallDeclinedPercent ?>%"></div>
                        </div>
                        <div class="stat-info">
                            <span class="stat-count"><?= $overallDeclined ?></span>
                            <span class="stat-percent"><?= $overallDeclinedPercent ?>%</span>
                        </div>
                    </div>
                    <div class="stat-row pending">
                        <div class="stat-bar-container">
                            <div class="stat-bar" style="width: <?= $overallPendingPercent ?>%"></div>
                        </div>
                        <div class="stat-info">
                            <span class="stat-count"><?= $overallPending ?></span>
                            <span class="stat-percent"><?= $overallPendingPercent ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Stats -->
            <div class="stat-card month-stats">
                <div class="stat-header">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></span>
                </div>
                <div class="stat-total">
                    <div class="stat-number"><?= number_format($monthTotal) ?></div>
                    <div class="stat-label">This Month</div>
                </div>
                <div class="stat-breakdown">
                    <div class="stat-row approved">
                        <div class="stat-bar-container">
                            <div class="stat-bar" style="width: <?= $monthApprovedPercent ?>%"></div>
                        </div>
                        <div class="stat-info">
                            <span class="stat-count"><?= $totalApproved ?></span>
                            <span class="stat-percent"><?= $monthApprovedPercent ?>%</span>
                        </div>
                    </div>
                    <div class="stat-row declined">
                        <div class="stat-bar-container">
                            <div class="stat-bar" style="width: <?= $monthDeclinedPercent ?>%"></div>
                        </div>
                        <div class="stat-info">
                            <span class="stat-count"><?= $totalDeclined ?></span>
                            <span class="stat-percent"><?= $monthDeclinedPercent ?>%</span>
                        </div>
                    </div>
                    <div class="stat-row pending">
                        <div class="stat-bar-container">
                            <div class="stat-bar" style="width: <?= $monthPendingPercent ?>%"></div>
                        </div>
                        <div class="stat-info">
                            <span class="stat-count"><?= $totalPending ?></span>
                            <span class="stat-percent"><?= $monthPendingPercent ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
    <!-- Main Calendar Panel -->
    <div class="main-calendar-panel">
        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="calendar-navigation">
                <button class="nav-btn" onclick="navigateMonth(-1)" title="Previous Month"></button>
                <button class="nav-btn" onclick="navigateMonth(1)" title="Next Month"></button>
                <h4 class="calendar-month-title"><?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></h4>
            </div>
            <div class="calendar-controls">
                <?php if ($filterStatus): ?>
                    <?php if ($filterStatus === 'pending'): ?>
                        <span class="btn btn-sm btn-outline-warning filter-status-btn">Under Review</span>
                    <?php elseif ($filterStatus === 'approved'): ?>
                        <span class="btn btn-sm btn-outline-success filter-status-btn">Approved</span>
                    <?php elseif ($filterStatus === 'declined'): ?>
                        <span class="btn btn-sm btn-outline-danger filter-status-btn">Declined</span>
                    <?php endif; ?>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-primary" onclick="navigateToToday()">
                    Today
                </button>
                <?php if ($filterStatus): ?>
                    <button class="btn btn-sm btn-outline-secondary" onclick="filterByStatus('all')">
                        Clear Filter
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid-container">
        <div class="main-calendar-grid">
            <!-- Day Headers -->
            <div class="calendar-day-header">Sunday</div>
            <div class="calendar-day-header">Monday</div>
            <div class="calendar-day-header">Tuesday</div>
            <div class="calendar-day-header">Wednesday</div>
            <div class="calendar-day-header">Thursday</div>
            <div class="calendar-day-header">Friday</div>
            <div class="calendar-day-header">Saturday</div>

            <?php
            // Group requests by date for easy lookup
            $requestsByDate = [];
            foreach ($monthRequests as $request) {
                // Apply status filter if set
                if ($filterStatus && $request['status'] !== $filterStatus) {
                    continue; // Skip events that don't match the filter
                }
                
                $date = $request['event_date'];
                if (!isset($requestsByDate[$date])) {
                    $requestsByDate[$date] = [];
                }
                $requestsByDate[$date][] = $request;
            }

            // Previous month days to fill empty cells
            $prevMonth = $currentMonth - 1;
            $prevYear = $currentYear;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            $daysInPrevMonth = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));
            
            for ($i = 0; $i < $dayOfWeek; $i++) {
                $prevDay = $daysInPrevMonth - ($dayOfWeek - 1 - $i);
                $prevDate = sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $prevDay);
                echo "<div class='main-calendar-day other-month' data-date='$prevDate' onclick='selectDate(\"$prevDate\")'>";
                echo "<div class='day-number'>$prevDay</div>";
                echo "</div>";
            }
            
            // Days of the current month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                $isToday = ($date === date('Y-m-d')) ? 'today' : '';
                $isSelected = ($date === $selectedDate) ? 'selected' : '';
                
                // Check if this day has many events (for scroll indicator)
                $hasScroll = isset($requestsByDate[$date]) && count($requestsByDate[$date]) > 4 ? 'has-scroll' : '';
                
                echo "<div class='main-calendar-day $isToday $isSelected $hasScroll' data-date='$date' onclick='showDayEvents(\"$date\")'>";
                echo "<div class='day-number'>$day</div>";
                
                // Display events for this date
                if (isset($requestsByDate[$date])) {
                    echo "<div class='day-events'>";
                    foreach ($requestsByDate[$date] as $event) {
                        $statusClass = $event['status'];
                        $timeStr = date('g:i A', strtotime($event['event_time']));
                        
                        echo "<div class='event-item $statusClass' onclick='viewEvent({$event['id']}, event)' title='{$event['event_name']}'>";
                        echo "<span class='event-time'>$timeStr</span>";
                        echo "<span class='event-title'>" . htmlspecialchars($event['event_name']) . "</span>";
                        echo "</div>";
                    }
                    echo "</div>";
                }
                
                echo "</div>";
            }
            
            // Next month days to fill remaining cells
            $totalCells = 42; // 6 rows × 7 days
            $cellsUsed = $dayOfWeek + $daysInMonth;
            $nextMonth = $currentMonth + 1;
            $nextYear = $currentYear;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
            
            for ($day = 1; $cellsUsed < $totalCells; $day++, $cellsUsed++) {
                $nextDate = sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $day);
                echo "<div class='main-calendar-day other-month' data-date='$nextDate' onclick='selectDate(\"$nextDate\")'>";
                echo "<div class='day-number'>$day</div>";
                echo "</div>";
            }
            ?>
        </div>
        </div>
    </div>

    <!-- Day Events Modal -->
    <div class="modal fade" id="dayEventsModal" tabindex="-1" aria-labelledby="dayEventsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dayEventsModalLabel">Events for Selected Day</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dayEventsContent">
                    <!-- Day events will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailsModalLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventDetailsContent">
                    <!-- Event details will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Event Modal -->
    <div class="modal fade" id="approveEventModal" tabindex="-1" aria-labelledby="approveEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveEventModalLabel">
                        <i class="bi bi-check-circle"></i> Approve Support Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this CRCY support request?</p>
                    <div class="alert alert-warning" id="approveEventDetails">
                        <!-- Event details will be populated here -->
                    </div>
                    <p><strong>⚠️ Note:</strong> Once approved, this action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmApproveBtn">
                        <i class="bi bi-check"></i> Approve Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Decline Event Modal -->
    <div class="modal fade" id="declineEventModal" tabindex="-1" aria-labelledby="declineEventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="declineEventModalLabel">
                        <i class="bi bi-x-circle"></i> Decline Support Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please provide a reason for declining this support request:</p>
                    <div class="alert alert-warning" id="declineEventDetails">
                        <!-- Event details will be populated here -->
                    </div>
                    <div class="mb-3">
                        <label for="declineReason" class="form-label">Reason for Decline <span class="text-danger">*</span></label>
                        <textarea id="declineReason" class="form-control" rows="3" 
                                  placeholder="e.g., Date conflict with existing event, insufficient volunteers available, etc." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeclineBtn">
                        <i class="bi bi-x"></i> Decline Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
        crossorigin="anonymous"></script>

<!-- Global Custom JS -->
<script src="assets/js/main.js"></script>

<!-- Dashboard-specific JS -->
<script src="assets/js/dashboard.js"></script>

</body>
</html>