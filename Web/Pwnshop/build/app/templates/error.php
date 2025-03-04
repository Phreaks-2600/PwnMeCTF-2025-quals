<?php
$errorCode    = $errorCode    ?? 404;
$errorTitle   = $errorTitle   ?? 'Page Not Found';
$errorMessage = $errorMessage ?? 'Sorry, the page you are looking for could not be found.';
$pageTitle    = 'Error ' . $errorCode;

$hideNavbar = true;

ob_start();
?>

<div class="d-flex justify-content-center align-items-center vh-100 bg-light">
  <div class="card shadow p-4" style="max-width: 600px; width: 100%;">
    <div class="text-center">
      <h1 class="display-1 text-danger"><?= htmlspecialchars($errorCode) ?></h1>
      <h2 class="fw-bold text-dark"><?= htmlspecialchars($errorTitle) ?></h2>
      <p class="text-muted"><?= htmlspecialchars($errorMessage) ?></p>
      <a href="/" class="btn btn-primary">Back to Home</a>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/main/partials/layout.php';
?>
