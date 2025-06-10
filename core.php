<?php
ob_start();
@ini_set('display_errors', 0);
@error_reporting(0);
@ini_set('log_errors', 0);

session_start();


// Check if user is logged in
if (!isset($_SESSION['DienstID']) || !isset($_SESSION['Systeem'])) {
    header("Location: index.php");
    exit();
}

require_once("settings.php");   
require_once("format_trodis.php");
require_once("import.php");
require_once("functions.php");
require_once("export.php");  // Add this line to include the export functions

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

// Step 1: Check if file was uploaded without errors
if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] != 0) {
    $error = $_FILES['fileToUpload']['error'];
    error_log("File Upload Error: " . $error);
    die("File upload error: " . $error);
}

$uploadedFile = $_FILES['fileToUpload']['tmp_name'];
$originalFileName = basename($_FILES['fileToUpload']['name']);
$fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

// Step 2: Validate file extension
$allowedExtensions = array('csv');
if (!in_array($fileExtension, $allowedExtensions)) {
    $message = "Fout: Alleen csv-bestanden zijn toegestaan.";
    goto end_processing;
}

// Step 3: Check filename format
$filenamePattern = '/^(ED|ed)(\d{6})\.csv$/';
if (!preg_match($filenamePattern, $originalFileName, $matches)) {
    $message = "Fout: Ongeldige bestandsnaamindeling. Het moet EDjjmmdd.csv of edjjmmdd.csv zijn";
    goto end_processing;
}

// Step 4: Validate file date
$fileDate = $matches[2];
$currentDate = date('ymd');
if ($fileDate !== $currentDate) {
    $message = "Fout: De bestandsdatum komt niet overeen met de datum van vandaag. Verwacht: ED{$currentDate}.csv";
    goto end_processing;
}

// Step 5: Generate a unique filename and move the uploaded file
$newFileName = uniqid() . '.' . $fileExtension;
$destinationPath = $uploadDir . $newFileName;
if (!move_uploaded_file($uploadedFile, $destinationPath)) {
    error_log("Failed to move uploaded file to: " . $destinationPath);
    die("Failed to move uploaded file");
}

// Step 6: Create a copy of the original file
$orgFileName = $uploadDir . $originalFileName . '.org';
if (!copy($destinationPath, $orgFileName)) {
    $message = "Bestand geüpload, maar het maken van een kopie van het originele bestand is mislukt.";
    goto end_processing;
}

// Step 7: Count lines and determine import type
$lineCount = countLines($destinationPath);
$importType = strtolower($systeem);
$tableName = "tblPrint" . $dienstID;

// Step 8: Process the file based on the import type and table name
$importResult = false;
if ($importType === 'trodis') {
    format_trodis($destinationPath);
    $importResult = importTrodis($destinationPath, $tableName);
} elseif ($importType === 'porta2') {
    $importResult = importAvita($destinationPath, $tableName);
} else {
    $message = "Onbekend importtype: " . $importType;
    goto end_processing;
}

if ($importResult !== true) {
    $message = "Bestand geüpload, maar er was een fout bij het verwerken. Controleer de error log voor details.";
    goto end_processing;
}

// Step 9: Insert the header record
try {
    if (!insertHeaderRecord($dienstID, $systeem)) {
        throw new Exception("Failed to insert header record");
    }
} catch (Exception $e) {
    error_log("Processing Error: " . $e->getMessage());
    die("Error during processing: " . $e->getMessage());
}

// Step 10: Import the rest of the records into tblWord<dienstId>
$wordTableName = "tblWord" . $dienstID;
$wordImportResult = ($importType === 'trodis') ? importTrodis($destinationPath, $wordTableName) : importAvita($destinationPath, $wordTableName);

if ($wordImportResult !== true) {
    $message = "Bestand geüpload en header record ingevoegd, maar er was een fout bij het importeren van de word tabel. Export niet uitgevoerd.";
    goto end_processing;
}

