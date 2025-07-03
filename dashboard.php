<?php
// dashboard.php - PGN Dashboard for tblDienst management

// Start the session
session_start();

// Include the settings.php for database credentials
require_once("settings.php");

// Set the timezone (optional, adjust as needed)
date_default_timezone_set('Europe/Amsterdam');

// Function to sanitize output
function escape($html) {
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}

// Establish a new mysqli connection using defined constants
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$message = "";

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Retrieve and sanitize POST data
    $dienstID = $_POST['DienstID'] ?? '';
    $user = $_POST['User'] ?? '';
    $email = $_POST['Email'] ?? '';
    $dienstnaam = $_POST['Dienstnaam'] ?? '';
    $systeem = $_POST['Systeem'] ?? '';
    $googleAuth = $_POST['GoogleAuth'] ?? '';

         // Validate required fields
     if (empty($dienstID) || empty($user) || empty($dienstnaam)) {
         $message = "Fout: DienstID, Gebruiker en Dienstnaam zijn verplichte velden.";
     } else {
        // Prepare and bind the update statement (excluding password fields)
        $updateQuery = "UPDATE tblDienst SET User = ?, Email = ?, Dienstnaam = ?, Systeem = ?, GoogleAuth = ? WHERE DienstID = ?";
        if ($stmt = $mysqli->prepare($updateQuery)) {
            $stmt->bind_param("ssssii", $user, $email, $dienstnaam, $systeem, $googleAuth, $dienstID);
                         if ($stmt->execute()) {
                 $message = "Record succesvol bijgewerkt voor DienstID: " . $dienstID;
             } else {
                 $message = "Fout bij bijwerken van record: " . $mysqli->error;
             }
            $stmt->close();
                 } else {
             $message = "Database fout: Kan update statement niet voorbereiden.";
         }
    }
}

// Fetch all records from tblDienst
$selectQuery = "SELECT DienstID, User, Email, Dienstnaam, Systeem, GoogleAuth, GoogleAuthSecret, Otp, OtpTimestamp 
                FROM tblDienst 
                ORDER BY DienstID ASC";
if ($stmt = $mysqli->prepare($selectQuery)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    die("Database error: Unable to prepare select statement.");
}

// Get total number of services
$totalQuery = "SELECT COUNT(*) as total FROM tblDienst";
if ($stmt = $mysqli->prepare($totalQuery)) {
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalServices = $totalRow['total'] ?? 0;
    $stmt->close();
} else {
    die("Database error: Unable to prepare total count statement.");
}

// Fetch recent login activity
$loginLogQuery = "SELECT Username, IPAddress, LEFT(UserAgent, 50) as UserAgent, LoginTime, LoginSuccess, FailureReason 
                  FROM tblLoginLog 
                  ORDER BY LoginTime DESC 
                  LIMIT 50";
