<?php
$pageTitle = 'My Cart';
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

  <div class="container my-5">
    <h1 class="mb-4"><?= htmlspecialchars($pageTitle) ?></h1>
    <div class="row">
      <!-- Cart Items -->
      <div class="col-lg-8" id="cartItems">
        <div class="text-center py-5">Loading...</div>
      </div>
      <!-- Cart Summary -->
      <div class="col-lg-4">
        <div class="card p-3">
          <div class="mb-3">
            <h5>Promo Code</h5>
            <div class="input-group">
              <input type="text" id="voucherCode" class="form-control" placeholder="Enter code">
              <button class="btn btn-outline-primary" onclick="applyVoucher()">Apply</button>
            </div>
            <div id="voucherMessage" class="mt-2"></div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between">
              <span>Subtotal:</span>
              <span id="subtotal">0.00 €</span>
            </div>
            <div class="d-flex justify-content-between" id="discountRow" style="display: none;">
              <span>Discount:</span>
              <span id="discount">-0.00 €</span>
            </div>
            <div class="d-flex justify-content-between fs-5 fw-bold">
              <span>Total:</span>
              <span id="total">0.00 €</span>
            </div>
          </div>
          <button class="btn btn-success w-100" id="checkoutButton" onclick="submitOrder()">Order</button>
        </div>
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
    let currentVoucher = null;
    let cartItems = new Map();
    let originalTotal = 0;
    let toastInstance;
    
    document.addEventListener('DOMContentLoaded', function() {
      toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
        delay: 3000
      });
      loadCart();
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

    function calculateTotal() {
      let subtotal = 0;
      cartItems.forEach(item => {
        subtotal += item.price * item.quantity;
      });
      document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' €';
      originalTotal = subtotal;
      if (currentVoucher) {
        const discount = Math.min(currentVoucher.amount, subtotal);
        const total = Math.max(0, subtotal - discount);
        document.getElementById('discount').textContent = '-' + discount.toFixed(2) + ' €';
        document.getElementById('total').textContent = total.toFixed(2) + ' €';
        document.getElementById('discountRow').style.display = 'flex';
      } else {
        document.getElementById('total').textContent = subtotal.toFixed(2) + ' €';
        document.getElementById('discountRow').style.display = 'none';
      }
    }
    
    async function loadCart() {
      try {
        const response = await fetch('/api/cart', {
          headers: {
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          }
        });
        const data = await response.json();
        if (data.success) {
          cartItems.clear();
          data.items.forEach(item => {
            cartItems.set(item.id, item);
          });
          displayCart(data.items);
          calculateTotal();
          document.getElementById('checkoutButton').disabled = data.items.length === 0;
        } else {
          data.message = 'Error loading cart';
          showNotification(data.message, 'error');
        }
      } catch (error) {
        showNotification(error.message, 'error');
      }
    }
    
    function displayCart(items) {
      const container = document.getElementById('cartItems');
      if (!items.length) {
        container.innerHTML = '<div class="text-center py-5">Your cart is empty.</div>';
        return;
      }
      container.innerHTML = items.map(item => `
        <div class="card mb-3">
          <div class="row g-0">
            <div class="col-4">
              <img src="${item.product_picture}" alt="${item.name}" class="img-fluid rounded-start">
            </div>
            <div class="col-8">
              <div class="card-body">
                <h5 class="card-title">${item.name}</h5>
                <p class="card-text">${item.price.toFixed(2)} €</p>
                <div class="d-flex align-items-center">
                  <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(${item.id}, ${item.quantity - 1})" ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                  <span class="mx-2">${item.quantity}</span>
                  <button class="btn btn-outline-secondary btn-sm" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                  <button class="btn btn-danger btn-sm ms-auto" onclick="removeItem(${item.id})">Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      `).join('');
    }
    
    async function updateQuantity(itemId, newQuantity) {
      if (newQuantity < 1) return;
      try {
        const response = await fetch(`/api/cart/${itemId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          },
          body: JSON.stringify({ quantity: newQuantity })
        });
        const data = await response.json();
        if (data.success) {
          data.message = 'Quantity updated successfully';
          showNotification(data.message, 'success');
          loadCart();
        } else {
          data.message = 'Error updating quantity';
          showNotification(data.message, 'error');
        }
      } catch (error) {
        showNotification(error.message, 'error');
      }
    }
    
    async function removeItem(itemId) {
      try {
        const response = await fetch(`/api/cart/${itemId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          }
        });
        const data = await response.json();
        if (data.success) {
          cartItems.delete(itemId);
          loadCart();
          data.message = 'Item deleted successfully';
          showNotification(data.message, 'success');
        } else {
          data.message = 'Error deleting item';
          showNotification(data.message, 'error');
        }
      } catch (error) {
        showNotification(error.message, 'error');
      }
    }
    
    async function applyVoucher() {
      const code = document.getElementById('voucherCode').value;
      if (!code) {
        showNotification('Please enter a promo code', 'warning');
        return;
      }
      try {
        const response = await fetch('/api/vouchers/validate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          },
          body: JSON.stringify({ code })
        });
        const data = await response.json();
        if (data.success) {
          currentVoucher = data.voucher;
          showNotification(`Promo code applied: ${data.voucher.amount}€ discount`, 'success');
          calculateTotal();
        } else {
          currentVoucher = null;
          showNotification(data.message || 'Invalid promo code', 'error');
          calculateTotal();
        }
      } catch (error) {
        showNotification(error.message, 'error');
      }
    }
    
    async function submitOrder() {
      try {
        let total = originalTotal;
        if (currentVoucher) {
          total = Math.max(0, originalTotal - currentVoucher.amount);
        }
        const orderData = {
          items: Array.from(cartItems.values()).map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price
          })),
          voucher_code: currentVoucher ? currentVoucher.code : null,
          total: total
        };
        const response = await fetch('/api/orders/create', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          },
          body: JSON.stringify(orderData)
        });
        const data = await response.json();
        if (data.success) { 
          data.message = 'Order created successfully';
          showNotification(data.message, 'success');
          window.location.href = `/order?id=${data.order_id}`;
        } else {
          data.message = 'Error creating order';
          showNotification(data.message, 'error');
        }
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
