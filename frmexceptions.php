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

// Function to clean the tblUitzonderingen table by removing records with empty patientnummer
function zuiver($pdo, $dienstnummer) {
    // Prepare the table name safely
    $table = "tblUitzonderingen" . intval($dienstnummer);

    try {
        // Prepare the DELETE statement
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE patientnummer = ''");
        $stmt->execute();
    } catch (PDOException $e) {
        // Log error or handle accordingly
        error_log("Failed to clean table $table: " . $e->getMessage());
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

    // Basic validation
    if (empty($patientnummer)) {
        $error .= "Patientnummer is verplicht.<br>";
    }

    // If no errors, proceed to process the data
    if (empty($error)) {
        // Prepare the table name safely
        $table = "tblUitzonderingen" . intval($_SESSION['DienstID']);

        try {
            switch ($action) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO `$table` (patientnummer, postcode, extra, Paragon, DienstID) VALUES (:patientnummer, :postcode, :extra, :paragon, :dienstID)");
                    $stmt->bindParam(':patientnummer', $patientnummer, PDO::PARAM_STR);
                    $stmt->bindParam(':postcode', $postcode, PDO::PARAM_STR);
                    $stmt->bindParam(':extra', $extra, PDO::PARAM_STR);
                    $stmt->bindParam(':paragon', $paragon, PDO::PARAM_STR);
                    $stmt->bindParam(':dienstID', $_SESSION['DienstID'], PDO::PARAM_INT);
                    $success = "Record succesvol toegevoegd.";
                    break;
                case 'update':
                    $stmt = $pdo->prepare("UPDATE `$table` SET patientnummer = :patientnummer, postcode = :postcode, extra = :extra, Paragon = :paragon WHERE UitzonderingID = :uitzonderingID AND DienstID = :dienstID");
                    $stmt->bindParam(':patientnummer', $patientnummer, PDO::PARAM_STR);
                    $stmt->bindParam(':postcode', $postcode, PDO::PARAM_STR);
                    $stmt->bindParam(':extra', $extra, PDO::PARAM_STR);
                    $stmt->bindParam(':paragon', $paragon, PDO::PARAM_STR);
                    $stmt->bindParam(':uitzonderingID', $uitzonderingID, PDO::PARAM_INT);
                    $stmt->bindParam(':dienstID', $_SESSION['DienstID'], PDO::PARAM_INT);
                    $success = "Record succesvol bijgewerkt.";
                    break;
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE UitzonderingID = :uitzonderingID AND DienstID = :dienstID");
                    $stmt->bindParam(':uitzonderingID', $uitzonderingID, PDO::PARAM_INT);
                    $stmt->bindParam(':dienstID', $_SESSION['DienstID'], PDO::PARAM_INT);
                    $success = "Record succesvol verwijderd.";
                    break;
                default:
                    throw new Exception("Ongeldige actie.");
            }

            $stmt->execute();

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
    <title>Uitzonderingenbestand bwerken</title>
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
                    <a href="upload.php" class="list-group-item list-group-item-action bg-dark text-white">Dagelijkse upload</a>
                    <a href="frmexceptions.php" class="list-group-item list-group-item-action bg-dark text-white">Uitzonderingen</a>
                    <a href="zoekpat.php" class="list-group-item list-group-item-action bg-dark text-white">Kalender opvragen</a>
                    <a href="download.php?file=nood.csv" class="list-group-item list-group-item-action bg-dark text-white">Download noodbestand</a>
                    <a href="download.php?file=uitzonderingen.csv" class="list-group-item list-group-item-action bg-dark text-white">Download uitzonderingen</a>
                    <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Afmelden</a>
                </nav>
            </div>

            <!-- Main Content -->
            <main class="col-md-10">
                <div class="container mt-4">
                    <h2>Uitzonderingenbestand bewerken</h2>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($operation_successful)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <button class="btn btn-primary" id="addNewBtn">Nieuwe patiënt toevoegen</button>
                        <button class="btn btn-secondary" id="modifyExistingBtn">Bestaande patiënt wijzigen</button>
                    </div>

                    <form action="frmexceptions.php" method="post" id="patientForm" style="display: none;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" id="uitzonderingID" name="uitzonderingID" value="">

                        <div class="form-group" id="existingPatientGroup" style="display: none;">
                            <label for="existing_patient">Selecteer bestaande patiënt:</label>
                            <select class="form-control" id="existing_patient" name="existing_patient">
                                <option value="">-- Selecteer een patiënt --</option>
                                <?php
                                try {
                                    $table = "tblUitzonderingen" . intval($_SESSION['DienstID']);
                                    $stmt = $pdo->prepare("SELECT UitzonderingID, patientnummer, postcode, extra FROM `$table` ORDER BY patientnummer");
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . escape($row['UitzonderingID']) . "'>" . 
                                             escape($row['patientnummer']) . " - " . 
                                             escape($row['postcode']) . " - " . 
                                             escape($row['extra']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value=''>Fout bij het ophalen van patiënten</option>";
                                    error_log("Database Error: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="patientnummer">Patientnummer:</label>
                            <input type="text" class="form-control" id="patientnummer" name="patientnummer" maxlength="13" required>
                        </div>

                        <div class="form-group">
                            <label for="postcode">Postcode Patient (bijv. 1234AB):</label> 
                            <input type="text" class="form-control" id="postcode" name="postcode" maxlength="6" pattern="[1-9][0-9]{3}[A-Za-z]{2}" title="Geldige postcode (bijv. 1234AB)">
                        </div>

                        <div class="form-group">
                            <label for="extra">Extra informatie:</label>
                            <input type="text" class="form-control" id="extra" name="extra" maxlength="30">
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="Paragon" name="Paragon" value="J">
                            <label class="form-check-label" for="Paragon">Printen en versturen uitbesteden aan PGN?</label>
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">Toevoegen</button>
                        <button type="button" class="btn btn-danger" id="deleteBtn" style="display:none;">Verwijderen</button>
                        <button type="button" class="btn btn-secondary" id="clearBtn">Formulier wissen</button>
                    </form>

                </div>

                <div class="mt-5">
                    <h3>Lijst van uitzonderingen</h3>
                    <table id="exceptionsTable">
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
                                $table = "tblUitzonderingen" . intval($_SESSION['DienstID']);
                                $stmt = $pdo->prepare("SELECT patientnummer, postcode, extra, Paragon FROM `$table` ORDER BY patientnummer");
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>";
                                    echo "<td>" . escape($row['patientnummer']) . "</td>";
                                    echo "<td>" . escape($row['postcode']) . "</td>";
                                    echo "<td>" . escape($row['extra']) . "</td>";
                                    echo "<td>" . ($row['Paragon'] === 'J' ? 'Ja' : 'Nee') . "</td>";
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='4'>Geen uitzonderingen gevonden</td></tr>";
                                error_log("Database Error: " . $e->getMessage());
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <footer >
        <div class="container text-center">
            <span class="text-muted">TOP versie 3.0</span>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
            $table = "tblUitzonderingen" . intval($_SESSION['DienstID']);
            $stmt = $pdo->prepare("SELECT UitzonderingID, patientnummer, postcode, extra, Paragon FROM `$table`");
            $stmt->execute();
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[$row['UitzonderingID']] = $row;
            }
            echo json_encode($data);
        ?>;

        addNewBtn.addEventListener('click', function() {
            form.style.display = 'block';
            existingPatientGroup.style.display = 'none';
            clearForm();
            submitBtn.textContent = 'Toevoegen';
            formAction.value = 'add';
        });

        modifyExistingBtn.addEventListener('click', function() {
            form.style.display = 'block';
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