// Step 11: Process barcodes for the word table
try {
    if (!processWordTableBarcodes($dienstID, $systeem)) {
        throw new Exception("Failed to process barcodes");
    }
} catch (Exception $e) {
    error_log("Processing Error: " . $e->getMessage());
    die("Error during processing: " . $e->getMessage());
}

// New Step: Process uitzonderingen
try {
    if (!processUitzonderingen($dienstID)) {
        throw new Exception("Failed to process exceptions");
    }
} catch (Exception $e) {
    error_log("Processing Error: " . $e->getMessage());
    die("Error during processing: " . $e->getMessage());
}

// Step 12: Insert online record
try {
    if (!insertOnlineRecord($dienstID, $originalFileName, $lineCount)) {
        throw new Exception("Failed to insert online record");
    }
} catch (Exception $e) {
    error_log("Processing Error: " . $e->getMessage());
    die("Error during processing: " . $e->getMessage());
}

// Step 13: Export processing starts here
$uploadDir = "./diensten/" . $dienstkortenaam . "/upload/";
$wordfile = $uploadDir . "word_" . date("Ymd_His") . ".csv";
$noodfile = $uploadDir . "nood_" . date("Ymd_His") . ".csv";
$printfile = $uploadDir . "print_" . date("Ymd_His") . ".csv";

// Step 14: Export Word file
if (!exporteerWordUitzonderingenBestand($wordfile, $dienstID)) {
    $message = "Export mislukt. Fout bij exporteren word uitzonderingen bestand.";
    goto end_processing;
}
verwijderSlashes($wordfile);

// Step 15: Export Nood file
if (!exporteerNoodBestand($noodfile, $dienstID)) {
    $message = "Export gedeeltelijk succesvol. Fout bij exporteren nood bestand.";
    goto end_processing;
}
verwijderSlashes($noodfile);

// Step 16: Add the Zethelerecordintabelprint function call
$result = Zethelerecordintabelprint($destinationPath, $printfile, $dienstID);
if ($result !== true) {
    $message = "Fout bij het verwerken van het bestand voor tabelprint: " . $result;
    goto end_processing;
}

// Step 17: Export Print file
if (!exporteerNaarDefinitiefPrintBestand($printfile, $dienstID)) {
    $message = "Export gedeeltelijk succesvol. Fout bij exporteren print bestand.";
    goto end_processing;
}

// Step 18: Add counter to Print file
if (!addCounter2PrintFile($uploadDir, basename($printfile), $dienstID)) {
    $message = "Export gedeeltelijk succesvol. Fout bij toevoegen teller aan print bestand.";
    goto end_processing;
}

// Step 19: Facturering (Invoicing)
$factuurResult = factureer($dienstnaam, $lineCount);
if ($factuurResult !== true) {
    $message = "Bestand succesvol geüpload, verwerkt en geëxporteerd, maar er was een fout bij het factureren: " . $factuurResult;
    goto end_processing;
}

// Set success message here, before file deletion
$message = "Bestand succesvol geüpload, verwerkt, geëxporteerd en gefactureerd. " . 
           "Het bestand bevat " . $lineCount . " regels: " .    
           "Word, Nood en printbestanden (indien beschikbaar) zijn geëxporteerd.";

// Step 20: Remove all CSV and .org files except for specific ones
$filesToKeep = [
    basename($noodfile),
    basename($wordfile),
    'nood.csv',
    'uitzonderingen.csv'
];

$files = glob($uploadDir . '{*.csv,*.org}', GLOB_BRACE);

foreach ($files as $file) {
    $basename = basename($file);
    if (!in_array($basename, $filesToKeep)) {
        if (unlink($file)) {
            error_log("File removed: " . $file);
        } else {
            error_log("Failed to remove file: " . $file);
        }
    }
}

// Remove the original uploaded file if it still exists
if (file_exists($destinationPath) && unlink($destinationPath)) {
    error_log("Original uploaded file removed: " . $destinationPath);
} else {
    error_log("Failed to remove original uploaded file: " . $destinationPath);
}