$loginLogResult = null;
if ($stmt = $mysqli->prepare($loginLogQuery)) {
    $stmt->execute();
    $loginLogResult = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="site.css" rel="stylesheet" type="text/css">
    <title>PGN Dashboard Beheer diensten</title>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        .dashboard-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px; 
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .message { padding: 12px; margin: 15px 0; border-radius: 6px; font-weight: 500; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .edit-form { 
            background: linear-gradient(145deg, #f8f9ff, #e8ecff);
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 8px; 
            border: 2px solid #5a67d8;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .edit-form input, .edit-form select { 
            margin: 5px 0; 
            padding: 10px; 
            width: 200px; 
            border: 2px solid #cbd5e0;
            background-color: #ffffff;
            color: #2d3748;
            border-radius: 6px;
            transition: border-color 0.3s ease;
        }
        .edit-form input:focus, .edit-form select:focus {
            outline: none;
            border-color: #5a67d8;
            box-shadow: 0 0 0 3px rgba(90, 103, 216, 0.1);
        }
        .edit-form label {
            color: #2d3748;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .btn { 
            padding: 10px 18px; 
            margin: 4px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-secondary { background: linear-gradient(135deg, #6c757d, #495057); color: white; }
        .btn-success { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .readonly { 
            background-color: #f7fafc; 
            color: #718096;
            border: 2px solid #e2e8f0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        th, td { 
            border: 1px solid #e2e8f0; 
            padding: 12px; 
            text-align: left; 
            background-color: #ffffff;
            color: #2d3748;
        }
        th { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) { background-color: #f7fafc; }
        tr:hover { background-color: #edf2f7; }
        .hidden { display: none; }
        .form-note {
            margin-top: 15px; 
            font-size: 13px; 
            color: #4a5568;
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #f6e05e;
        }
        h2 { 
            color: #2d3748; 
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1); 
            margin-bottom: 10px;
        }
        .info-section {
            background: linear-gradient(145deg, #f0f4ff, #e0e7ff);
            border: 2px solid #5a67d8;
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div style="text-align: right; margin-bottom: 15px;">
            <a href="export_factuur.php" class="btn btn-primary" style="text-decoration: none;">Export Factuurtabel (CSV)</a>
        </div>
        <div align="center">
            <h2>PGN Dashboard</h2>
            <p>Totaal aantal diensten: <strong><?php echo escape($totalServices); ?></strong></p>
        </div>

                 <?php if (!empty($message)): ?>
             <div class="message <?php echo (strpos($message, 'Error') === 0 || strpos($message, 'Fout') === 0) ? 'error' : 'success'; ?>">
                 <?php echo escape($message); ?>
             </div>
         <?php endif; ?>

        <table>
            <thead>
                                 <tr>
                     <th>DienstID</th>
                     <th>Gebruiker</th>
                     <th>E-mail</th>
                     <th>Dienstnaam</th>
                     <th>Systeem</th>
                     <th>Google Auth</th>
                     <th>Secret</th>
                     <th>2SV/2FA Status</th>
                     <th>Acties</th>
                 </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr id="row-<?php echo $row['DienstID']; ?>">
                        <td><?php echo escape($row['DienstID']); ?></td>
                        <td><?php echo escape($row['User']); ?></td>
                        <td><?php echo escape($row['Email']); ?></td>
                        <td><?php echo escape($row['Dienstnaam']); ?></td>
                        <td><?php echo escape($row['Systeem']); ?></td>
                        <td><?php echo escape($row['GoogleAuth']); ?></td>
                                                 <td><?php echo !empty($row['GoogleAuthSecret']) ? 'Ja' : 'Nee'; ?></td>
                         <td><?php echo !empty($row['Otp']) ? 'Actief (' . escape(substr($row['OtpTimestamp'], 0, 16)) . ')' : 'Geen'; ?></td>
                         <td>
                             <button class="btn btn-primary" onclick="editRecord(<?php echo $row['DienstID']; ?>)">Bewerken</button>
                         </td>
                    </tr>
                    
                    <!-- Edit form row (initially hidden) -->
                    <tr id="edit-<?php echo $row['DienstID']; ?>" class="hidden">
                        <td colspan="9">
                            <form method="post" action="dashboard.php" class="edit-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="DienstID" value="<?php echo escape($row['DienstID']); ?>">
                                
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                    <div>
                                        <label><strong>DienstID:</strong></label><br>
                                        <input type="text" value="<?php echo escape($row['DienstID']); ?>" readonly class="readonly">
                                    </div>
                                    
                                                                         <div>
                                         <label><strong>Gebruiker:</strong></label><br>
                                         <input type="text" name="User" value="<?php echo escape($row['User']); ?>" required>
                                     </div>
                                     
                                     <div>
                                         <label><strong>E-mail:</strong></label><br>
                                         <input type="email" name="Email" value="<?php echo escape($row['Email']); ?>">
                                     </div>
                                    
                                    <div>
                                        <label><strong>Dienstnaam:</strong></label><br>
                                        <input type="text" name="Dienstnaam" value="<?php echo escape($row['Dienstnaam']); ?>" required>
                                    </div>
                                    
                                    <div>
                                        <label><strong>Systeem:</strong></label><br>
                                                                                 <select name="Systeem">
                                             <option value="trodis" <?php echo $row['Systeem'] === 'trodis' ? 'selected' : ''; ?>>trodis</option>
                                             <option value="porta2" <?php echo $row['Systeem'] === 'porta2' ? 'selected' : ''; ?>>porta2</option>
                                             <option value="" <?php echo empty($row['Systeem']) ? 'selected' : ''; ?>>Geen</option>
                                         </select>
                                    </div>
                                    
                                    <div>
                                        <label><strong>GoogleAuth:</strong></label><br>
                                                                                 <select name="GoogleAuth">
                                             <option value="0" <?php echo $row['GoogleAuth'] == '0' ? 'selected' : ''; ?>>Uitgeschakeld (0)</option>
                                             <option value="1" <?php echo $row['GoogleAuth'] == '1' ? 'selected' : ''; ?>>Ingeschakeld (1)</option>
                                         </select>
                                    </div>
                                </div>
                                
                                                                 <div style="margin-top: 15px;">
                                     <button type="submit" class="btn btn-success">Wijzigingen Opslaan</button>
                                     <button type="button" class="btn btn-secondary" onclick="cancelEdit(<?php echo $row['DienstID']; ?>)">Annuleren</button>
                                 </div>
                                 
                                 <div class="form-note">
                                     <strong>Opmerking:</strong> Wachtwoord velden (Hash), Google Auth Secret, en OTP velden kunnen niet worden gewijzigd om veiligheidsredenen.
                                 </div>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Login Activity Section -->
        <?php if ($loginLogResult && $loginLogResult->num_rows > 0): ?>
        <div style="margin-top: 40px;">
            <h3 style="color: #2d3748; margin-bottom: 15px;">Recente Login Activiteit (Laatste 50)</h3>
            <table style="font-size: 0.9em;">
                <thead>
                    <tr>
                        <th>Tijd</th>
                        <th>Gebruiker</th>
                        <th>IP Adres</th>
                        <th>Status</th>
                        <th>Reden (bij falen)</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($logRow = $loginLogResult->fetch_assoc()): ?>
                        <tr style="<?php echo $logRow['LoginSuccess'] ? 'background-color: #f0fff4;' : 'background-color: #fff5f5;'; ?>">
                            <td><?php echo escape(date('d-m-Y H:i:s', strtotime($logRow['LoginTime']))); ?></td>
                            <td><?php echo escape($logRow['Username']); ?></td>
                            <td><?php echo escape($logRow['IPAddress']); ?></td>
                            <td>
                                <?php if ($logRow['LoginSuccess']): ?>
                                    <span style="color: #38a169; font-weight: bold;">✓ Succesvol</span>
                                <?php else: ?>
                                    <span style="color: #e53e3e; font-weight: bold;">✗ Gefaald</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape($logRow['FailureReason'] ?? '-'); ?></td>
                            <td style="font-size: 0.8em; color: #718096;"><?php echo escape($logRow['UserAgent'] ?? '-'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="margin-top: 40px; padding: 20px; background-color: #f7fafc; border-radius: 8px; border: 2px solid #e2e8f0;">
            <h3 style="color: #2d3748; margin-bottom: 10px;">Login Activiteit</h3>
            <p style="color: #718096;">Geen login activiteit gevonden. De tabel wordt automatisch aangemaakt bij de eerste login poging.</p>
        </div>
        <?php endif; ?>

        <div class="info-section" style="margin-top: 30px; padding: 20px; border-radius: 8px;">
             <h4>Dashboard Informatie</h4>
             <ul>
                 <li><strong>Doel:</strong> Lezen en bijwerken van dienstentbel</li>
                 <li><strong>Beveiliging:</strong> Beschermd door htaccess/passwd (geen applicatie authenticatie)</li>
                 <li><strong>Beperkingen:</strong> Geen aanmaken of verwijderen van records toegestaan</li>
                 <li><strong>Login Logging:</strong> Alle login pogingen worden gelogd  inclusief IP-adres en tijdstempel</li>
                 <li><strong>Laatst Bijgewerkt:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
             </ul>
         </div>

        <div align="center" style="margin-top: 20px;">
            <small>PGN Dashboard v1.0 - <?php echo date('Y'); ?></small>
        </div>
    </div>

    <script>
        function editRecord(dienstID) {
            // Hide all edit forms first
            const allEditRows = document.querySelectorAll('[id^="edit-"]');
            allEditRows.forEach(row => row.classList.add('hidden'));
            
            // Show the edit form for the selected record
            const editRow = document.getElementById('edit-' + dienstID);
            if (editRow) {
                editRow.classList.remove('hidden');
                editRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        function cancelEdit(dienstID) {
            const editRow = document.getElementById('edit-' + dienstID);
            if (editRow) {
                editRow.classList.add('hidden');
            }
        }
        
        // Auto-hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessages = document.querySelectorAll('.message.success');
            successMessages.forEach(message => {
                setTimeout(() => {
                    message.style.opacity = '0';
                    setTimeout(() => message.remove(), 500);
                }, 5000);
            });
        });
    </script>

    <?php
    // Close the connection
    $mysqli->close();
    ?>
</body>
</html> 