<?php
header('Content-Type: text/html; charset=UTF-8', true, 404);
$uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
$script = $_SERVER['SCRIPT_FILENAME'] ?? 'unknown';
$root = __DIR__;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiHome 404</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f7f7f7; color: #222; }
        .box { max-width: 900px; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        code { background: #f2f2f2; padding: 2px 6px; border-radius: 4px; }
        h1 { color: #b42318; }
    </style>
</head>
<body>
    <div class="box">
        <h1>404 - Page not found</h1>
        <p>This page could not be found on the server.</p>
        <p><strong>Requested URL:</strong> <code><?= htmlspecialchars($uri) ?></code></p>
        <p><strong>Root folder:</strong> <code><?= htmlspecialchars($root) ?></code></p>
        <p><strong>Current file:</strong> <code><?= htmlspecialchars($script) ?></code></p>
        <p>Check whether the file exists in the server folder and whether the URL path matches the project folder structure.</p>
        <p>Example expected URL:</p>
        <p><code>https://digihome.infinityfree.io/DigiHome/seeker/home.php</code></p>
    </div>
</body>
</html>
