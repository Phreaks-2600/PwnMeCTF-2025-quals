<?php
use App\Models\Settings;

$settings = new Settings();
$currentSettings = $settings->getGlobalSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($currentSettings['site_name']) ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" type="image/png" href="/assets/img/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/img/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/img/favicon/site.webmanifest" />
</head>
<body>
    <?php
    if (!isset($hideNavbar) || !$hideNavbar) {
        require_once __DIR__ . '/header.php';
    }
    ?>
    <?= $content ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
