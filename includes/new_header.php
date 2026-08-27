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
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/images/diwa_logo-no_word.png">
    <!-- Outfit Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Driver.js Guided Tour CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <!-- calendar CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/user_calendar.css">
    <!-- Google Identity Services (GSI) SDK -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

<!-- header -->
<nav class="navbar sticky-top">
    <div class="container-fluid d-flex align-items-center" style="max-height: 100%;">

        <!-- logo -->
        <a class="navbar-brand" href="<?= APP_URL ?>/index">
            <img src="<?= APP_URL ?>/assets/images/diwa_logo-white.png" alt="DIWA Logo" class="brand-logo-landscape">
        </a>

        <!-- right side -->
        <div class="ms-auto" id="navbarMain">
            <ul class="navbar-nav">
                <?php if (is_user_logged_in()): ?>

                    <!-- Logged in -->
                    <li class="nav-item dropdown">
                        <a class="nav-link p-0 d-flex align-items-center gap-2 text-decoration-none"
                        href="#"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">

                            <div class="text-end lh-sm">
                                <div class="fw-medium text-white small">
                                    <?= e($_SESSION['user_name']) ?>
                                </div>
                                <div class="text-white-50" style="font-size: 0.7rem;">
                                    <?= e($_SESSION['user_email']) ?>
                                </div>
                            </div>

                            <?php if (!empty($_SESSION['user_picture'])): ?>
                                <img
                                    src="<?= e($_SESSION['user_picture']) ?>"
                                    alt="<?= e($_SESSION['user_name']) ?>"
                                    class="user-avatar"
                                >
                            <?php else: ?>
                                <span class="user-avatar user-avatar-fallback">
                                    <i class="bi bi-person-fill"></i>
                                </span>
                            <?php endif; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-0">
                            <?php if (is_admin_logged_in()): ?>
                                <li class="mb-1">
                                    <a class="dropdown-item text-dark small fw-semibold"
                                    href="<?= APP_URL ?>/admin/calendar">
                                        Admin Dashboard
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li class="mb-1">
                                <a class="dropdown-item text-dark small fw-semibold"
                                href="<?= APP_URL ?>/my-reservations">
                                    My Reservations
                                </a>
                            </li>

                            <li><hr class="dropdown-divider mb-2"></li>

                            <li>
                                <a class="dropdown-item text-danger small fw-semibold"
                                href="<?= APP_URL ?>/logout">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Sign Out
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

<!-- Global Alert Container (kept for layout compatibility; alerts now render via SweetAlert2) -->
<div id="globalAuthAlertContainer" class="container"></div>
<?php
$flash = get_flash_message();
if ($flash):
?>
    <script>window.globalFlashMessage = { type: <?= json_encode($flash['type']) ?>, message: <?= json_encode($flash['message']) ?> };</script>
<?php endif; ?>

<script>
function showAuthAlert(type, message) {
    const isDanger = (type === 'danger');
    const title = isDanger ? 'Access Restricted' : 'Notice';

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: isDanger ? 'error' : 'info',
            title: title,
            text: message,
            confirmButtonColor: '#951a1d',
            confirmButtonText: 'Got it',
            customClass: { confirmButton: 'btn btn-primary px-4' },
            buttonsStyling: false
        });
    } else {
        alert(title + ': ' + message);
    }
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
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_token: response.credential
        })
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
            showAuthAlert(
                'danger',
                data.error || 'Access restricted: Only official University of the Philippines (@up.edu.ph) accounts are permitted.'
            );
        }
    })
    .catch(err => {
        console.error('Google Auth Error:', err);
        showAuthAlert(
            'danger',
            'An error occurred while communicating with the server during Google Sign-In.'
        );
    });
}


// Wait until Google Identity Services is loaded
function initializeGoogleSignIn() {
    if (typeof google === 'undefined' || !google.accounts) {
        setTimeout(initializeGoogleSignIn, 100);
        return;
    }

    google.accounts.id.initialize({
        client_id: '<?= GOOGLE_CLIENT_ID ?>',
        callback: handleGoogleSignIn,
        auto_select: false
    });

    console.log('Google Sign-In initialized.');
}


// Initialize Google
initializeGoogleSignIn();

window.addEventListener('load', function () {
    if (typeof google !== 'undefined' && google.accounts) {
        google.accounts.id.initialize({
            client_id: '<?= GOOGLE_CLIENT_ID ?>',
            callback: handleGoogleSignIn,
            auto_select: false
        });

        google.accounts.id.renderButton(
            document.getElementById('googleSignInButton'),
            {
                type: 'standard',
                shape: 'rectangular',
                theme: 'outline',
                text: 'signin_with',
                size: 'large',
                locale: 'en',
                logo_alignment: 'left'
            }
        );
    }
});

document.getElementById('btnLogin')?.addEventListener('click', function () {
    google.accounts.id.prompt((notification) => {
        console.log('Google prompt notification:', notification);

        if (notification.isNotDisplayed()) {
            console.log('Google prompt was not displayed:', notification.getNotDisplayedReason());
        }

        if (notification.isSkippedMoment()) {
            console.log('Google prompt was skipped:', notification.getSkippedReason());
        }
    });
});

</script>


