<?php
@ini_set('display_errors', 0);
@error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'settings.php';
require_once 'functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate or retrieve CSRF token in session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

// Basic session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '3600');

// Ensure session directory exists and is writable
$sessionPath = sys_get_temp_dir() . '/php_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);

// Function to log login attempts
function logLoginAttempt($username, $success, $failureReason = null) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create table if it doesn't exist
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS tblLoginLog (
                LogID INT AUTO_INCREMENT PRIMARY KEY,
                Username VARCHAR(50),
                IPAddress VARCHAR(45),
                UserAgent TEXT,
                LoginTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                LoginSuccess BOOLEAN,
                FailureReason VARCHAR(100)
            )
        ";
        $pdo->exec($createTableSQL);
        
        // Insert log entry
        $stmt = $pdo->prepare("INSERT INTO tblLoginLog (Username, IPAddress, UserAgent, LoginSuccess, FailureReason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $username,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $success ? 1 : 0,
            $failureReason
        ]);
    } catch (Exception $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Verify token
        if (!isset($_POST['csrf_token'])) {
            throw new Exception("Security token missing. Please refresh the page and try again.");
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            throw new Exception("Security token missing. Please refresh the page and try again.");
        }
        
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid security token. Please refresh the page and try again.");
        }

        // Sanitize input
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            logLoginAttempt($username, false, 'Empty credentials');
            throw new Exception("Vul alstublieft alle velden in.");
        }

        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT DienstID, Systeem, Dienstnaam, User, Email, Hash, GoogleAuth FROM tblDienst WHERE User = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                logLoginAttempt($username, false, 'User not found');
                throw new Exception("Ongeldige gebruikersnaam of wachtwoord.");
            }

            if (!password_verify($password, $user['Hash'])) {
                logLoginAttempt($username, false, 'Invalid password');
                throw new Exception("Ongeldige gebruikersnaam of wachtwoord.");
            }

            // Log successful login
            logLoginAttempt($username, true);

            // Check if user has Google Authenticator enabled
            if (!empty($user['GoogleAuth']) && $user['GoogleAuth'] == 1) {
                // Google Auth is fully enabled - go to verification
                $_SESSION['temp_user'] = $user['User'];
                $_SESSION['ga_required'] = true;
                header("Location: google_auth_verify.php");
                exit();
            } else if (isset($user['GoogleAuth']) && $user['GoogleAuth'] == 0) {
                // Google Auth is disabled - redirect to setup (no email!)
                $_SESSION['DienstID'] = $user['DienstID'];
                $_SESSION['Dienstnaam'] = $user['Dienstnaam'];
                $_SESSION['Systeem'] = $user['Systeem'];
                $_SESSION['User'] = $user['User'];
                
                header("Location: google_auth_setup.php");
                exit();
            } else if (!empty($user['Email'])) {
                $otp = generateOTP();
                $currentDateTime = date('Y-m-d H:i:s');
                
                $stmtOtp = $pdo->prepare("UPDATE tblDienst SET Otp = :otp, OtpTimestamp = :otpTimestamp WHERE User = :username");
                if (!$stmtOtp->execute(['otp' => $otp, 'otpTimestamp' => $currentDateTime, 'username' => $username])) {
                    throw new Exception("Failed to update OTP in database");
                }
                
                if (!sendOTPEmail($user['Email'], $otp)) {
                    throw new Exception("Er is een fout opgetreden bij het verzenden van de e-mail. Probeer het later opnieuw.");
                }
                
                // Store username in session
                $_SESSION['temp_user'] = $user['User'];
                
                // Force session data to be saved before redirecting
                session_write_close();
                
                // Secure redirect without exposing session ID
                header("Location: otp_verification.php");
                exit();
            } else {
                // Set session variables
                $_SESSION['DienstID'] = $user['DienstID'];
                $_SESSION['Dienstnaam'] = $user['Dienstnaam'];
                $_SESSION['Systeem'] = $user['Systeem'];
                $_SESSION['User'] = $user['User'];
                
                // Force session write
                session_write_close();
                
                header("Location: upload.php");
                exit();
            }
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
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
