<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['DienstID']) || !isset($_SESSION['Systeem'])) {
    header("Location: index.php");
    exit();
}

require_once("settings.php");
require_once("import.php");
require_once("functions.php");

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed");
}

// Variables
$dienstID = $_SESSION['DienstID'];
$dienstkortenaam = $_SESSION['User'];
$dienstnaam = $_SESSION['Dienstnaam'];
$systeem = $_SESSION['Systeem'];

// Upload directory
$uploadDir = "./diensten/" . $dienstkortenaam . "/upload/";

// Ensure the upload directory exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = "";

// Check if file was uploaded without errors
if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] == 0) {
    $uploadedFile = $_FILES['fileToUpload']['tmp_name'];
    $originalFileName = basename($_FILES['fileToUpload']['name']);
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    // Validate file extension
    $allowedExtensions = array('csv');
    if (!in_array($fileExtension, $allowedExtensions)) {
        $message = "Fout: Alleen csv-bestanden zijn toegestaan.";
    } else {
        // Check filename format
        $filenamePattern = '/^(ED|ed)(\d{6})\.csv$/';
        if (!preg_match($filenamePattern, $originalFileName, $matches)) {
            $message = "Fout: Ongeldige bestandsnaamindeling. Het moet EDjjmmdd.csv of edjjmmdd.csv zijn";
        } else {
            $fileDate = $matches[2];
            $currentDate = date('ymd');
            
            if ($fileDate !== $currentDate) {
                $message = "Fout: De bestandsdatum komt niet overeen met de datum van vandaag. Verwacht: ED{$currentDate}.csv";
            } else {
                // Generate a unique filename
                $newFileName = uniqid() . '.' . $fileExtension;
                $destinationPath = $uploadDir . $newFileName;

                // Move the uploaded file to the destination
                if (move_uploaded_file($uploadedFile, $destinationPath)) {
                    // File uploaded successfully
                    $orgFileName = $uploadDir . $originalFileName . '.org';

                    // Create a copy of the original file
                    if (copy($destinationPath, $orgFileName)) {
                        $lineCount = countLines($destinationPath);
                        
                        // Determine the correct import type and table name
                        $importType = strtolower($systeem);
                        $tableName = "tblPrint" . $dienstID;
                        
                        // Process the file based on the import type and table name
                        if ($importType === 'trodis') {
                            trodis2tdas($destinationPath);

                            $importResult = importTrodis($destinationPath, $tableName);
                        } elseif ($importType === 'porta2') {
                            $importResult = importAvita($destinationPath, $tableName);
                        } else {
                            $importResult = false;
                            $message = "Onbekend importtype: " . $importType;
                        }
                        
                        if ($importResult === true) {
                            // Insert the header record
                            insertHeaderRecord($dienstID, $systeem);

                            // Import the rest of the records into tblWord<dienstId>
                            $wordTableName = "tblWord" . $dienstID;
                            
                            if ($importType === 'trodis') {
                                $wordImportResult = importTrodis($destinationPath, $wordTableName);
                            } elseif ($importType === 'porta2') {
                                $wordImportResult = importAvita($destinationPath, $wordTableName);
                            } else {
                                $wordImportResult = false;
                                $message .= " Onbekend importtype voor word tabel: " . $importType;
                            }

                            if ($wordImportResult === true) {
                                // Process barcodes for the word table
                                $barcodeProcessResult = processWordTableBarcodes($dienstID, $systeem);

                                if ($barcodeProcessResult === true) {
                                    $message = "Bestand succesvol geüpload en verwerkt. " . 
                                               "Het bestand bevat " . $lineCount . " regels. " .
                                               "Header record en word tabel succesvol geïmporteerd. " .
                                               "Barcodes zijn bijgewerkt.";
                                } else {
                                    $message = "Bestand geüpload, word tabel geïmporteerd, maar er was een fout bij het bijwerken van de barcodes. Controleer de error log voor details.";
                                }
                            } else {
                                $message = "Bestand geüpload en header record ingevoegd, maar er was een fout bij het importeren van de word tabel. Controleer de error log voor details.";
                            }
                        } else {
                            $message = "Bestand geüpload, maar er was een fout bij het verwerken. Controleer de error log voor details.";
                        }
                    } else {
                        $message = "Bestand geüpload, maar het maken van een kopie van het originele bestand is mislukt.";
                    }
                } else {
                    $message = "Sorry, er is een fout opgetreden bij het uploaden van uw bestand.";
                }
            }
        }
    }
} else {
    $message = "Fout: " . $_FILES['fileToUpload']['error'];
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestandsupload Resultaat - TOP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white">
                <h3 class="text-center py-3">Menu</h3>
                <nav class="list-group list-group-flush">
                    <a href="upload.php" class="list-group-item list-group-item-action bg-dark text-white">Upload</a>
                    <a href="frmexceptions.php" class="list-group-item list-group-item-action bg-dark text-white">Uitzonderingen</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark text-white">Rapporten</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark text-white">Service</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark text-white">Download</a>
                    <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Afmelden</a>
                </nav>
            </div>

            <!-- Main Content -->
            <main class="col-md-10">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Bestandsupload Resultaat</h1>
                    </div>
                    <div class="card-body">
                        <p>U bent aangemeld als <?php echo htmlspecialchars($dienstnaam); ?></p>
                        <div class="alert <?php echo strpos($message, 'Fout') !== false ? 'alert-danger' : 'alert-success'; ?>" role="alert">
                            <?php echo $message; ?>
                        </div>
                        <a href="upload.php" class="btn btn-primary">Terug naar Upload</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <footer class="footer">
        <div class="container text-center">
            <span class="text-muted">TOP versie 3.0</span>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
