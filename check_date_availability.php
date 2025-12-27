<?php
/**
 * CRCY Dispatch System
 * Date and Time Availability API - Real-time conflict checking
 * File: check_date_availability.php
 */

require_once 'config.php';

header('Content-Type: application/json');

// Handle time-based conflict checking
if (isset($_POST['date']) && isset($_POST['time']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $endTime = $_POST['end_time'] ?? null;
    
    // Validate inputs
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
        echo json_encode(['error' => 'Invalid date or time format']);
        exit;
    }
    
    try {
        // Use the existing checkDateConflict function from config.php
        $conflicts = checkDateConflict($date, $time, $endTime);
        
        if (!empty($conflicts)) {
            $conflict = $conflicts[0]; // Get first conflict
            echo json_encode([
                'conflict' => true,
                'date' => $date,
                'time' => $time,
                'conflicting_event' => [
                    'name' => $conflict['event_name'],
                    'time' => formatTime($conflict['event_time']),
                    'venue' => $conflict['venue']
                ]
            ]);
        } else {
            echo json_encode([
                'conflict' => false,
                'date' => $date,
                'time' => $time
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error checking conflicts: ' . $e->getMessage()]);
    }
    exit;
}

// Handle date-only checking (for backward compatibility)
if ((isset($_POST['date']) && $_SERVER['REQUEST_METHOD'] === 'POST') || isset($_GET['date'])) {
    $date = $_POST['date'] ?? $_GET['date'];
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['error' => 'Invalid date format']);
        exit;
    }
    
    try {
        $db = getDB();
        
        // Get all approved events on this date with their times
        $stmt = $db->prepare("
            SELECT event_name, organization, event_time, event_end_time, venue
            FROM support_requests 
            WHERE event_date = ? AND status = 'approved'
            ORDER BY event_time ASC
        ");
        $stmt->execute([$date]);
        $events = $stmt->fetchAll();
        
        if (!empty($events)) {
            echo json_encode([
                'conflict' => true,
                'date' => $date,
                'events' => array_map(function($event) {
                    return [
                        'name' => $event['event_name'],
                        'organization' => $event['organization'],
                        'time' => formatTime($event['event_time']),
                        'end_time' => $event['event_end_time'] ? formatTime($event['event_end_time']) : null,
                        'venue' => $event['venue']
                    ];
                }, $events),
                'message' => 'This date has ' . count($events) . ' scheduled event(s). Please check times for conflicts.'
            ]);
        } else {
            echo json_encode([
                'conflict' => false,
                'date' => $date
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// Otherwise, return ALL occupied dates (for blocking in date picker)
try {
    $db = getDB();
    
    // Get all dates that have approved events
    $stmt = $db->query("
        SELECT event_date, event_name, organization 
        FROM support_requests 
        WHERE status = 'approved'
        ORDER BY event_date ASC
    ");
    
    $occupiedDates = [];
    $count = 0;
    while ($row = $stmt->fetch()) {
        $occupiedDates[$row['event_date']] = [
            'event_name' => $row['event_name'],
            'organization' => $row['organization']
        ];
        $count++;
    }
    
    error_log("Found $count occupied dates");
    error_log("Occupied dates: " . json_encode(array_keys($occupiedDates)));
    
    echo json_encode([
        'success' => true,
        'occupied_dates' => $occupiedDates,
        'count' => $count
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>