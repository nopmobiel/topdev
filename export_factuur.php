<?php
// export_factuur.php - Export tblFactuur to CSV

require_once("settings.php");

// Set headers to force download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=facturen_export_' . date('Ymd_His') . '.csv');

// Connect to DB
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Query all rows from tblFactuur
$result = $mysqli->query("SELECT * FROM tblFactuur ORDER BY datum DESC");
if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Output CSV
$output = fopen('php://output', 'w');

// Output column headers
if ($row = $result->fetch_assoc()) {
    fputcsv($output, array_keys($row));
    fputcsv($output, array_values($row));
    // Output the rest
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$mysqli->close();
exit;
?> 