<?php
/**
 * EVSU Event Management System
 * Notification History Page
 * File: history.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Notification History - EVSU Admin';
$customCSS = ['dashboard'];
$customJS = ['dashboard'];

$db = getDB();

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Build query based on filters
$whereClause = "WHERE 1=1";
$params = [];

if ($filterStatus !== 'all') {
    $whereClause .= " AND er.status = ?";
    $params[] = $filterStatus;
}

if ($filterMonth && $filterYear) {
    $whereClause .= " AND MONTH(er.event_date) = ? AND YEAR(er.event_date) = ?";
    $params[] = $filterMonth;
    $params[] = $filterYear;
}

// Fetch notification history
$stmt = $db->prepare("
SELECT 
    er.*,
    u.full_name AS admin_name,
    nh.sent_at,
    nh.subject AS notification_subject,
    nh.attachments_sent,
    nh.action_type
FROM event_requests er
INNER JOIN notification_history nh ON nh.request_id = er.id -- Changed from LEFT JOIN
LEFT JOIN users u ON nh.admin_id = u.id
$whereClause
AND er.status IN ('approved', 'declined')
ORDER BY nh.sent_at DESC
");
$stmt->execute($params);
$history = $stmt->fetchAll();

// Get stats
$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'approved'");
$totalApproved = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'declined'");
$totalDeclined = $stmt->fetchColumn();

$totalProcessed = $totalApproved + $totalDeclined;

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container py-4">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <h6 class="text-muted">Total Processed</h6>
                <h2 class="text-primary"><?= $totalProcessed ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h6 class="text-muted">Approved</h6>
                <h2 class="text-success"><?= $totalApproved ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h6 class="text-muted">Declined</h6>
                <h2 class="text-danger"><?= $totalDeclined ?></h2>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-history"></i> Notification History
            </h5>
            <div class="d-flex gap-2">
                <!-- Status Filter -->
                <select class="form-select form-select-sm" style="width: auto;" 
                        onchange="window.location.href='history.php?status=' + this.value + '&month=<?= $filterMonth ?>&year=<?= $filterYear ?>'">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="declined" <?= $filterStatus === 'declined' ? 'selected' : '' ?>>Declined</option>
                </select>
                
                <!-- Month Filter -->
                <select class="form-select form-select-sm" style="width: auto;"
                        onchange="window.location.href='history.php?status=<?= $filterStatus ?>&month=' + this.value + '&year=<?= $filterYear ?>'">
                    <option value="">All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <!-- Year Filter -->
                <select class="form-select form-select-sm" style="width: auto;"
                        onchange="window.location.href='history.php?status=<?= $filterStatus ?>&month=<?= $filterMonth ?>&year=' + this.value">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($history)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No notification history found with the current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Organization</th>
                                <th>Event Date</th>
                                <th>Status</th>
                                <th>Reviewed By</th>
                                <th>Notification Sent</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['event_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($item['organization']) ?></td>
                                    <td>
                                        <?= formatDate($item['event_date']) ?>
                                        <br>
                                        <small class="text-muted"><?= formatTime($item['event_time']) ?></small>
                                    </td>
                                    <td>
                                        <?= getStatusBadge($item['status']) ?>
                                        <br>
                                        <small class="text-muted"><?= ucfirst($item['action_type']) ?></small>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($item['admin_name'] ?? 'N/A') ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= $item['reviewed_at'] ? date('M j, Y', strtotime($item['reviewed_at'])) : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($item['sent_at']): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                            <?= date('M j, Y g:i A', strtotime($item['sent_at'])) ?>
                                            <?php if ($item['attachments_sent']): ?>
                                                <br><small class="text-muted"><i class="fas fa-paperclip"></i> With attachments</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not sent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="view_request.php?id=<?= $item['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h6><i class="fas fa-info-circle"></i> About Notification History</h6>
        <ul class="mb-0">
            <li>This page shows all approved and declined event requests</li>
            <li>Filter by status, month, or year to find specific records</li>
            <li>Click "View" to see full details of any request</li>
            <li>Notifications are automatically sent when you approve or decline a request</li>
        </ul>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>