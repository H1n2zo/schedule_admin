<?php
/**
 * CRCY Dispatch System
 * Get Event Details - AJAX Handler
 * File: get_event_details.php
 */

require_once 'config.php';
requireAdmin();

// Set HTML header for modal content
header('Content-Type: text/html; charset=utf-8');

// Check if request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$eventId) {
    echo '<div class="alert alert-danger">Invalid event ID provided.</div>';
    exit;
}

try {
    $db = getDB();
    
    // Get event details
    $stmt = $db->prepare("
        SELECT 
            id, event_name, organization, event_date, event_time, event_end_time,
            volunteers_needed, status, requester_email, requester_name, venue, 
            event_description, submitted_at, expected_participants, contact_number,
            requester_position, volunteer_roles, special_requirements
        FROM support_requests 
        WHERE id = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        echo '<div class="alert alert-danger">Event not found.</div>';
        exit;
    }
    
    // Format event details for display
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
    $eventDate = date('l, F j, Y', strtotime($event['event_date']));

    // Display event in same card style as day events modal
    echo '<div class="card mb-3 ' . $statusClass . '">';
    echo '<div class="card-body">';
    
    // Event header
    echo '<div class="d-flex justify-content-between align-items-start mb-3">';
    echo '<div>';
    echo '<h5 class="card-title mb-1 fw-bold text-uppercase">' . htmlspecialchars($event['event_name']) . '</h5>';
    echo '<p class="text-muted mb-0">' . date('F j, Y', strtotime($event['event_date'])) . ' | ' . $timeRange . '</p>';
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
    if (!empty($event['expected_participants'])) {
        echo '<p class="mb-2"><strong>Expected Participants:</strong> ' . htmlspecialchars($event['expected_participants']) . '</p>';
    }
    echo '</div>';
    echo '<div class="col-md-6">';
    echo '<p class="mb-2"><strong>Requester:</strong> ' . htmlspecialchars($event['requester_name']) . '</p>';
    if (!empty($event['requester_position'])) {
        echo '<p class="mb-2"><strong>Position:</strong> ' . htmlspecialchars($event['requester_position']) . '</p>';
    }
    echo '<p class="mb-2"><strong>Email:</strong> ' . htmlspecialchars($event['requester_email']) . '</p>';
    if (!empty($event['contact_number'])) {
        echo '<p class="mb-2"><strong>Contact Number:</strong> ' . htmlspecialchars($event['contact_number']) . '</p>';
    }
    echo '</div>';
    echo '</div>';

    // Event description
    if (!empty($event['event_description'])) {
        echo '<div class="mt-3">';
        echo '<strong>Event Description:</strong>';
        echo '<p class="mt-1">' . nl2br(htmlspecialchars($event['event_description'])) . '</p>';
        echo '</div>';
    }

    // Volunteer roles
    if (!empty($event['volunteer_roles'])) {
        echo '<div class="mt-3">';
        echo '<strong>Volunteer Roles Needed:</strong>';
        echo '<p class="mt-1">' . nl2br(htmlspecialchars($event['volunteer_roles'])) . '</p>';
        echo '</div>';
    }

    // Special requirements
    if (!empty($event['special_requirements'])) {
        echo '<div class="mt-3">';
        echo '<strong>Special Requirements:</strong>';
        echo '<p class="mt-1">' . nl2br(htmlspecialchars($event['special_requirements'])) . '</p>';
        echo '</div>';
    }

    // Get and display attachments
    $stmt = $db->prepare("SELECT * FROM attachments WHERE request_id = ?");
    $stmt->execute([$eventId]);
    $attachments = $stmt->fetchAll();

    if (!empty($attachments)) {
        echo '<div class="mt-4">';
        echo '<strong><i class="fas fa-paperclip me-1"></i> Attachments (' . count($attachments) . '):</strong>';
        echo '<div class="mt-2">';
        
        foreach ($attachments as $file) {
            $isImage = strpos($file['file_type'], 'image') !== false;
            $isPDF = strpos($file['file_type'], 'pdf') !== false;
            $fileSize = number_format($file['file_size'] / 1024, 2);
            
            echo '<div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">';
            echo '<div class="d-flex align-items-center">';
            
            // File icon
            if ($isImage) {
                echo '<i class="fas fa-image text-primary me-2"></i>';
            } elseif ($isPDF) {
                echo '<i class="fas fa-file-pdf text-danger me-2"></i>';
            } else {
                echo '<i class="fas fa-file text-secondary me-2"></i>';
            }
            
            // File info
            echo '<div>';
            echo '<div class="fw-bold" style="font-size: 0.9rem;">' . htmlspecialchars($file['file_name']) . '</div>';
            echo '<small class="text-muted">' . $fileSize . ' KB</small>';
            echo '</div>';
            echo '</div>';
            
            // Action button
            echo '<div>';
            echo '<a href="download.php?id=' . $file['id'] . '&action=view" target="_blank" class="btn btn-sm btn-outline-primary" title="View file">';
            echo '<i class="fas fa-eye"></i>';
            echo '</a>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }

    // Action buttons
    if ($event['status'] === 'pending') {
        echo '<div class="mt-3 d-flex gap-2">';
        echo '<button class="btn btn-sm btn-success" onclick="approveEvent(' . $event['id'] . ', \'' . htmlspecialchars($event['event_name'], ENT_QUOTES) . '\', \'' . htmlspecialchars($event['organization'], ENT_QUOTES) . '\', \'' . htmlspecialchars($eventDate, ENT_QUOTES) . '\', \'' . htmlspecialchars($timeRange, ENT_QUOTES) . '\')">';
        echo '<i class="fas fa-check me-1"></i>Approve';
        echo '</button>';
        echo '<button class="btn btn-sm btn-danger" onclick="declineEvent(' . $event['id'] . ', \'' . htmlspecialchars($event['event_name'], ENT_QUOTES) . '\', \'' . htmlspecialchars($event['organization'], ENT_QUOTES) . '\', \'' . htmlspecialchars($eventDate, ENT_QUOTES) . '\', \'' . htmlspecialchars($timeRange, ENT_QUOTES) . '\')">';
        echo '<i class="fas fa-times me-1"></i>Decline';
        echo '</button>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    ?>

<?php
} catch (PDOException $e) {
    error_log("Database error in get_event_details.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">';
    echo '<i class="fas fa-exclamation-triangle"></i> ';
    echo 'Error loading event details. Please try again later.';
    echo '</div>';
}
?>