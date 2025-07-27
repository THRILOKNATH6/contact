<?php
require_once '../includes/auth.php';
require_once '../classes/FileManager.php';

$auth = new Auth();
$auth->requireLogin();

if (!isset($_GET['file_id'])) {
    http_response_code(404);
    exit('File not found');
}

$file_id = intval($_GET['file_id']);
$current_user_id = $auth->getCurrentUserId();

$fileManager = new FileManager();
$file_info = $fileManager->downloadFile($file_id, $current_user_id);

if (!$file_info) {
    http_response_code(403);
    exit('Access denied or file not found');
}

// Set appropriate headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file_info['name'] . '"');
header('Content-Length: ' . $file_info['size']);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output the file
readfile($file_info['path']);
exit();
?>