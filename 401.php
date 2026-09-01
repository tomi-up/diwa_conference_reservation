<?php
/**
 * Custom Standalone 401 Unauthorized Error Template (No Header/Footer)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(401);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 Authentication Required - UP Mindanao & DiWA Center</title>
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

                <!-- Custom Key Person Lock Visual Icon -->
                <div class="d-flex justify-content-center my-3">
                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning-emphasis" style="width: 100px; height: 100px;">
                        <i class="bi bi-person-lock" style="font-size: 3.25rem;"></i>
                    </div>
                </div>

                <span class="badge bg-warning-subtle text-warning-emphasis font-monospace px-3 py-1.5 rounded-pill mb-3 fw-bold" style="font-size: 0.8rem;">
                    ERROR 401
                </span>

                <h1 class="fw-bold text-dark mb-2" style="font-size: 1.75rem; color: #0f172a;">
                    Authentication Required
                </h1>

                <p class="text-secondary mb-4" style="font-size: 0.9375rem; line-height: 1.6;">
                    Your session has expired or authentication is required to access this resource. Please sign in with your official <strong>@up.edu.ph</strong> account.
                </p>

                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2.5 mb-2">
                    <a href="<?= APP_URL ?>/reserve" class="btn btn-primary px-4 py-2.5 rounded-3 fw-bold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In with UP Mail
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
