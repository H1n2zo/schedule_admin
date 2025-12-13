<?php
/**
 * EVSU Event Management System
 * Dashboard Page - FIXED with filter functionality
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

// CHANGED: Handle filter parameter instead of status
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : null;
$showAllPending = $filterStatus === 'pending';
$showAllApproved = $filterStatus === 'approved';
$showAllDeclined = $filterStatus === 'declined';

// Get ALL requests for the current month (for calendar display)
$stmt = $db->prepare("
    SELECT 
        id, event_name, organization, event_date, event_time, event_end_time,
        volunteers_needed, status, requester_email, requester_name
    FROM event_requests 
    WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?
    ORDER BY event_date, event_time
");
$stmt->execute([$currentMonth, $currentYear]);
$monthRequests = $stmt->fetchAll();

// Get ONLY PENDING requests for current month (for normal calendar display)
$stmt = $db->prepare("
    SELECT 
        id, event_name, organization, event_date, event_time, event_end_time,
        volunteers_needed, status, requester_email, requester_name
    FROM event_requests 
    WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?
    AND status = 'pending'
    ORDER BY event_date, event_time
");
$stmt->execute([$currentMonth, $currentYear]);
$pendingRequests = $stmt->fetchAll();

// Get ALL pending requests (for "show all" mode in sidebar)
$stmt = $db->query("
    SELECT 
        id, event_name, organization, event_date, event_time, event_end_time,
        volunteers_needed, status, requester_email, requester_name
    FROM event_requests 
    WHERE status = 'pending'
    ORDER BY event_date, event_time
");
$allPendingRequests = $stmt->fetchAll();

// ADDED: Get ALL approved requests
$stmt = $db->query("
    SELECT 
        id, event_name, organization, event_date, event_time, event_end_time,
        volunteers_needed, status, requester_email, requester_name
    FROM event_requests 
    WHERE status = 'approved'
    ORDER BY event_date, event_time
");
$allApprovedRequests = $stmt->fetchAll();

// ADDED: Get ALL declined requests
$stmt = $db->query("
    SELECT 
        id, event_name, organization, event_date, event_time, event_end_time,
        volunteers_needed, status, requester_email, requester_name
    FROM event_requests 
    WHERE status = 'declined'
    ORDER BY event_date, event_time
");
$allDeclinedRequests = $stmt->fetchAll();

// Filter for sidebar display based on selected date OR show all mode
$displayRequests = $pendingRequests; // Default: current month pending
$displayApprovedRequests = [];
$displayDeclinedRequests = [];

// CHANGED: When clicking a date, clear filters
if ($selectedDate) {
    // Show pending requests for selected date only
    $displayRequests = array_filter($pendingRequests, function($req) use ($selectedDate) {
        return $req['event_date'] === $selectedDate;
    });
    
    // Also get approved events for the selected date
    $stmt = $db->prepare("
        SELECT 
            id, event_name, organization, event_date, event_time, event_end_time,
            volunteers_needed, status, requester_email, requester_name
        FROM event_requests 
        WHERE event_date = ? AND status = 'approved'
        ORDER BY event_time
    ");
    $stmt->execute([$selectedDate]);
    $displayApprovedRequests = $stmt->fetchAll();
    
    // ADDED: Get declined events for the selected date
    $stmt = $db->prepare("
        SELECT 
            id, event_name, organization, event_date, event_time, event_end_time,
            volunteers_needed, status, requester_email, requester_name
        FROM event_requests 
        WHERE event_date = ? AND status = 'declined'
        ORDER BY event_time
    ");
    $stmt->execute([$selectedDate]);
    $displayDeclinedRequests = $stmt->fetchAll();
} elseif ($showAllPending) {
    // Show ALL pending requests from all time
    $displayRequests = $allPendingRequests;
} elseif ($showAllApproved) {
    // Show ALL approved requests
    $displayRequests = [];
    $displayApprovedRequests = $allApprovedRequests;
} elseif ($showAllDeclined) {
    // Show ALL declined requests
    $displayRequests = [];
    $displayDeclinedRequests = $allDeclinedRequests;
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
$declinedByDate = []; // ADDED

foreach ($monthRequests as $request) {
    $date = $request['event_date'];
    if (!isset($requestsByDate[$date])) {
        $requestsByDate[$date] = [];
        $countsByDate[$date] = 0;
        $approvedByDate[$date] = 0;
        $pendingByDate[$date] = 0;
        $declinedByDate[$date] = 0; // ADDED
    }
    $requestsByDate[$date][] = $request;
    $countsByDate[$date]++;
    
    if ($request['status'] === 'approved') {
        $approvedByDate[$date]++;
    }
    if ($request['status'] === 'pending') {
        $pendingByDate[$date]++;
    }
    if ($request['status'] === 'declined') { // ADDED
        $declinedByDate[$date]++;
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
    ORDER BY year ASC, month ASC
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
                    <!-- CHANGED: filter=pending instead of status=pending -->
                    <a href="dashboard.php?filter=pending" class="stats-card-link">
                        <div class="stats-card <?= $showAllPending ? 'active-filter' : '' ?>">
                            <h6 class="text-muted">Under Review</h6>
                            <h2 class="text-warning"><?= $totalPending ?></h2>
                            <small class="text-muted">
                                <i class="fas fa-mouse-pointer"></i> Click to view all under review
                            </small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <!-- CHANGED: filter=approved instead of history.php -->
                    <a href="dashboard.php?filter=approved" class="stats-card-link">
                        <div class="stats-card stats-card-clickable <?= $showAllApproved ? 'active-filter' : '' ?>">
                            <h6 class="text-muted">Approved Events</h6>
                            <h2 class="text-success"><?= $totalApproved ?></h2>
                            <small class="text-success">
                                <i class="fas fa-mouse-pointer"></i> Click to view all approved
                            </small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <!-- CHANGED: filter=declined instead of history.php -->
                    <a href="dashboard.php?filter=declined" class="stats-card-link">
                        <div class="stats-card stats-card-clickable <?= $showAllDeclined ? 'active-filter' : '' ?>">
                            <h6 class="text-muted">Declined Requests</h6>
                            <h2 class="text-danger"><?= $totalDeclined ?></h2>
                            <small class="text-danger">
                                <i class="fas fa-mouse-pointer"></i> Click to view all declined
                            </small>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Calendar Navigation -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><?= $monthName ?></h4>
                <div>
                    <a href="dashboard.php?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-chevron-left"></i> Prev
                    </a>
                    <a href="dashboard.php" class="btn btn-sm btn-primary">Today</a>
                    <a href="dashboard.php?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-sm btn-outline-primary">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
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
                        $isActive = ($mr['month'] == $currentMonth && $mr['year'] == $currentYear && !$showAllPending && !$showAllApproved && !$showAllDeclined);
                    ?>
                        <a href="dashboard.php?month=<?= $mr['month'] ?>&year=<?= $mr['year'] ?>" 
                           class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?> month-quick-link">
                            <?= $monthLabel ?>
                            <span class="badge bg-light text-dark ms-1"><?= $mr['request_count'] ?></span>
                        </a>
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
                    $approvedCount = $approvedByDate[$date] ?? 0;
                    $pendingCount = $pendingByDate[$date] ?? 0;
                    $declinedCount = $declinedByDate[$date] ?? 0; // ADDED
                    
                    // Only add has-approved class if there ARE approved events
                    $hasApproved = $approvedCount > 0 ? 'has-approved' : '';
                    $hasDeclined = $declinedCount > 0 ? 'has-declined' : ''; // ADDED
                    
                    echo "<div class='calendar-day $isToday $isSelected $hasApproved $hasDeclined' data-date='$date' onclick='selectDate(\"$date\")'>";
                    
                    // Day number - bigger and centered
                    echo "<div class='day-number'>$day</div>";
                    
                    // Show approved indicator only if there are approved events (just a check, no count)
                    if ($approvedCount > 0) {
                        echo "<div class='approved-indicator' title='Has approved event'>";
                        echo "<i class='fas fa-check'></i>";
                        echo "</div>";
                    }
                    
                    // ADDED: Show declined indicator
                    if ($declinedCount > 0) {
                        echo "<div class='declined-indicator' title='$declinedCount declined request" . ($declinedCount > 1 ? 's' : '') . "'>";
                        echo "<i class='fas fa-times'></i> $declinedCount";
                        echo "</div>";
                    }
                    
                    // Show pending indicator if there are pending requests (yellow badge)
                    if ($pendingCount > 0) {
                        echo "<div class='pending-indicator' title='$pendingCount pending request" . ($pendingCount > 1 ? 's' : '') . "'>";
                        echo "<i class='fas fa-clock'></i> $pendingCount";
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Sidebar - Shows Approved + Pending + Declined for selected date, or filtered view -->
        <div class="col-md-4 p-4 bg-white sidebar-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <?php if ($showAllPending): ?>
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> All Under Review
                    </h5>
                    <button onclick="window.location.href='dashboard.php'" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                <?php elseif ($showAllApproved): ?>
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle"></i> All Approved Events
                    </h5>
                    <button onclick="window.location.href='dashboard.php'" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                <?php elseif ($showAllDeclined): ?>
                    <h5 class="mb-0">
                        <i class="fas fa-times-circle"></i> All Declined Requests
                    </h5>
                    <button onclick="window.location.href='dashboard.php'" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                <?php elseif ($selectedDate): ?>
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day"></i> <?= formatDate($selectedDate) ?>
                    </h5>
                    <button onclick="clearDateFilter()" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                <?php else: ?>
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> Under Review
                    </h5>
                <?php endif; ?>
            </div>

            <div class="alert alert-info alert-sm">
                <small>
                    <i class="fas fa-info-circle"></i> 
                    <?php if ($showAllPending): ?>
                        Showing all under review from all months.
                    <?php elseif ($showAllApproved): ?>
                        Showing all approved events from all months.
                    <?php elseif ($showAllDeclined): ?>
                        Showing all declined requests from all months.
                    <?php elseif ($selectedDate): ?>
                        Showing all events for this date.
                    <?php else: ?>
                        Showing pending requests for <?= $monthName ?> only. Click stats cards to filter.
                    <?php endif; ?>
                </small>
            </div>
            
            <?php if ($selectedDate && empty($displayRequests) && empty($displayApprovedRequests) && empty($displayDeclinedRequests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No events on this date.</p>
                    <button onclick="clearDateFilter()" class="btn btn-sm btn-primary">
                        Show All Under Review
                    </button>
                </div>
            <?php elseif (!$selectedDate && !$showAllPending && !$showAllApproved && !$showAllDeclined && empty($displayRequests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No pending requests for this month.</p>
                    <a href="dashboard.php?filter=pending" class="btn btn-sm btn-warning">
                        <i class="fas fa-list"></i> Show All Pending
                    </a>
                </div>
            <?php elseif ($showAllPending && empty($displayRequests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">No pending requests found! All caught up.</p>
                    <a href="dashboard.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-calendar"></i> Back to Calendar
                    </a>
                </div>
            <?php elseif ($showAllApproved && empty($displayApprovedRequests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No approved events found.</p>
                    <a href="dashboard.php" class="btn btn-sm btn-primary">Back to Calendar</a>
                </div>
            <?php elseif ($showAllDeclined && empty($displayDeclinedRequests)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No declined requests found.</p>
                    <a href="dashboard.php" class="btn btn-sm btn-primary">Back to Calendar</a>
                </div>
            <?php else: ?>
                <?php if ($selectedDate): ?>
                    <!-- Show Approved Events First -->
                    <?php if (!empty($displayApprovedRequests)): ?>
                        <div class="approved-section mb-3">
                            <h6 class="text-success mb-2">
                                <i class="fas fa-check-circle"></i> Approved Events (<?= count($displayApprovedRequests) ?>)
                            </h6>
                            <div id="approvedList">
                            <?php foreach ($displayApprovedRequests as $req): ?>
                                <div class="request-list-item approved" 
                                     data-status="approved"
                                     onclick="viewRequest(<?= $req['id'] ?>)">
                                    <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-building"></i> <?= htmlspecialchars($req['organization']) ?><br>
                                        <i class="fas fa-calendar"></i> <?= formatDate($req['event_date']) ?><br>
                                        <i class="fas fa-clock"></i> <?= formatTime($req['event_time']) ?>
                                        <?php if ($req['event_end_time']): ?>
                                            - <?= formatTime($req['event_end_time']) ?>
                                        <?php endif; ?><br>
                                        <i class="fas fa-users"></i> <?= $req['volunteers_needed'] ?> volunteers
                                    </small>
                                    <div class="mt-2">
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> Approved
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Show Declined Events -->
                    <?php if (!empty($displayDeclinedRequests)): ?>
                        <div class="declined-section mb-3">
                            <h6 class="text-danger mb-2">
                                <i class="fas fa-times-circle"></i> Declined Requests (<?= count($displayDeclinedRequests) ?>)
                            </h6>
                            <div id="declinedList">
                            <?php foreach ($displayDeclinedRequests as $req): ?>
                                <div class="request-list-item declined" 
                                     data-status="declined"
                                     onclick="viewRequest(<?= $req['id'] ?>)">
                                    <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-building"></i> <?= htmlspecialchars($req['organization']) ?><br>
                                        <i class="fas fa-calendar"></i> <?= formatDate($req['event_date']) ?><br>
                                        <i class="fas fa-clock"></i> <?= formatTime($req['event_time']) ?>
                                        <?php if ($req['event_end_time']): ?>
                                            - <?= formatTime($req['event_end_time']) ?>
                                        <?php endif; ?><br>
                                        <i class="fas fa-users"></i> <?= $req['volunteers_needed'] ?> volunteers
                                    </small>
                                    <div class="mt-2">
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times-circle"></i> Declined
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Show Pending Requests -->
                    <?php if (!empty($displayRequests)): ?>
                        <div class="pending-section">
                            <h6 class="text-warning mb-2">
                                <i class="fas fa-clock"></i> Under Review (<?= count($displayRequests) ?>)
                            </h6>
                            <div id="requestsList">
                            <?php foreach ($displayRequests as $req): ?>
                                <div class="request-list-item pending" 
                                     data-status="pending"
                                     onclick="viewRequest(<?= $req['id'] ?>)">
                                    <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-building"></i> <?= htmlspecialchars($req['organization']) ?><br>
                                        <i class="fas fa-calendar"></i> <?= formatDate($req['event_date']) ?><br>
                                        <i class="fas fa-clock"></i> <?= formatTime($req['event_time']) ?>
                                        <?php if ($req['event_end_time']): ?>
                                            - <?= formatTime($req['event_end_time']) ?>
                                        <?php endif; ?><br>
                                        <i class="fas fa-users"></i> <?= $req['volunteers_needed'] ?> volunteers
                                    </small>
                                    <div class="mt-2">
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Under Review
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($showAllApproved): ?>
                    <!-- Show ALL Approved -->
                    <div class="approved-count-badge mb-3">
                        <i class="fas fa-check-circle"></i> 
                        <?= count($displayApprovedRequests) ?> approved event<?= count($displayApprovedRequests) != 1 ? 's' : '' ?>
                        <span class="ms-2 badge bg-success">All Time</span>
                    </div>
                    
                    <div id="approvedList">
                    <?php foreach ($displayApprovedRequests as $req): ?>
                        <div class="request-list-item approved" 
                             data-status="approved"
                             onclick="viewRequest(<?= $req['id'] ?>)">
                            <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-building"></i> <?= htmlspecialchars($req['organization']) ?><br>
                                <i class="fas fa-calendar"></i> <?= formatDate($req['event_date']) ?><br>
                                <i class="fas fa-clock"></i> <?= formatTime($req['event_time']) ?>
                                <?php if ($req['event_end_time']): ?>
                                    - <?= formatTime($req['event_end_time']) ?>
                                <?php endif; ?><br>
                                <i class="fas fa-users"></i> <?= $req['volunteers_needed'] ?> volunteers
                            </small>
                            <div class="mt-2">
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Approved
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    
                <?php elseif ($showAllDeclined): ?>
                    <!-- Show ALL Declined -->
                    <div class="declined-count-badge mb-3">
                        <i class="fas fa-times-circle"></i> 
                        <?= count($displayDeclinedRequests) ?> declined request<?= count($displayDeclinedRequests) != 1 ? 's' : '' ?>
                        <span class="ms-2 badge bg-danger">All Time</span>
                    </div>
                    
                    <div id="declinedList">
                    <?php foreach ($displayDeclinedRequests as $req): ?>
                        <div class="request-list-item declined" 
                             data-status="declined"
                             onclick="viewRequest(<?= $req['id'] ?>)">
                            <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-building"></i> <?= htmlspecialchars($req['organization']) ?><br>
                                <i class="fas fa-calendar"></i> <?= formatDate($req['event_date']) ?><br>
                                <i class="fas fa-clock"></i> <?= formatTime($req['event_time']) ?>
                                <?php if ($req['event_end_time']): ?>
                                    - <?= formatTime($req['event_end_time']) ?>
                                <?php endif; ?><br>
                                <i class="fas fa-users"></i> <?= $req['volunteers_needed'] ?> volunteers
                            </small>
                            <div class="mt-2">
                                <span class="badge badge-danger">
                                    <i class="fas fa-times-circle"></i> Declined
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Show All Pending (No Date Selected OR Show All Mode) -->
                    <div class="pending-count-badge mb-3">
                        <i class="fas fa-exclamation-circle"></i> 
                        <?= count($displayRequests) ?> under review<?= count($displayRequests) > 1 ? 's' : '' ?>
                        <?php if ($showAllPending): ?>
                            <span class="ms-2 badge bg-warning text-dark">All Time</span>
                        <?php endif; ?>
                    </div>
                    
                    <div id="requestsList">
                    <?php foreach ($displayRequests as $req): ?>
                        <div class="request-list-item pending" 
                             data-status="pending"
                             onclick="viewRequest(<?= $req['id'] ?>)">
                            <h6 class="mb-1"><?= htmlspecialchars($req['event_name']) ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-building"></i> <?= htmlspecialchars($req['organization']) ?><br>
                                <i class="fas fa-calendar"></i> <?= formatDate($req['event_date']) ?><br>
                                <i class="fas fa-clock"></i> <?= formatTime($req['event_time']) ?>
                                <?php if ($req['event_end_time']): ?>
                                    - <?= formatTime($req['event_end_time']) ?>
                                <?php endif; ?><br>
                                <i class="fas fa-users"></i> <?= $req['volunteers_needed'] ?> volunteers
                            </small>
                            <div class="mt-2">
                                <span class="badge badge-warning">
                                    <i class="fas fa-clock"></i> Under Review
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($totalPending > 0 || $totalApproved > 0 || $totalDeclined > 0): ?>
            <div class="mt-4 pt-3 border-top">
                <div class="d-grid">
                    <a href="history.php" class="btn btn-outline-primary">
                        <i class="fas fa-history"></i> View All History
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

    .pending-count-badge {
        background: #fff3cd;
        border: 2px solid #ffc107;
        color: #856404;
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
    }
    
    .approved-count-badge {
        background: #e8f5e9;
        border: 2px solid #2e7d32;
        color: #1b5e20;
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
    }
    
    .declined-count-badge {
        background: #ffebee;
        border: 2px solid #c62828;
        color: #8e0000;
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
    }

    .alert-sm {
        padding: 8px 12px;
        font-size: 0.875rem;
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

    .request-list-item.approved {
        border-left-color: #28a745;
        background: #f1f8f4;
    }

    .request-list-item.approved:hover {
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        border-left-color: #218838;
    }
    
    .request-list-item.declined {
        border-left-color: #dc3545;
        background: #fff5f5;
    }

    .request-list-item.declined:hover {
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        border-left-color: #c82333;
    }

    .approved-section h6,
    .pending-section h6,
    .declined-section h6 {
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 15px;
    }

    .pending-indicator {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #ffc107;
        color: #000;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 4px rgba(255, 193, 7, 0.4);
    }
    
    .declined-indicator {
        position: absolute;
        bottom: 10px;
        left: 10px;
        background: #dc3545;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.4);
    }
    
    .calendar-day.has-declined {
        border-bottom: 4px solid #dc3545;
    }

    /* Make day numbers bigger and more prominent */
    .calendar-day .day-number {
        font-size: 28px;
        font-weight: bold;
        color: #495057;
        margin-bottom: 8px;
        display: block;
        text-align: center;
        padding-top: 15px;
    }

    .calendar-day.today .day-number {
        color: var(--evsu-maroon);
    }

    .calendar-day.selected .day-number {
        color: var(--evsu-gold);
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
    }
    
    /* Fix stats card links - remove blue underline */
    .stats-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .stats-card-link:hover {
        text-decoration: none;
        color: inherit;
    }
    
    .stats-card-clickable {
        cursor: pointer;
    }
    
    .stats-card.active-filter {
        border: 3px solid var(--evsu-gold);
        box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        transform: translateY(-5px);
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>