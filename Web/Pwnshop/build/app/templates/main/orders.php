<?php
$pageTitle = 'My Orders';
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
    <!-- Filters -->
    <div class="row mb-4">
      <div class="col-md-4 mb-2">
        <input type="text" id="searchInput" class="form-control" placeholder="Search an order...">
      </div>
      <div class="col-md-4 mb-2">
        <select id="statusFilter" class="form-select">
          <option value="all">All status</option>
          <option value="pending">Pending</option>
          <option value="validated">Validated</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="col-md-4 mb-2">
        <select id="sortSelect" class="form-select">
          <option value="date_desc">Most recent</option>
          <option value="date_asc">Oldest</option>
          <option value="total_desc">Decreasing amount</option>
          <option value="total_asc">Increasing amount</option>
        </select>
      </div>
    </div>
    <div class="text-end mb-3">
      <button class="btn btn-primary" id="searchButton">Search</button>
    </div>
    <!-- Orders list -->
    <div id="ordersList" class="row g-3"></div>
    <!-- Pagination -->
    <nav>
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
  
  <script>
    let currentPage = 1;
    const itemsPerPage = 10;
    let toastInstance;
    document.addEventListener('DOMContentLoaded', function() {
      toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
        delay: 3000
      });
      loadOrders();
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
    async function loadOrders(page = 1, query = '', status = 'all', sort = 'date_desc') {
      try {
        const response = await fetch('/api/orders/search', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
          },
          body: JSON.stringify({ query, status, sort, page, limit: itemsPerPage })
        });
        const data = await response.json();
        if (data.success) {
          renderOrders(data.orders);
          renderPagination(data.total, page);
        } else {
          throw new Error(data.message || 'Error loading orders');
        }
      } catch (error) {
        console.error('Error:', error);
        showNotification(error.message, 'error');
        document.getElementById('ordersContainer').innerHTML = '<div class="alert alert-danger">An error occurred while loading orders.</div>';
      }
    }
    
    function renderOrders(orders) {
      const container = document.getElementById('ordersList');
      if (!orders || orders.length === 0) {
        container.innerHTML = '<div class="col-12 text-center">No orders found.</div>';
        return;
      }
      const status = 'pending';
      const statusText = {
        completed: 'Completed',
        cancelled: 'Cancelled',
        pending:   'Pending'
      };
      const badgeClass = {
        completed: 'bg-success',
        cancelled: 'bg-danger',
        pending:   'bg-warning text-dark'
      };
      container.innerHTML = orders.map(order => `
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Order #${order.order_id}</h5>
              <p class="card-text">Date: ${new Date(order.order_date).toLocaleDateString('fr-FR')}</p>
              <p class="card-text">Total: ${parseFloat(order.total).toFixed(2)} €</p>
              <span class="badge ${badgeClass[order.status] ?? 'bg-warning text-dark'}">${statusText[order.status] ?? 'Pending'}</span>
              <a href="/order?id=${order.order_id}" class="btn btn-outline-primary btn-sm">See details</a>
            </div>
          </div>
        </div>
      `).join('');
    }
    
    function renderPagination(total, currentPage) {
      const totalPages = Math.ceil(total / itemsPerPage);
      const pagination = document.getElementById('pagination');
      if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
      }
      let html = '';
      html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                 <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a>
               </li>`;
      for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                   <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                 </li>`;
      }
      html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                 <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>
               </li>`;
      pagination.innerHTML = html;
    }
    
    function changePage(page) {
      loadOrders(
        page,
        document.getElementById('searchInput').value,
        document.getElementById('statusFilter').value,
        document.getElementById('sortSelect').value
      );
    }
    
    document.getElementById('searchButton').addEventListener('click', () => {
      loadOrders(
        1,
        document.getElementById('searchInput').value,
        document.getElementById('statusFilter').value,
        document.getElementById('sortSelect').value
      );
    });
  </script>
</body>
</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
