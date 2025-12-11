<?php
/**
 * EVSU Event Management System
 * Dashboard Page - Updated with External Assets
 * File: dashboard.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Dashboard - EVSU Admin Panel';
$customCSS = ['dashboard']; // Loads assets/css/dashboard.css
$customJS = ['dashboard']; // Loads assets/js/dashboard.js

$db = getDB();

// Get current month and year
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selectedDate = isset($_GET['date']) ? $_GET['date'] : null;

// Get requests for the current month
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

// Filter for sidebar display
$displayRequests = $monthRequests;
if ($selectedDate) {
    $displayRequests = array_filter($monthRequests, function($req) use ($selectedDate) {
        return $req['event_date'] === $selectedDate;
    });
}

// Get pending actions count
$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'pending_notification'");
$pendingCount = $stmt->fetchColumn();

// Get stats
$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'pending'");
$pendingRequests = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'approved'");
$approvedRequests = $stmt->fetchColumn();

// Group requests by date for calendar with counts
$requestsByDate = [];
$countsByDate = [];
$approvedByDate = [];

foreach ($monthRequests as $request) {
    $date = $request['event_date'];
    if (!isset($requestsByDate[$date])) {
        $requestsByDate[$date] = [];
        $countsByDate[$date] = 0;
        $approvedByDate[$date] = 0;
    }
    $requestsByDate[$date][] = $request;
    $countsByDate[$date]++;
    
    if ($request['status'] === 'approved') {
        $approvedByDate[$date]++;
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
                        <h2 class="text-warning"><?= $pendingRequests ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h6 class="text-muted">Approved Events</h6>
                        <h2 class="text-success"><?= $approvedRequests ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <h6 class="text-muted">Pending Actions</h6>
                        <h2 class="text-info"><?= $pendingCount ?></h2>
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

            <!-- Month Quick Links -->
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
                    $hasEvents = $count > 0 ? 'has-events' : '';
                    $hasApproved = $approvedCount > 0 ? 'has-approved' : '';
                    
                    echo "<div class='calendar-day $isToday $isSelected $hasEvents $hasApproved' data-date='$date' onclick='selectDate(\"$date\")'>";
                    echo "<div class='day-number'>$day</div>";
                    
                    if ($approvedCount > 0) {
                        echo "<div class='approved-indicator' title='Has approved events'>";
                        echo "<i class='fas fa-check'></i>";
                        echo "</div>";
                    }
                    
                    if ($count > 0) {
                        echo "<div class='event-count-badge'>";
                        echo "<i class='fas fa-calendar'></i> ";
                        echo "$count event" . ($count > 1 ? 's' : '');
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Sidebar - Request List -->
        <div class="col-md-4 p-4 bg-white">
            <?php if ($selectedDate): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day"></i> <?= formatDate($selectedDate) ?>
                    </h5>
                    <button onclick="clearDateFilter()" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            <?php else: ?>
                <h5 class="mb-3">Requests for <?= $monthName ?></h5>
            <?php endif; ?>
            
            <!-- Status Filter Buttons -->
            <div class="btn-group w-100 mb-3" role="group">
                <input type="radio" class="btn-check" name="statusFilter" id="filterAll" autocomplete="off" checked>
                <label class="btn btn-outline-secondary btn-sm" for="filterAll" onclick="filterByStatus('all')">
                    All
                </label>
                
                <input type="radio" class="btn-check" name="statusFilter" id="filterPending" autocomplete="off">
                <label class="btn btn-outline-warning btn-sm" for="filterPending" onclick="filterByStatus('pending')">
                    Pending
                </label>
                
                <input type="radio" class="btn-check" name="statusFilter" id="filterApproved" autocomplete="off">
                <label class="btn btn-outline-success btn-sm" for="filterApproved" onclick="filterByStatus('approved')">
                    Approved
                </label>
                
                <input type="radio" class="btn-check" name="statusFilter" id="filterDisapproved" autocomplete="off">
                <label class="btn btn-outline-danger btn-sm" for="filterDisapproved" onclick="filterByStatus('disapproved')">
                    Disapproved
                </label>
            </div>
            
            <?php if (empty($displayRequests)): ?>
                <p class="text-muted" id="emptyMessage">
                    <?php if ($selectedDate): ?>
                        No requests on this date.
                    <?php else: ?>
                        No requests for this month.
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <div id="requestsList">
                <?php foreach ($displayRequests as $req): ?>
                    <div class="request-list-item <?= $req['status'] ?>" 
                         data-status="<?= $req['status'] ?>"
                         onclick="viewRequest(<?= $req['id'] ?>)">
                        <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                        <small class="text-muted">
                            <?= htmlspecialchars($req['organization']) ?><br>
                            <?= formatDate($req['event_date']) ?> at <?= formatTime($req['event_time']) ?>
                        </small>
                        <div class="mt-2">
                            <?= getStatusBadge($req['status']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>