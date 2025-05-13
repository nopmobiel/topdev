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

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$error = '';
$success = '';
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
    
    // Check if user already has Google Auth enabled
    $stmt = $pdo->prepare("SELECT GoogleAuth, GoogleAuthSecret FROM tblDienst WHERE DienstID = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $ga_enabled = !empty($user['GoogleAuth']) && $user['GoogleAuth'] == 1;
    
    // Process form submission to enable Google Auth
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'enable') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = "Ongeldige beveiligingstoken. Vernieuw de pagina en probeer het opnieuw.";
        } else {
            // Generate new secret and save it
            $secret = $ga->createSecret();
            
            $stmt = $pdo->prepare("UPDATE tblDienst SET GoogleAuthSecret = :secret, GoogleAuth = 0 WHERE DienstID = :id");
            $stmt->execute([':secret' => $secret, ':id' => $user_id]);
            
            $success = "Google Authenticator is ingesteld. Scan de QR-code hieronder met uw Google Authenticator app en verifieer de code.";
            
            // Create QR code URL
            $qrCodeUrl = $ga->getQRCodeGoogleUrl('TOP-' . $username, $secret, 'TOP');
        }
    }
    // Process form submission to verify and finalize Google Auth setup
    else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'verify') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = "Ongeldige beveiligingstoken. Vernieuw de pagina en probeer het opnieuw.";
        } else {
            $verification_code = $_POST['verification_code'] ?? '';
            $secret = $user['GoogleAuthSecret'];
            
            if (empty($verification_code)) {
                $error = "Voer de verificatiecode in.";
            } else if (empty($secret)) {
                $error = "Stel eerst Google Authenticator in.";
            } else {
                // Verify the code
                $checkResult = $ga->verifyCode($secret, $verification_code, 2);
                if ($checkResult) {
                    // Update user record to enable Google Auth
                    $stmt = $pdo->prepare("UPDATE tblDienst SET GoogleAuth = 1 WHERE DienstID = :id");
                    $stmt->execute([':id' => $user_id]);
                    
                    // Send email notification
                    $stmt = $pdo->prepare("SELECT Email FROM tblDienst WHERE DienstID = :id");
                    $stmt->execute([':id' => $user_id]);
                    $userEmail = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!empty($userEmail['Email'])) {
                        sendGoogleAuthEmail($userEmail['Email'], $username);
                    }
                    
                    $success = "Google Authenticator is succesvol ingesteld en geactiveerd. Voortaan zult u bij het inloggen om een verificatiecode worden gevraagd.";
                    $ga_enabled = true;
                } else {
                    $error = "Ongeldige verificatiecode. Probeer het opnieuw.";
                    // Get the secret again for a new QR code
                    $secret = $user['GoogleAuthSecret'];
                    $qrCodeUrl = $ga->getQRCodeGoogleUrl('TOP-' . $username, $secret, 'TOP');
                }
            }
        }
    }
    // Process form submission to disable Google Auth
    else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'disable') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = "Ongeldige beveiligingstoken. Vernieuw de pagina en probeer het opnieuw.";
        } else {
            // Disable Google Auth and clear secret
            $stmt = $pdo->prepare("UPDATE tblDienst SET GoogleAuth = 0, GoogleAuthSecret = NULL WHERE DienstID = :id");
            $stmt->execute([':id' => $user_id]);
            
            // Send email notification
            $stmt = $pdo->prepare("SELECT Email FROM tblDienst WHERE DienstID = :id");
            $stmt->execute([':id' => $user_id]);
            $userEmail = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!empty($userEmail['Email'])) {
                sendGoogleAuthDisabledEmail($userEmail['Email'], $username);
            }
            
            $success = "Google Authenticator is uitgeschakeld voor uw account.";
            $ga_enabled = false;
        }
    }
    // If user has a secret but Google Auth is not fully enabled, show the QR code for verification
    else if (!$ga_enabled && !empty($user['GoogleAuthSecret'])) {
        $secret = $user['GoogleAuthSecret'];
        $qrCodeUrl = $ga->getQRCodeGoogleUrl('TOP-' . $username, $secret, 'TOP');
    }
    
    // Get user info for the header
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

