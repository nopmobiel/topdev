<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateOTP() {
    return rand(100000, 999999);
}

function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'vps.transip.email';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ddcare@vps.transip.email';
        $mail->Password   = 'gskvfkzqTRKfGbKQ';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ddcare@ddcare.nl', 'Trombose.net webmaster');
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




?>