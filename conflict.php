<?php
/**
 * Schedule Conflict Explanation Page (Minimalist Edition)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS);

$page_title = "Schedule Unavailable - Conference Room Reservation System";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow-sm border-0 text-center p-4">
                <div class="card-body">
                    <h2 class="fw-bold text-danger mb-2">Schedule Unavailable</h2>
                    <p class="lead text-secondary mb-4">
                        The requested schedule overlaps with an existing reservation.
                    </p>
                    <p class="text-muted mb-4">
                        Please choose another time slot or select a different reservation date.
                    </p>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="reserve<?= ($date) ? "?date=" . urlencode($date) : "" ?>" class="btn btn-primary px-4">
                            Back to Reservation Form
                        </a>
                        <a href="availability.php<?= ($date) ? "?date=" . urlencode($date) : "" ?>" class="btn btn-outline-secondary px-4">
                            Check Available Slots
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
