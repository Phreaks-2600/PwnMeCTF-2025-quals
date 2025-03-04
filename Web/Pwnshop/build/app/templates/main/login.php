<?php
use App\Auth\AuthController;
use App\Api\Permissions;
use App\Models\Settings;

$settings = new Settings();
$currentSettings = $settings->getGlobalSettings();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($currentSettings['site_name']) ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow p-4" style="max-width: 500px; width: 100%;">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Login</h2>
            <p class="text-muted">Welcome back</p>
        </div>
        
        <div class="alert alert-danger d-none" id="error-message"></div>
        
        <form id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="text-center mt-3">
            <p class="mb-0">No account yet? <a href="/register">Register</a></p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorDiv = document.getElementById('error-message');
            errorDiv.classList.add('d-none');
            
            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: document.getElementById('username').value,
                        password: document.getElementById('password').value
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    if (data.token) {
                        document.cookie = `jwt=${data.token}; path=/; secure; samesite=Strict`;
                    }
                    
                    setTimeout(() => {
                        window.location.href = '/products';
                    }, 100);
                } else {
                    errorDiv.textContent = data.message || 'Error while logging in';
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.textContent = 'Error while logging in';
                errorDiv.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
