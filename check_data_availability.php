<?php
/**
 * EVSU Event Management System
 * Date Availability API - Returns ALL occupied dates
 * File: check_date_availability.php
 * 
 * IMPORTANT: Save this file as "check_date_availability.php" (not check_DATA_availability.php)
 */

require_once 'config.php';

header('Content-Type: application/json');

// Enable error logging for debugging
error_log("Date availability check requested");

// If a specific date is requested, check that date
if (isset($_GET['date'])) {
    $date = $_GET['date'];
    
    error_log("Checking specific date: " . $date);
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        error_log("Invalid date format: " . $date);
        echo json_encode(['error' => 'Invalid date format']);
        exit;
    }
    
    try {
        $db = getDB();
        
        // Check if this specific date has an approved event
        $stmt = $db->prepare("
            SELECT event_name, organization 
            FROM event_requests 
            WHERE event_date = ? AND status = 'approved'
            LIMIT 1
        ");
        $stmt->execute([$date]);
        $result = $stmt->fetch();
        
        if ($result) {
            error_log("Date $date is OCCUPIED by: " . $result['event_name']);
            echo json_encode([
                'available' => false,
                'date' => $date,
                'conflict' => [
                    'event_name' => $result['event_name'],
                    'organization' => $result['organization']
                ]
            ]);
        } else {
            error_log("Date $date is AVAILABLE");
            echo json_encode([
                'available' => true,
                'date' => $date
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
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
        FROM event_requests 
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