<?php
/**
 * CRCY Dispatch System
 * File Download/View Handler
 * File: download.php
 */

require_once 'config.php';
requireAdmin();

// Get file ID
$fileId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'view'; // 'view' or 'download'

if (!$fileId) {
    die('Invalid file ID');
}

$db = getDB();

// Get file information
$stmt = $db->prepare("SELECT * FROM attachments WHERE id = ?");
$stmt->execute([$fileId]);
$file = $stmt->fetch();

if (!$file) {
    die('File not found');
}

// Check if file exists on filesystem
if (!file_exists($file['file_path'])) {
    die('File does not exist on server');
}

// Get file content
$fileContent = file_get_contents($file['file_path']);

// Set headers based on action
if ($action === 'download') {
    // Force download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
} else {
    // View in browser (if possible)
    header('Content-Type: ' . $file['file_type']);
    header('Content-Disposition: inline; filename="' . $file['file_name'] . '"');
}

// Set additional headers
header('Content-Length: ' . $file['file_size']);
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file
echo $fileContent;
exit;
?>