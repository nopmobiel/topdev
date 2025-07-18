<?php
require_once("settings.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);


function insertHeaderRecord($dienstID, $system) {
    $filename = ($system == 'porta2') ? 'veldnamenporta2' : 'veldnamentrodis';
    $filepath = __DIR__ . '/velddefinities/' . $filename;

    if (file_exists($filepath)) {
        $tableName = "tblWord" . $dienstID;
        try {
            if ($system == 'porta2') {
                $result = importAvita($filepath, $tableName, true);
            } else {
                $result = importTrodis($filepath, $tableName, true);
            }
            return $result;
        } catch (Exception $e) {
            error_log("Error importing header record: " . $e->getMessage());
            return false;
        }
    } else {
        error_log("File not found: $filepath");
        return false;
    }
}

function cleanEmptyLines($inputfile) {
    $lines = file($inputfile, FILE_IGNORE_NEW_LINES);
    $cleanLines = [];
    
    foreach ($lines as $line) {
        if (trim($line) !== '') {
            $cleanLines[] = $line;
        }
    }
    
    // Write back only non-empty lines with consistent line endings
    $content = implode("\r\n", $cleanLines);
    if (!empty($cleanLines)) {
        $content .= "\r\n"; // Add final line ending
    }
    
    file_put_contents($inputfile, $content);
    
    return count($cleanLines);
}

function importTrodis($inputfile, $tablename, $empty = true) {
    try {
        if (!file_exists($inputfile)) {
            throw new Exception("Input file not found: " . $inputfile);
        }

        // Clean empty lines first
        $actualLineCount = cleanEmptyLines($inputfile);
        echo "<h3>DEBUG: Cleaned file now has $actualLineCount lines (removed empty lines)</h3>";

        $options = array(
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
        );

        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Enable LOCAL INFILE for this session
        $pdo->exec("SET GLOBAL local_infile = 1");

        if ($empty) {
            $queryDel = "TRUNCATE TABLE `$tablename`";
            $pdo->exec($queryDel);
            // Reset the auto-increment counter to 1
            $pdo->exec("ALTER TABLE `$tablename` AUTO_INCREMENT = 1");
        }

        // Construct the LOAD DATA INFILE query
        $query = "LOAD DATA LOCAL INFILE '" . addslashes($inputfile) . "' 
                  INTO TABLE " . $tablename . " 
                  FIELDS TERMINATED BY ';' 
                  LINES TERMINATED BY '\\r\\n'";

        // Execute the query
        $result = $pdo->exec($query);
        echo "<h3>DEBUG: LOAD DATA INFILE imported $result records</h3>";

        if ($result === false) {
            throw new Exception("Failed to import data into table: " . $tablename);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Database Error in importTrodis: " . $e->getMessage());
        throw new Exception("Database error during import: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error in importTrodis: " . $e->getMessage());
        throw $e;
    }
}

function importAvita($inputFile, $tableName, $empty = true) {
    try {
        if (!file_exists($inputFile)) {
            throw new Exception("Input file not found: " . $inputFile);
        }

        $options = array(
            PDO::MYSQL_ATTR_LOCAL_INFILE => true,
        );

        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Enable LOCAL INFILE for this session
        $pdo->exec("SET GLOBAL local_infile = 1");

        if ($empty) {
            $queryDel = "TRUNCATE TABLE `$tableName`";
            $pdo->exec($queryDel);
            // Reset the auto-increment counter to 1
            $pdo->exec("ALTER TABLE `$tableName` AUTO_INCREMENT = 1");
        }

        // Construct the LOAD DATA INFILE query
        $query = "LOAD DATA LOCAL INFILE '" . addslashes($inputFile) . "' 
                  INTO TABLE " . $tableName . " 
                  FIELDS TERMINATED BY ';' 
                  ENCLOSED BY '\"' 
                  LINES TERMINATED BY '\\r\\n'";

        // Execute the query
        $result = $pdo->exec($query);

        if ($result === false) {
            throw new Exception("Failed to import data into table: " . $tableName);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Database Error in importAvita: " . $e->getMessage());
        throw new Exception("Database error during import: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error in importAvita: " . $e->getMessage());
        throw $e;
    }
}

function processWordTableBarcodes($dienstID, $system) {
    try {
        if (empty($dienstID) || empty($system)) {
            throw new Exception("Missing required parameters: dienstID or system");
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
        
        $tableName = "tblWord" . $dienstID;
        $fieldName = ($system === 'porta2') ? 'labnummer' : 'werknr';

        // Verify table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("Table does not exist: " . $tableName);
        }

        $query = "SELECT $fieldName FROM $tableName WHERE $fieldName != :fieldName";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':fieldName' => $fieldName]);

        while ($row = $stmt->fetch()) {
            $number = $row[$fieldName];
            $barcode = CreateBarcode($number);

            $updateQuery = "UPDATE $tableName SET barcode = :barcode WHERE $fieldName = :number";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateResult = $updateStmt->execute([
                ':barcode' => $barcode,
                ':number' => $number
            ]);

            if (!$updateResult) {
                throw new Exception("Error updating barcode for $fieldName $number");
            }
        }

        return true;
    } catch (PDOException $e) {
        error_log("PDO Error in processWordTableBarcodes: " . $e->getMessage());
        throw new Exception("Database error during barcode processing: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error in processWordTableBarcodes: " . $e->getMessage());
        throw $e;
    }
}

function insertOnlineRecord($dienstID, $filename, $lineCount) {
    try {
        if (empty($dienstID) || empty($filename) || empty($lineCount)) {
            throw new Exception("Missing required parameters for online record");
        }

        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if table exists, if not just return true
        $stmt = $pdo->query("SHOW TABLES LIKE 'tblOnline'");
        if ($stmt->rowCount() === 0) {
            return true; // ← Just ignore if table doesn't exist
        }

        $stmt = $pdo->prepare("INSERT INTO tblOnline (DienstID, Filename, LineCount, UploadDate) VALUES (:dienstID, :filename, :lineCount, NOW())");
        $result = $stmt->execute([
            ':dienstID' => $dienstID,
            ':filename' => $filename,
            ':lineCount' => $lineCount
        ]);

        if (!$result) {
            throw new Exception("Failed to insert online record");
        }

        return true;
    } catch (PDOException $e) {
        error_log("Database Error in insertOnlineRecord: " . $e->getMessage());
        throw new Exception("Database error during online record insertion: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error in insertOnlineRecord: " . $e->getMessage());
        throw $e;
    }
}