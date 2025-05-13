<?php
// Set session ID from URL parameter BEFORE starting any session
if (isset($_GET['sid'])) {
    // Only set the session ID if no session is active yet
    if (session_status() == PHP_SESSION_NONE) {
        session_id($_GET['sid']);
    }
}

// Start the session ONCE
session_start();

// Check if temp_user is set in session, if not, try to get it from URL parameters
if (!isset($_SESSION['temp_user']) && isset($_GET['user'])) {
    $_SESSION['temp_user'] = $_GET['user'];
}

// If temp_user still not set, redirect to login
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
                    // Handle error if session regeneration fails, perhaps log and show generic error
                    echo "<div class='alert alert-danger'>Sessieherstel mislukt. Probeer opnieuw in te loggen.</div>";
                    exit();
                }

                // Set required session variables
                $_SESSION['DienstID'] = $userDetails['DienstID'];
                $_SESSION['Systeem'] = $userDetails['Systeem'];
                $_SESSION['User'] = $username;
                $_SESSION['Dienstnaam'] = $username;

                header("Location: upload.php"); // Redirect to the dashboard
                exit();
            } else {
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
</body>
</html>