<?php
/**
 * EVSU Event Management System
 * Notification History Page with Pagination - FIXED stats card styling
 * File: history.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Notification History - EVSU Admin';
$customCSS = ['dashboard'];
$customJS = ['dashboard'];

$db = getDB();

// Pagination settings
$itemsPerPage = 20;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;

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

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) 
    FROM event_requests er
    $whereClause
    AND er.status IN ('approved', 'declined')
";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Get all approved and declined requests with pagination
$stmt = $db->prepare("
    SELECT 
        er.*,
        u.full_name as admin_name,
        nh.sent_at,
        nh.subject as notification_subject,
        nh.attachments_sent
    FROM event_requests er
    LEFT JOIN users u ON er.reviewed_by = u.id
    LEFT JOIN notification_history nh ON er.id = nh.request_id
    $whereClause
    AND er.status IN ('approved', 'declined')
    ORDER BY er.event_date DESC, er.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $itemsPerPage;
$params[] = $offset;
$stmt->execute($params);
$history = $stmt->fetchAll();

// Get stats
$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'approved'");
$totalApproved = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'declined'");
$totalDeclined = $stmt->fetchColumn();

$totalProcessed = $totalApproved + $totalDeclined;

// Get available months/years for filter
$stmt = $db->query("
    SELECT DISTINCT 
        YEAR(event_date) as year, 
        MONTH(event_date) as month
    FROM event_requests 
    WHERE status IN ('approved', 'declined')
    ORDER BY year DESC, month DESC
");
$availableMonths = $stmt->fetchAll();

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container py-4">
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <a href="history.php" class="stats-card-link">
                <div class="stats-card">
                    <h6 class="text-muted">Total Events</h6>
                    <h2 class="text-primary"><?= $totalProcessed ?></h2>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="history.php?status=approved" class="stats-card-link">
                <div class="stats-card">
                    <h6 class="text-muted">Approved</h6>
                    <h2 class="text-success"><?= $totalApproved ?></h2>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="history.php?status=declined" class="stats-card-link">
                <div class="stats-card">
                    <h6 class="text-muted">Declined</h6>
                    <h2 class="text-danger"><?= $totalDeclined ?></h2>
                </div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">
                <i class="fas fa-history"></i> Notification History
                <?php if ($totalItems > 0): ?>
                    <span class="badge bg-secondary"><?= $totalItems ?> total</span>
                <?php endif; ?>
            </h5>
            <div class="d-flex gap-2 flex-wrap">
                <!-- Status Filter -->
                <select class="form-select form-select-sm" style="width: auto;" 
                        onchange="window.location.href='history.php?status=' + this.value + '&month=<?= $filterMonth ?>&year=<?= $filterYear ?>'">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="declined" <?= $filterStatus === 'declined' ? 'selected' : '' ?>>Declined</option>
                </select>
                
                <!-- Month/Year Filter -->
                <select class="form-select form-select-sm" style="width: auto;"
                        onchange="window.location.href='history.php?status=<?= $filterStatus ?>&month=' + this.value.split('-')[0] + '&year=' + this.value.split('-')[1]">
                    <option value="0-0">All Time</option>
                    <?php foreach ($availableMonths as $m): ?>
                        <option value="<?= $m['month'] ?>-<?= $m['year'] ?>" 
                                <?= ($filterMonth == $m['month'] && $filterYear == $m['year']) ? 'selected' : '' ?>>
                            <?= date('F Y', mktime(0, 0, 0, $m['month'], 1, $m['year'])) ?>
                        </option>
                    <?php endforeach; ?>
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
                                <th>Event Date & Time</th>
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
                                        <small class="text-muted">
                                            <?= formatTime($item['event_time']) ?>
                                            <?php if ($item['event_end_time']): ?>
                                                - <?= formatTime($item['event_end_time']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><?= getStatusBadge($item['status']) ?></td>
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
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center p-3 border-top">
                    <div class="text-muted">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $itemsPerPage, $totalItems) ?> of <?= $totalItems ?> events
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <!-- Previous -->
                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $currentPage - 1 ?>&status=<?= $filterStatus ?>&month=<?= $filterMonth ?>&year=<?= $filterYear ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page numbers -->
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . $filterStatus . '&month=' . $filterMonth . '&year=' . $filterYear . '">1</a></li>';
                                if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $activeClass = $i == $currentPage ? 'active' : '';
                                echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="?page=' . $i . '&status=' . $filterStatus . '&month=' . $filterMonth . '&year=' . $filterYear . '">' . $i . '</a></li>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&status=' . $filterStatus . '&month=' . $filterMonth . '&year=' . $filterYear . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <!-- Next -->
                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $currentPage + 1 ?>&status=<?= $filterStatus ?>&month=<?= $filterMonth ?>&year=<?= $filterYear ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h6><i class="fas fa-info-circle"></i> About Notification History</h6>
        <ul class="mb-0">
            <li>This page shows all approved and declined event requests, sorted by event date (January to December)</li>
            <li>Use the filters to find specific events by status or time period</li>
            <li>Click "View" to see full details of any request</li>
            <li>Notifications are automatically sent when you approve or decline a request</li>
            <li>Showing <?= $itemsPerPage ?> events per page for easier navigation</li>
        </ul>
    </div>
</div>

<style>
.pagination .page-link {
    color: var(--evsu-maroon);
    border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
    background-color: var(--evsu-maroon);
    border-color: var(--evsu-maroon);
    color: white;
}

.pagination .page-link:hover {
    background-color: var(--maroon-light);
    border-color: var(--evsu-gold);
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    background-color: #fff;
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
</style>

<?php
// Include footer
include 'includes/footer.php';
?>