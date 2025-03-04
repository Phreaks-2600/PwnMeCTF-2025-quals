<?php
use App\Auth\AuthController;
use App\Database\Database;
use App\Models\User;
use App\Models\Voucher;

$auth = new AuthController();
$db = Database::getInstance()->getConnection();
$user = new User($db);
$voucher = new Voucher($db);

$currentUser       = $auth->getCurrentUser();
$userProfile       = $user->getProfile($currentUser['user_id']);
$userBalance       = $user->getBalance($currentUser['user_id']);
$availableVouchers = $voucher->getUserVouchers($currentUser['user_id']);

$pageTitle = 'My Profile';
ob_start();
?>

<div class="container my-5">
  <div class="card mb-4">
    <div class="card-body d-flex align-items-center">
      <img src="<?= htmlspecialchars($userProfile['profile_picture'] ?: '/assets/img/profiles/default-avatar.png') ?>"
           alt="Profile Picture"
           class="rounded-circle me-3"
           style="width:120px; height:120px; object-fit:cover;">
      <div>
        <h3><?= htmlspecialchars($userProfile['username']) ?></h3>
        <p><?= htmlspecialchars($userProfile['email']) ?></p>
        <button class="btn btn-primary" onclick="editProfile()">
          Edit Profile
        </button>
      </div>
    </div>
  </div>
  <div class="row mb-4">
    <div class="col-md-6 mb-3">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">My Balance</h4>
          <p class="display-6"><?= number_format($userBalance, 2, ',', ' ') ?> €</p>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title">My Discount Vouchers</h4>
          <?php if (empty($availableVouchers)): ?>
            <p>No discount vouchers available.</p>
          <?php else: ?>
            <ul class="list-group" id="vouchersList">
              <?php foreach ($availableVouchers as $voucher): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <?= htmlspecialchars($voucher['code']) ?>
                  <span>-<?= number_format($voucher['amount'], 2, ',', ' ') ?> €</span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit My Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="alertMessage" class="alert d-none"></div>
        <form id="profileForm">
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" required value="">
          </div>
          <div class="mb-3">
            <label for="firstName" class="form-label">First Name</label>
            <input type="text" id="firstName" name="firstName" class="form-control" value="">
          </div>
          <div class="mb-3">
            <label for="lastName" class="form-label">Last Name</label>
            <input type="text" id="lastName" name="lastName" class="form-control" value="">
          </div>
          <div class="mb-3" id="imagePreview">
            <label for="profilePicture" class="form-label">Profile Picture</label>
            <input type="file" id="profilePicture" name="profilePicture" class="form-control" accept="image/*">
            <img id="currentImage" src=""
                 alt="Current Profile Picture"
                 class="img-thumbnail mt-2"
                 style="width:100px; height:100px; object-fit:cover;">
          </div>
          <button type="submit" class="btn btn-primary w-100">Save</button>
        </form>
        <hr>
        <h5 class="mt-3">Change Password</h5>
        <form id="passwordForm">
          <div class="mb-3">
            <label for="currentPassword" class="form-label">Current Password</label>
            <input type="password" id="currentPassword" name="currentPassword" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <input type="password" id="newPassword" name="newPassword" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-secondary w-100">Change Password</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let toastInstance;
  document.addEventListener('DOMContentLoaded', function() {
    const profileModalEl = document.getElementById('editProfileModal');
    window.profileModalInstance = new bootstrap.Modal(profileModalEl);
    toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
      delay: 3000
    });
    loadProfile();
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

  function showProfileModal() {
      window.profileModalInstance.show();
  }

  function closeModal() {
      window.profileModalInstance.hide();
      document.getElementById('profileForm').reset();
  }

  document.getElementById('profilePicture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('currentImage').src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  async function loadProfile() {
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      try {
          const response = await fetch('/api/profile', {
              headers: {
                  'Authorization': `Bearer ${token}`
              }
          });
          const data = await response.json();
          if (data.success) {
              const profile = data.user;
          }
      } catch (error) {
          console.error('Error:', error);
      }
  }
  
  async function editProfile() {
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      try {
          const response = await fetch(`/api/profile`, {
              headers: {
                  'Authorization': `Bearer ${token}`
              }
          });
          const data = await response.json();
          if (data.success) {
              const profile = data.user;
              document.getElementById('email').value = profile.email;
              document.querySelector('input[name="firstName"]').value = profile.first_name;
              document.querySelector('input[name="lastName"]').value = profile.last_name;
              const preview = document.getElementById('currentImage');
              if (profile.profile_picture) {
                  preview.src = profile.profile_picture;
                  preview.style.display = 'block';
                  preview.dataset.originalImage = profile.profile_picture;
              } else {
                  preview.style.display = 'none';
                  preview.dataset.originalImage = '';
              }
              showProfileModal();
          }
      } catch (error) {
          console.error('Error:', error);
      }
  }

  document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    try {
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      const formData = new FormData(this);
      const imageFile = formData.get('profilePicture');
      let imageBase64 = null;
      if (imageFile && imageFile.size > 0) {
          const reader = new FileReader();
          imageBase64 = await new Promise((resolve) => {
              reader.onload = (e) => resolve(e.target.result);
              reader.readAsDataURL(imageFile);
          });
          profileData = {
                email: formData.get('email'),
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                image: imageBase64
            };
      } else {
        profileData = {
          email: formData.get('email'),
          firstName: formData.get('firstName'),
          lastName: formData.get('lastName')
        };
      }
      try { 
        const response = await fetch('/api/profile', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(profileData)
        });
        const result = await response.json();
        if (result.success) {
          closeModal();
          loadProfile();
          showNotification('Profile updated successfully', 'success');
          setTimeout(() => location.reload(), 1500);
        } else {
          result.message = 'Error updating profile';
          showNotification(result.message, 'error');
        }
      } catch (error) {
        showNotification(error.message, 'error');
      }
    } catch (outerError) {
      console.error('Unexpected error:', outerError);
      showNotification(outerError.message, 'error');
    }
  });

  document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
      showNotification('The passwords do not match', 'error');
      return;
    }
    
    try {
      const response = await fetch('/api/profile/password', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer <?= htmlspecialchars($_COOKIE["jwt"] ?? '', ENT_QUOTES) ?>`
        },
        body: JSON.stringify({
          currentPassword: document.getElementById('currentPassword').value,
          newPassword: newPassword
        })
      });
      const data = await response.json();
      if (data.success) {
        closeModal();
        loadProfile();
        showNotification('Password changed successfully', 'success');
        this.reset();
      } else {
        data.message = 'Error changing password';
        showNotification(data.message, 'error');
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
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
