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
        <a href="logout.php" class="list-group-item <?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
            Afmelden
        </a>
    </nav>
</div> 