// Function to send Google Auth enabled email notification
function sendGoogleAuthEmail($email, $username) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Google Authenticator ingeschakeld - TOP';
        $mail->Body    = "Beste gebruiker,<br><br>Google Authenticator tweestapsverificatie is zojuist ingeschakeld voor uw account <b>$username</b>.<br><br>
                         Als u dit niet heeft gedaan, neem dan direct contact op met de beheerder.<br><br>
                         Met vriendelijke groet,<br>
                         TOP Systeem";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send Google Auth disabled email notification
function sendGoogleAuthDisabledEmail($email, $username) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Google Authenticator uitgeschakeld - TOP';
        $mail->Body    = "Beste gebruiker,<br><br>Google Authenticator tweestapsverificatie is zojuist uitgeschakeld voor uw account <b>$username</b>.<br><br>
                         Als u dit niet heeft gedaan, neem dan direct contact op met de beheerder.<br><br>
                         Met vriendelijke groet,<br>
                         TOP Systeem";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Authenticator instellen</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
    <style>
        /* Override any problematic styles */
        .form-container .card-body {
            color: #000000;
            background-color: #ffffff;
        }
        .form-container .card-body p,
        .form-container .card-body ul,
        .form-container .card-body li,
        .form-container .card-body label,
        .form-container .card-body strong {
            color: #000000;
        }
        .form-container a {
            color: #0000EE;
        }
        .form-container a:hover {
            color: #0000AA;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-danger strong {
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'menu.php'; ?>
            
            <main class="col-md-10 py-2 pl-4 pr-4">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Google Authenticator instellen</h1>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Tweestapsverificatie met Google Authenticator</h5>
                        </div>
                        <div class="card-body">
                            <p style="color: #000 !important;">Google Authenticator biedt een extra beveiligingslaag voor uw account door middel van tweestapsverificatie. Naast uw wachtwoord heeft u ook een tijdelijke code nodig die wordt gegenereerd door de Google Authenticator app op uw smartphone.</p>
                            <p style="color: #000 !important;">De app is beschikbaar voor:</p>
                            <ul style="color: #000 !important;">
                                <li style="color: #000 !important;"><a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</a></li>
                                <li style="color: #000 !important;"><a href="https://apps.apple.com/nl/app/google-authenticator/id388497605" target="_blank">iOS (iPhone, iPad)</a></li>
                            </ul>
                            
                            <?php if ($ga_enabled): ?>
                                <div class="alert alert-danger" role="alert">
                                    <strong>Google Authenticator is ingeschakeld voor uw account</strong><br>
                                    Bij het inloggen zult u worden gevraagd om een verificatiecode van de Google Authenticator app in te voeren.
                                </div>
                                
                                <form method="post" action="google_auth_setup.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                                    <input type="hidden" name="action" value="disable">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Weet u zeker dat u tweestapsverificatie wilt uitschakelen? Dit maakt uw account minder veilig.')">Tweestapsverificatie uitschakelen</button>
                                </form>
                            <?php elseif ($qrCodeUrl): ?>
                                <div class="alert alert-danger" role="alert">
                                    <strong>Voltooi de instelling van Google Authenticator</strong><br>
                                    Scan de QR-code hieronder met de Google Authenticator app op uw smartphone, en voer daarna de verificatiecode in om de instelling te voltooien.
                                </div>
                                
                                <div class="text-center mb-4">
                                    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code voor Google Authenticator" class="img-fluid" style="background-color: white; padding: 10px; border-radius: 5px;">
                                    <p class="mt-2">Of voer de volgende code handmatig in: <strong><?php echo htmlspecialchars($secret); ?></strong></p>
                                </div>
                                
                                <form method="post" action="google_auth_setup.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                                    <input type="hidden" name="action" value="verify">
                                    
                                    <div class="form-group">
                                        <label for="verification_code">Verificatiecode van Google Authenticator</label>
                                        <input type="text" class="form-control" id="verification_code" name="verification_code" placeholder="6-cijferige code" required autocomplete="off">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Verificatiecode controleren</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-danger" role="alert">
                                    <strong>Google Authenticator is niet ingeschakeld voor uw account</strong><br>
                                    Klik op de knop hieronder om tweestapsverificatie met Google Authenticator in te stellen.
                                </div>
                                
                                <form method="post" action="google_auth_setup.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                                    <input type="hidden" name="action" value="enable">
                                    <button type="submit" class="btn btn-primary">Tweestapsverificatie instellen</button>
                                </form>
                            <?php endif; ?>
                        </div>
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