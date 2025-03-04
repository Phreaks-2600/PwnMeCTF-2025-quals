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
    <title>Register - <?= htmlspecialchars($currentSettings['site_name']) ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow p-4" style="max-width: 500px; width: 100%;">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Create Account</h2>
            <p class="text-muted">Join us today</p>
        </div>
        
        <div class="alert alert-danger d-none" id="error-message"></div>
        
        <form id="registerForm">
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="8">
                <small class="text-muted">At least 8 characters</small>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign Up</button>
        </form>
        <div class="text-center mt-3">
            <p class="mb-0">Already have an account? <a href="/">Login</a></p>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorDiv = document.getElementById('error-message');
            errorDiv.classList.add('d-none');
            
            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: document.getElementById('username').value,
                        password: document.getElementById('password').value,
                        email: document.getElementById('email').value,
                        firstName: document.getElementById('first_name').value,
                        lastName: document.getElementById('last_name').value
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/';
                } else {
                    errorDiv.textContent = data.error || 'Registration error';
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.textContent = 'Error during registration';
                errorDiv.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>