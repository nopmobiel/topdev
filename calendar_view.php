<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

session_start();
require_once("settings.php");

// Define session timeout duration (e.g., 30 minutes)
define('SESSION_TIMEOUT', 1800); // 1800 seconds = 30 minutes

// Check if the session has timed out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();

// Redirect if not logged in or if DienstID is not set
if (!isset($_SESSION['DienstID'])) {
    header("Location: index.php");
    exit();
}

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

$patientId = $_GET['id'] ?? '';
$dienstId = $_SESSION['DienstID'];

// NEW: If no patient ID, show batch view
if (empty($patientId)) {
    // Pagination for batch view
    $recordsPerPage = 6; // Show 6 calendars per page
    $page = $_GET['page'] ?? 1;
    $offset = ($page - 1) * $recordsPerPage;
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get system type
        $stmt = $pdo->prepare("SELECT systeem FROM tblDienst WHERE DienstID = :dienstId");
        $stmt->execute(['dienstId' => $dienstId]);
        $systeem = $stmt->fetchColumn();

        // Get from tblWord instead of tblOnline
        $tableName = "tblWord" . $dienstId;
        
        // Get total count
        if ($systeem === 'trodis') {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM $tableName 
                                     WHERE CONCAT(COALESCE(`w1-ma`,''), COALESCE(`w1-di`,''), COALESCE(`w1-wo`,''), 
                                                 COALESCE(`w1-do`,''), COALESCE(`w1-vr`,''), COALESCE(`w1-za`,''), COALESCE(`w1-zo`,''),
                                                 COALESCE(`w2-ma`,''), COALESCE(`w2-di`,''), COALESCE(`w2-wo`,''), 
                                                 COALESCE(`w2-do`,''), COALESCE(`w2-vr`,''), COALESCE(`w2-za`,''), COALESCE(`w2-zo`,'')) != ''");
        } else {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM $tableName 
                                     WHERE CONCAT(COALESCE(wk1d1,''), COALESCE(wk1d2,''), COALESCE(wk1d3,''), 
                                                 COALESCE(wk1d4,''), COALESCE(wk1d5,''), COALESCE(wk1d6,''), COALESCE(wk1d7,''),
                                                 COALESCE(wk2d1,''), COALESCE(wk2d2,''), COALESCE(wk2d3,'')) != ''");
        }
        $totalRecords = $countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $recordsPerPage);
        
        // Get current page records - FILTER OUT EMPTY CALENDARS
        if ($systeem === 'trodis') {
            // For Trodis: check if any w*-* fields have values
            $stmt = $pdo->prepare("SELECT * FROM $tableName 
                                  WHERE CONCAT(COALESCE(`w1-ma`,''), COALESCE(`w1-di`,''), COALESCE(`w1-wo`,''), 
                                              COALESCE(`w1-do`,''), COALESCE(`w1-vr`,''), COALESCE(`w1-za`,''), COALESCE(`w1-zo`,''),
                                              COALESCE(`w2-ma`,''), COALESCE(`w2-di`,''), COALESCE(`w2-wo`,''), 
                                              COALESCE(`w2-do`,''), COALESCE(`w2-vr`,''), COALESCE(`w2-za`,''), COALESCE(`w2-zo`,'')) != ''
                                  LIMIT :limit OFFSET :offset");
        } else {
            // For Porta2: check if any wk*d* fields have values  
            $stmt = $pdo->prepare("SELECT * FROM $tableName 
                                  WHERE CONCAT(COALESCE(wk1d1,''), COALESCE(wk1d2,''), COALESCE(wk1d3,''), 
                                              COALESCE(wk1d4,''), COALESCE(wk1d5,''), COALESCE(wk1d6,''), COALESCE(wk1d7,''),
                                              COALESCE(wk2d1,''), COALESCE(wk2d2,''), COALESCE(wk2d3,'')) != ''
                                  LIMIT :limit OFFSET :offset");
        }

        $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        handleError("Database error occurred", $e);
    }
    
    // Render batch view
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Batch Kalender Overzicht</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="/site.css">
        <style>
            .calendar-mini { transform: scale(0.7); transform-origin: top left; }
            .calendar-card { height: 600px; overflow: hidden; }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <?php include 'menu.php'; ?>
                
                <main class="col-md-10 py-2 pl-4 pr-4">
                    <div class="form-header">
                        <h1>Batch Kalender Overzicht</h1>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="Calendar pagination">
                        <ul class="pagination justify-content-center">
                            <?php if($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>">Vorige</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>">Volgende</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <!-- Calendar Grid -->
                    <div class="row">
                        <?php foreach($patients as $patient): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card calendar-card">
                                    <div class="card-body calendar-mini">
                                        <?php
                                        if ($systeem === 'trodis') {
                                            echo generateTrodisLayout($patient);
                                        } elseif ($systeem === 'porta2') {
                                            echo generatePorta2Layout($patient);
                                        }
                                        ?>
                                    </div>
                                    <div class="card-footer text-center">
                                        <a href="?id=<?php echo urlencode($patient['patientnummer']); ?>" 
                                           class="btn btn-sm btn-primary">
                                            Volledige kalender
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Bottom Pagination -->
                    <nav aria-label="Calendar pagination">
                        <ul class="pagination justify-content-center">
                            <?php if($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>">Vorige</a>
                                </li>
                            <?php endif; ?>
                            <?php if($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>">Volgende</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <div class="text-center mt-3 mb-4">
                        <p class="text-muted">Totaal: <?php echo $totalRecords; ?> kalenders | Pagina <?php echo $page; ?> van <?php echo $totalPages; ?></p>
                    </div>
                </main>
            </div>
        </div>
    </body>
    </html>
    <?php
    ob_end_flush();
    exit;
}

// EXISTING: Single patient view (when ID is provided)
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

    // Fetch patient data from tblWord instead of tblOnline
    $tableName = "tblWord" . $dienstId;
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
    <style>
        .trodis-layout {
            background-color: white;
            color: black;
            padding: 20px;
        }
        .trodis-layout table {
            background-color: white;
            color: black;
            border-collapse: collapse;
            width: 100%;
        }
        .trodis-layout th, .trodis-layout td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            position: relative;
        }
        .trodis-layout th {
            background-color: #f2f2f2;
        }
        .trodis-layout .patient-info {
            margin-bottom: 20px;
        }
        .trodis-layout .additional-info {
            margin-top: 20px;
        }
        .trodis-layout .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
        .trodis-layout .past-date {
            text-decoration: line-through;
            color: #999;
        }
        .trodis-layout .today-date {
            background-color: #ffeb3b;
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
    <div class="trodis-layout">
        <h2><?php echo htmlspecialchars($_SESSION['Dienstnaam'] ?? ''); ?></h2>
        <div class="patient-info">
            <p><?php echo htmlspecialchars($patientData['aanroep'] . ' ' . $patientData['naam']); ?><br>
            <?php echo htmlspecialchars($patientData['straat']); ?><br>
            <?php echo htmlspecialchars($patientData['postcode'] . ' ' . $patientData['plaats']); ?></p>
        </div>

        <table class="table table-bordered mt-4">
            <tr>
                <th>Begindatum</th>
                <th>Anticoagulans</th>
                <th>Hercontrole datum</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($patientData['begindatum']); ?></td>
                <td><?php echo htmlspecialchars($patientData['anticoagulans']); ?></td>
                <td><?php echo htmlspecialchars($patientData['herc-datum']); ?></td>
            </tr>
        </table>

        <table class="table table-bordered mt-4">
            <tr>
                <th></th>
                <?php for ($week = 1; $week <= 8; $week++) : ?>
                    <th>Week <?php echo $week; ?></th>
                <?php endfor; ?>
            </tr>
            <?php
            $days = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'];

            $startDate = DateTime::createFromFormat('d-m-Y', $patientData['begindatum']);
            if ($startDate === false) {
                $startDate = new DateTime($patientData['begindatum']);
            }

            $today = new DateTime();
            $today->setTime(0, 0, 0);

            // Get the day of the week for the start date (0=Sunday, 1=Monday, ..., 6=Saturday)
            $startDayIndex = (int)$startDate->format('w');

            // Adjust calendar start to Sunday before or on the begindatum
            $calendarStartDate = clone $startDate;
            $calendarStartDate->modify('-' . $startDayIndex . ' days');

            // Iterate through each day of the week and each week column
            foreach ($days as $dayIndex => $day) {
                echo "<tr><th>" . ucfirst($day) . "</th>";
                for ($week = 1; $week <= 8; $week++) {
                    $key = "w$week-$day";

                    // Calculate the date for this cell
                    $cellDate = clone $calendarStartDate;
                    $cellDate->modify('+' . (($week - 1) * 7 + $dayIndex) . ' days');

                    // Determine if the cell should be filled with a value
                    $value = ($cellDate >= $startDate) ? ($patientData[$key] ?? '') : '';

                    // Determine the class for past, present, or future dates
                    $class = '';
                    if ($cellDate < $today) {
                        $class = 'past-date';
                    } elseif ($cellDate == $today) {
                        $class = 'today-date';
                    }

                    echo "<td class='$class'>";
                    // Only display the date label if there's a value in the cell
                    if (!empty($value)) {
                        echo "<span class='date-label'>" . $cellDate->format('d-m') . "</span>";
                    }
                    echo htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            ?>
        </table>

        <div class="additional-info mt-4">
            <p>Patientnummer: <?php echo htmlspecialchars($patientData['patientnummer']); ?></p>
            <p>INR: <?php echo htmlspecialchars($patientData['inr']); ?></p>
            <p>INR bereik: <?php echo htmlspecialchars($patientData['inrbereik']); ?></p>
        </div>

        <button onclick="printCalendar()" class="btn btn-primary mt-3">Deze brief afdrukken</button>
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
        <h2><?php echo htmlspecialchars($_SESSION['Dienstnaam'] ?? ''); ?></h2>
        <!-- Debug: <?php echo htmlspecialchars(print_r($_SESSION, true)); ?> -->
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

        <div id="pdf-content" style="display: none;">
            <!-- This will be populated with a clone of the calendar content -->
        </div>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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

        function generatePDF() {
            // Get the calendar element
            var element = document.querySelector('.porta2-layout');
            
            // Configure the PDF options
            var opt = {
                margin:       1,
                filename:     'calendar.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            // Generate and save the PDF
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
<?php
// Flush the output buffer and send content to browser
ob_end_flush();
?>