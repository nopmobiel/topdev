<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED);

// Redirect if not logged in
if (!isset($_SESSION['DienstID'])) {
    header("Location: index.php");
    exit;
}

// Include required files
require_once 'settings.php';
require_once 'functions.php';
require_once 'vendor/autoload.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$qrCodeUrl = '';
$secret = '';

// Initialize Google Authenticator
$ga = new PHPGangsta_GoogleAuthenticator();

try {
    // Connect to database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user data
    $user_id = $_SESSION['DienstID'];
    $username = $_SESSION['User'];
    
    // Check if user has Google Auth enabled
    $stmt = $pdo->prepare("SELECT GoogleAuth, GoogleAuthSecret FROM tblDienst WHERE DienstID = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $ga_enabled = !empty($user['GoogleAuth']) && $user['GoogleAuth'] == 1;
    
    if (!$ga_enabled || empty($user['GoogleAuthSecret'])) {
        // If 2SV is not enabled, they shouldn't be here. Redirect to setup.
        header("Location: google_auth_setup.php");
        exit;
    }
    
    // Get the existing secret and generate a QR code for it
    $secret = $user['GoogleAuthSecret'];
    $qrCodeUrl = $ga->getQRCodeGoogleUrl('TOP-' . $username, $secret, 'TOP');

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
    <title>Apparaat Toevoegen</title>
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
                        <h1>Apparaat toevoegen</h1>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                <strong>Scan de QR-code met uw extra apparaat</strong><br>
                                Open de Google Authenticator app op uw nieuwe telefoon of tablet en scan de QR-code hieronder. Dit koppelt uw account aan het nieuwe apparaat.
                            </div>
                            
                            <div class="text-center mb-4">
                                <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code voor Google Authenticator" class="img-fluid" style="max-width: 300px; background-color: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd;">
                                <p class="mt-3"><strong>Of voer de volgende code handmatig in:</strong><br>
                                <code style="font-size: 16px; background-color: #f8f9fa; padding: 5px;"><?php echo htmlspecialchars($secret); ?></code></p>
                            </div>

                            <div class="text-center">
                                <a href="upload.php" class="btn btn-primary">Terug naar Dashboard</a>
                            </div>
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