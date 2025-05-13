<?php
// change_password.php - Allow users to change their password
session_start();

// Redirect if not logged in
if (!isset($_SESSION['DienstID'])) {
    header("Location: index.php");
    exit;
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once 'settings.php';
require_once 'functions.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$error = '';
$success = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Ongeldige beveiligingstoken. Vernieuw de pagina en probeer het opnieuw.";
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "Alle velden zijn verplicht.";
        } else if ($new_password !== $confirm_password) {
            $error = "Nieuwe wachtwoorden komen niet overeen.";
        } else {
            // Password requirements validation
            if (strlen($new_password) < 8) {
                $error = "Wachtwoord moet minimaal 8 tekens bevatten.";
            } else if (!preg_match('/[A-Z]/', $new_password)) {
                $error = "Wachtwoord moet minimaal één hoofdletter bevatten.";
            } else if (!preg_match('/[a-z]/', $new_password)) {
                $error = "Wachtwoord moet minimaal één kleine letter bevatten.";
            } else if (!preg_match('/[0-9]/', $new_password)) {
                $error = "Wachtwoord moet minimaal één cijfer bevatten.";
            } else if (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
                $error = "Wachtwoord moet minimaal één speciaal teken bevatten.";
            } else {
                try {
                    // Connect to database
                    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Get user from database
                    $user_id = $_SESSION['DienstID'];
                    $username = $_SESSION['User'];
                    
                    $stmt = $pdo->prepare("SELECT Hash FROM tblDienst WHERE DienstID = :id AND User = :username");
                    $stmt->execute([':id' => $user_id, ':username' => $username]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        // Verify current password
                        if (password_verify($current_password, $user['Hash'])) {
                            // Hash new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            
                            // Update password in database
                            $update_stmt = $pdo->prepare("UPDATE tblDienst SET Hash = :hash WHERE DienstID = :id");
                            $update_stmt->execute([':hash' => $hashed_password, ':id' => $user_id]);
                            
                            $success = "Wachtwoord is succesvol gewijzigd.";
                        } else {
                            $error = "Huidig wachtwoord is onjuist.";
                        }
                    } else {
                        $error = "Gebruiker niet gevonden.";
                    }
                } catch (PDOException $e) {
                    $error = "Database fout: " . $e->getMessage();
                    error_log("Password change error: " . $e->getMessage());
                }
            }
        }
    }
}

// Database connection to get user data for header
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
        $error = "Geen gegevens gevonden voor de opgegeven dienst.";
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "Er is een probleem opgetreden bij het ophalen van de gegevens. Probeer het later opnieuw.";
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord wijzigen</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'menu.php'; ?>
            
            <main class="col-md-10 py-2 pl-4 pr-4">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Wachtwoord wijzigen</h1>
                    </div>
                    
                    <div class="card-body">
                        <?php if (isset($dienstnaam)): ?>
                            <p>U bent aangemeld als <?php echo htmlspecialchars($dienstnaam); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="change_password.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="form-group mb-3">
                                <label for="current_password">Huidig wachtwoord</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="new_password">Nieuw wachtwoord</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted">
                                    Wachtwoord moet minimaal 8 tekens bevatten, inclusief minstens één hoofdletter, 
                                    één kleine letter, één cijfer en één speciaal teken.
                                </small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="confirm_password">Bevestig nieuw wachtwoord</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Wachtwoord wijzigen</button>
                        </form>
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