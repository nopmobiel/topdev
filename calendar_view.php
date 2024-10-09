<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

session_start();
require_once("settings.php");

// Function to handle errors
function handleError($message, $error = null) {
    echo "<div style='background-color: #ffcccc; border: 1px solid #ff0000; padding: 10px; margin: 10px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . htmlspecialchars($message) . "</p>";
    if ($error instanceof Exception) {
        echo "<p>Details: " . htmlspecialchars($error->getMessage()) . "</p>";
        echo "<p>File: " . htmlspecialchars($error->getFile()) . " on line " . $error->getLine() . "</p>";
    }
    echo "</div>";
    ob_end_flush();
    exit;
}

// Redirect if not logged in or if DienstID is not set
if (!isset($_SESSION['DienstID'])) {
    handleError("Not logged in or DienstID not set. Please log in.");
}

$patientId = $_GET['id'] ?? '';
$dienstId = $_SESSION['DienstID'];

if (empty($patientId)) {
    handleError("Patient ID is missing.");
}

// Fetch system type and patient data
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch system type
    $stmt = $pdo->prepare("SELECT systeem FROM tblDienst WHERE DienstID = :dienstId");
    $stmt->execute(['dienstId' => $dienstId]);
    $systeem = $stmt->fetchColumn();

    if (!$systeem) {
        handleError("System type not found for DienstID: " . htmlspecialchars($dienstId));
    }

    // Fetch patient data
    $tableName = "tblOnline" . $dienstId;
    $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE patientnummer = :patientId");
    $stmt->execute(['patientId' => $patientId]);
    $patientData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patientData) {
        handleError("Patient not found with ID: " . htmlspecialchars($patientId));
    }

} catch (PDOException $e) {
    handleError("Database error occurred", $e);
}

// Functions to generate layout for each system
function generateTrodisLayout($patientData) {
    ob_start();
    ?>
    <div class="trodis-layout">
        <h2>Trodis Kalender</h2>
        <div class="patient-info">
            <p>Naam: <?php echo htmlspecialchars($patientData['naam'] ?? ''); ?></p>
            <p>Patientnummer: <?php echo htmlspecialchars($patientData['patientnummer'] ?? ''); ?></p>
            <p>Telefoon: <a href="tel:<?php echo htmlspecialchars($patientData['telno'] ?? ''); ?>" style="color: #007bff; text-decoration: none;">
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($patientData['telno'] ?? ''); ?></a></p>
            <!-- Add more patient info as needed -->
        </div>
        <!-- Add calendar layout specific to Trodis here -->
    </div>
    <?php
    return ob_get_clean();
}

