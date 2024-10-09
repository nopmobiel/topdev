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
                    <a href="zoekpat.php" class="list-group-item list-group-item-action bg-dark text-white">Zoek Patient</a>
                    <a href="download.php?file=nood.csv" class="list-group-item list-group-item-action bg-dark text-white">Download noodbestand</a>
                    <a href="download.php?file=uitzonderingen.csv" class="list-group-item list-group-item-action bg-dark text-white">Download uitzonderingen</a>
                    <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-white">Afmelden</a>
                </nav>
            </div>

            <!-- Main Content -->
            <main class="col-md-10 bg-light text-dark">
                <div class="container mt-4">
                    <h2 class="text-dark">Zoek op patiëntnummer</h2>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="zoekpat.php" method="post">
                        <div class="form-group">
                            <label for="searchTerm" class="text-dark">Patientnummer:</label>
                            <input type="text" class="form-control" id="searchTerm" name="searchTerm" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Zoeken</button>
                    </form>

                    <?php if ($searchResult !== null): ?>
                        <?php if (count($searchResult) > 0): ?>
                            <table class="table mt-4 table-striped">
                                <thead class="thead-dark">
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
                                                <a href="calendar_view.php?id=<?php echo escape($row['patientnummer']); ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-calendar-alt"></i> Bekijk Kalender
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="mt-4 text-dark">Geen resultaten gevonden.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <span class="text-muted">TOP versie 3.0</span>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>