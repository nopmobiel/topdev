<?php
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

// Database connection
require_once "settings.php";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch Dienstnaam and Systeem using DienstID
    $stmt = $pdo->prepare("SELECT Dienstnaam, Systeem FROM tblDienst WHERE DienstID = :dienstID");
    $stmt->bindParam(':dienstID', $_SESSION['DienstID']);
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
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Uploaden doseerkalenders</title>
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
                        <h1>Uploaden doseerkalenders</h1>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php else: ?>
                            <p>U bent aangemeld als <?php echo htmlspecialchars($dienstnaam); ?></p>
                            <form action="core.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <div class="form-group">
                                    <label for="fileToUpload">Bestand kiezen om te uploaden:</label>
                                    <input type="file" class="form-control-file" name="fileToUpload" id="fileToUpload" required accept=".csv,.txt">
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">Upload Bestand</button>
                            </form>
                        <?php endif; ?>
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