<?php
use App\Auth\AuthController;
use App\Api\Permissions;
use App\Models\User;

$auth = new AuthController();
if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_USERS, $auth->getCurrentUser()['permissions'])) {
    header('Location: /');
    exit;
}

$pageTitle = 'Users Management';
$currentPage = 'users';

ob_start();
?>

<div class="admin-header">
  <h1>Users Management</h1>
  <button onclick="showAddUserModal(true)" class="btn btn-primary btn-admin">Add User</button>
</div>

<div class="admin-card">
  <div id="usersTable">
    <!-- Users table will be loaded here -->
  </div>
</div>

<!-- Modal structure using Bootstrap -->
<div id="userModal" class="modal fade" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalLabel">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="userForm" class="admin-form">
          <input type="hidden" name="userId" id="userId">
          <div class="form-group">
            <input type="text" name="username" placeholder="Username" class="form-control" required>
          </div>
          <div class="form-group">
            <input type="text" name="firstName" placeholder="First Name" class="form-control" required>
          </div>
          <div class="form-group">
            <input type="text" name="lastName" placeholder="Last Name" class="form-control" required>
          </div>
          <div class="form-group">
            <input type="email" name="email" placeholder="Email" class="form-control" required>
          </div>
          <div class="form-group">
            <input type="password" name="password" placeholder="Password" class="form-control">
          </div>
          <div class="mb-3">
            <label for="image">User image</label>
            <input type="file" name="image" accept="image/*" id="userImage" class="form-control">
            <div id="imagePreview" class="mt-2">
              <img id="currentImage" src="" alt="Preview" class="img-thumbnail mt-2" style="width:100px; height:100px; object-fit:cover;">
            </div>
          </div>
          <h3>Permissions</h3>
          <div class="permissions-grid">
            <?php foreach (Permissions::getAllPermissions() as $permission): ?>
              <label>
                <input type="checkbox" name="permissions[]" value="<?= $permission ?>">
                <?= $permission ?>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-primary btn-admin">Save</button>
            <button type="button" class="btn btn-secondary btn-admin" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

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
  let userModalInstance;
  let toastInstance;
  
  document.addEventListener('DOMContentLoaded', function() {
    const userModalElement = document.getElementById('userModal');
    userModalInstance = new bootstrap.Modal(userModalElement);
    toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
      delay: 3000
    });
    loadUsers();
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


  function showAddUserModal(isAdd = true) {
    isEditing = !isAdd;
    document.getElementById('userModalLabel').textContent = isAdd ? 'Add User' : 'Edit User';
    if (isAdd) {
      document.getElementById('userForm').reset();
      document.querySelector('input[name="password"]').required = true;
      const currentImage = document.getElementById('currentImage');
      currentImage.src = '';
      currentImage.style.display = 'none';
    } else {
      document.querySelector('input[name="password"]').required = false;
    }
    userModalInstance.show();
  }

  function closeModal() {
    userModalInstance.hide();
    document.getElementById('userForm').reset();
  }

  async function loadUsers() {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch('/api/users', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (data.success) {
        renderUsersTable(data.users);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  }

  function renderUsersTable(users) {
    const table = `
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${users.map(user => `
            <tr>
              <td>${user.id}</td>
              <td>${user.username}</td>
              <td>${user.firstName}</td>
              <td>${user.lastName}</td>
              <td>${user.email}</td>
              <td>
                <button onclick="editUser(${user.id})" class="btn btn-secondary btn-sm">Edit</button>
                <button onclick="deleteUser(${user.id})" class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    document.getElementById('usersTable').innerHTML = table;
  }

  async function editUser(userId) {
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch(`/api/users/${userId}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (data.success) {
        const user = data.user;
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = user.id;
        document.querySelector('input[name="username"]').value = user.username;
        document.querySelector('input[name="firstName"]').value = user.firstName;
        document.querySelector('input[name="lastName"]').value = user.lastName;
        document.querySelector('input[name="email"]').value = user.email;
        const preview = document.getElementById('currentImage');
        if (user.user_picture) {
            preview.src = user.user_picture;
            preview.style.display = 'block';
            preview.dataset.originalImage = user.user_picture;
        } else {
            preview.style.display = 'none';
            preview.dataset.originalImage = '';
        }
        const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
        checkboxes.forEach(checkbox => {
          checkbox.checked = user.permissions.includes(checkbox.value);
        });
        showAddUserModal(false);
        showNotification('User details loaded successfully', 'success');
      }
    } catch (error) {
      showNotification('Error while loading user details', 'error');
    }
  }

  async function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    try {
      const response = await fetch(`/api/users/${userId}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (data.success) {
        loadUsers();
        showNotification('User deleted successfully', 'success');
      } else {
        showNotification(data.message || 'Error while deleting user', 'error');
      }
    } catch (error) {
      showNotification('Error while deleting user', 'error');
    }
  }

  document.getElementById('userImage').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
              const preview = document.getElementById('currentImage');
              preview.src = e.target.result;
              preview.style.display = 'block';
          }
          reader.readAsDataURL(file);
      }
  });

  document.getElementById('userForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
    const formData = new FormData(this);
    const userId = formData.get('userId');
    const imageFile = formData.get('image');
    let imageBase64 = null;
    if (imageFile && imageFile.size > 0) {
          const reader = new FileReader();
          imageBase64 = await new Promise((resolve) => {
              reader.onload = (e) => resolve(e.target.result);
              reader.readAsDataURL(imageFile);
          });
          userData = {
                username: formData.get('username'),
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                image: imageBase64
            };
      } else if (isEditing) {
          const currentImage = document.getElementById('currentImage');
          image_url = currentImage.dataset.originalImage;
          userData = {
                username: formData.get('username'),
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                image_url: image_url
            };
      }
    
    if (formData.get('password')) {
      userData.password = formData.get('password');
    }
    try {
      const response = await fetch(isEditing ? `/api/users/${userId}` : '/api/users', {
        method: isEditing ? 'PUT' : 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(userData)
      });
      const data = await response.json();
      if (data.success) {
        closeModal();
        loadUsers();
        showNotification('User saved successfully', 'success');
      } else {
        showNotification(data.message || 'Error during the operation', 'error');
      }
    } catch (error) {
      showNotification('Error during the operation', 'error');
    }
  });

  loadUsers();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
