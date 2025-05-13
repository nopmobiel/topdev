<?php
session_start();
require_once("settings.php");

// Redirect if not logged in or if DienstID is not set
if (!isset($_SESSION['DienstID'])) {
    header("Location: index.php");
    exit;
}

// Initialize variables
$error = "";
$searchResult = null;

// Establish a new PDO connection using defined constants
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "Er is een probleem opgetreden bij het verbinden met de database. Probeer het later opnieuw.";
}

// Function to sanitize output
function escape($html) {
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchTerm = trim($_POST['searchTerm'] ?? '');
    
    if (!empty($searchTerm)) {
        try {
            $tablename = "tblOnline" . $_SESSION['DienstID'];
            $sql = "SELECT * FROM `$tablename` WHERE patientnummer = :searchTerm";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':searchTerm', $searchTerm, PDO::PARAM_STR);
            $stmt->execute();
            $searchResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search Error: " . $e->getMessage());
            $error = "Er is een probleem opgetreden bij het zoeken. Probeer het later opnieuw.";
        }
    } else {
        $error = "Voer een patientnummer in.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoek Patient</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
    <style>
        .table-bordered-bottom td, .table-bordered-bottom th {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding: 12px 8px;
            color: white;
        }
        .results-header {
            background-color: var(--secondary-color);
            color: var(--text-color);
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            margin-bottom: 0;
            text-align: left;
        }
        .results-header h3 {
            margin: 0;
            background-color: transparent;
            box-shadow: none;
            border-radius: 0;
            padding: 0;
        }
        .form-group label {
            color: white;
        }
        .card-body {
            color: white;
        }
        .alert-info {
            background-color: rgba(23, 162, 184, 0.2);
            color: white;
            border-color: rgba(23, 162, 184, 0.3);
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
                <div class="form-container">
                    <div class="form-header">
                        <h1>Zoek op patiëntnummer in huidige bestand</h1>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <form action="zoekpat.php" method="post">
                                <div class="form-group">
                                    <label for="searchTerm">Patientnummer:</label>
                                    <input type="text" class="form-control" id="searchTerm" name="searchTerm" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Zoeken</button>
                            </form>
                        </div>

                        <?php if ($searchResult !== null && count($searchResult) > 0): ?>
                            <div class="results-header">
                                <h3>Resultaten</h3>
                            </div>
                            
                            <table class="table table-bordered-bottom">
                                <thead>
                                    <tr>
                                        <th>Patientnummer</th>
                                        <th>Naam</th>
                                        <th>Begindatum</th>
                                        <th>Kalender</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($searchResult as $row): ?>
                                        <tr>
                                            <td><?php echo escape($row['patientnummer']); ?></td>
                                            <td><?php echo escape($row['naam']); ?></td>
                                            <td><?php echo escape($row['begindatum']); ?></td>
                                            <td>
                                                <button onclick="openCalendarInNewWindow('<?php echo escape($row['patientnummer']); ?>')" class="btn btn-primary">
                                                    Bekijk kalender
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($searchResult !== null): ?>
                            <div class="alert alert-info mt-4" role="alert">
                                Geen resultaten gevonden.
                            </div>
                        <?php endif; ?>
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
        function openCalendarInNewWindow(patientId) {
            const calendarUrl = `calendar_view.php?id=${patientId}`;
            const newWindow = window.open(calendarUrl, 'Calendar', 'width=1000,height=600');
            
            if (newWindow === null || typeof newWindow === 'undefined') {
                alert('Pop-up geblokkeerd. Sta pop-ups toe voor deze site om de kalender in een nieuw venster te openen.');
            } else {
                newWindow.focus();
            }
        }
    </script>
</body>
</html>