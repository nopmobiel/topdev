<?php
session_start();

// Redirect if not logged in or if User is not set
if (!isset($_SESSION['User'])) {
    header("Location: index.php");
    exit;
}

// Get the user (dienstkortenaam) from the session
$dienstkortenaam = $_SESSION['User'];

// Validate and sanitize the requested filename
$filename = filter_input(INPUT_GET, 'file', FILTER_SANITIZE_STRING);

if (!$filename || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid filename");
}

// Set the path to the protected directory
$uploadDir = "./diensten/" . $dienstkortenaam . "/upload/";
$filepath = $uploadDir . $filename;

// Check if file exists and is within the allowed directory
$realpath = realpath($filepath);

if ($realpath === false || strpos($realpath, realpath($uploadDir)) !== 0) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found");
}

// Check if file is readable
if (!is_readable($filepath)) {
    header("HTTP/1.1 403 Forbidden");
    exit("File is not readable");
}

// Set headers for file download
$mime = mime_content_type($filepath);
header("Content-Type: $mime");
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));

// Output file contents
readfile($filepath);
exit;
?>