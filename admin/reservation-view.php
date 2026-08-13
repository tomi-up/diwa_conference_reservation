<?php
/**
 * Admin Reservation Detailed View & Actions (With SweetAlert2 Cancellation)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/email.php';

$pdo = get_db_connection();
$id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    redirect('reservations.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (verify_csrf_token($token)) {
        if ($action === 'resend_email') {
            $res = get_reservation_by_id($id, $pdo);
            if ($res && $res['status'] === 'CONFIRMED') {
                send_reservation_confirmation_email($id, $pdo);
            } elseif ($res && $res['status'] === 'CANCELLED') {
                send_reservation_cancellation_email($id, $res['rejection_reason'] ?? 'Administrative cancellation', $pdo);
            }
            set_flash_message('success', 'Email notification successfully resent.');
        } elseif ($action === 'cancel') {
            $reason = sanitize_input($_POST['cancellation_reason'] ?? 'Administrative cancellation');
            update_reservation_status($id, 'CANCELLED', $reason, $pdo);
            send_reservation_cancellation_email($id, $reason, $pdo);
            set_flash_message('warning', "Reservation has been cancelled.");
        }
        redirect("reservation-view.php?id={$id}");
    }
}

$reservation = get_reservation_by_id($id, $pdo);

if (!$reservation) {
    set_flash_message('danger', 'Reservation not found.');
    redirect('reservations.php');
}

$page_title = "Reservation Details - Admin Portal";
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h3 fw-bold mb-1">Conference Room Reservation</h1>
        <div class="font-monospace fs-5 text-danger fw-bold">
            <?= format_reservation_id($reservation['id'], $reservation['created_at']) ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="reservations.php" class="btn btn-outline-secondary btn-sm fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Reservations
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-info-circle text-primary me-2"></i>Reservation Details
                </h5>
                <div>
                    <?php if ($reservation['status'] === 'CONFIRMED'): ?>
                        <span class="badge bg-success fs-6 px-3 py-1.5 fw-semibold"><i class="bi bi-check-lg me-1"></i>Confirmed</span>
                    <?php elseif ($reservation['status'] === 'CANCELLED'): ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-1.5 fw-semibold"><i class="bi bi-x-circle me-1"></i>Cancelled</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6 px-3 py-1.5 fw-semibold">Rejected</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4 p-md-5">
                
                <div class="mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Requester</div>
                    <div class="fs-5 fw-bold text-dark"><?= e($reservation['requester_name']) ?></div>
                </div>

                <div class="mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Email</div>
                    <div class="fs-6 text-primary fw-semibold"><?= e($reservation['requester_email']) ?></div>
                </div>

                <div class="mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Office / Team</div>
                    <div class="fs-6 text-dark fw-medium"><?= e($reservation['project_team_office']) ?></div>
                </div>

                <div class="mb-4">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Purpose</div>
                    <div class="p-3 bg-light rounded border text-dark fs-6">
                        <?= nl2br(e($reservation['purpose'])) ?>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="small text-muted fw-bold text-uppercase mb-1">Date</div>
                        <div class="fs-6 fw-bold text-dark"><?= format_date($reservation['reservation_date']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted fw-bold text-uppercase mb-1">Time</div>
                        <div class="fs-6 fw-bold text-dark"><?= format_time($reservation['start_time']) ?> &ndash; <?= format_time($reservation['end_time']) ?></div>
                    </div>
                </div>

                <?php if (!empty($reservation['rejection_reason'])): ?>
                    <div class="mb-4">
                        <div class="small text-danger fw-bold text-uppercase mb-1">Reason for Cancellation</div>
                        <div class="p-3 bg-danger-subtle text-danger rounded border border-danger-subtle fw-semibold fs-6">
                            <?= nl2br(e($reservation['rejection_reason'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center pt-4 border-top gap-3 flex-wrap">
                    <form method="POST" action="reservation-view.php?id=<?= $id ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="resend_email">
                        <button type="submit" class="btn btn-outline-primary fw-semibold">
                            <i class="bi bi-envelope-at me-1"></i> Resend Email
                        </button>
                    </form>

                    <?php if ($reservation['status'] === 'CONFIRMED'): ?>
                        <form method="POST" action="reservation-view.php?id=<?= $id ?>" class="form-cancel-reservation">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="cancellation_reason" class="cancellation-reason-input" value="">
                            <button type="button" class="btn btn-danger btn-cancel-trigger fw-semibold px-4">
                                <i class="bi bi-x-circle me-1"></i> Cancel Reservation
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
