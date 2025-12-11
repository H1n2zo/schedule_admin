<?php
/**
 * EVSU Event Management System
 * Dashboard Page - Fixed Calendar Display
 * File: dashboard.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Dashboard - EVSU Admin Panel';
$customCSS = ['dashboard'];
$customJS = ['dashboard'];

$db = getDB();

// Get current month and year
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selectedDate = isset($_GET['date']) ? $_GET['date'] : null;

// Get ALL requests for the current month (for calendar display)
$stmt = $db->prepare("
    SELECT 
        id, event_name, organization, event_date, event_time, 
        volunteers_needed, status, requester_email, requester_name
    FROM event_requests 
    WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?
    ORDER BY event_date, event_time
");
$stmt->execute([$currentMonth, $currentYear]);
$monthRequests = $stmt->fetchAll();

// Get ONLY PENDING requests for sidebar display
$stmt = $db->prepare("
    SELECT 
        id, event_name, organization, event_date, event_time, 
        volunteers_needed, status, requester_email, requester_name
    FROM event_requests 
    WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?
    AND status = 'pending'
    ORDER BY event_date, event_time
");
$stmt->execute([$currentMonth, $currentYear]);
$pendingRequests = $stmt->fetchAll();

// Filter for sidebar display based on selected date
$displayRequests = $pendingRequests;
if ($selectedDate) {
    $displayRequests = array_filter($pendingRequests, function($req) use ($selectedDate) {
        return $req['event_date'] === $selectedDate;
    });
}

// Get stats
$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'pending'");
$totalPending = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'approved'");
$totalApproved = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'declined'");
$totalDeclined = $stmt->fetchColumn();

// Group ALL requests by date for calendar with counts
$requestsByDate = [];
$countsByDate = [];
$approvedByDate = [];
$pendingByDate = [];

foreach ($monthRequests as $request) {
    $date = $request['event_date'];
    if (!isset($requestsByDate[$date])) {
        $requestsByDate[$date] = [];
        $countsByDate[$date] = 0;
        $approvedByDate[$date] = 0;
        $pendingByDate[$date] = 0;
    }
    $requestsByDate[$date][] = $request;
    $countsByDate[$date]++;
    
    if ($request['status'] === 'approved') {
        $approvedByDate[$date]++;
    }
    if ($request['status'] === 'pending') {
        $pendingByDate[$date]++;
    }
}

// Calendar helper functions
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Navigation
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Get months with requests
$stmt = $db->query("
    SELECT DISTINCT 
        YEAR(event_date) as year, 
        MONTH(event_date) as month,
        COUNT(*) as request_count
    FROM event_requests 
    GROUP BY YEAR(event_date), MONTH(event_date)
    ORDER BY year DESC, month DESC
");
$monthsWithRequests = $stmt->fetchAll();

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8 p-4">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <h6 class="text-muted">Pending Requests</h6>
                        <h2 class="text-warning"><?= $totalPending ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h6 class="text-muted">Approved Events</h6>
                        <h2 class="text-success"><?= $totalApproved ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h6 class="text-muted">Declined Requests</h6>
                        <h2 class="text-danger"><?= $totalDeclined ?></h2>
                    </div>
                </div>
            </div>

            <!-- Calendar Navigation -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><?= $monthName ?></h4>
                <div>
                    <button onclick="navigateMonth(<?= $prevMonth ?>, <?= $prevYear ?>)" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-chevron-left"></i> Prev
                    </button>
                    <a href="dashboard.php" class="btn btn-sm btn-primary">Today</a>
                    <button onclick="navigateMonth(<?= $nextMonth ?>, <?= $nextYear ?>)" class="btn btn-sm btn-outline-primary">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <?php if (!empty($monthsWithRequests)): ?>
            <div class="mb-3">
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-calendar-check"></i> Months with requests:
                </small>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($monthsWithRequests as $mr): 
                        $monthLabel = date('F Y', mktime(0, 0, 0, $mr['month'], 1, $mr['year']));
                        $isActive = ($mr['month'] == $currentMonth && $mr['year'] == $currentYear);
                    ?>
                        <button onclick="navigateMonth(<?= $mr['month'] ?>, <?= $mr['year'] ?>)" 
                               class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?> month-quick-link">
                            <?= $monthLabel ?>
                            <span class="badge bg-light text-dark ms-1"><?= $mr['request_count'] ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Calendar -->
            <div class="calendar-grid">
                <div class="text-center fw-bold">Sun</div>
                <div class="text-center fw-bold">Mon</div>
                <div class="text-center fw-bold">Tue</div>
                <div class="text-center fw-bold">Wed</div>
                <div class="text-center fw-bold">Thu</div>
                <div class="text-center fw-bold">Fri</div>
                <div class="text-center fw-bold">Sat</div>

                <?php
                // Empty cells before first day
                for ($i = 0; $i < $dayOfWeek; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                
                // Days of the month
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                    $isToday = ($date === date('Y-m-d')) ? 'today' : '';
                    $isSelected = ($date === $selectedDate) ? 'selected' : '';
                    $count = $countsByDate[$date] ?? 0;
                    $approvedCount = $approvedByDate[$date] ?? 0;
                    $pendingCount = $pendingByDate[$date] ?? 0;
                    $hasEvents = $count > 0 ? 'has-events' : '';
                    
                    // Only add has-approved class if there ARE approved events
                    $hasApproved = $approvedCount > 0 ? 'has-approved' : '';
                    
                    echo "<div class='calendar-day $isToday $isSelected $hasEvents $hasApproved' data-date='$date' onclick='selectDate(\"$date\")'>";
                    echo "<div class='day-number'>$day</div>";
                    
                    // Show approved indicator only if there are approved events
                    if ($approvedCount > 0) {
                        echo "<div class='approved-indicator' title='$approvedCount approved event" . ($approvedCount > 1 ? 's' : '') . "'>";
                        echo "<i class='fas fa-check'></i>";
                        echo "</div>";
                    }
                    
                    // Show pending indicator if there are pending requests
                    if ($pendingCount > 0) {
                        echo "<div class='pending-indicator' title='$pendingCount pending request" . ($pendingCount > 1 ? 's' : '') . "'>";
                        echo "<i class='fas fa-clock'></i> $pendingCount";
                        echo "</div>";
                    }
                    
                    if ($count > 0) {
                        echo "<div class='event-count-badge'>";
                        echo "<i class='fas fa-calendar'></i> ";
                        echo "$count total";
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Sidebar - PENDING Requests Only -->
        <div class="col-md-4 p-4 bg-white sidebar-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <?php if ($selectedDate): ?>
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day"></i> <?= formatDate($selectedDate) ?>
                    </h5>
                    <button onclick="clearDateFilter()" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                <?php else: ?>
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> Pending Requests
                    </h5>
                <?php endif; ?>
            </div>

            <div class="alert alert-info alert-sm">
                <small>
                    <i class="fas fa-info-circle"></i> 
                    Showing pending requests only. 
                    <a href="history.php" class="alert-link">View history</a> for approved/declined.
                </small>
            </div>
            
            <?php if (empty($displayRequests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted" id="emptyMessage">
                        <?php if ($selectedDate): ?>
                            No pending requests on this date.
                        <?php else: ?>
                            No pending requests for this month.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($pendingRequests) && $selectedDate): ?>
                        <button onclick="clearDateFilter()" class="btn btn-sm btn-primary">
                            Show All Pending
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="pending-count-badge mb-3">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= count($displayRequests) ?> pending request<?= count($displayRequests) > 1 ? 's' : '' ?>
                </div>
                
                <div id="requestsList">
                <?php foreach ($displayRequests as $req): ?>
                    <div class="request-list-item pending" 
                         data-status="pending"
                         onclick="viewRequest(<?= $req['id'] ?>)">
                        <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                        <small class="text-muted">
                            <i class="fas fa-building"></i> <?= htmlspecialchars($req['organization']) ?><br>
                            <i class="fas fa-calendar"></i> <?= formatDate($req['event_date']) ?> 
                            <i class="fas fa-clock"></i> <?= formatTime($req['event_time']) ?><br>
                            <i class="fas fa-users"></i> <?= $req['volunteers_needed'] ?> volunteers
                        </small>
                        <div class="mt-2">
                            <span class="badge badge-warning">
                                <i class="fas fa-clock"></i> Pending Review
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($totalPending > 0): ?>
            <div class="mt-4 pt-3 border-top">
                <div class="d-grid">
                    <a href="history.php" class="btn btn-outline-primary">
                        <i class="fas fa-history"></i> View Approved & Declined History
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .sidebar-section {
        border-left: 1px solid #dee2e6;
        min-height: calc(100vh - 56px);
        position: sticky;
        top: 56px;
        max-height: calc(100vh - 56px);
        overflow-y: auto;
    }

    .calendar-legend {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.875rem;
    }

    .legend-badge {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        display: inline-block;
        border: 2px solid;
    }

    .legend-approved {
        background: #e8f5e9;
        border-color: #2e7d32;
    }

    .legend-pending {
        background: #fff3cd;
        border-color: #ffc107;
    }

    .legend-today {
        background: #fff5f5;
        border-color: var(--evsu-maroon);
    }

    .pending-count-badge {
        background: #fff3cd;
        border: 2px solid #ffc107;
        color: #856404;
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
    }

    .alert-sm {
        padding: 8px 12px;
        font-size: 0.875rem;
    }

    .pending-indicator {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #ffc107;
        color: #000;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 3px;
    }

    .request-list-item {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #e9ecef;
        border-left: 4px solid #ffc107;
    }

    .request-list-item:hover {
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        transform: translateX(5px);
        border-left-color: var(--evsu-gold);
    }

    .request-list-item h6 {
        color: var(--evsu-maroon);
        font-weight: 600;
        margin-bottom: 8px;
    }

    .request-list-item small {
        font-size: 0.85rem;
        display: block;
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .sidebar-section {
            position: relative;
            top: 0;
            min-height: auto;
            max-height: none;
            border-left: none;
            border-top: 1px solid #dee2e6;
        }

        .calendar-legend {
            font-size: 0.75rem;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>