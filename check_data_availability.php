<?php
/**
 * EVSU Event Management System
 * Date Availability API
 * File: check_date_availability.php
 */

require_once 'config.php';

header('Content-Type: application/json');

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
    while ($row = $stmt->fetch()) {
        $occupiedDates[$row['event_date']] = [
            'event_name' => $row['event_name'],
            'organization' => $row['organization']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'occupied_dates' => $occupiedDates
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>