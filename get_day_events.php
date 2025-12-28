<?php
/**
 * CRCY Dispatch System
 * Get Day Events - AJAX endpoint for day events modal
 * File: get_day_events.php
 */

// Ensure we output HTML, not JSON
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';
requireAdmin();

// Get the date parameter
$date = isset($_GET['date']) ? $_GET['date'] : null;

if (!$date) {
    echo '<div class="alert alert-danger">Invalid date provided.</div>';
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo '<div class="alert alert-danger">Invalid date format.</div>';
    exit;
}

$db = getDB();

try {
    // Get all events for the specified date
    $stmt = $db->prepare("
        SELECT 
            id, event_name, organization, event_date, event_time, event_end_time,
            volunteers_needed, status, requester_email, requester_name, venue, event_description
        FROM support_requests 
        WHERE event_date = ?
        ORDER BY event_time
    ");
    $stmt->execute([$date]);
    $events = $stmt->fetchAll();

    if (empty($events)) {
        echo '<div class="alert alert-info text-center">';
        echo '<i class="fas fa-calendar-times fa-3x mb-3 text-muted"></i>';
        echo '<h5>No Events Scheduled</h5>';
        echo '<p class="text-muted">There are no events scheduled for this day.</p>';
        echo '</div>';
        exit;
    }

    // Display events
    foreach ($events as $event) {
        $statusClass = '';
        $statusIcon = '';
        $statusText = '';
        
        switch ($event['status']) {
            case 'pending':
                $statusClass = 'border-warning border-3';
                $statusIcon = 'fas fa-clock text-warning';
                $statusText = 'Under Review';
                break;
            case 'approved':
                $statusClass = 'border-success border-3';
                $statusIcon = 'fas fa-check-circle text-success';
                $statusText = 'Approved';
                break;
            case 'declined':
                $statusClass = 'border-danger border-3';
                $statusIcon = 'fas fa-times-circle text-danger';
                $statusText = 'Declined';
                break;
        }

        $startTime = date('g:i A', strtotime($event['event_time']));
        $endTime = $event['event_end_time'] ? date('g:i A', strtotime($event['event_end_time'])) : null;
        $timeRange = $endTime ? "$startTime - $endTime" : $startTime;

        echo '<div class="card mb-3 ' . $statusClass . '">';
        echo '<div class="card-body">';
        
        // Event header
        echo '<div class="d-flex justify-content-between align-items-start mb-3">';
        echo '<div>';
        echo '<h5 class="card-title mb-1">' . htmlspecialchars($event['event_name']) . '</h5>';
        echo '<p class="text-muted mb-0"><i class="fas fa-clock me-1"></i>' . $timeRange . '</p>';
        echo '</div>';
        echo '<span class="badge bg-light text-dark border">';
        echo '<i class="' . $statusIcon . ' me-1"></i>' . $statusText;
        echo '</span>';
        echo '</div>';

        // Event details
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<p class="mb-2"><strong>Organization:</strong> ' . htmlspecialchars($event['organization']) . '</p>';
        echo '<p class="mb-2"><strong>Venue:</strong> ' . htmlspecialchars($event['venue']) . '</p>';
        echo '<p class="mb-2"><strong>Volunteers Needed:</strong> ' . htmlspecialchars($event['volunteers_needed']) . '</p>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<p class="mb-2"><strong>Requester:</strong> ' . htmlspecialchars($event['requester_name']) . '</p>';
        echo '<p class="mb-2"><strong>Email:</strong> ' . htmlspecialchars($event['requester_email']) . '</p>';
        echo '</div>';
        echo '</div>';

        // Event description
        if (!empty($event['event_description'])) {
            echo '<div class="mt-3">';
            echo '<strong>Description:</strong>';
            echo '<p class="mt-1">' . nl2br(htmlspecialchars($event['event_description'])) . '</p>';
            echo '</div>';
        }

        // Action buttons
        echo '<div class="mt-3 d-flex gap-2">';
        echo '<button class="btn btn-sm btn-outline-primary" onclick="viewEvent(' . $event['id'] . ', event)">';
        echo '<i class="fas fa-eye me-1"></i>View Details';
        echo '</button>';
        
        if ($event['status'] === 'pending') {
            $eventDate = date('l, F j, Y', strtotime($event['event_date']));
            echo '<button class="btn btn-sm btn-success" onclick="approveEvent(' . $event['id'] . ', \'' . htmlspecialchars($event['event_name'], ENT_QUOTES) . '\', \'' . htmlspecialchars($event['organization'], ENT_QUOTES) . '\', \'' . htmlspecialchars($eventDate, ENT_QUOTES) . '\', \'' . htmlspecialchars($timeRange, ENT_QUOTES) . '\')">';
            echo '<i class="fas fa-check me-1"></i>Approve';
            echo '</button>';
            echo '<button class="btn btn-sm btn-danger" onclick="declineEvent(' . $event['id'] . ', \'' . htmlspecialchars($event['event_name'], ENT_QUOTES) . '\', \'' . htmlspecialchars($event['organization'], ENT_QUOTES) . '\', \'' . htmlspecialchars($eventDate, ENT_QUOTES) . '\', \'' . htmlspecialchars($timeRange, ENT_QUOTES) . '\')">';
            echo '<i class="fas fa-times me-1"></i>Decline';
            echo '</button>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

} catch (PDOException $e) {
    error_log("Database error in get_day_events.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-exclamation-triangle"></i> ';
    echo 'Error loading events. Please try again later.';
    echo '</div>';
}
?>