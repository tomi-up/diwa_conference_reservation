<?php
/**
 * Reservation Success Confirmation Page (Minimalist Edition)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reservation.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$reservation = null;

if ($id) {
    $pdo = get_db_connection();
    $reservation = get_reservation_by_id($id, $pdo);
}

if (!$reservation) {
    redirect('index.php');
}

$page_title = "Reservation Confirmed - Conference Room Reservation System";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-4">

            <div class="card shadow-sm border-0 text-center p-4">
                <div class="card-body">
                    <h2 class="fw-bold text-success mb-2">Reservation Confirmed</h2>
                    <p class="text-secondary mb-4">Your conference room reservation has been successfully recorded.</p>

                    <div class="card bg-light border-0 mb-4 text-start p-3">
                        <div class="card-body">
                            <h6 class="text-uppercase text-muted fw-bold mb-3 border-bottom pb-2">
                                Reservation Summary
                            </h6>
                            <dl class="row mb-0 fs-6">
                                <dt class="col-sm-4 text-muted">Reservation ID:</dt>
                                <dd class="col-sm-8 fw-bold text-primary"><?= format_reservation_id($reservation['id'], $reservation['created_at']) ?></dd>

                                <dt class="col-sm-4 text-muted">Facility:</dt>
                                <dd class="col-sm-8 fw-semibold"><?= e(CONFERENCE_ROOM_NAME) ?></dd>

                                <dt class="col-sm-4 text-muted">Date:</dt>
                                <dd class="col-sm-8"><?= format_date($reservation['reservation_date']) ?></dd>

                                <dt class="col-sm-4 text-muted">Time Slot:</dt>
                                <dd class="col-sm-8"><?= format_time($reservation['start_time']) ?> &ndash; <?= format_time($reservation['end_time']) ?></dd>

                                <dt class="col-sm-4 text-muted">Requester Email:</dt>
                                <dd class="col-sm-8"><?= e($reservation['requester_email']) ?></dd>
                            </dl>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        A confirmation email has been sent to your email address.
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="reserve" class="btn btn-primary px-4">
                            Make Another Reservation
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary px-4">
                            Return Home
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
