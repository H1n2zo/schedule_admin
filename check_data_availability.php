<?php
/**
 * EVSU Event Management System
 * Check Date Availability API
 * File: check_date_availability.php
 */

require_once 'config.php';

header('Content-Type: application/json');

$date = isset($_GET['date']) ? $_GET['date'] : '';

if (empty($date)) {
    echo json_encode(['error' => 'No date provided']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

try {
    $db = getDB();
    
    // Check if date has any approved events
    $stmt = $db->prepare("
        SELECT COUNT(*) as count, event_name, organization 
        FROM event_requests 
        WHERE event_date = ? AND status = 'approved'
        GROUP BY event_name, organization
        LIMIT 1
    ");
    $stmt->execute([$date]);
    $result = $stmt->fetch();
    
    if ($result && $result['count'] > 0) {
        echo json_encode([
            'available' => false,
            'date' => $date,
            'conflict' => [
                'event_name' => $result['event_name'],
                'organization' => $result['organization']
            ]
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'date' => $date
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>