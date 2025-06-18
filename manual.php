<?php
// Public manual page - no authentication required

// Read the manual content
$manualContent = file_get_contents('HANDLEIDING.md');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOP Handleiding</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
    <style>
        .manual-content {
            white-space: pre-wrap;
            font-family: monospace;
            padding: 20px;
            background: #ffffff;
            border-radius: 4px;
            color: #000000;
            border: 1px solid #ddd;
        }
        .manual-container {
            background: #ffffff;
            color: #000000;
            padding: 20px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 1200px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        body {
            background: #f8f9fa;
        }
        .manual-header {
            background-color: var(--secondary-color, #B22222);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .manual-header h1 {
            margin: 0;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="manual-container">
            <div class="manual-header">
                <h1>TOP Handleiding</h1>
            </div>
            <div class="manual-content">
                <?php echo htmlspecialchars($manualContent); ?>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 