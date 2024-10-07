<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure to include settings.php at the top of this file
require_once 'settings.php';

function generateOTP() {
    return rand(100000, 999999);
}

function sendOTPEmail($email, $otp) {
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
        $mail->Subject = 'Uw eenmalige pincode voor TOP';
        $mail->Body    = "Uw pincode is: <b>$otp</b><br>Deze is geldig voor 10 minuten.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Count the number of lines in a file
 *
 * @param string $file Path to the file
 * @return int Number of lines in the file
 */
function countLines($file) {
    $linecount = 0;
    $handle = fopen($file, "r");
    while(!feof($handle)){
        $line = fgets($handle);
        $linecount++;
    }
    fclose($handle);
    return $linecount;
}




function CreateBarcode($werknummer) {
    // Ensure the input is 8 digits long
    $werknummer = str_pad($werknummer, 8, '0', STR_PAD_LEFT);
    
    $barcode = "(";
    
    for ($i = 0; $i < 8; $i += 2) {
        $digit1 = intval($werknummer[$i]);
        $digit2 = intval($werknummer[$i + 1]);
        
        // Encode the first digit (odd position)
        $oddEncoding = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9][$digit1];
        
        // Encode the second digit (even position)
        $evenEncoding = [48, 49, 50, 51, 52, 53, 54, 55, 56, 57][$digit2];
        
        // Combine the encodings
        $combinedEncoding = $oddEncoding + $evenEncoding;
        
        // Convert to ASCII character
        $barcode .= chr($combinedEncoding);
    }
    
    return $barcode . ")";
}

function scp_the_file($localFile, $remoteFile) {
    // Remove leading './' if present
    $localFile = ltrim($localFile, './');

    if (!file_exists($localFile)) {
        error_log("Local file does not exist: $localFile");
        return false;
    }

    $connection = ssh2_connect(SCP_HOST, SCP_PORT);
    if (!$connection) {
        error_log("Failed to connect to SSH server");
        return false;
    }

    if (!ssh2_auth_password($connection, SCP_USERNAME, SCP_PASSWORD)) {
        error_log("SSH authentication failed");
        return false;
    }

    // Try to create the remote directory if it doesn't exist
    $remoteDir = dirname($remoteFile);
    $sftp = ssh2_sftp($connection);
    ssh2_sftp_mkdir($sftp, $remoteDir, 0755, true);

    if (!ssh2_scp_send($connection, $localFile, $remoteFile, 0644)) {
        $error = error_get_last();
        error_log("SCP transfer failed: " . $error['message']);
        error_log("Local file: $localFile");
        error_log("Remote file: $remoteFile");
        return false;
    }

    error_log("Successfully transferred: $localFile to $remoteFile");
    return true;
}

?>