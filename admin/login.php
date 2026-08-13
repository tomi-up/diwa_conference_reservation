<?php
/**
 * Admin Login Page with DIWA Branding
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    redirect('index');
}

$error = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $email_val = sanitize_input($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';

        if (empty($email_val) || empty($password)) {
            $error = 'Please enter both email address and password.';
        } else {
            $res = login_admin($email_val, $password);
            if ($res['success']) {
                set_flash_message('success', 'Welcome back, ' . e($_SESSION['admin_name']));
                redirect('index');
            } else {
                $error = $res['message'];
            }
        }
    }
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Conference Room Reservation</title>
    <!-- Outfit Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body {
            background-color: var(--diwa-slate);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-sm-5">
    <div class="text-center mb-4">
        <img src="<?= APP_URL ?>/assets/images/diwa_logo.png" alt="DIWA Logo" style="max-height: 80px; width: auto;" class="mb-3">
        <h3 class="fw-bold text-dark mb-1">Admin Portal</h3>
        <p class="text-muted small">Conference Room Reservation System</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show border-0" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
            <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="login" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="email" class="form-label">Administrator Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($email_val) ?>" placeholder="admin@example.com" required autofocus>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold">
            Log In to Dashboard
        </button>
    </form>

    <div class="text-center mt-4 pt-3 border-top">
        <a href="<?= APP_URL ?>/reserve" class="text-muted small text-decoration-none">
            Return to Public Site
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
