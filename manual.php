<?php
session_start();

// Redirect if not logged in or if DienstID is not set
if (!isset($_SESSION['DienstID'])) {
    header("Location: index.php");
    exit;
}

// Include required files
require_once 'settings.php';
require_once 'version.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Read the manual content
$manualContent = file_get_contents('HANDLEIDING.md');

// Convert markdown to HTML (basic conversion)
function convertMarkdownToHtml($markdown) {
    // Convert headers
    $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $markdown);
    $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^#### (.*$)/m', '<h4>$1</h4>', $html);
    
    // Convert lists
    $html = preg_replace('/^\* (.*$)/m', '<li>$1</li>', $html);
    $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
    
    // Convert bold
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
    
    // Convert links
    $html = preg_replace('/\[(.*?)\]\((.*?)\)/s', '<a href="$2" target="_blank">$1</a>', $html);
    
    // Convert paragraphs
    $html = '<p>' . str_replace("\n\n", '</p><p>', $html) . '</p>';
    
    return $html;
}

$htmlContent = convertMarkdownToHtml($manualContent);
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
            color: #000000;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .manual-content h1,
        .manual-content h2,
        .manual-content h3,
        .manual-content h4 {
            color: #000000;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        .manual-content p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        .manual-content ul {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        .manual-content li {
            margin-bottom: 0.5rem;
        }
        .manual-content a {
            color: #0000EE;
        }
        .manual-content a:hover {
            color: #0000AA;
            text-decoration: underline;
        }
        .manual-content code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 4px;
            font-family: monospace;
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
                        <h1>TOP Handleiding</h1>
                    </div>
                    <div class="card-body">
                        <div class="manual-content">
                            <?php echo $htmlContent; ?>
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