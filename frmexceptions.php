<?php
// frmexceptions.php

session_start();

// Redirect if not logged in or if DienstID is not set
if (!isset($_SESSION['DienstID'])) {
    header("Location: index.php");
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include the settings.php for database credentials
require_once("settings.php");

// Helper function to validate and format DienstID consistently
function validateDienstID($dienstID) {
    $dienstID = (string)$dienstID;
    if (!preg_match('/^[0-9]{2}$/', $dienstID)) {
        throw new Exception("Invalid DienstID format - must be 2 digits (00-99)");
    }
    return $dienstID;
}

// Initialize variables
$dienstnaam = $systeem = "";
$error = "";
$success = "";

// Establish a new PDO connection using defined constants
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch Dienstnaam and Systeem using DienstID
    $stmt = $pdo->prepare("SELECT Dienstnaam, Systeem FROM tblDienst WHERE DienstID = :dienstID");
    $stmt->bindParam(':dienstID', $_SESSION['DienstID'], PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $dienstnaam = $row['Dienstnaam'];
        $systeem = $row['Systeem'];
    } else {
        throw new Exception("Geen gegevens gevonden voor de opgegeven dienst.");
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "Er is een probleem opgetreden bij het ophalen van de gegevens. Probeer het later opnieuw.";
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Function to sanitize output
function escape($html) {
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}

// Secure function to get table name with validation
function getTableName($dienstID) {
    // Validate DienstID using helper function
    $dienstID = validateDienstID($dienstID);
    
    return "tblUitzonderingen" . $dienstID;
}

// Function to clean the tblUitzonderingen table by removing records with empty patientnummer
function zuiver($pdo, $dienstnummer) {
    try {
        $table = getTableName($dienstnummer);
        
        // Verify table exists before attempting to delete
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Table does not exist");
        }
        
        // Use prepared statement with validated table name
        $sql = "DELETE FROM `" . $table . "` WHERE patientnummer = ''";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } catch (Exception $e) {
        // Log error but don't expose details to user
        error_log("Failed to clean table: " . $e->getMessage());
        throw new Exception("Database operation failed");
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $post_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $post_token)) {
        die("Ongeldige CSRF-token.");
    }

    // Retrieve and sanitize POST data
    $uitzonderingID = isset($_POST['uitzonderingID']) ? intval($_POST['uitzonderingID']) : 0;
    $patientnummer = trim($_POST['patientnummer'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $extra = trim($_POST['extra'] ?? '');
    $paragon = isset($_POST['Paragon']) && $_POST['Paragon'] === 'J' ? 'J' : 'N';
    $action = $_POST['action'] ?? '';

    // Input validation
    if (empty($patientnummer)) {
        $error .= "Patientnummer is verplicht.<br>";
    } elseif (!preg_match('/^[A-Za-z0-9\-_]{1,50}$/', $patientnummer)) {
        $error .= "Patientnummer bevat ongeldige tekens.<br>";
    }
    
    if (!empty($postcode) && !preg_match('/^[0-9]{4}[A-Za-z]{2}$/', str_replace(' ', '', $postcode))) {
        $error .= "Postcode heeft een ongeldig formaat.<br>";
    }

    // If no errors, proceed to process the data
    if (empty($error)) {
        try {
            $table = getTableName($_SESSION['DienstID']);
            
            // Verify table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() === 0) {
                throw new Exception("Database table not found");
            }

            switch ($action) {
                case 'add':
                    $sql = "INSERT INTO `" . $table . "` (patientnummer, postcode, extra, Paragon, DienstID) VALUES (:patientnummer, :postcode, :extra, :paragon, :dienstID)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':patientnummer', $patientnummer, PDO::PARAM_STR);
                    $stmt->bindParam(':postcode', $postcode, PDO::PARAM_STR);
                    $stmt->bindParam(':extra', $extra, PDO::PARAM_STR);
                    $stmt->bindParam(':paragon', $paragon, PDO::PARAM_STR);
                    $stmt->bindParam(':dienstID', $_SESSION['DienstID'], PDO::PARAM_INT);
                    $stmt->execute();
                    $success = "Record succesvol toegevoegd.";
                    break;
                case 'update':
                    $sql = "UPDATE `" . $table . "` SET patientnummer = :patientnummer, postcode = :postcode, extra = :extra, Paragon = :paragon WHERE UitzonderingID = :uitzonderingID AND DienstID = :dienstID";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':patientnummer', $patientnummer, PDO::PARAM_STR);
                    $stmt->bindParam(':postcode', $postcode, PDO::PARAM_STR);
                    $stmt->bindParam(':extra', $extra, PDO::PARAM_STR);
                    $stmt->bindParam(':paragon', $paragon, PDO::PARAM_STR);
                    $stmt->bindParam(':uitzonderingID', $uitzonderingID, PDO::PARAM_INT);
                    $stmt->bindParam(':dienstID', $_SESSION['DienstID'], PDO::PARAM_INT);
                    $stmt->execute();
                    $success = "Record succesvol bijgewerkt.";
                    break;
                case 'delete':
                    $sql = "DELETE FROM `" . $table . "` WHERE UitzonderingID = :uitzonderingID AND DienstID = :dienstID";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':uitzonderingID', $uitzonderingID, PDO::PARAM_INT);
                    $stmt->bindParam(':dienstID', $_SESSION['DienstID'], PDO::PARAM_INT);
                    $stmt->execute();
                    $success = "Record succesvol verwijderd.";
                    break;
                default:
                    throw new Exception("Ongeldige actie.");
            }

            // Clear the form fields
            $patientnummer = $postcode = $extra = "";
            $paragon = 'N';

            // Clean the table
            zuiver($pdo, $_SESSION['DienstID']);

            // Instead of echoing JSON, set a flag
            $operation_successful = true;
        } catch (PDOException $e) {
            $error .= "Fout bij het verwerken van het record: " . escape($e->getMessage()) . "<br>";
        } catch (Exception $e) {
            $error .= $e->getMessage() . "<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uitzonderingenbestand bewerken</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
    <style>
        .alert {
            position: relative !important;
            z-index: 1;
            margin-bottom: 20px;
        }
        
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-container label {
            color: #495057;
            font-weight: 500;
        }
        
        .form-container .form-control {
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
        }
        
        .form-container .form-control:focus {
            color: #495057;
            background-color: #fff;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .form-container .form-check-label {
            color: #495057;
        }
        
        #exceptionsTable {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }
        
        #exceptionsTable th,
        #exceptionsTable td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            color: #333;
            background-color: white;
        }
        
        #exceptionsTable th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        #exceptionsTable tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        
        #exceptionsTable tr:hover td {
            background-color: #e9ecef;
        }
        
        .table-responsive {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-group {
            margin-bottom: 20px;
        }
        
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include common menu -->
            <?php include 'menu.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 py-2 pl-4 pr-4">
                <div class="main-content">
                    <h2>Uitzonderingenbestand bewerken</h2>

                    <!-- Alert Messages -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($operation_successful)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="btn-group mb-3" role="group">
                        <button class="btn btn-primary" id="addNewBtn">Nieuwe patiënt toevoegen</button>
                        <button class="btn btn-secondary" id="modifyExistingBtn">Bestaande patiënt wijzigen</button>
                    </div>

                    <!-- Patient Form -->
                    <div class="form-container" id="patientFormContainer" style="display: none;">
                        <form action="frmexceptions.php" method="post" id="patientForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" id="uitzonderingID" name="uitzonderingID" value="">

                            <div class="form-group" id="existingPatientGroup" style="display: none;">
                                <label for="existing_patient">Selecteer bestaande patiënt:</label>
                                <select class="form-control" id="existing_patient" name="existing_patient">
                                    <option value="">-- Selecteer een patiënt --</option>
                                    <?php
                                    try {
                                        $table = getTableName($_SESSION['DienstID']);
                                        $stmt = $pdo->prepare("SELECT UitzonderingID, patientnummer, postcode, extra FROM `" . $table . "` ORDER BY patientnummer");
                                        $stmt->execute();
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . escape($row['UitzonderingID']) . "'>" . 
                                                 escape($row['patientnummer']) . " - " . 
                                                 escape($row['postcode']) . " - " . 
                                                 escape($row['extra']) . "</option>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<option value=''>Fout bij het ophalen van patiënten</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="patientnummer">Patientnummer:</label>
                                        <input type="text" class="form-control" id="patientnummer" name="patientnummer" maxlength="13" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="postcode">Postcode Patient (bijv. 1234AB):</label> 
                                        <input type="text" class="form-control" id="postcode" name="postcode" maxlength="6" pattern="[1-9][0-9]{3}[A-Za-z]{2}" title="Geldige postcode (bijv. 1234AB)">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="extra">Extra informatie:</label>
                                <input type="text" class="form-control" id="extra" name="extra" maxlength="30">
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="Paragon" name="Paragon" value="J">
                                <label class="form-check-label" for="Paragon">Printen en versturen uitbesteden aan PGN?</label>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="submitBtn">Toevoegen</button>
                                <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;">Verwijderen</button>
                                <button type="button" class="btn btn-secondary" id="clearBtn">Formulier wissen</button>
                            </div>
                        </form>
                    </div>

                    <!-- Exceptions List -->
                    <div class="mt-4">
                        <h3>Lijst van uitzonderingen</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="exceptionsTable">
                                <thead>
                                    <tr>
                                        <th>Patientnummer</th>
                                        <th>Postcode</th>
                                        <th>Extra</th>
                                        <th>Printen bij PGN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $table = getTableName($_SESSION['DienstID']);
                                        $stmt = $pdo->prepare("SELECT patientnummer, postcode, extra, Paragon FROM `" . $table . "` ORDER BY patientnummer");
                                        $stmt->execute();
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr>";
                                            echo "<td>" . escape($row['patientnummer']) . "</td>";
                                            echo "<td>" . escape($row['postcode']) . "</td>";
                                            echo "<td>" . escape($row['extra']) . "</td>";
                                            echo "<td>" . ($row['Paragon'] === 'J' ? 'Ja' : 'Nee') . "</td>";
                                            echo "</tr>";
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='4'>Geen uitzonderingen gevonden</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var formContainer = document.getElementById('patientFormContainer');
        var form = document.getElementById('patientForm');
        var addNewBtn = document.getElementById('addNewBtn');
        var modifyExistingBtn = document.getElementById('modifyExistingBtn');
        var existingPatientGroup = document.getElementById('existingPatientGroup');
        var submitBtn = document.getElementById('submitBtn');
        var deleteBtn = document.getElementById('deleteBtn');
        var clearBtn = document.getElementById('clearBtn');
        var formAction = document.getElementById('formAction');
        var existingPatientSelect = document.getElementById('existing_patient');

        var patientData = <?php
            try {
                $table = getTableName($_SESSION['DienstID']);
                $stmt = $pdo->prepare("SELECT UitzonderingID, patientnummer, postcode, extra, Paragon FROM `" . $table . "`");
                $stmt->execute();
                $data = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data[$row['UitzonderingID']] = $row;
                }
                echo json_encode($data);
            } catch (Exception $e) {
                echo '{}';
            }
        ?>;

        addNewBtn.addEventListener('click', function() {
            formContainer.style.display = 'block';
            existingPatientGroup.style.display = 'none';
            clearForm();
            submitBtn.textContent = 'Toevoegen';
            formAction.value = 'add';
        });

        modifyExistingBtn.addEventListener('click', function() {
            formContainer.style.display = 'block';
            existingPatientGroup.style.display = 'block';
            clearForm();
            submitBtn.textContent = 'Bijwerken';
            formAction.value = 'update';
        });

        existingPatientSelect.addEventListener('change', function() {
            var selectedUitzonderingID = this.value;
            if (selectedUitzonderingID && patientData[selectedUitzonderingID]) {
                document.getElementById('patientnummer').value = patientData[selectedUitzonderingID].patientnummer;
                document.getElementById('postcode').value = patientData[selectedUitzonderingID].postcode;
                document.getElementById('extra').value = patientData[selectedUitzonderingID].extra;
                document.getElementById('Paragon').checked = patientData[selectedUitzonderingID].Paragon === 'J';
                document.getElementById('uitzonderingID').value = selectedUitzonderingID;
                
                deleteBtn.style.display = 'inline-block';
            } else {
                clearForm();
            }
        });

        clearBtn.addEventListener('click', clearForm);

        deleteBtn.addEventListener('click', function() {
            if (confirm('Weet u zeker dat u deze patiënt wilt verwijderen?')) {
                formAction.value = 'delete';
                form.submit();
            }
        });

        function clearForm() {
            form.reset();
            deleteBtn.style.display = 'none';
            existingPatientSelect.value = '';
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            form.submit();
        });
    });
    </script>
</body>
</html>