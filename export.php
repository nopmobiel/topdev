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

function exporteerWordBestand($outputfile, $dienstID) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        $tempFile = tempnam(sys_get_temp_dir(), 'word_export_');
        $query = "SELECT * FROM tblWord$dienstID WHERE uitzondering='J' OR uitzondering ='u'";
        $stmt = $pdo->query($query);

        $fp = fopen($tempFile, 'w');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, $row, ';', '"');
        }
        fclose($fp);

        if (!rename($tempFile, $outputfile)) {
            throw new Exception("Kon het bestand niet verplaatsen naar de gewenste locatie.");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error in exporteerWordBestand: " . $e->getMessage());
        return "Fout bij het exporteren van het Word bestand: " . $e->getMessage();
    }
}

function exporteerNoodBestand($outputfile, $dienstID) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        $tempFile = tempnam(sys_get_temp_dir(), 'nood_export_');
        $query = "SELECT * FROM tblWord$dienstID WHERE uitzondering<>'J'";
        $stmt = $pdo->query($query);

        $fp = fopen($tempFile, 'w');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($fp, $row, ';', '"');
        }
        fclose($fp);

        if (!rename($tempFile, $outputfile)) {
            throw new Exception("Kon het bestand niet verplaatsen naar de gewenste locatie.");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error in exporteerNoodBestand: " . $e->getMessage());
        return "Fout bij het exporteren van het Nood bestand: " . $e->getMessage();
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

function factureer($dienst, $aantal) {
    $pdo = getDatabaseConnection();

    if (!$pdo) {
        return "Communicatiefout met de database.";
    }

    try {
        $datum = date('Ymd');
        $tijd = date('H:i:s');

        $query = "INSERT INTO tblFactuur (DienstID, Datum, Aantal, Status, Tijd) VALUES (:dienst, :datum, :aantal, '0', :tijd)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':dienst' => $dienst,
            ':datum' => $datum,
            ':aantal' => $aantal,
            ':tijd' => $tijd
        ]);

        return true;
    } catch (PDOException $e) {
        error_log("Database Error in factureer: " . $e->getMessage());
        return "Fout bij het factureren: " . $e->getMessage();
    }
}
