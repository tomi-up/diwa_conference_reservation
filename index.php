<?php
/**
 * Public Landing Homepage with DIWA Branding
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Home - Conference Room Reservation System";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Minimalist Hero Section -->
<section class="hero-header text-center" hidden>
    <div class="container">
        <h1 class="hero-title mb-3">Conference Room Reservation</h1>
        <p class="hero-subtitle lead mx-auto mb-4" style="max-width: 640px;">
            Seamless schedule checking and room bookings for <?= e(CONFERENCE_ROOM_NAME) ?>.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="reserve" class="btn btn-primary px-4 py-2 fw-semibold">
                <i class="bi bi-calendar-check me-2"></i> Reserve Room & Check Availability
            </a>
        </div>
    </div>
</section>


<!-- Content Section -->
<div class="position-relative overflow-hidden min-vh-100 w-100 d-flex align-items-center py-0">
    
    <!-- background image -->
    <img src="<?= APP_URL ?>/assets/images/diwa_header.jpg" class="position-absolute top-0 start-0 w-100 h-100" alt="Background" style="object-fit: cover; z-index: 1; filter: blur(5px);">
    
    <!-- dark tint overlay -->
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: 0.4; z-index: 2;"></div>

    <!-- main content section -->
    <div class="container position-relative" style="z-index: 3;">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-9">

                <div style="background-color: #ffffffc7;" class="position-relative border shadow-lg mb-4 px-4 py-5 text-center">
                    <div class="card-body">
                        <div class="mb-3">
                            <img src="<?= APP_URL ?>/assets/images/diwa_logo-no_word.png" alt="DIWA Logo" class="brand-logo-square mb-2" style="max-height: 110px; width: auto;">
                        </div>
                        <h3 class="fw-bold text-dark"><?= e(CONFERENCE_ROOM_NAME) ?></h3>
                        <p class="text-muted mb-4">Do you have an upcoming meeting? Reserve the conference room now!</p>
                        
                        <div hidden class="row g-3 justify-content-center mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border text-start">
                                    <span class="text-muted small fw-semibold d-block">LOCATION</span>
                                    <strong class="text-dark">DIWA Center Main Office</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border text-start">
                                    <span class="text-muted small fw-semibold d-block">CAPACITY</span>
                                    <strong class="text-dark">15 Persons</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border text-start">
                                    <span class="text-muted small fw-semibold d-block">AVAILABILITY</span>
                                    <strong class="text-success">Automated Checking</strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center">
                            <a href="reserve" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                                <i class="bi bi-calendar-check me-2"></i> Book Reservation
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
