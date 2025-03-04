<?php
use App\Auth\AuthController;
use App\Api\Permissions;

$auth = new AuthController();
if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_ORDERS, $auth->getCurrentUser()['permissions'])) {
    header('Location: /');
    exit;
}

$pageTitle = 'Orders Management';
$currentPage = 'orders';

ob_start();
?>

<div class="admin-header">
  <h1>Orders Management</h1>
</div>

<div class="admin-card">
  <div id="ordersTable">
    <!-- The orders table will be loaded here -->
  </div>
</div>

<!-- Modal: Using Bootstrap structure -->
<div id="orderModal" class="modal fade" tabindex="-1" aria-labelledby="OrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="OrderModalLabel">Edit Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="orderForm" class="admin-form">
          <input type="hidden" name="orderId" id="orderId">
          <div class="form-group">
            <label for="shipping_name">Shipping Name</label>
            <input type="text" name="shipping_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="shipping_address">Shipping Address</label>
            <input type="text" name="shipping_address" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="shipping_city">Shipping City</label>
            <input type="text" name="shipping_city" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="shipping_zipcode">Shipping Zip Code</label>
            <input type="text" name="shipping_zipcode" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="shipping_country">Shipping Country</label>
            <input type="text" name="shipping_country" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="shipping_phone">Shipping Phone</label>
            <input type="text" name="shipping_phone" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="status">Status</label>
            <select name="status" class="form-control" required>
              <option value="pending">Pending</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary btn-admin">Save</button>
            <button type="button" onclick="closeModal()" class="btn btn-secondary btn-admin">Cancel</button>
          </div>
        </form>
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
  let isEditing = false;
  let orderModalInstance;
  let toastInstance;

  document.addEventListener('DOMContentLoaded', function() {
    const orderModalElement = document.getElementById('orderModal');
    orderModalInstance = new bootstrap.Modal(orderModalElement);
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

  function showOrderModal(isAdd = true) {
    isEditing = !isAdd;
    document.getElementById('OrderModalLabel').textContent = isAdd ? 'Add Order' : 'Edit Order';
    if (isAdd) {
      document.getElementById('orderForm').reset();
      document.getElementById('orderId').value = '';
    }
    orderModalInstance.show();
  }

  async function loadOrders() {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch('/api/orders', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (!data.success) {
        throw new Error(data.message || 'Error loading orders');
      }
      renderOrdersTable(data.orders);
    } catch (error) {
      console.error('Error:', error);
      showNotification(error.message, 'error');
    }
  }

  function closeModal() {
    orderModalInstance.hide();
    document.getElementById('orderForm').reset();
  }

  function renderOrdersTable(orders) {
    const table = `
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Date</th>
            <th>Total</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${orders.map(order => `
            <tr>
              <td>${order.id}</td>
              <td>${order.shipping_name}</td>
              <td>${new Date(order.created_at).toLocaleDateString()}</td>
              <td>${order.total}€</td>
              <td>${order.status}</td>
              <td>
                <button onclick="editOrder(${order.id})" class="btn btn-secondary btn-sm">Edit</button>
                <button onclick="deleteOrder(${order.id})" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    document.getElementById('ordersTable').innerHTML = table;
  }

  async function editOrder(orderId) {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch(`/api/orders/${orderId}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (data.success) {
        const order = data.order;
        console.log(order);
        document.getElementById('orderId').value = order.id;
        document.querySelector('select[name="status"]').value = order.status;
        document.querySelector('input[name="shipping_name"]').value = order.shipping_name;
        document.querySelector('input[name="shipping_address"]').value = order.shipping_address;
        document.querySelector('input[name="shipping_city"]').value = order.shipping_city;
        document.querySelector('input[name="shipping_zipcode"]').value = order.shipping_zipcode;
        document.querySelector('input[name="shipping_country"]').value = order.shipping_country;
        document.querySelector('input[name="shipping_phone"]').value = order.shipping_phone;
        showOrderModal(false);
        isEditing = true;
      }
    } catch (error) {
      console.error('Error:', error);
    }
  }

  async function deleteOrder(orderId) {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch(`/api/orders/${orderId}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      });

      const data = await response.json();
      if (data.success) {
        loadOrders();
        showNotification('Order deleted successfully');
      } else {
        throw new Error(data.message || 'Error deleting order');
      }

      showNotification('Order deleted successfully', 'success');
      loadOrders();
    } catch (error) {
      console.error('Error:', error);
      showNotification(error.message, 'error');
    }
  }

  document.getElementById('orderForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      const orderId = document.getElementById('orderId').value;
      const formData = new FormData(this);
      const data = {
          status: formData.get('status'),
          shipping_name: formData.get('shipping_name'),
          shipping_address: formData.get('shipping_address'),
          shipping_city: formData.get('shipping_city'),
          shipping_zipcode: formData.get('shipping_zipcode'),
          shipping_country: formData.get('shipping_country'),
          shipping_phone: formData.get('shipping_phone')
      };

      try {
          const response = await fetch(`/api/orders${orderId ? `/${orderId}` : ''}`, {
              method: orderId ? 'PUT' : 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'Authorization': `Bearer ${token}`
              },
              body: JSON.stringify(data)
          });
          const result = await response.json();
          if (result.success) {
              closeModal();
              loadOrders();
              showNotification(orderId ? 'Order updated successfully' : 'Order created successfully');
          } else {
              throw new Error(result.message || 'Error during operation');
          }
      } catch (error) {
          showNotification(error.message, 'error');
      }
  });

  function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
          const later = () => {
              clearTimeout(timeout);
              func(...args);
          };
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
      };
  }

  document.addEventListener('DOMContentLoaded', loadOrders);
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
