<?php
/**
 * CRCY Dispatch System
 * Update Event Status - AJAX Handler
 * File: update_status.php
 */

require_once 'config.php';
requireAdmin();

// Set JSON header
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$eventId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';
$declineReason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// Validate inputs
if (!$eventId || !in_array($newStatus, ['pending', 'approved', 'declined'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

try {
    $db = getDB();
    
    // Get current event details
    $stmt = $db->prepare("SELECT * FROM support_requests WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }
    
    // Handle approval with conflict checking
    if ($newStatus === 'approved') {
        // First, update the current event to approved
        $stmt = $db->prepare("
            UPDATE support_requests 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $eventId]);
        
        // Find conflicting events on the same date
        $stmt = $db->prepare("
            SELECT id, event_name, event_time, event_end_time, organization, requester_name, requester_email
            FROM support_requests 
            WHERE event_date = ? 
            AND id != ? 
            AND status = 'pending'
            AND (
                (event_time <= ? AND (event_end_time IS NULL OR event_end_time > ?)) OR
                (event_time < ? AND event_end_time > ?) OR
                (event_time >= ? AND event_time < ?)
            )
        ");
        
        $eventDate = $event['event_date'];
        $eventStartTime = $event['event_time'];
        $eventEndTime = $event['event_end_time'] ?: $event['event_time']; // Use start time if no end time
        
        $stmt->execute([
            $eventDate, 
            $eventId,
            $eventStartTime, $eventStartTime,  // Conflicts that start before and end after our start
            $eventEndTime, $eventStartTime,    // Conflicts that start before our end and end after our start
            $eventStartTime, $eventEndTime     // Conflicts that start within our time range
        ]);
        
        $conflictingEvents = $stmt->fetchAll();
        
        // Auto-reject conflicting events
        if (!empty($conflictingEvents)) {
            $conflictIds = array_column($conflictingEvents, 'id');
            $placeholders = str_repeat('?,', count($conflictIds) - 1) . '?';
            
            $autoRejectReason = "Automatically declined due to time conflict with approved event: '{$event['event_name']}' on " . 
                               date('F j, Y', strtotime($eventDate)) . " at " . 
                               date('g:i A', strtotime($eventStartTime));
            
            $stmt = $db->prepare("
                UPDATE support_requests 
                SET status = 'declined', rejection_reason = ?, updated_at = NOW() 
                WHERE id IN ($placeholders)
            ");
            
            $params = array_merge([$autoRejectReason], $conflictIds);
            $stmt->execute($params);
            
            // Log the auto-rejections
            foreach ($conflictingEvents as $conflictEvent) {
                logSecurityEvent('auto_rejection', [
                    'rejected_event_id' => $conflictEvent['id'],
                    'rejected_event_name' => $conflictEvent['event_name'],
                    'approved_event_id' => $eventId,
                    'approved_event_name' => $event['event_name'],
                    'conflict_date' => $eventDate,
                    'admin_user' => $_SESSION['username'] ?? 'unknown'
                ]);
            }
        }
        
        $conflictCount = count($conflictingEvents);
        $successMessage = "Event '{$event['event_name']}' has been approved.";
        if ($conflictCount > 0) {
            $successMessage .= " Additionally, $conflictCount conflicting event(s) were automatically declined.";
        }
        
    } elseif ($newStatus === 'declined' && $declineReason) {
        // Handle manual decline with reason
        $stmt = $db->prepare("
            UPDATE support_requests 
            SET status = ?, rejection_reason = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $declineReason, $eventId]);
        $successMessage = "Event '{$event['event_name']}' has been declined.";
        
    } else {
        // Handle other status updates
        $stmt = $db->prepare("
            UPDATE support_requests 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $eventId]);
        $successMessage = "Event '{$event['event_name']}' status updated to " . ucfirst($newStatus) . ".";
    }
    
    // Log the action
    logSecurityEvent('status_update', [
        'event_id' => $eventId,
        'event_name' => $event['event_name'],
        'old_status' => $event['status'],
        'new_status' => $newStatus,
        'admin_user' => $_SESSION['username'] ?? 'unknown'
    ]);
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => $successMessage
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in update_status.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred. Please try again.'
    ]);
}
?>