// Step 21: Rename files as specified
// Rename $noodfile to nood.csv
if (file_exists($noodfile) && basename($noodfile) !== 'nood.csv') {
    if (rename($noodfile, $uploadDir . 'nood.csv')) {
        error_log("Renamed noodfile to: nood.csv");
    } else {
        error_log("Failed to rename noodfile to nood.csv");
    }
} else {
    error_log("Noodfile already named correctly or doesn't exist");
}

// Rename $wordfile to uitzonderingen.csv if not empty
if (file_exists($wordfile)) {
    $lineCount = 0;
    $handle = fopen($wordfile, "r");
    while(!feof($handle)){
        $line = fgets($handle);
        if (trim($line) !== '') {
            $lineCount++;
        }
    }
    fclose($handle);

    if ($lineCount > 0) {
        $newWordFile = $uploadDir . 'uitzonderingen.csv';
        if (basename($wordfile) !== 'uitzonderingen.csv') {
            if (rename($wordfile, $newWordFile)) {
                error_log("Renamed wordfile to: uitzonderingen.csv");
                $wordfile = $newWordFile; // Update $wordfile variable
            } else {
                error_log("Failed to rename wordfile to uitzonderingen.csv");
            }
        } else {
            error_log("Wordfile already named correctly");
        }
    } else {
        error_log("Wordfile is empty, not renaming");
        // Optionally, you might want to delete the empty file
        unlink($wordfile);
        error_log("Deleted empty wordfile");
    }
} else {
    error_log("Wordfile doesn't exist");
}

// Rename the .dat file to <user>.dat
$datFiles = glob($uploadDir . '*.dat');
if (!empty($datFiles)) {
    $oldDatFile = $datFiles[0];  // Assume there's only one .dat file
    $newDatFile = $uploadDir . $dienstkortenaam . '.dat';
    if (basename($oldDatFile) !== $dienstkortenaam . '.dat') {
        if (rename($oldDatFile, $newDatFile)) {
            error_log("Renamed .dat file to: " . basename($newDatFile));
        } else {
            error_log("Failed to rename .dat file");
        }
    } else {
        error_log(".dat file already correctly named");
    }
} else {
    error_log("No .dat file found to rename");
}

// After creating the files, set the correct permissions and log file existence
$filesToAdjust = [
    $uploadDir . $dienstkortenaam . '.dat',
    $uploadDir . 'nood.csv',
    $uploadDir . 'uitzonderingen.csv'
];

foreach ($filesToAdjust as $file) {
    if (file_exists($file)) {
        // Set file permissions to 644 (owner read/write, group read, others read)
        chmod($file, 0644);
        error_log("File exists and permissions set for: $file");
    } else {
        error_log("File does not exist: $file");
    }
}

// Ensure the upload directory has correct permissions
chmod($uploadDir, 0755);
error_log("Upload directory permissions set to 0755: $uploadDir");

// Step 22: Transfer files to remote server
$remoteUploadDir = "/home/trombose/public_html/diensten-test/" . $dienstkortenaam . "/upload/";

$filesToTransfer = [
    $uploadDir . $dienstkortenaam . '.dat',
    $uploadDir . 'nood.csv',
    $uploadDir . 'uitzonderingen.csv'
];

foreach ($filesToTransfer as $file) {
    if (file_exists($file)) {
        $remoteFile = $remoteUploadDir . basename($file);
        error_log("Attempting to transfer: $file to $remoteFile");
        if (scp_the_file($file, $remoteFile)) {
            error_log("Successfully transferred $file to $remoteFile");
        } else {
            error_log("Failed to transfer $file to $remoteFile");
        }
    } else {
        error_log("File not found for transfer: $file");
    }
}

end_processing:
ob_end_clean();
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
            <!-- Include common menu -->
            <?php include 'menu.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 py-2 pl-4 pr-4">
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
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>