<?php
// Start the session
session_start();

// Function to log login attempts
function logLoginAttempt($username, $success, $failureReason = null) {
    try {
        require_once 'settings.php';
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

// Check if temp_user is set in session
if (!isset($_SESSION['temp_user'])) {
    header("Location: index.php");
    exit();
}

// From here, $_SESSION['temp_user'] should be available
$username = $_SESSION['temp_user']; // Retrieve username from session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'settings.php'; // Database settings
    $inputOtp = $_POST['otp'];

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Retrieve OTP and timestamp from database
        $stmt = $pdo->prepare("SELECT Otp, OtpTimestamp FROM tblDienst WHERE User = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $storedOtp = $result['Otp'];
            $otpTimestamp = strtotime($result['OtpTimestamp']);
            $currentTime = time();

            // Verify OTP and its validity (10 minutes)
            if ($inputOtp == $storedOtp && ($currentTime - $otpTimestamp) <= 600) {
                // Fetch additional user details
                $stmtUserDetails = $pdo->prepare("SELECT DienstID, Systeem FROM tblDienst WHERE User = :username");
                $stmtUserDetails->bindParam(':username', $username);
                $stmtUserDetails->execute();
                $userDetails = $stmtUserDetails->fetch(PDO::FETCH_ASSOC);

                // Regenerate session ID now that OTP is verified, before setting final session data
                if (!session_regenerate_id(true)) {
                    // Handle error if session regeneration fails
                    error_log("Session regeneration failed for user: " . $username);
                    echo "<div class='alert alert-danger'>Sessieherstel mislukt. Probeer opnieuw in te loggen.</div>";
                    exit();
                }

                // Clear temporary session data
                unset($_SESSION['temp_user']);
                
                // Set required session variables
                $_SESSION['DienstID'] = $userDetails['DienstID'];
                $_SESSION['Systeem'] = $userDetails['Systeem'];
                $_SESSION['User'] = $username;
                $_SESSION['Dienstnaam'] = $username;

                // Log successful OTP verification
                logLoginAttempt($username, true, 'OTP verified');

                header("Location: upload.php"); // Redirect to the dashboard
                exit();
            } else {
                // Log failed OTP attempt
                logLoginAttempt($username, false, 'Invalid or expired OTP');
                echo "<div class='alert alert-danger'>Ongeldige of verlopen PIN. <a href='index.php'>Klik hier om terug te gaan naar de startpagina</a></div>";
            }
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Verbindingsfout: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2 staps verificatie</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="form-container">
                    <div class="form-header">
                        <h3 class="top">Inloggen bij TOP - 2 staps verificatie</h3>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="otp" class="form-label">Voer pincode in:</label>
                                <input type="text" id="otp" name="otp" class="form-control" required>
                            </div>
                            <div class="button-container">
                                <button type="submit" class="btn btn-primary btn-block">Controleer</button>
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