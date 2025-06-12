<?php


require_once("settings.php");

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
        if (!is_numeric($dienstID) || $dienstID <= 0 || $dienstID > 9999) {
            throw new Exception("Invalid DienstID");
        }
        
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
        $tableName = "tblWord" . (int)$dienstID;
        
        // Verify table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Table does not exist");
        }
        
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
        if (!is_numeric($dienstID) || $dienstID <= 0 || $dienstID > 9999) {
            throw new Exception("Invalid DienstID");
        }
        
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
        $tableName = "tblWord" . (int)$dienstID;
        
        // Verify table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Table does not exist");
        }
        
        // Write data rows
        $query = "SELECT * FROM `" . $tableName . "` WHERE uitzondering<>'J'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, $row, ';', '"');
        }
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

    $lines = file($pad . $fileprintdata);
    $fptarget = fopen($targetfile, "w");
    
    if (!$fptarget) {
        return "Fout bij het openen van het doelbestand: $targetfile";
    }

    $recordcounter = 0;
    foreach ($lines as $line) {
        $recordcounter++;
        $teller = $dienstID;
        $teller .= substr($fileprintdata, 2, 6);
        $teller .= sprintf("%04d", $recordcounter);

        $record = rtrim($line) . $teller . "\r\n";
        fwrite($fptarget, $record);
    }

    fclose($fptarget);
    return true;
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

        // No truncation needed, as the short name will fit
        $query = "INSERT INTO tblFactuur (dienst, datum, aantal, prijs, tijd, opmerking) 
                  VALUES (:dienst, :datum, :aantal, :prijs, :tijd, :opmerking)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':dienst' => $dienstkortenaam, // Use the short name directly
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
            $record = rtrim($buffer);
            $record = str_replace('"', '""', $record);
            $record = '"' . $record . '"';

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