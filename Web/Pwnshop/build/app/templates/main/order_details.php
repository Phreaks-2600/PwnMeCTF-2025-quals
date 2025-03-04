<?php
$pageTitle = 'Détails de la commande';
ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($currentSettings['site_name'] ?? 'Site Name') ?></title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <?php require_once __DIR__ . '/partials/header.php'; ?>

  <main class="container my-5">
    <!-- Back link -->
    <a href="/orders" class="btn btn-link mb-4">&larr; Back to orders</a>

    <!-- Card containing order details -->
    <div class="card shadow-sm">
      <!-- Order header -->
      <div class="card-header bg-white">
        <div class="row">
          <div class="col-md-8">
            <h2 class="h5 mb-0">Order #<?= htmlspecialchars($order['id'] ?? 'N/A') ?></h2>
            <small class="text-muted">
              Placed on <?= (new DateTime($order['created_at'] ?? 'now'))->format('F d, Y') ?>
            </small>
          </div>
          <div class="col-md-4 text-md-end">
            <?php
              $status = $order['status'] ?? 'pending';
              $statusText = [
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'pending'   => 'Pending'
              ];
              $badgeClass = [
                'completed' => 'bg-success',
                'cancelled' => 'bg-danger',
                'pending'   => 'bg-warning text-dark'
              ];
              $statusDisplay = $statusText[$status] ?? 'Pending';
              $badge = $badgeClass[$status] ?? 'bg-warning text-dark';
            ?>
            <span class="badge <?= $badge ?>"><?= htmlspecialchars($statusDisplay) ?></span>
          </div>
        </div>
      </div>

      <!-- Card body -->
      <div class="card-body">
        <div class="row g-4">
          <!-- Summary -->
          <div class="col-md-6">
            <h4 class="h6">Summary</h4>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span>Number of items</span>
                <span>
                  <?= isset($order['items']) ? array_sum(array_column($order['items'], 'quantity')) : 0 ?>
                </span>
              </li>
              <?php if (!empty($order['voucher_code'])): ?>
                <li class="list-group-item d-flex justify-content-between">
                  <span>Promo code</span>
                  <span>
                    <?= htmlspecialchars($order['voucher_code']) ?>
                    ( -<?= number_format($order['voucher_amount'] ?? 0, 2, '.', ',') ?> €)
                  </span>
                </li>
              <?php endif; ?>
              <li class="list-group-item d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span><?= number_format($order['total'] ?? 0, 2, '.', ',') ?> €</span>
              </li>
            </ul>
          </div>

          <!-- Shipping information -->
          <div class="col-md-6">
            <h4 class="h6">Shipping Information</h4>
            <ul class="list-group list-group-flush">
              <li class="list-group-item">
                <strong>Name:</strong>
                <?= htmlspecialchars($order['shipping_name'] ?? 'Not specified') ?>
              </li>
              <li class="list-group-item">
                <strong>Address:</strong>
                <?= htmlspecialchars($order['shipping_address'] ?? 'Not specified') ?>
              </li>
              <li class="list-group-item">
                <strong>Zip code:</strong>
                <?= htmlspecialchars($order['shipping_zipcode'] ?? 'Not specified') ?>
              </li>
              <li class="list-group-item">
                <strong>City:</strong>
                <?= htmlspecialchars($order['shipping_city'] ?? 'Not specified') ?>
              </li>
              <li class="list-group-item">
                <strong>Country:</strong>
                <?= htmlspecialchars($order['shipping_country'] ?? 'Not specified') ?>
              </li>
              <?php if (!empty($order['shipping_phone'])): ?>
                <li class="list-group-item">
                  <strong>Phone:</strong>
                  <?= htmlspecialchars($order['shipping_phone']) ?>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <!-- Ordered items list -->
        <div class="mt-4">
          <h4 class="h6">Ordered Items</h4>
          <?php if (!empty($order['items'])): ?>
            <div class="list-group">
              <?php foreach ($order['items'] as $item): ?>
                <div class="list-group-item">
                  <div class="row align-items-center">
                    <div class="col-md-8">
                      <h5 class="mb-1">
                        <?= htmlspecialchars($item['name'] ?? $item['product_name'] ?? 'Unknown product') ?>
                      </h5>
                      <p class="mb-0 text-muted">
                        <?= $item['quantity'] ?? 1 ?> article<?= (($item['quantity'] ?? 1) > 1) ? 's' : '' ?>
                      </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                      <p class="mb-0">
                        <?= number_format(($item['price'] ?? $item['item_price'] ?? 0), 2, ',', ' ') ?> €<br>
                        <small class="text-muted">per unit</small>
                      </p>
                      <p class="mb-0 fw-bold">
                        Total: <?= number_format((($item['price'] ?? $item['item_price'] ?? 0) * ($item['quantity'] ?? 1)), 2, ',', ' ') ?> €
                      </p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-center text-muted">No items in this order.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="notificationToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"></div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    /* Styles additionnels éventuels */
    a.btn-link {
      text-decoration: none;
    }
  </style>
  <script>
    let toastInstance;
    document.addEventListener('DOMContentLoaded', function() {
      toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
        delay: 3000
      });
      loadOrderDetails();
    });

  function showNotification(message, type = 'success') {
    const toast = document.getElementById('notificationToast');
    const toastBody = toast.querySelector('.toast-body');
    
    toast.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning'); 
    
    switch(type) {
      case 'success':
        toast.classList.add('text-bg-success');
        break;
      case 'error':
        toast.classList.add('text-bg-danger');
        break;
      case 'warning':
        toast.classList.add('text-bg-warning');
        break;
    }
    
    toastBody.textContent = message;
    toastInstance.show();
  }

  async function loadOrderDetails() {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const orderId = urlParams.get('id');
      if (!orderId) {
        throw new Error('Identifiant de commande manquant');
      }

      const response = await fetch(`/api/orders/${orderId}`, {
        headers: {
          'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
        }
      });
      const data = await response.json();
      
      if (!data.success) {
        data.message = 'Error loading order details';
        showNotification(data.message, 'error');
      }
      data.message = 'Order details loaded successfully';
      showNotification(data.message, 'success');
    } catch (error) {
      showNotification(error.message, 'error');
    }
  }
  </script>
</body>
</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
