<?php
use App\Auth\AuthController;
use App\Api\Permissions;
use App\Models\Product;

$auth = new AuthController();
if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_PRODUCTS, $auth->getCurrentUser()['permissions'])) {
    header('Location: /');
    exit;
}

$pageTitle = 'Products Management';
$currentPage = 'products';

ob_start();
?>

<div class="admin-header">
    <h1>Products Management</h1>
    <button onclick="showProductModal(true)" class="btn btn-primary btn-admin">Add a product</button>
</div>

<div class="admin-card">
    <div id="productsTable"></div>
</div>

<!-- Modal using Bootstrap markup -->
<div id="productModal" class="modal fade" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productModalLabel">Add a product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="productForm" class="admin-form">
          <input type="hidden" id="productId" value="">
          <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" class="form-control" required></textarea>
          </div>
          <div class="form-group">
            <label for="price">Price</label>
            <input type="number" name="price" step="0.01" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="stock">Stock</label>
            <input type="number" name="stock" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="category">Category</label>
            <input type="text" name="category" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="image">Product image</label>
            <input type="file" name="image" accept="image/*" id="productImage" class="form-control">
            <div id="imagePreview" class="mt-2">
              <img id="currentImage" src="" alt="Preview" class="img-thumbnail mt-2" style="width:100px; height:100px; object-fit:cover;">
            </div>
          </div>
          <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
  let productModalInstance;
  let toastInstance;

  // Initialize the Bootstrap modal and toast on DOMContentLoaded
  document.addEventListener('DOMContentLoaded', function() {
      const productModalEl = document.getElementById('productModal');
      productModalInstance = new bootstrap.Modal(productModalEl);
      toastInstance = new bootstrap.Toast(document.getElementById('notificationToast'), {
        delay: 3000
      });
      loadProducts();
  });

  function showProductModal(isAdd = true) {
      isEditing = !isAdd;
      // Update the modal title using the same ID as defined in the modal header
      document.getElementById('productModalLabel').textContent = isAdd ? 'Add a product' : 'Edit the product';
      if (isAdd) {
          document.getElementById('productForm').reset();
          document.getElementById('productId').value = '';
          const currentImage = document.getElementById('currentImage');
          currentImage.src = '';
          currentImage.style.display = 'none';
      }
      productModalInstance.show();
  }

  function closeModal() {
      productModalInstance.hide();
      document.getElementById('productForm').reset();
  }

  function showNotification(message, type = 'success') {
    const toast = document.getElementById('notificationToast');
    const toastBody = toast.querySelector('.toast-body');
    
    // Remove any existing color classes
    toast.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning');
    
    // Add the appropriate color class
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

  async function loadProducts() {
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      try {
          const response = await fetch('/api/products', {
              headers: {
                  'Authorization': `Bearer ${token}`
              }
          });
          const data = await response.json();
          if (data.success) {
              renderProductsTable(data.products);
          }
      } catch (error) {
          console.error('Error:', error);
      }
  }

  function renderProductsTable(products) {
      const table = `
          <table class="table table-striped">
              <thead>
                  <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Price</th>
                      <th>Stock</th>
                      <th>Category</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
                  ${products.map(product => `
                      <tr>
                          <td>${product.id}</td>
                          <td>${product.name}</td>
                          <td>${product.price}€</td>
                          <td>${product.stock}</td>
                          <td>${product.category}</td>
                          <td>
                              <button onclick="editProduct(${product.id})" class="btn btn-secondary btn-sm">Edit</button>
                              <button onclick="deleteProduct(${product.id})" class="btn btn-danger btn-sm">Delete</button>
                          </td>
                      </tr>
                  `).join('')}
              </tbody>
          </table>
      `;
      document.getElementById('productsTable').innerHTML = table;
  }

  async function editProduct(productId) {
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      try {
          const response = await fetch(`/api/products/${productId}`, {
              headers: {
                  'Authorization': `Bearer ${token}`
              }
          });
          const data = await response.json();
          if (data.success) {
              const product = data.product;
              document.getElementById('productId').value = product.id;
              document.querySelector('input[name="name"]').value = product.name;
              document.querySelector('textarea[name="description"]').value = product.description;
              document.querySelector('input[name="price"]').value = product.price;
              document.querySelector('input[name="stock"]').value = product.stock;
              document.querySelector('input[name="category"]').value = product.category;
              const preview = document.getElementById('currentImage');
              if (product.product_picture) {
                  preview.src = product.product_picture;
                  preview.style.display = 'block';
                  preview.dataset.originalImage = product.product_picture;
              } else {
                  preview.style.display = 'none';
                  preview.dataset.originalImage = '';
              }
              document.getElementById('productModalLabel').textContent = 'Edit the product';
              showProductModal(false);
              isEditing = true;
          }
      } catch (error) {
          console.error('Error:', error);
      }
  }

  async function deleteProduct(productId) {
      if (!confirm('Are you sure you want to delete this product?')) return;
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      try {
          const response = await fetch(`/api/products/${productId}`, {
              method: 'DELETE',
              headers: { 'Authorization': `Bearer ${token}` }
          });
          const data = await response.json();
          if (data.success) {
              loadProducts();
              showNotification('Product deleted successfully');
          } else {
              throw new Error(data.message || 'Error deleting product');
          }
      } catch (error) {
          console.error('Error:', error);
          showNotification(error.message, 'error');
      }
  }

  document.getElementById('productImage').addEventListener('change', function(e) {
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

  document.getElementById('productForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const token = '<?= htmlspecialchars($_COOKIE["jwt"], ENT_QUOTES); ?>';
      const productId = document.getElementById('productId').value;
      const formData = new FormData(this);
      const imageFile = formData.get('image');
      let imageBase64 = null;
      
      if (imageFile && imageFile.size > 0) {
          const reader = new FileReader();
          imageBase64 = await new Promise((resolve) => {
              reader.onload = (e) => resolve(e.target.result);
              reader.readAsDataURL(imageFile);
          });
          data = {
                name: formData.get('name'),
                description: formData.get('description'),
                price: parseFloat(formData.get('price')),
                stock: parseInt(formData.get('stock')),
                category: formData.get('category'),
                image: imageBase64
            };
      } else if (isEditing) {
          const currentImage = document.getElementById('currentImage');
          image_url = currentImage.dataset.originalImage;
          data = {
                name: formData.get('name'),
                description: formData.get('description'),
                price: parseFloat(formData.get('price')),
                stock: parseInt(formData.get('stock')),
                category: formData.get('category'),
                image_url: image_url
                };
      }


      try {
          const response = await fetch(`/api/products${productId ? `/${productId}` : ''}`, {
              method: productId ? 'PUT' : 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'Authorization': `Bearer ${token}`
              },
              body: JSON.stringify(data)
          });
          const result = await response.json();
          if (result.success) {
              closeModal();
              loadProducts();
              showNotification(productId ? 'Product updated successfully' : 'Product created successfully');
          } else {
              result.message = 'Error during the operation';
              showNotification(result.message, 'error');
          }
      } catch (error) {
          console.error('Error:', error);
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

  loadProducts();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
