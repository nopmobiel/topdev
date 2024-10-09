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
    showErrorAndExit('Ongeldige bestandsnaam. Probeer het opnieuw.');
}

// Set the path to the protected directory
$uploadDir = "./diensten/" . $dienstkortenaam . "/upload/";
$filepath = $uploadDir . $filename;

// Check if file exists and is within the allowed directory
$realpath = realpath($filepath);

if ($realpath === false || strpos($realpath, realpath($uploadDir)) !== 0) {
    showErrorAndExit('Bestand niet gevonden. Het kan zijn dat het bestand is verlopen of nog niet is aangemaakt.');
}

// Check if file is readable
if (!is_readable($filepath)) {
    showErrorAndExit('Het bestand kan niet worden gelezen.');
}

// Set headers for file download
$mime = mime_content_type($filepath);
header("Content-Type: $mime");
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));

// Output file contents
readfile($filepath);
exit;

function showErrorAndExit($message) {
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Foutmelding</title>
        <link rel="stylesheet" href="site.css">
    </head>
    <body>
    <h2>Uitzonderingen downloaden</h2>
        <div class="container">
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <button onclick="window.history.back()" class="btn-primary">Ga terug</button>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>