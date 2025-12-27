<?php
/**
 * EVSU Event Management System
 * Statistics Page - Comprehensive Analytics
 * File: statistics.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Event Statistics - EVSU Admin';
$customCSS = ['dashboard'];
$customJS = ['dashboard'];

$db = getDB();

// Get filter parameters
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterOrg = isset($_GET['org']) ? $_GET['org'] : 'all';

// Build WHERE clause based on filters
$whereClause = "WHERE status IN ('approved', 'declined')";
$params = [];

if ($filterYear) {
    $whereClause .= " AND YEAR(event_date) = ?";
    $params[] = $filterYear;
}

if ($filterMonth) {
    $whereClause .= " AND MONTH(event_date) = ?";
    $params[] = $filterMonth;
}

if ($filterOrg !== 'all') {
    $whereClause .= " AND organization = ?";
    $params[] = $filterOrg;
}

// Get overall statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as total_declined,
        SUM(CASE WHEN status = 'approved' THEN volunteers_needed ELSE 0 END) as total_volunteers
    FROM event_requests
    $whereClause
");
$stmt->execute($params);
$stats = $stmt->fetch();

// Calculate approval rate
$approvalRate = $stats['total_requests'] > 0 
    ? round(($stats['total_approved'] / $stats['total_requests']) * 100, 1) 
    : 0;

// Get monthly breakdown
$stmt = $db->prepare("
    SELECT 
        YEAR(event_date) as year,
        MONTH(event_date) as month,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
    FROM event_requests
    $whereClause
    GROUP BY YEAR(event_date), MONTH(event_date)
    ORDER BY year, month
");
$stmt->execute($params);
$monthlyData = $stmt->fetchAll();

// Get organization statistics
$orgWhereClause = str_replace("organization = ?", "1=1", $whereClause);
$orgParams = array_filter($params, function($key) use ($filterOrg) {
    return $key !== array_search($filterOrg, $params);
}, ARRAY_FILTER_USE_KEY);

$stmt = $db->prepare("
    SELECT 
        organization,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
        SUM(CASE WHEN status = 'approved' THEN volunteers_needed ELSE 0 END) as volunteers
    FROM event_requests
    $orgWhereClause
    GROUP BY organization
    ORDER BY total DESC
    LIMIT 10
");
$stmt->execute(array_values($orgParams));
$orgStats = $stmt->fetchAll();

// Get top events by volunteers
$stmt = $db->prepare("
    SELECT event_name, organization, event_date, volunteers_needed, status
    FROM event_requests
    $whereClause
    AND status = 'approved'
    ORDER BY volunteers_needed DESC
    LIMIT 10
");
$stmt->execute($params);
$topVolunteerEvents = $stmt->fetchAll();

// Get decline reasons (if you have a decline_reason field)
$stmt = $db->prepare("
    SELECT 
        MONTH(event_date) as month,
        COUNT(*) as count
    FROM event_requests
    $whereClause
    AND status = 'declined'
    GROUP BY MONTH(event_date)
    ORDER BY month
");
$stmt->execute($params);
$declineByMonth = $stmt->fetchAll();

// Get available years and organizations for filters
$stmt = $db->query("
    SELECT DISTINCT YEAR(event_date) as year 
    FROM event_requests 
    WHERE status IN ('approved', 'declined')
    ORDER BY year DESC
");
$availableYears = $stmt->fetchAll();

$stmt = $db->query("
    SELECT DISTINCT organization 
    FROM event_requests 
    WHERE status IN ('approved', 'declined')
    ORDER BY organization
");
$availableOrgs = $stmt->fetchAll();

// Prepare data for charts
$monthlyChartData = [];
$monthlyChartLabels = [];
foreach ($monthlyData as $data) {
    $monthName = date('M', mktime(0, 0, 0, $data['month'], 1));
    $monthlyChartLabels[] = $monthName . ' ' . $data['year'];
    $monthlyChartData['approved'][] = $data['approved'];
    $monthlyChartData['declined'][] = $data['declined'];
}

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-chart-line"></i> Event Statistics</h3>
            <p class="text-muted mb-0">
                <?php if ($filterMonth && $filterYear): ?>
                    Showing data for <?= date('F Y', mktime(0, 0, 0, $filterMonth, 1, $filterYear)) ?>
                <?php elseif ($filterYear): ?>
                    Showing data for <?= $filterYear ?>
                <?php else: ?>
                    Showing all-time statistics
                <?php endif; ?>
                <?php if ($filterOrg !== 'all'): ?>
                    - <?= htmlspecialchars($filterOrg) ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Years</option>
                        <?php foreach ($availableYears as $y): ?>
                            <option value="<?= $y['year'] ?>" <?= $filterYear == $y['year'] ? 'selected' : '' ?>>
                                <?= $y['year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Organization</label>
                    <select name="org" class="form-select" onchange="this.form.submit()">
                        <option value="all">All Organizations</option>
                        <?php foreach ($availableOrgs as $org): ?>
                            <option value="<?= htmlspecialchars($org['organization']) ?>" 
                                    <?= $filterOrg === $org['organization'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($org['organization']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="statistics.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h6 class="text-muted">Total Events</h6>
                <h2 class="text-primary"><?= $stats['total_requests'] ?></h2>
                <small class="text-muted">Approved + Declined</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h6 class="text-muted">Approved Events</h6>
                <h2 class="text-success"><?= $stats['total_approved'] ?></h2>
                <small class="text-success">
                    <?= $approvalRate ?>% approval rate
                </small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h6 class="text-muted">Declined Events</h6>
                <h2 class="text-danger"><?= $stats['total_declined'] ?></h2>
                <small class="text-danger">
                    <?= round(100 - $approvalRate, 1) ?>% decline rate
                </small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h6 class="text-muted">Total Volunteers</h6>
                <h2 class="text-info"><?= $stats['total_volunteers'] ?></h2>
                <small class="text-muted">For approved events</small>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Monthly Trend Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Monthly Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Approval Rate Pie Chart -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Approval Rate</h5>
                </div>
                <div class="card-body">
                    <canvas id="approvalPieChart"></canvas>
                    <div class="text-center mt-3">
                        <h3 class="mb-0"><?= $approvalRate ?>%</h3>
                        <small class="text-muted">Overall Approval Rate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Organizations -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-building"></i> Top Organizations</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Organization</th>
                                    <th class="text-center">Total Events</th>
                                    <th class="text-center">Approved</th>
                                    <th class="text-center">Declined</th>
                                    <th class="text-center">Approval Rate</th>
                                    <th class="text-center">Volunteers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach ($orgStats as $org): 
                                    $orgApprovalRate = $org['total'] > 0 
                                        ? round(($org['approved'] / $org['total']) * 100, 1) 
                                        : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge">#<?= $rank++ ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($org['organization']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $org['total'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?= $org['approved'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= $org['declined'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 25px; min-width: 100px;">
                                            <div class="progress-bar bg-success" 
                                                 style="width: <?= $orgApprovalRate ?>%"
                                                 role="progressbar">
                                                <?= $orgApprovalRate ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $org['volunteers'] ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($orgStats)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No data available for the selected filters
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Events by Volunteers -->
    <?php if (!empty($topVolunteerEvents)): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Top Events by Volunteer Count</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Organization</th>
                                    <th>Date</th>
                                    <th class="text-center">Volunteers</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topVolunteerEvents as $event): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($event['event_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($event['organization']) ?></td>
                                    <td><?= formatDate($event['event_date']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info" style="font-size: 14px;">
                                            <i class="fas fa-users"></i> <?= $event['volunteers_needed'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Export Options -->
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-download"></i> Export Statistics</h6>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-success" onclick="exportToCSV()">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Monthly Trend Chart
const monthlyCtx = document.getElementById('monthlyTrendChart');
if (monthlyCtx) {
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthlyChartLabels) ?>,
            datasets: [
                {
                    label: 'Approved',
                    data: <?= json_encode($monthlyChartData['approved'] ?? []) ?>,
                    backgroundColor: 'rgba(46, 125, 50, 0.8)',
                    borderColor: 'rgba(46, 125, 50, 1)',
                    borderWidth: 2
                },
                {
                    label: 'Declined',
                    data: <?= json_encode($monthlyChartData['declined'] ?? []) ?>,
                    backgroundColor: 'rgba(198, 40, 40, 0.8)',
                    borderColor: 'rgba(198, 40, 40, 1)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Approval Rate Pie Chart
const pieCtx = document.getElementById('approvalPieChart');
if (pieCtx) {
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Declined'],
            datasets: [{
                data: [<?= $stats['total_approved'] ?>, <?= $stats['total_declined'] ?>],
                backgroundColor: [
                    'rgba(46, 125, 50, 0.8)',
                    'rgba(198, 40, 40, 0.8)'
                ],
                borderColor: [
                    'rgba(46, 125, 50, 1)',
                    'rgba(198, 40, 40, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Export to CSV function
function exportToCSV() {
    // Create CSV content
    let csv = 'Organization Statistics\n\n';
    csv += 'Organization,Total Events,Approved,Declined,Approval Rate,Volunteers\n';
    
    <?php foreach ($orgStats as $org): 
        $orgApprovalRate = $org['total'] > 0 ? round(($org['approved'] / $org['total']) * 100, 1) : 0;
    ?>
    csv += '<?= addslashes($org['organization']) ?>,<?= $org['total'] ?>,<?= $org['approved'] ?>,<?= $org['declined'] ?>,<?= $orgApprovalRate ?>%,<?= $org['volunteers'] ?>\n';
    <?php endforeach; ?>
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'evsu_statistics_<?= date('Y-m-d') ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<style>
.rank-badge {
    display: inline-block;
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
    color: var(--evsu-gold);
    border-radius: 50%;
    text-align: center;
    line-height: 35px;
    font-weight: 700;
    font-size: 14px;
}

.progress {
    background-color: #e9ecef;
}

.progress-bar {
    font-weight: 600;
    font-size: 12px;
}

@media print {
    .navbar,
    .btn,
    .card:last-child {
        display: none !important;
    }
    
    body {
        background: white !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        page-break-inside: avoid;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>
