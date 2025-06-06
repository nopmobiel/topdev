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
// Using filter_var instead of deprecated FILTER_SANITIZE_STRING
$filename = filter_input(INPUT_GET, 'file', FILTER_DEFAULT);
$filename = $filename ? trim(filter_var($filename, FILTER_UNSAFE_RAW)) : '';

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
        <title>Uitzonderingen downloaden</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="site.css">
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-md-8">
                    <div class="form-container">
                        <div class="form-header">
                            <h3>Uitzonderingen downloaden</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                            <div class="text-center mt-4">
                                <button onclick="window.history.back()" class="btn btn-primary">Ga terug</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="site.js"></script>
    </body>
    </html>
    <?php
    exit;
}
?>