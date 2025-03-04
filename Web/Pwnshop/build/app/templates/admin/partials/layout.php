<?php
use App\Models\Settings;

$settings = new Settings();
$currentSettings = $settings->getGlobalSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($currentSettings['site_name']) ?></title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="icon" type="image/png" href="/assets/img/favicon/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon/favicon.svg" />
  <link rel="shortcut icon" href="/assets/img/favicon/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png" />
  <link rel="manifest" href="/assets/img/favicon/site.webmanifest" />
</head>
<body>
  <header>
    <nav class="admin-nav">
      <div class="container">
        <h1>Administration</h1>
        <div class="admin-nav-links">
          <a href="/">Home</a>
          <a href="/admin/users" <?= $currentPage === 'users' ? 'class="active"' : ''; ?>>Users</a>
          <a href="/admin/products" <?= $currentPage === 'products' ? 'class="active"' : ''; ?>>Products</a>
          <a href="/admin/orders" <?= $currentPage === 'orders' ? 'class="active"' : ''; ?>>Orders</a>
          <a href="/admin/vouchers" <?= $currentPage === 'vouchers' ? 'class="active"' : ''; ?>>Vouchers</a>
          <a href="/admin/settings" <?= $currentPage === 'settings' ? 'class="active"' : ''; ?>>Settings</a>
        </div>
      </div>
    </nav>
  </header>

  <main class="admin-container">
    <?= $content ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
