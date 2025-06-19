<?php


require_once("settings.php");

// Helper function to validate and format DienstID consistently
function validateDienstID($dienstID) {
    $dienstID = (string)$dienstID;
    if (!preg_match('/^[0-9]{2}$/', $dienstID)) {
        throw new Exception("Invalid DienstID format - must be 2 digits (00-99)");
    }
    return $dienstID;
}

// Set the timezone to match the local timezone
date_default_timezone_set('Europe/Amsterdam');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function getDatabaseConnection() {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        return false;
    }
}

function exporteerWordUitzonderingenBestand($outputfile, $dienstID) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        // Validate DienstID
        $dienstID = validateDienstID($dienstID);
        
        // Get system type for this dienst
        $stmt = $pdo->prepare("SELECT Systeem FROM tblDienst WHERE DienstID = :dienstID");
        $stmt->execute([':dienstID' => $dienstID]);
        $dienst = $stmt->fetch(PDO::FETCH_ASSOC);
        $system = strtolower($dienst['Systeem']);
        
        // Determine header file
        $headerFile = ($system == 'porta2') ? 'veldnamenporta2' : 'veldnamentrodis';
        $headerPath = __DIR__ . '/velddefinities/' . $headerFile;
        
        $tempFile = tempnam(sys_get_temp_dir(), 'word_export_');
        $fp = fopen($tempFile, 'w');
        
        // Write header row first
        if (file_exists($headerPath)) {
            $headerContent = file_get_contents($headerPath);
            fwrite($fp, trim($headerContent) . "\r\n");
        }
        
        // Secure query with validated table name
        $tableName = "tblWord" . $dienstID;
        
        // Table existence assumed - created manually
        
        // Write data rows
        $query = "SELECT * FROM `" . $tableName . "` WHERE uitzondering='J'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, $row, ';', '"');
        }
        fclose($fp);

        if (!rename($tempFile, $outputfile)) {
            throw new Exception("Kon het bestand niet verplaatsen naar de gewenste locatie.");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error in exporteerWordUitzonderingenBestand: " . $e->getMessage());
        return "Fout bij het exporteren van het word uitzonderingen bestand: " . $e->getMessage();
    }
}

function exporteerNoodBestand($outputfile, $dienstID) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        // Validate DienstID
        $dienstID = validateDienstID($dienstID);
        
        // Delete existing file first to ensure fresh data
        if (file_exists($outputfile)) {
            unlink($outputfile);
        }
        
        // Get system type for this dienst
        $stmt = $pdo->prepare("SELECT Systeem FROM tblDienst WHERE DienstID = :dienstID");
        $stmt->execute([':dienstID' => $dienstID]);
        $dienst = $stmt->fetch(PDO::FETCH_ASSOC);
        $system = strtolower($dienst['Systeem']);
        
        // Determine header file
        $headerFile = ($system == 'porta2') ? 'veldnamenporta2' : 'veldnamentrodis';
        $headerPath = __DIR__ . '/velddefinities/' . $headerFile;
        
        // Write directly to the output file instead of using temp file
        $fp = fopen($outputfile, 'w');
        if (!$fp) {
            throw new Exception("Could not open output file for writing: $outputfile");
        }
        
        // Write header row first
        if (file_exists($headerPath)) {
            $headerContent = file_get_contents($headerPath);
            fwrite($fp, trim($headerContent) . "\r\n");
        }
        
        // Secure query with validated table name
        $tableName = "tblWord" . $dienstID;
        
        // Table existence assumed - created manually
        
        // Write data rows
        $query = "SELECT * FROM `" . $tableName . "`";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, $row, ';', '"');
        }
        
        // Ensure data is written to disk before closing
        fflush($fp);
        fclose($fp);

        return true;
    } catch (Exception $e) {
        error_log("Error in exporteerNoodBestand: " . $e->getMessage());
        return "Fout bij het exporteren van het noodbestand: " . $e->getMessage();
    }
}

function exporteerNaarDefinitiefPrintBestand($outputfile, $dienstID) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        $tempFile = tempnam(sys_get_temp_dir(), 'print_export_');
        $query = "SELECT record FROM tblPrint$dienstID WHERE printen <> 'N'";
        $stmt = $pdo->query($query);

        $fp = fopen($tempFile, 'w');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fwrite($fp, $row['record'] . "\r\n");
        }
        fclose($fp);

        if (!rename($tempFile, $outputfile)) {
            throw new Exception("Kon het bestand niet verplaatsen naar de gewenste locatie.");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error in exporteerNaarDefinitiefPrintBestand: " . $e->getMessage());
        return "Fout bij het exporteren van het definitieve print bestand: " . $e->getMessage();
    }
}

