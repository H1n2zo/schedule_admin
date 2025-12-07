<?php
require_once 'config.php';
requireAdmin();

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

// Group requests by date for calendar
$requestsByDate = [];
foreach ($monthRequests as $request) {
    $date = $request['event_date'];
    if (!isset($requestsByDate[$date])) {
        $requestsByDate[$date] = [];
    }
    $requestsByDate[$date][] = $request;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EVSU Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --evsu-maroon: #800000;
            --evsu-gold: #FFD700;
            --evsu-white: #FFFFFF;
            --maroon-dark: #5c0000;
            --gold-dark: #d4af37;
            --maroon-light: #fff5f5;
        }
        body { background: #f5f7fa; }
        .navbar { background: var(--evsu-maroon) !important; }
        .sidebar { min-height: calc(100vh - 56px); background: white; border-right: 1px solid #dee2e6; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-top: 20px; }
        .calendar-day { background: white; border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; min-height: 120px; cursor: pointer; transition: all 0.2s; }
        .calendar-day:hover { border-color: var(--evsu-gold); box-shadow: 0 2px 8px rgba(255,215,0,0.3); }
        .calendar-day.empty { background: #f8f9fa; cursor: default; }
        .calendar-day.today { border-color: var(--evsu-maroon); background: var(--maroon-light); border-width: 3px; }
        .calendar-day.selected { border-color: var(--evsu-gold); background: #fffbf0; border-width: 3px; box-shadow: 0 4px 12px rgba(255,215,0,0.4); }
        .day-number { font-size: 18px; font-weight: bold; color: #495057; margin-bottom: 8px; }
        .event-indicator { font-size: 11px; padding: 3px 8px; border-radius: 12px; margin: 2px 0; display: inline-block; }
        .event-indicator.pending { background: #fff8e1; color: #f57c00; border: 1px solid var(--gold-dark); }
        .event-indicator.approved { background: #e8f5e9; color: #2e7d32; }
        .event-indicator.disapproved { background: #ffebee; color: #c62828; }
        .stats-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 3px solid var(--evsu-gold); }
        .stats-card h2 { color: var(--evsu-maroon); }
        .request-list-item { background: white; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; border-left: 4px solid #dee2e6; }
        .request-list-item:hover { box-shadow: 0 2px 8px rgba(128,0,0,0.15); transform: translateX(5px); }
        .request-list-item.pending { border-left-color: var(--gold-dark); }
        .request-list-item.approved { border-left-color: #28a745; }
        .request-list-item.disapproved { border-left-color: #dc3545; }
        .badge { padding: 5px 10px; }
        .btn-warning { background-color: var(--evsu-gold); border-color: var(--gold-dark); color: var(--maroon-dark); font-weight: 600; }
        .btn-warning:hover { background-color: var(--gold-dark); border-color: var(--gold-dark); color: white; }
        .btn-primary { background-color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .btn-primary:hover { background-color: var(--maroon-dark); border-color: var(--maroon-dark); }
        .btn-outline-primary { color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .btn-outline-primary:hover { background-color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .text-warning { color: var(--gold-dark) !important; }
        .month-quick-link { font-size: 13px; }
        .month-quick-link .badge { font-size: 11px; }
        .btn-outline-secondary { color: #6c757d; border-color: #6c757d; }
        .btn-outline-secondary:hover { background-color: #6c757d; border-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand">🎓 EVSU Admin Panel</span>
            <div class="d-flex align-items-center">
                <a href="pending_actions.php" class="btn btn-warning btn-sm me-3">
                    <i class="fas fa-bell"></i> Pending Actions (<?= $pendingCount ?>)
                </a>
                <span class="text-white me-3"><?= $_SESSION['full_name'] ?></span>
                <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

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
                        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-chevron-left"></i> Prev
                        </a>
                        <a href="dashboard.php" class="btn btn-sm btn-primary">Today</a>
                        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-sm btn-outline-primary">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
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
                            <a href="?month=<?= $mr['month'] ?>&year=<?= $mr['year'] ?>" 
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
                        $requests = $requestsByDate[$date] ?? [];
                        
                        echo "<div class='calendar-day $isToday $isSelected' onclick='selectDate(\"$date\")'>";
                        echo "<div class='day-number'>$day</div>";
                        
                        foreach ($requests as $req) {
                            echo "<div class='event-indicator {$req['status']}'>";
                            echo htmlspecialchars(substr($req['event_name'], 0, 20));
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
                        <a href="?month=<?= $currentMonth ?>&year=<?= $currentYear ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                <?php else: ?>
                    <h5 class="mb-3">Requests for <?= $monthName ?></h5>
                <?php endif; ?>
                
                <?php if (empty($displayRequests)): ?>
                    <p class="text-muted">
                        <?php if ($selectedDate): ?>
                            No requests on this date.
                        <?php else: ?>
                            No requests for this month.
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($displayRequests as $req): ?>
                        <div class="request-list-item <?= $req['status'] ?>" 
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function selectDate(date) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', date);
            window.location.href = currentUrl.toString();
        }
        
        function viewRequest(id) {
            window.location.href = 'view_request.php?id=' + id;
        }
    </script>
</body>
</html>