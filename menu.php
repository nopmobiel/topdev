<?php
// menu.php - Common menu include for all pages
// Get the current page filename to highlight the active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Menu -->
<div class="col-md-2 px-0">
    <div id="menu">
        <a href="upload.php" class="menu-title">
            <h3 class="text-center">Menu</h3>
        </a>
    </div>
    <nav class="list-group">
        <a href="upload.php" class="list-group-item <?php echo ($current_page == 'upload.php') ? 'active' : ''; ?>">
            Dagelijkse upload
        </a>
        <a href="frmexceptions.php" class="list-group-item <?php echo ($current_page == 'frmexceptions.php') ? 'active' : ''; ?>">
            Uitzonderingen
        </a>
        <a href="download.php?file=nood.csv" class="list-group-item <?php echo (($current_page == 'download.php') && ($_GET['file'] ?? '') == 'nood.csv') ? 'active' : ''; ?>">
            Download noodbestand
        </a>
        <a href="download.php?file=uitzonderingen.csv" class="list-group-item <?php echo (($current_page == 'download.php') && ($_GET['file'] ?? '') == 'uitzonderingen.csv') ? 'active' : ''; ?>">
            Download uitzonderingen
        </a>
        <a href="change_password.php" class="list-group-item <?php echo ($current_page == 'change_password.php') ? 'active' : ''; ?>">
            Wachtwoord wijzigen
        </a>
        <?php
        // Check if the user has 2SV enabled to show the 'Add Device' link
        if (isset($_SESSION['DienstID'])) {
            try {
                $pdo_menu = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $stmt_menu = $pdo_menu->prepare("SELECT GoogleAuth FROM tblDienst WHERE DienstID = :id");
                $stmt_menu->execute([':id' => $_SESSION['DienstID']]);
                $user_2sv = $stmt_menu->fetch(PDO::FETCH_ASSOC);

                if ($user_2sv && $user_2sv['GoogleAuth'] == 1) {
                    echo '<a href="add_2sv_device.php" class="list-group-item ' . ($current_page == 'add_2sv_device.php' ? 'active' : '') . '">Apparaat toevoegen</a>';
                }
            } catch (PDOException $e) {
                // Do not show the link if there is a database error
            }
        }
        ?>
        <a href="manual.php" class="list-group-item <?php echo ($current_page == 'manual.php') ? 'active' : ''; ?>">
            Handleiding
        </a>
        <a href="about.php" class="list-group-item <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
            Over TOP
        </a>
        <a href="logout.php" class="list-group-item <?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
            Afmelden
        </a>
    </nav>
</div> 