<?php
require_once("settings.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);


function insertHeaderRecord($dienstID, $system) {
    $filename = ($system == 'porta2') ? 'veldnamenporta2' : 'veldnamentrodis';
    $filepath = __DIR__ . '/velddefinities/' . $filename;

    if (file_exists($filepath)) {
        $tableName = "tblWord" . $dienstID;
        if ($system == 'porta2') {
            importAvita($filepath, $tableName, true);
        } else {
            importTrodis($filepath, $tableName, true);
        }
    } else {
        // Handle error: file not found
        error_log("File not found: $filepath");
      //  echo "Error: Header definition file not found.";
    }
}




function importTrodis($inputfile, $tablename, $empty = true) {
    // First, convert the file

    try {
        // Attempt to enable LOCAL INFILE
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
        }

        // Construct the LOAD DATA INFILE query
        $query = "LOAD DATA LOCAL INFILE '" . addslashes($inputfile) . "' 
                  INTO TABLE " . $tablename . " 
                  FIELDS TERMINATED BY ';' 
                  LINES TERMINATED BY '\\r\\n'";

        // Execute the query
        $result = $pdo->exec($query);

        if ($result !== false) {
          //  echo "Data imported successfully. Rows affected: $result";
            return true;
        } else {
         //   echo "Error importing data.";
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo "An error occurred while importing data: " . $e->getMessage();
        return false;
    }
}

function importAvita($inputFile, $tableName, $empty = true) {
    try {
        // Attempt to enable LOCAL INFILE
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
        }

        // Construct the LOAD DATA INFILE query
        $query = "LOAD DATA LOCAL INFILE '" . addslashes($inputFile) . "' 
                  INTO TABLE " . $tableName . " 
                  FIELDS TERMINATED BY ';' 
                  ENCLOSED BY '\"' 
                  LINES TERMINATED BY '\\r\\n'";

        // Execute the query
        $result = $pdo->exec($query);

        if ($result !== false) {
        //    echo "Data imported successfully. Rows affected: $result";
            return true;
        } else {
            echo "Error importing data.";
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo "An error occurred while importing data: " . $e->getMessage();
        return false;
    }
}

function processWordTableBarcodes($dienstID, $system) {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tableName = "tblWord" . $dienstID;
        $fieldName = ($system === 'porta2') ? 'labnummer' : 'werknr';

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
        error_log("PDO Error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        return false;
    }
}

function insertOnlineRecord($dienstID) {
    $wordTableName = "tblWord" . $dienstID;
    $onlineTableName = "tblOnline" . $dienstID;
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "INSERT IGNORE INTO `$onlineTableName` SELECT * FROM `$wordTableName`";
        $pdo->exec($query);

        return true;
    } catch (PDOException $e) {
        error_log("Database Error in insertOnlineRecord: " . $e->getMessage());
        return false;
    }
}