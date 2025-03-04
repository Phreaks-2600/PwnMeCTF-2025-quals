<?php
use App\Models\Settings;

$settings = new Settings();
$currentSettings = $settings->getGlobalSettings();
$pageTitle = 'Products';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($currentSettings['site_name']) ?></title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="icon" type="image/png" href="/assets/img/favicon/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon/favicon.svg" />
  <link rel="shortcut icon" href="/assets/img/favicon/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png" />
  <link rel="manifest" href="/assets/img/favicon/site.webmanifest" />
  <style>
    .card-img-top {
      height: 200px;
      object-fit: cover;
    }
    .badge {
      font-size: 0.9rem;
    }
  </style>
</head>
<body class="bg-light">
  <?php require_once __DIR__ . '/partials/header.php'; ?>

  <div class="container mt-5 pt-5">
    <h1 class="text-center mb-4"><?= htmlspecialchars($pageTitle) ?></h1>
    
    <!-- Filters -->
    <div class="row mb-4 g-3">
      <div class="col-md-4">
        <input type="text" class="form-control" id="searchInput" placeholder="Search a product...">
      </div>
      <div class="col-md-4">
        <select class="form-select" id="categoryFilter">
          <option value="all">All categories</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= htmlspecialchars($category['category']) ?>">
              <?= htmlspecialchars($category['category']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <select class="form-select" id="sortSelect">
          <option value="id_asc">Most recent</option>
          <option value="id_desc">Oldest</option>
          <option value="name_asc">Name (A-Z)</option>
          <option value="name_desc">Name (Z-A)</option>
          <option value="price_asc">Price (ascending)</option>
          <option value="price_desc">Price (descending)</option>
        </select>
      </div>
    </div>
    
    <div class="text-center mb-4">
      <button class="btn btn-primary" id="searchButton">Search</button>
    </div>
    
    <!-- Products grid -->
    <div class="row" id="productsGrid">
      <!-- Products will be injected here via JavaScript -->
    </div>
    
    <!-- Pagination -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>
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
  <!-- JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Utility functions
    function getStockStatus(stock) {
      if (stock <= 0) {
        return '<span class="badge bg-danger">Out of stock</span>';
      } else if (stock < 10) {
        return `<span class="badge bg-warning text-dark">Only ${stock} left</span>`;
      }
      return '<span class="badge bg-success">In stock</span>';
    }

    function formatPrice(price) {
      return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2 }).format(price);
    }

    function escapeHtml(unsafe) {
      return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    // Rendering products as Bootstrap cards
    function renderProducts(products) {
      const grid = document.getElementById('productsGrid');
      if (products.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center">No products found</div>';
        return;
      }
      grid.innerHTML = products.map(product => `
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <img src="${product.product_picture || '/assets/images/default-product.jpg'}" class="card-img-top" alt="${escapeHtml(product.name)}">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">${escapeHtml(product.name)}</h5>
              <p class="card-text">${escapeHtml(product.description)}</p>
              <p>${getStockStatus(product.stock)}</p>
              <div class="mt-auto d-flex justify-content-between align-items-center">
                <span class="fw-bold">${formatPrice(product.price)} €</span>
                <button class="btn btn-sm btn-primary" onclick="addToCart(${product.id})" ${product.stock <= 0 ? 'disabled' : ''}>
                  Add to cart
                </button>
              </div>
            </div>
          </div>
        </div>
      `).join('');
    }
    
    let toastInstance;
    document.addEventListener('DOMContentLoaded', function() {
      toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
        delay: 3000
      });
      loadProducts();
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

    // Rendering pagination
    function renderPagination(total, currentPage, itemsPerPage) {
      const pagination = document.getElementById('pagination');
      const totalPages = Math.ceil(total / itemsPerPage);
      if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
      }
      let html = '';
      // Previous button
      html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                 <a class="page-link" href="#" onclick="changePage(${currentPage-1})">Previous</a>
               </li>`;
      // Page numbers (simplified display)
      for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                   <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                 </li>`;
      }
      // Next button
      html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                 <a class="page-link" href="#" onclick="changePage(${currentPage+1})">Next</a>
               </li>`;
      pagination.innerHTML = html;
    }

    // Loading products via the API
    async function loadProducts(page = 1, query = '', category = 'all', sort = 'id_asc') {
      const searchButton = document.getElementById('searchButton');
      searchButton.disabled = true;
      try {
        const response = await fetch('/api/products/search', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          },
          body: JSON.stringify({
            query,
            category,
            sort,
            page,
            limit: 10
          })
        });
        const data = await response.json();
        if (data.success) {
          renderProducts(data.products);
          renderPagination(data.total, page, 10);
        }
      } catch (error) {
        console.error('Erreur:', error);
      } finally {
        searchButton.disabled = false;
      }
    }

    function changePage(page) {
      loadProducts(
        page,
        document.getElementById('searchInput').value,
        document.getElementById('categoryFilter').value,
        document.getElementById('sortSelect').value
      );
    }

    // User actions management
    document.getElementById('searchButton').addEventListener('click', () => {
      loadProducts(
        1,
        document.getElementById('searchInput').value,
        document.getElementById('categoryFilter').value,
        document.getElementById('sortSelect').value
      );
    });
    // Function to add to cart
    async function addToCart(productId) {
      try {
        const response = await fetch('/api/cart', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          },
          body: JSON.stringify({
            product_id: productId,
            quantity: 1
          })
        });
        const data = await response.json();
        if (data.success) {
            loadCartContent();
            loadProducts();
            showNotification('Product added to cart');
        } else {
          showNotification(data.message || 'Error adding product to cart', 'error');
        }
      } catch (error) {
        showNotification('Error adding product to cart', 'error');
      }
    }
  </script>
</body>
</html>
