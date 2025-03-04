<?php
use App\Auth\AuthController;
use App\Api\Permissions;

$auth = new AuthController();
if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_VOUCHERS, $auth->getCurrentUser()['permissions'])) {
  header('Location: /');
  exit;
}

$pageTitle = 'Vouchers Management';
$currentPage = 'vouchers';

ob_start();
?>

<div class="admin-header">
  <h1>Vouchers Management</h1>
  <button onclick="showAddVoucherModal(true)" class="btn btn-primary btn-admin">Add Voucher</button>
</div>

<div class="admin-card">
  <div id="vouchersTable">
    <!-- The list of vouchers will be loaded here -->
  </div>
</div>

<!-- Modal: Using Bootstrap structure -->
<div id="voucherModal" class="modal fade" tabindex="-1" aria-labelledby="VoucherModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="VoucherModalLabel">Add/Edit Voucher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="voucherForm" class="admin-form">
          <input type="hidden" name="voucherId" id="voucherId">
          <div class="form-group">
            <input type="text" name="code" placeholder="Code" class="form-control" required>
          </div>
          <div class="form-group">
            <input type="number" name="amount" placeholder="Amount" class="form-control" required>
          </div>
          <div class="form-group">
            <input type="number" name="max_uses" placeholder="Max Uses" class="form-control">
          </div>
          <div class="form-group form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
            <label class="form-check-label" for="isActive">Is Active</label>
          </div>
          <div class="mt-3">
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
  let voucherModalInstance;
  let toastInstance;

  document.addEventListener('DOMContentLoaded', function() {
    const voucherModalElement = document.getElementById('voucherModal');
    voucherModalInstance = new bootstrap.Modal(voucherModalElement);
    toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
      delay: 3000
    });
    loadVouchers();
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

  function showAddVoucherModal(isAdd = true) {
    isEditing = !isAdd;
    document.getElementById('VoucherModalLabel').textContent = isAdd ? 'Add Voucher' : 'Edit Voucher';
    if (isAdd) {
      document.getElementById('voucherForm').reset();
    }
    voucherModalInstance.show();
  }

  function closeModal() {
    voucherModalInstance.hide();
    document.getElementById('voucherForm').reset();
  }

  async function loadVouchers() {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch('/api/vouchers', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (data.success) {
        renderVouchersTable(data.vouchers);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  }

  function renderVouchersTable(vouchers) {
    const table = `
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Amount</th>
            <th>Max Uses</th>
            <th>Is Active</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${vouchers.map(voucher => `
            <tr>
              <td>${voucher.id}</td>
              <td>${voucher.code}</td>
              <td>${voucher.amount}</td>
              <td>${voucher.max_uses}</td>
              <td>${voucher.is_active ? 'Yes' : 'No'}</td>
              <td>
                <button onclick="editVoucher(${voucher.id})" class="btn btn-secondary btn-sm">Edit</button>
                <button onclick="deleteVoucher(${voucher.id})" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    document.getElementById('vouchersTable').innerHTML = table;
  }

  async function editVoucher(voucherId) {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch(`/api/vouchers/${voucherId}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (data.success) {
        const voucher = data.voucher;
        document.getElementById('voucherForm').reset();
        document.getElementById('voucherId').value = voucher.id;
        document.querySelector('input[name="code"]').value = voucher.code;
        document.querySelector('input[name="amount"]').value = voucher.amount;
        document.querySelector('input[name="max_uses"]').value = voucher.max_uses;
        document.getElementById('isActive').checked = voucher.is_active;
        showAddVoucherModal(false);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  }

  async function deleteVoucher(voucherId) {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch(`/api/vouchers/${voucherId}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (data.success) {
        loadVouchers();
        showNotification('Voucher deleted successfully');
      } else {
        throw new Error(data.message || 'Error deleting voucher');
      }
    } catch (error) {
      console.error('Error:', error);
      showNotification(error.message, 'error');
    }
  }

  document.getElementById('voucherForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    const formData = new FormData(this);
    const voucherId = formData.get('voucherId');
    const voucherData = {
      code: formData.get('code'),
      amount: formData.get('amount'),
      max_uses: formData.get('max_uses'),
      is_active: formData.get('is_active') ? true : false
    };
    try {
      const response = await fetch(isEditing ? `/api/vouchers/${voucherId}` : '/api/vouchers', {
        method: isEditing ? 'PUT' : 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(voucherData)
      });
      const data = await response.json();
      if (data.success) {
        closeModal();
        loadVouchers();
        showNotification(isEditing ? 'Voucher updated successfully' : 'Voucher created successfully');
      } else {
        throw new Error(data.message || 'Error during operation');
      }
    } catch (error) {
      showNotification(error.message, 'error');
    }
  });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