function generatePorta2Layout($patientData) {
    ob_start();
    ?>
    <style>
        .porta2-layout {
            background-color: white;
            color: black;
            padding: 20px;
        }
        .porta2-layout table {
            background-color: white;
            color: black;
            border-collapse: collapse;
            width: 100%;
        }
        .porta2-layout th, .porta2-layout td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            position: relative;
        }
        .porta2-layout th {
            background-color: #f2f2f2;
        }
        .porta2-layout .patient-info {
            margin-bottom: 20px;
        }
        .porta2-layout .additional-info {
            margin-top: 20px;
        }
        .porta2-layout .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
        .porta2-layout .past-date {
            text-decoration: line-through;
            color: #999;
        }
        .porta2-layout .today-date {
            background-color: #ffeb3b; /* Highlight today's date */
        }
        .phone-link {
            color: #007bff;
            text-decoration: none;
        }
        .date-label {
            font-size: 0.7em;
            position: absolute;
            top: 2px;
            right: 2px;
            color: #666;
        }
    </style>
    <div class="porta2-layout">
        <h2> <?php echo htmlspecialchars($_SESSION['Dienstnaam'] ?? ''); ?></h2>
        <div class="patient-info">
            <p><?php echo htmlspecialchars($patientData['aanroep'] . ' ' . $patientData['naam']); ?><br>
            <?php echo htmlspecialchars($patientData['strhuisnotoev']); ?><br>
            <?php echo htmlspecialchars($patientData['postcode'] . ' ' . $patientData['plaats']); ?></p>
            <p>Telefoon: <a href="tel:<?php echo htmlspecialchars($patientData['telno'] ?? ''); ?>" class="phone-link">
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($patientData['telno'] ?? ''); ?></a></p>
      
        </div>

        <table class="table table-bordered mt-4">
            <tr>
                <th>Begindatum</th>
                <th>Anticoagulans</th>
                <th>Volgende datum</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($patientData['begindatum']); ?></td>
                <td><?php echo htmlspecialchars($patientData['anticoagulans']); ?></td>
                <td><?php echo htmlspecialchars($patientData['volgende_datum']); ?></td>
            </tr>
        </table>

        <table class="table table-bordered mt-4">
            <tr>
                <th></th>
                <th>Week 1</th>
                <th>Week 2</th>
                <th>Week 3</th>
                <th>Week 4</th>
                <th>Week 5</th>
                <th>Week 6</th>
                <th>Week 7</th>
            </tr>
            <?php
            $days = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];
            
            $startDate = DateTime::createFromFormat('d-m-Y', $patientData['begindatum']);
            if ($startDate === false) {
                $startDate = DateTime::createFromFormat('Y-m-d', $patientData['begindatum']);
            }
            
            if ($startDate === false) {
                $startDate = new DateTime();
            }
            
            $today = new DateTime();
            $today->setTime(0, 0, 0);

            // Find the first non-empty value in week 1
            $firstNonEmptyDay = null;
            for ($i = 1; $i <= 7; $i++) {
                if (!empty($patientData["wk1d$i"])) {
                    $firstNonEmptyDay = $i - 1; // Adjust to 0-based index
                    break;
                }
            }

            // If no non-empty day found, default to the start of the week
            if ($firstNonEmptyDay === null) {
                $firstNonEmptyDay = 0;
            }

            // Set calendar start date to the first non-empty day
            $calendarStartDate = clone $startDate;
            $calendarStartDate->modify('-' . $firstNonEmptyDay . ' days');

            foreach ($days as $dayIndex => $day) {
                echo "<tr><th>$day</th>";
                for ($week = 1; $week <= 7; $week++) {
                    $key = "wk{$week}d" . ($dayIndex + 1);
                    $value = $patientData[$key] ?? '';

                    $cellDate = clone $calendarStartDate;
                    $cellDate->modify('+' . (($week - 1) * 7 + $dayIndex) . ' days');
                    
                    $class = $cellDate < $today ? 'past-date' : '';
                    if ($cellDate == $today) {
                        $class = 'today-date';
                    }
                    $dateLabel = !empty($value) ? $cellDate->format('d-m') : '';

                    echo "<td class='$class'>";
                    if (!empty($dateLabel)) {
                        echo "<span class='date-label'>$dateLabel</span>";
                    }
                    echo htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            ?>
        </table>

        <div class="additional-info mt-4">
            <p>Patientnummer: <?php echo htmlspecialchars($patientData['patientnummer']); ?></p>
            <p>INR: <?php echo htmlspecialchars($patientData['INR']); ?></p>
            <p>Streefgebied: <?php echo htmlspecialchars($patientData['streefgebied']); ?></p>
            <p>Werknr: <?php echo htmlspecialchars($patientData['werknr']); ?></p>
        </div>

        <button onclick="printCalendar()" class="btn btn-primary mt-3">Deze brief afdrukken</button>
    </div>
    <?php
    return ob_get_clean();
}

// Include Font Awesome for icons
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doseerkalender</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php
        if ($systeem === 'trodis') {
            echo generateTrodisLayout($patientData);
        } elseif ($systeem === 'porta2') {
            echo generatePorta2Layout($patientData);
        } else {
            handleError("Unknown system type: " . htmlspecialchars($systeem));
        }
        ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function printCalendar() {
            window.print();
        }
    </script>
</body>
</html>
<?php
// Flush the output buffer and send content to browser
ob_end_flush();
?>