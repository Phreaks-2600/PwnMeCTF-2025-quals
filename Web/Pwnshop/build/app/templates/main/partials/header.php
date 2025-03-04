<?php
use App\Auth\AuthController;
use App\Api\Permissions;
use App\Models\Settings;

$auth = new AuthController();
if (!$auth->isAuthenticated()) {
    header('Location: /');
    exit;
}

$user = $auth->getCurrentUser();
$currentUri = $_SERVER['REQUEST_URI'];
$settings = new Settings();
$currentSettings = $settings->getGlobalSettings();
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/products">
      <?= htmlspecialchars($currentSettings['site_name']) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item">
          <a class="nav-link <?= $currentUri === '/products' ? 'active' : '' ?>" href="/products">Products</a>
        </li>
        <!-- Dropdown Cart -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="cartDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Cart
          </a>
          <div class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="cartDropdown" style="min-width:300px;">
            <div class="cart-preview-content">
              <div class="cart-items"></div>
              <div class="border-top pt-2 mt-2">
                <div class="d-flex justify-content-between">
                  <span class="fw-bold">Total:</span>
                  <span class="fw-bold" id="cartTotal">0.00€</span>
                </div>
                <a href="/cart" class="btn btn-primary btn-sm w-100 mt-2">View Cart</a>
              </div>
            </div>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentUri === '/orders' ? 'active' : '' ?>" href="/orders">Orders</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentUri === '/profile' ? 'active' : '' ?>" href="/profile">Profile</a>
        </li>
        <?php if (in_array(Permissions::MANAGE_PLATFORM, $user['permissions'])): ?>
          <li class="nav-item">
            <a class="nav-link text-primary" href="/admin">Administration</a>
          </li>
        <?php endif; ?>
        <li class="nav-item ms-3">
          <a class="btn btn-outline-danger btn-sm" href="/logout">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div style="padding-top: 70px;"></div>

<style>
  .navbar {
    padding: 1rem 0;
  }
  .navbar-brand {
    font-size: 1.5rem;
    color: #2c3e50;
  }
  .nav-link {
    color: #2c3e50;
    font-weight: 500;
    padding: 0.5rem 1rem;
  }
  .nav-link:hover,
  .nav-link.active {
    color: #0d6efd;
  }
  /* Dropdown Cart Styling */
  .dropdown-menu {
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .cart-items {
    max-height: 250px;
    overflow-y: auto;
  }
  .cart-preview-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
  }
  .cart-preview-item:last-child {
    border-bottom: none;
  }
  .cart-preview-item img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    margin-right: 0.75rem;
    border-radius: 4px;
  }
  .cart-preview-item-info {
    flex: 1;
  }
  .cart-preview-item-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
  }
  .cart-preview-item-price {
    color: #666;
    font-size: 0.85rem;
  }
</style>

<script>
  async function loadCartContent() {
    try {
      const response = await fetch('/api/cart', {
        headers: {
          'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
        }
      });
      const data = await response.json();
      if (data.success) {
        renderCartPreview(data.items, data.total);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  }

  function renderCartPreview(items, total) {
    const cartItems = document.querySelector('.cart-items');
    const cartTotal = document.getElementById('cartTotal');
    if (!cartItems || !cartTotal) return;
    cartItems.innerHTML = items.map(item => `
      <div class="cart-preview-item">
        <img src="${item.product_picture || '/assets/images/default-product.jpg'}" alt="${escapeHtml(item.name)}">
        <div class="cart-preview-item-info">
          <div class="cart-preview-item-name">${escapeHtml(item.name)}</div>
          <div class="cart-preview-item-price">${item.quantity}x ${formatPrice(item.price)}€</div>
        </div>
      </div>
    `).join('');
    cartTotal.textContent = `${formatPrice(total)}€`;
  }

  function escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(price);
  }

  document.addEventListener('DOMContentLoaded', loadCartContent);
  setInterval(loadCartContent, 30000);
</script>
