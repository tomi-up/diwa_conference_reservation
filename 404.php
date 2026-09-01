<?php
/**
 * Custom Standalone 404 Page Not Found Error Template (No Header/Footer, No Lottie)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Page Not Found - UP Mindanao & DiWA Center</title>
    <!-- Outfit Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            max-width: 540px;
            width: 100%;
        }
        .error-number {
            font-size: 6.5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, #951a1d 0%, #32444e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 d-flex justify-content-center">
            <div class="error-card p-4 p-md-5 text-center">
                <!-- Clean Brand Logo -->
                <div class="mb-4">
                    <img src="<?= APP_URL ?>/assets/images/diwa_logo_landscape.png" alt="DIWA Logo" style="height: 48px; width: auto;">
                </div>

                <!-- Custom 404 Typography Visual -->
                <div class="error-number mb-2">404</div>

                <span class="badge bg-danger-subtle text-danger font-monospace px-3 py-1.5 rounded-pill mb-3 fw-bold" style="font-size: 0.8rem;">
                    PAGE NOT FOUND
                </span>

                <h1 class="fw-bold text-dark mb-2" style="font-size: 1.75rem; color: #0f172a;">
                    Lost in Space?
                </h1>

                <p class="text-secondary mb-4" style="font-size: 0.9375rem; line-height: 1.6;">
                    The page you are looking for doesn't exist, has been removed, or the URL might be mistyped.
                </p>

                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2.5 mb-2">
                    <a href="<?= APP_URL ?>/reserve" class="btn btn-primary px-4 py-2.5 rounded-3 fw-bold shadow-sm">
                        <i class="bi bi-house-door-fill me-2"></i>Back to Reservation Page
                    </a>
                </div>

                <div class="mt-4 pt-3 border-top text-muted small">
                    UP Mindanao &ndash; DiWA Center Conference Room Reservation System
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
