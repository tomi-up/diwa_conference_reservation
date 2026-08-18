<?php
/**
 * Shared Header Template for Public Interface with Full-Visibility DIWA Logo & UP Google Sign-In
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'DIWA Center Conference Room Reservation System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <!-- Outfit Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <!-- Driver.js Guided Tour CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
    <!-- Google Identity Services (GSI) SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

<!-- High-Visibility Clean Navigation Header -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top shadow-sm bg-white">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/index">
            <img src="<?= APP_URL ?>/assets/images/diwa_logo.png" alt="DIWA Logo" class="brand-logo-landscape">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= (in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'index'])) ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/index">
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (in_array(basename($_SERVER['PHP_SELF']), ['reserve.php', 'reserve'])) ? 'active fw-semibold' : '' ?>" href="<?= APP_URL ?>/reserve">
                        Reserve
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <button type="button" id="btnStartTutorial" class="btn btn-outline-danger btn-sm rounded-pill fw-bold px-3 py-1 shadow-sm d-inline-flex align-items-center gap-1.5" title="Click to view interactive step-by-step reservation guide">
                        <i class="bi bi-question-circle-fill text-danger fs-6"></i> How to Book?
                    </button>
                </li>
            </ul>
            <ul class="navbar-nav align-items-center gap-2">
                <?php if (is_user_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-light border px-3 py-1.5 d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="badge bg-danger rounded-pill px-2">UP</span>
                            <span class="fw-semibold text-dark small"><?= e($_SESSION['user_name']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li class="dropdown-header text-muted small pb-1">Logged in with UP Mail</li>
                            <li><span class="dropdown-item-text fw-bold text-dark small py-1"><?= e($_SESSION['user_email']) ?></span></li>
                            <?php if (!empty($_SESSION['is_admin']) || !empty($_SESSION['admin_logged_in'])): ?>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item text-primary small fw-semibold" href="<?= APP_URL ?>/admin/calendar">
                                        <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider mb-1"></li>
                            <li>
                                <a class="dropdown-item text-danger small fw-semibold" href="<?= APP_URL ?>/logout">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <div id="g_id_onload"
                             data-client_id="<?= GOOGLE_CLIENT_ID ?>"
                             data-callback="handleGoogleSignIn"
                             data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin" 
                             data-type="standard" 
                             data-shape="rectangular" 
                             data-theme="outline" 
                             data-text="signin_with" 
                             data-size="large" 
                             data-locale="en"
                             data-logo_alignment="left">
                        </div>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<!-- Global Alert Container for Notifications & Auth Errors -->
<div id="globalAuthAlertContainer" class="container">
    <?php 
    $flash = get_flash_message();
    if ($flash): 
        $isDanger = ($flash['type'] === 'danger');
        $bgClass = $isDanger ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-light text-dark border';
        $iconClass = $isDanger ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle-fill text-primary';
    ?>
        <div class="alert alert-dismissible fade show border shadow-sm rounded-3 p-3 my-3 d-flex align-items-center justify-content-between <?= $bgClass ?>" role="alert" style="max-width: 800px; margin: 1rem auto;">
            <div class="d-flex align-items-center gap-3">
                <i class="bi <?= $iconClass ?> fs-4 flex-shrink-0"></i>
                <div><?= e($flash['message']) ?></div>
            </div>
            <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>

<script>
function showAuthAlert(type, message) {
    const container = document.getElementById('globalAuthAlertContainer');
    if (!container) return;
    
    const isDanger = (type === 'danger');
    const bgClass = isDanger ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-light text-dark border';
    const iconClass = isDanger ? 'bi-exclamation-triangle-fill text-danger' : 'bi-info-circle-fill text-primary';
    const title = isDanger ? 'Access Restricted' : 'Notice';
    
    container.innerHTML = `
        <div class="alert alert-dismissible fade show border shadow-sm rounded-3 p-3 my-3 d-flex align-items-center justify-content-between ${bgClass}" role="alert" style="max-width: 800px; margin: 1rem auto; transition: all 0.3s ease;">
            <div class="d-flex align-items-center gap-3">
                <i class="bi ${iconClass} fs-4 flex-shrink-0"></i>
                <div>
                    <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">${title}</h6>
                    <div class="small opacity-90">${escapeHtml(message)}</div>
                </div>
            </div>
            <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Auto dismiss after 8 seconds
    setTimeout(() => {
        const alertEl = container.querySelector('.alert');
        if (alertEl && window.bootstrap && window.bootstrap.Alert) {
            const bsAlert = window.bootstrap.Alert.getOrCreateInstance(alertEl);
            if (bsAlert) bsAlert.close();
        }
    }, 8000);
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function handleGoogleSignIn(response) {
    if (!response || !response.credential) {
        showAuthAlert('danger', 'Google authentication failed. Please try again.');
        return;
    }
    
    fetch('<?= APP_URL ?>/api/google_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_token: response.credential })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.redirect_url) {
                window.location.href = '<?= APP_URL ?>/' + data.redirect_url;
            } else {
                window.location.reload();
            }
        } else {
            showAuthAlert('danger', data.error || 'Access restricted: Only official University of the Philippines (@up.edu.ph) accounts are permitted.');
        }
    })
    .catch(err => {
        console.error('Google Auth Error:', err);
        showAuthAlert('danger', 'An error occurred while communicating with the server during Google Sign-In.');
    });
}
</script>


