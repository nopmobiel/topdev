<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'settings.php';
require_once 'functions.php'; // Create this file for helper functions

// Secure session settings
ini_set('session.cookie_secure', '1'); // Ensure cookies are sent over HTTPS
ini_set('session.cookie_httponly', '1'); // Prevent JavaScript access to session cookies
session_start();

// Generate a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    // Sanitize input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Vul alstublieft alle velden in.";
    } else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT DienstID, Systeem, Dienstnaam, User, Email, Hash FROM tblDienst WHERE User = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['Hash'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                if (!empty($user['Email'])) {
                    // Handle 2FA
                    $otp = generateOTP();
                    $currentDateTime = date('Y-m-d H:i:s');
                    
                    $stmtOtp = $pdo->prepare("UPDATE tblDienst SET Otp = :otp, OtpTimestamp = :otpTimestamp WHERE User = :username");
                    $stmtOtp->execute(['otp' => $otp, 'otpTimestamp' => $currentDateTime, 'username' => $username]);
                    
                    if (sendOTPEmail($user['Email'], $otp)) {
                        $_SESSION['temp_user'] = $user['User'];
                        header("Location: otp_verification.php");
                        exit();
                    } else {
                        $error_message = "Er is een fout opgetreden bij het verzenden van de e-mail. Probeer het later opnieuw.";
                    }
                } else {
                    // Direct login for users without email
                    $_SESSION['DienstID'] = $user['DienstID'];
                    $_SESSION['Dienstnaam'] = $user['Dienstnaam'];
                    $_SESSION['Systeem'] = $user['Systeem'];
                    $_SESSION['User'] = $user['User'];
                    header("Location: upload.php");
                    exit();
                }
            } else {
                $error_message = "Ongeldige gebruikersnaam of wachtwoord.";
                // Optionally, log the failed attempt here
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $error_message = "Er is een fout opgetreden. Probeer het later opnieuw.";
        }
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
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="p-3">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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
    <footer class="footer">
        <div class="container text-center">
            <span>Software versie 3.0 <a href="https://www.ddcare.nl" target="_blank">DDCare B.V.</a> ism PGN</span>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
