<?php
// topwatch.php

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fopmerking'])) {
    // Retrieve and sanitize POST data
    $fopmerking = $_POST['fopmerking'] ?? '';
    $fdienst = $_POST['fdienst'] ?? '';
    $fdatum = $_POST['fdatum'] ?? '';
    $ftijd = $_POST['ftijd'] ?? '';

    // Prepare and bind the update statement
    $updateQuery = "UPDATE tblFactuur SET opmerking = ? WHERE datum = ? AND dienst = ? AND tijd = ?";
    if ($stmt = $mysqli->prepare($updateQuery)) {
        $stmt->bind_param("ssss", $fopmerking, $fdatum, $fdienst, $ftijd);
        $stmt->execute();
        $stmt->close();
    } else {
        // Handle query preparation error
        die("Database error: Unable to prepare statement.");
    }
}

// Get current system date
$systeemdatum = date("Y-m-d");

// Fetch records for the current date
$selectQuery = "SELECT f.dienst AS factuur_dienst, d.User AS dienst_user, f.datum, f.aantal, f.tijd, f.opmerking 
                FROM tblFactuur f
                LEFT JOIN tblDienst d ON f.dienst = d.Dienstnaam
                WHERE f.datum = ? 
                ORDER BY f.tijd DESC";
if ($stmt = $mysqli->prepare($selectQuery)) {
    $stmt->bind_param("s", $systeemdatum);
    $stmt->execute();
    $result = $stmt->get_result();
    $number = $result->num_rows;
    $stmt->close();
} else {
    die("Database error: Unable to prepare select statement.");
}

// Fetch total number of services
$totalServicesQuery = "SELECT SUM(aantal) AS total_aantal FROM tblFactuur WHERE datum = ?";
if ($stmt = $mysqli->prepare($totalServicesQuery)) {
    $stmt->bind_param("s", $systeemdatum);
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $aantal2 = $totalRow['total_aantal'] ?? 0;
    $stmt->close();
} else {
    die("Database error: Unable to prepare total services statement.");
}

// Fetch services that have not yet come
$pendingServicesQuery = "SELECT User FROM tblDienst WHERE User NOT IN (SELECT dienst FROM tblFactuur WHERE datum = ?) AND User <> 'demo'";
if ($stmt = $mysqli->prepare($pendingServicesQuery)) {
    $stmt->bind_param("s", $systeemdatum);
    $stmt->execute();
    $pendingResult = $stmt->get_result();
    $pendingServices = [];
    while ($row = $pendingResult->fetch_assoc()) {
        $pendingServices[] = $row['User'];
    }
    // Remove td-test and td-test2 from pending services
    $pendingServices = array_filter($pendingServices, function($service) {
        return $service !== 'td-test' && $service !== 'td-test2';
    });
    $komtnog = implode(", ", $pendingServices);
    $stmt->close();
} else {
    die("Database error: Unable to prepare pending services statement.");
}

// Get version from session
$versie = $_SESSION["versie"] ?? '1.0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Refresh" content="10">
    <link href="site.css" rel="stylesheet" type="text/css">
    <title>Topwatch</title>
</head>
<body>
    <div align="center">
        <h3>Topwatch</h3>
    </div>

    <center>
        <table border="1" cellpadding="5">
            <tr>
                <th>Tijd</th>
                <th>Dienst</th>
                <th>Aantal</th>
                <th>Opmerkingen</th>
                <th>Opmerkingen</th>
                <th colspan="5" style="padding: 0;">
                </th>
            
            </tr>

        
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    // Use User from tblDienst if available, otherwise use dienst from tblFactuur
                    $dienst = $row['dienst_user'] ?? $row['factuur_dienst'];
                    $datum = $row['datum'];
                    $tijd = $row['tijd'];
                    $opmerking = $row['opmerking'];
                    $aantal = $row['aantal'];

                    // Format date
                    $formattedDate = date("d-m-Y", strtotime($datum));

                    // Determine cell color based on time and service
                    $cellColor = "";
                    if (strtotime($tijd) >= strtotime('17:00:00') && $dienst !== "td-amsterdam") {
                        $cellColor = " style='background-color: red;'";
                    }
                ?>
                <tr>
                    <td<?php echo $cellColor; ?>><?php echo escape($tijd); ?></td>
                    <td><?php echo escape($dienst); ?></td>
                    <td><?php echo escape($aantal); ?></td>
                    <td>
                        <form method="post" action="topwatch.php">
                            <input type="text" size="20" name="fopmerking" value="<?php echo escape($opmerking); ?>">
                            <input type="hidden" name="fdienst" value="<?php echo escape($row['factuur_dienst']); ?>">
                            <input type="hidden" name="fdatum" value="<?php echo escape($datum); ?>">
                            <input type="hidden" name="ftijd" value="<?php echo escape($tijd); ?>">
                            <input type="submit" value="OK">
                        </form>
                    </td>
                    <td><?php echo escape($opmerking); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <h5>Totaal aantal diensten binnengekomen: <b><?php echo escape($number); ?></b></h5>
        <h5>Aantal brieven binnen (incl dubbel aangeleverd): <b><?php echo escape($aantal2); ?></b></h5>

        <?php
        // Assuming $komtnog is a string containing comma-separated diensten
        $diensten = array_filter(array_map('trim', explode(',', $komtnog)));
        $dienstenCount = count($diensten);
        ?>
        <h5>Aantal diensten die nog komen: <b><?php echo escape($dienstenCount); ?></b></h5>
        <p><?php echo escape(wordwrap($komtnog, 140, "\n", true)); ?></p>

        <br>
        <pre>Topwatch versie 3.0</pre>
    </center>

    <?php
    // Close the connection
    $mysqli->close();
    ?>
</body>
</html>
