<?php
session_start();

// Redirect if not logged in or if DienstID is not set
if (!isset($_SESSION['DienstID'])) {
    header("Location: index.php");
    exit;
}

// Include required files
require_once 'settings.php';
require_once 'version.php';  // Include the version file

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-itunes-app" content="app-id=none">  <!-- This will disable Reader View -->
    <title>Over TOP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
    <style>
        /* Override any problematic styles */
        .form-container .card-body {
            color: #000000;
            background-color: #ffffff;
        }
        .form-container .card-body p,
        .form-container .card-body ul,
        .form-container .card-body li,
        .form-container .card-body h4,
        .form-container .card-body strong {
            color: #000000;
        }
        .form-container a {
            color: #0000EE;
        }
        .form-container a:hover {
            color: #0000AA;
        }
        .version-info {
            text-align: right;
            font-size: 0.9rem;
            margin-top: 30px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'menu.php'; ?>

            <!-- Main Content -->
            <main class="col-md-10 py-2 pl-4 pr-4">
                <div class="form-container">
                    <div class="form-header">
                        <h1>Over TOP</h1>
                    </div>
                    <div class="card-body">
                        <h4>TOP: Trombosedienst Outsource Printing</h4>
                        <p>TOP is een gespecialiseerd systeem voor het verwerken en produceren van doseerkalenders voor trombosepatiënten. Het systeem verwerkt dagelijks gegevens van ongeveer 40 trombosediensten in Nederland en produceert jaarlijks meer dan 1 miljoen kalenders.</p>
                        
                        <p>TOP is een samenwerking tussen <a href="https://ddcare.nl" target="_blank">DDCare</a> en <a href="https://printinggroup.nl" target="_blank">Printing Group Netherlands (PGN)</a>. DDCare verzorgt de ontwikkeling en het beheer van het systeem, terwijl Printing Group Netherlands verantwoordelijk is voor de productie, distributie van de kalenders en de technische ondersteuning.</p>

                        <h4>Onze diensten</h4>
                        <p>Naast TOP biedt DDCare een breed scala aan diensten op het gebied van digitale zorg:</p>
                        
                        <ul>
                            <li><strong>Patiëntportalen</strong> - Veilige omgevingen waar patiënten hun medische gegevens kunnen inzien</li>
                            <li><strong>E-health oplossingen</strong> - Digitale toepassingen voor betere zorgverlening</li>
                            <li><strong>Medische software</strong> - Op maat gemaakte applicaties voor zorgverleners</li>
                            <li><strong>Data-analyse</strong> - Inzichten uit medische gegevens voor betere zorg</li>
                        </ul>

                        <p>Voor meer informatie over onze diensten, bezoek <a href="https://ddcare.nl" target="_blank">DDCare.nl</a>.</p>

                        <h4>Printing Group Netherlands (PGN)</h4>
                        <p>Printing Group Netherlands is een toonaangevende specialist in het verwerken, produceren en distribueren van documenten met gevoelige informatie. Met jarenlange ervaring in de zorgsector zorgt PGN voor de betrouwbare productie en levering van doseerkalenders voor trombosepatiënten.</p>
                        
                        <p>Printing Group Netherlands verzorgt ook de technische ondersteuning voor het TOP systeem.</p>
                        
                        <p>Voor meer informatie over Printing Group Netherlands, bezoek <a href="https://printinggroup.nl" target="_blank">PrintingGroup.nl</a>.</p>

                        <h4>Contact</h4>
                        <p>Voor vragen en ondersteuning over het TOP systeem kunt u contact opnemen met Printing Group Netherlands via de contactgegevens op hun website.</p>
                        
                        <!-- Version information section -->
                        <div class="version-info">
                            <p>Versie: <?php echo APP_VERSION; ?><br>
                            Uitgebracht: <?php echo APP_VERSION_DATE; ?></p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 