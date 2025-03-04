<?php
use App\Models\Settings;

$pageTitle = 'Order Summary';
$settings = new Settings();
$currentSettings = $settings->getGlobalSettings();
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
  <div class="container my-5">
    <div class="card mx-auto" style="max-width: 800px;">
      <div class="card-body">
        <h2 class="card-title text-center mb-4">Order Summary</h2>
        <p class="text-center text-muted mb-4">Please confirm your order details</p>
        <!-- Order summary -->
        <?php if (!empty($order['items'])): ?>
          <ul class="list-group mb-4">
            <?php foreach ($order['items'] as $item): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                  <small>Quantity: <?= $item['quantity'] ?></small>
                </div>
                <span><?= number_format($item['price'], 2) ?> €</span>
              </li>
            <?php endforeach; ?>
            <li class="list-group-item d-flex justify-content-between">
              <strong>Total:</strong>
              <strong><?= number_format($order['total'], 2) ?> €</strong>
            </li>
          </ul>
        <?php endif; ?>
        <!-- Delivery information form -->
        <form id="checkoutForm">
          <h4 class="mb-3">Delivery Information</h4>
          <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" id="address" name="address" class="form-control" required>
          </div>
          <div class="row mb-3">
            <div class="col">
              <label for="zipcode" class="form-label">Postal Code</label>
              <input type="text" id="zipcode" name="zipcode" class="form-control" required>
            </div>
            <div class="col">
              <label for="city" class="form-label">City</label>
              <input type="text" id="city" name="city" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="country" class="form-label">Country</label>
            <input type="text" id="country" name="country" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-control">
          </div>
          <button type="submit" class="btn btn-primary w-100">Confirm Order</button>
          <button type="button" class="btn btn-danger w-100 mt-2" onclick="cancelOrder(<?= $order['id'] ?>)">Cancel</button>
        </form>
      </div>
    </div>
  </div>
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="notificationToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"></div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <script>
  let toastInstance;
    document.addEventListener('DOMContentLoaded', function() {
      toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
        delay: 3000
      });
      loadUserProfile();
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

    async function loadUserProfile() {
      try {
        const response = await fetch('/api/profile', {
          headers: {
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          }
        });
        const data = await response.json();
        if (data.success) {
          document.getElementById('name').value = `${data.user.first_name} ${data.user.last_name}`.trim();
        }
      } catch (error) {
        showNotification(error.message, 'error');
      }
    }
    document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const orderXml = `
                <order>
                    <shipping_address>
                        <name>${document.getElementById('name').value}</name>
                        <address>${document.getElementById('address').value}</address>
                        <zipcode>${document.getElementById('zipcode').value}</zipcode>
                        <city>${document.getElementById('city').value}</city>
                        <country>${document.getElementById('country').value}</country>
                        <phone>${document.getElementById('phone').value}</phone>
                    </shipping_address>
                </order>`;

            try {
                const response = await fetch('/api/orders/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
                    },
                    body: JSON.stringify({ xml: orderXml, id: <?= $order['id'] ?> })
                });

                const data = await response.json();
                if (data.success) {
                    data.message = 'Order validated successfully';
                    showNotification(data.message, 'success');
                    window.location.href = `/order?id=<?= $order['id'] ?>`;
                } else {
                    data.message = 'Error validating order';
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification(error.message, 'error');
            }
        });
        async function cancelOrder(orderId) {
            const response = await fetch(`/api/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
                }
            });
            const data = await response.json();
            if (data.success) {
                data.message = 'Order cancelled successfully';
                showNotification(data.message, 'success');
                window.location.href = `/orders`;
            } else {
                data.message = 'Error cancelling order';
                showNotification(data.message, 'error');
            }
        }
  </script>
</body>
</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
