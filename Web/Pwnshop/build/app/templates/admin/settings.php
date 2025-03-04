<?php
use App\Auth\AuthController;
use App\Api\Permissions;
use App\Models\Settings;

$auth = new AuthController();
if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_PLATFORM, $auth->getCurrentUser()['permissions'])) {
  header('Location: /');
  exit;
}

$currentPage = 'settings';
$pageTitle = 'Settings';

$settings = new Settings();
$currentSettings = $settings->getGlobalSettings();

$customCssPath = __DIR__ . '/../../public/assets/css/custom.css';
$customCss = file_get_contents($customCssPath);

ob_start();
?>

<div class="admin-header">
  <h1>Settings</h1>
</div>

<div class="admin-card">
  <h2>General Configuration</h2>
  <form id="settingsForm" class="admin-form">
    <div class="form-group">
      <label for="siteName">Site Name</label>
      <input type="text" id="siteName" name="siteName" value="<?= htmlspecialchars($currentSettings['site_name'] ?? 'PwnMe Shop'); ?>" class="form-control">
    </div>
    <div class="form-group">
      <label for="contactEmail">Contact Email</label>
      <input type="email" id="contactEmail" name="contactEmail" value="<?= htmlspecialchars($currentSettings['contact_email'] ?? 'contact@pwnme.fr'); ?>" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary btn-admin">Save</button>
  </form>
</div>

<div class="admin-card">
  <h2>Custom CSS</h2>
  <form id="customCssForm" class="admin-form">
    <div class="form-group">
      <label for="customCss">Custom CSS</label>
      <textarea id="customCss" name="customCss" rows="10" class="form-control code-editor" placeholder="/* Add your custom CSS here */"><?= $customCss; ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-admin">Apply CSS</button>
  </form>
</div>

<div class="admin-card">
  <h2>LESS Files</h2>
  <form id="lessUploadForm" class="admin-form">
    <div class="mb-3">
      <label for="lessFile" class="form-label">LESS File</label>
      <input type="file" id="lessFile" name="lessFile" class="form-control file-input" accept=".less">
      <small class="help-text">Select a .less file to upload</small>
    </div>
    <div class="form-group">
      <label for="lessPath">Destination Path (optional)</label>
      <input type="text" id="lessPath" name="lessPath" placeholder="e.g., components/button" class="form-control">
      <small class="help-text">Leave empty for root folder</small>
    </div>
    <button type="submit" class="btn btn-primary btn-admin">Upload</button>
  </form>

  <div class="less-files-list">
    <h3>LESS Files Available</h3>
    <div id="lessFilesList">Loading...</div>
  </div>
</div>

<div class="admin-card">
  <h2>LESS Imports Directories</h2>
  <form id="lessImportForm" class="admin-form">
    <div class="form-group">
      <label for="physicalPath">Physical Path</label>
      <input type="text" id="physicalPath" name="physicalPath" placeholder="e.g., /var/www/mysite/bootstrap" class="form-control">
      <small class="help-text">Absolute path to the directory containing the LESS files</small>
    </div>
    <div class="form-group">
      <label for="importPath">Import Path</label>
      <input type="text" id="importPath" name="importPath" placeholder="e.g., /mysite/bootstrap" class="form-control">
      <small class="help-text">Path used in LESS imports (@import "path/file.less")</small>
    </div>
    <button type="submit" class="btn btn-primary btn-admin">Add</button>
  </form>

  <div class="less-imports-list">
    <h3>Configured Import Directories</h3>
    <div id="lessImportsList">Loading...</div>
  </div>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  
  try {
    const response = await fetch('/api/settings/global', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify(Object.fromEntries(formData))
    });

    const data = await response.json();
    if (data.success) {
      showNotification('Settings saved successfully', 'success');
    } else {
      throw new Error(data.message || 'Error while saving');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification(error.message, 'error');
  }
});

document.getElementById('customCssForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  
  try {
    const response = await fetch('/api/settings/css', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        css: formData.get('customCss')
      })
    });

    const data = await response.json();
    if (data.success) {
      showNotification('Custom CSS applied successfully', 'success');
      // Reload the theme CSS
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = '/assets/css/theme.css?v=' + Date.now();
      document.head.appendChild(link);
    } else {
      throw new Error(data.message || 'Error while updating CSS');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification(error.message, 'error');
  }
});

document.getElementById('lessUploadForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  
  try {
    const response = await fetch('/api/settings/less/upload', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      body: formData
    });

    const data = await response.json();
    if (data.success) {
      showNotification('LESS File uploaded successfully', 'success');
      loadLessFiles();
    } else {
      throw new Error(data.message || 'Error while uploading LESS file');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification(error.message, 'error');
  }
});

async function loadLessFiles() {
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  try {
    const response = await fetch('/api/settings/less/files', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    const data = await response.json();
    if (data.success) {
      const filesList = document.getElementById('lessFilesList');
      filesList.innerHTML = data.files.map(file => `
        <div class="less-file-item">
          <span>${file.path}</span>
          <div class="actions">
            <button onclick="deleteLessFile('${file.path}')" class="btn btn-danger btn-sm">Delete</button>
          </div>
        </div>
      `).join('') || 'No LESS files available';
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification('Error while loading LESS files', 'error');
  }
}

async function deleteLessFile(path) {
  if (!confirm('Do you really want to delete this file?')) return;
  
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  try {
    const response = await fetch('/api/settings/less/delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ path })
    });

    const data = await response.json();
    if (data.success) {
      showNotification('File deleted successfully', 'success');
      loadLessFiles();
    } else {
      throw new Error(data.message || 'Error while deleting file');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification(error.message, 'error');
  }
}

document.addEventListener('DOMContentLoaded', loadLessFiles);

function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  setTimeout(() => notification.classList.add('show'), 100);
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

async function loadLessImports() {
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  try {
    const response = await fetch('/api/settings/less/imports', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    const data = await response.json();
    if (data.success) {
      const importsList = document.getElementById('lessImportsList');
      importsList.innerHTML = data.imports.map(imp => `
        <div class="less-import-item">
          <div class="paths">
            <div>
              <span class="path-label">Physical path:</span>
              <strong>${imp.physical_path}</strong>
            </div>
            <div>
              <span class="path-label">Import path:</span>
              <strong>${imp.import_path}</strong>
            </div>
          </div>
          <div class="actions">
            <button onclick="deleteLessImport('${imp.physical_path}')" class="btn btn-danger btn-sm">Delete</button>
          </div>
        </div>
      `).join('') || 'No configured import directory';
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification('Error while loading import directories', 'error');
  }
}

document.getElementById('lessImportForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  
  try {
    const response = await fetch('/api/settings/less/imports', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        physicalPath: formData.get('physicalPath'),
        importPath: formData.get('importPath')
      })
    });

    const data = await response.json();
    if (data.success) {
      showNotification('Import directory added successfully', 'success');
      e.target.reset();
      loadLessImports();
    } else {
      throw new Error(data.message || 'Error while adding import directory');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification(error.message, 'error');
  }
});

async function deleteLessImport(physicalPath) {
  if (!confirm('Do you really want to delete this import directory?')) return;
  const token = '<?= htmlspecialchars($_COOKIE["jwt"] ?? "", ENT_QUOTES); ?>';
  try {
    const response = await fetch('/api/settings/less/imports', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ physicalPath })
    });
    const data = await response.json();
    if (data.success) {
      showNotification('Import directory deleted successfully', 'success');
      loadLessImports();
    } else {
      throw new Error(data.message || 'Error while deleting import directory');
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification(error.message, 'error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadLessFiles();
  loadLessImports();
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/partials/layout.php';
?>