function addCounter2PrintFile($pad, $fileprintdata, $dienstID) {
    $targetfile = $pad . substr($fileprintdata, 0, 8) . ".dat";

    $lines = file($pad . $fileprintdata, FILE_IGNORE_NEW_LINES);
    $fptarget = fopen($targetfile, "w");
    
    if (!$fptarget) {
        return "Fout bij het openen van het doelbestand: $targetfile";
    }

    $recordcounter = 0;
    foreach ($lines as $line) {
        // Skip empty lines
        if (trim($line) === '') {
            continue;
        }
        
        $recordcounter++;
        
        // Extract date correctly from filename like "print_20250619_103447.csv"
        $datePart = substr($fileprintdata, 8, 6); // Skip "print_20" and take 6 chars
        
        $counterPart = str_pad($recordcounter, 4, "0", STR_PAD_LEFT);
        $teller = $dienstID . $datePart . $counterPart;
        
        $newRecord = $line . ";\"" . $teller . "\"";
        
        fwrite($fptarget, $newRecord . "\n");
    }
    
    fclose($fptarget);
    return "Bestand $targetfile succesvol aangemaakt met " . ($recordcounter) . " records.";
}

function verwijderSlashes($fileworddata) {
    $lines = file($fileworddata);
    $fptarget = fopen($fileworddata, "w");
    
    if (!$fptarget) {
        return "Fout bij het openen van het bestand: $fileworddata";
    }

    foreach ($lines as $line) {
        $wegschrijf = stripslashes($line);
        fwrite($fptarget, $wegschrijf);
    }

    fclose($fptarget);
    return true;
}

function factureer($dienstkortenaam, $aantal) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        $datum = date('Y-m-d');
        $tijd = date('H:i:s');
        $prijs = 0;
        $opmerking = ""; // Empty string for opmerking

        // Truncate dienst name to 20 characters to fit database field (being more conservative)
        $dienstkortenaam = substr($dienstkortenaam, 0, 20);
        
        $query = "INSERT INTO tblFactuur (dienst, datum, aantal, prijs, tijd, opmerking) 
                  VALUES (:dienst, :datum, :aantal, :prijs, :tijd, :opmerking)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':dienst' => $dienstkortenaam, // Use the truncated name
            ':datum' => $datum,
            ':aantal' => $aantal,
            ':prijs' => $prijs,
            ':tijd' => $tijd,
            ':opmerking' => $opmerking
        ]);

        return true;
    } catch (PDOException $e) {
        error_log("Database Error in factureer: " . $e->getMessage());
        return "Fout bij het factureren: " . $e->getMessage();
    }
}

function Zethelerecordintabelprint($inputfile, $outputfile, $dienstID) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        $pdo->exec("SET NAMES latin1");
        $pdo->exec("SET CHARACTER SET latin1");
        $pdo->exec("SET character_set_connection=latin1");

        $fpInput = fopen($inputfile, "r");
        if (!$fpInput) {
            throw new Exception("Kon het invoerbestand niet openen: $inputfile");
        }

        $fpOutput = fopen($outputfile, "w");
        if (!$fpOutput) {
            fclose($fpInput);
            throw new Exception("Kon het uitvoerbestand niet openen: $outputfile");
        }

        $rownum = 1;
        $tableName = "tblPrint" . $dienstID;

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE $tableName SET record = :record WHERE teller = :rownum");

        while (($buffer = fgets($fpInput)) !== false) {
            // Store the record as-is, without additional quote escaping
            $record = rtrim($buffer, "\r\n");
            
            // Don't escape quotes - they're already properly formatted from import
            // $record = str_replace('"', '""', $record);

            $result = $stmt->execute([':record' => $record, ':rownum' => $rownum]);

            if ($result === false) {
                throw new Exception("Fout bij het updaten van record $rownum: " . implode(", ", $stmt->errorInfo()));
            }

            $rownum++;
            fwrite($fpOutput, $buffer);
        }

        $pdo->commit();

        fclose($fpInput);
        fclose($fpOutput);

        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in Zethelerecordintabelprint: " . $e->getMessage());
        return false;
    }
}

function processUitzonderingen($dienstID) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return false;
    }

    try {
        // Validate DienstID
        $dienstID = validateDienstID($dienstID);
        
        $wordTable = "tblWord$dienstID";
        $uitzonderingenTable = "tblUitzonderingen$dienstID";

        $query = "UPDATE $wordTable w
                  SET w.uitzondering = 'J'
                  WHERE EXISTS (
                      SELECT 1
                      FROM $uitzonderingenTable u
                      WHERE w.postcode = u.postcode
                      AND w.patientnummer = u.patientnummer
                  )";

        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $affectedRows = $stmt->rowCount();
        
        return true;

    } catch (Exception $e) {
        error_log("Error in processUitzonderingen: " . $e->getMessage());
        return false;
    }
}