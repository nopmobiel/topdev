<?php
session_start();

// Include required files
require_once 'settings.php';
require_once 'vendor/autoload.php';

// Check if temp_user is set
if (!isset($_SESSION['temp_user']) || !isset($_SESSION['ga_required'])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Ongeldige beveiligingstoken. Vernieuw de pagina en probeer het opnieuw.";
    } else {
        $verification_code = $_POST['verification_code'] ?? '';
        
        if (empty($verification_code)) {
            $error = "Voer de verificatiecode in.";
        } else {
            // Connect to database
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Get user info and secret
                $username = $_SESSION['temp_user'];
                $stmt = $pdo->prepare("SELECT DienstID, Dienstnaam, Systeem, User, GoogleAuthSecret FROM tblDienst WHERE User = :username");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    $error = "Gebruiker niet gevonden. Probeer opnieuw in te loggen.";
                } else {
                    // Verify the code
                    $ga = new PHPGangsta_GoogleAuthenticator();
                    $checkResult = $ga->verifyCode($user['GoogleAuthSecret'], $verification_code, 2);
                    
                    if ($checkResult) {
                        // Code is valid, complete the login
                        $_SESSION['DienstID'] = $user['DienstID'];
                        $_SESSION['Dienstnaam'] = $user['Dienstnaam'];
                        $_SESSION['Systeem'] = $user['Systeem'];
                        $_SESSION['User'] = $user['User'];
                        
                        // Clean up temp variables
                        unset($_SESSION['temp_user']);
                        unset($_SESSION['ga_required']);
                        
                        // Redirect to dashboard
                        header("Location: upload.php");
                        exit();
                    } else {
                        $error = "Ongeldige verificatiecode. Probeer het opnieuw.";
                    }
                }
            } catch (PDOException $e) {
                error_log("Database Error: " . $e->getMessage());
                $error = "Er is een probleem opgetreden bij het verifiëren van de code. Probeer het later opnieuw.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Authenticator Verificatie</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Verificatiecode</h1>
                    </div>
                    <div class="card-body">
                        <p>Voer de 6-cijferige verificatiecode in van uw Google Authenticator app.</p>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="google_auth_verify.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="form-group">
                                <label for="verification_code">Verificatiecode</label>
                                <input type="text" class="form-control" id="verification_code" name="verification_code" placeholder="6-cijferige code" required autocomplete="off" autofocus>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">Verifiëren</button>
                            </div>
                            
                            <div class="text-center">
                                <a href="logout.php">Terug naar login</a>
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
</body>
</html> 