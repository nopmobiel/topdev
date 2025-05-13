<?php
// Include deployment configuration (handles environment-specific settings)
require_once 'deploy_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Function to log and display errors
function handleError($message, $error = null) {
    $errorMessage = $message;
    if ($error) {
        $errorMessage .= " Error details: " . $error->getMessage();
        error_log($errorMessage);
    }
    return $errorMessage;
}

require 'vendor/autoload.php';
require_once 'settings.php';
require_once 'functions.php';

// Simple token storage
function getTokenFile() {
    return sys_get_temp_dir() . '/token_' . session_id() . '.txt';
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug current cookies
error_log("Current cookies: " . print_r($_COOKIE, true));

// Generate or retrieve CSRF token in session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("Generated new CSRF token: " . $_SESSION['csrf_token']);
}
$token = $_SESSION['csrf_token'];
error_log("Using session CSRF token: " . $token);

// Basic session debug
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Debug token validation
        error_log("POST request received");
        error_log("All cookies: " . print_r($_COOKIE, true));
        error_log("All POST data: " . print_r($_POST, true));
        
        // Verify token
        if (!isset($_POST['csrf_token'])) {
            error_log("POST token is missing");
            throw new Exception("Security token missing. Please refresh the page and try again.");
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            error_log("Session token is missing");
            throw new Exception("Security token missing. Please refresh the page and try again.");
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            error_log("Token mismatch - Session: " . $_SESSION['csrf_token'] . " vs POST: " . $_POST['csrf_token']);
            throw new Exception("Invalid security token. Please refresh the page and try again.");
        }

        // Sanitize input
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            throw new Exception("Vul alstublieft alle velden in.");
        }

        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT DienstID, Systeem, Dienstnaam, User, Email, Hash, GoogleAuth FROM tblDienst WHERE User = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Ongeldige gebruikersnaam of wachtwoord.");
            }

            if (!password_verify($password, $user['Hash'])) {
                throw new Exception("Ongeldige gebruikersnaam of wachtwoord.");
            }

            // Check if user has Google Authenticator enabled
            if (!empty($user['GoogleAuth']) && $user['GoogleAuth'] == 1) {
                // Store username in session
                $_SESSION['temp_user'] = $user['User'];
                $_SESSION['ga_required'] = true;
                
                // Redirect to Google Authenticator verification
                header("Location: google_auth_verify.php");
                exit();
            } else if (!empty($user['Email'])) {
                // Handle 2FA with email OTP
                $otp = generateOTP();
                $currentDateTime = date('Y-m-d H:i:s');
                
                $stmtOtp = $pdo->prepare("UPDATE tblDienst SET Otp = :otp, OtpTimestamp = :otpTimestamp WHERE User = :username");
                if (!$stmtOtp->execute(['otp' => $otp, 'otpTimestamp' => $currentDateTime, 'username' => $username])) {
                    throw new Exception("Failed to update OTP in database");
                }
                
                if (!sendOTPEmail($user['Email'], $otp)) {
                    throw new Exception("Er is een fout opgetreden bij het verzenden van de e-mail. Probeer het later opnieuw.");
                }
                
                // Store username in session using both temp_user and a cookie backup
                $_SESSION['temp_user'] = $user['User'];
                
                // Force session data to be saved before redirecting
                session_write_close();
                
                // Redirect with the session ID in the URL as a fallback mechanism
                header("Location: otp_verification.php?sid=" . urlencode(session_id()) . "&user=" . urlencode($user['User']));
                exit();
            } else {
                // Direct login for users without email or Google Auth
                $_SESSION['DienstID'] = $user['DienstID'];
                $_SESSION['Dienstnaam'] = $user['Dienstnaam'];
                $_SESSION['Systeem'] = $user['Systeem'];
                $_SESSION['User'] = $user['User'];
                header("Location: upload.php");
                exit();
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    } catch (Exception $e) {
        $error_message = handleError("Login failed", $e);
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen bij TOP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
</head>
<body>  
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Inloggen bij TOP</h1>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error_message ?? ''); ?>
                            </div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] ?? ''); ?>" method="post" class="p-3">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
                            <div class="form-group">
                                <label for="username">Gebruikersnaam</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="password">Wachtwoord</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">Inloggen</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="site.js"></script>
</body>
</html>
