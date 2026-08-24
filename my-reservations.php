<?php
/**
 * Public "My Reservations" Page — logged-in users can view their booking
 * history and self-cancel a reservation up to 24 hours before it starts.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservation.php';

require_user_login();

$pdo = get_db_connection();
$reservations = get_user_reservations((int)$_SESSION['user_id'], $pdo);

$now = time();
$upcoming = [];
$history = [];

foreach ($reservations as $res) {
    $start_timestamp = strtotime($res['reservation_date'] . ' ' . $res['start_time']);
    $res['is_upcoming']     = ($res['status'] === 'CONFIRMED' && $start_timestamp >= $now);
    $res['can_cancel']      = ($res['status'] === 'CONFIRMED' && ($start_timestamp - $now) >= 86400);

    if ($res['is_upcoming']) {
        $upcoming[] = $res;
    } else {
        $history[] = $res;
    }
}

$page_title = "My Reservations - Conference Room Reservation System";
require_once __DIR__ . '/includes/header.php';

$status_badge_map = [
    'CONFIRMED' => 'bg-success',
    'CANCELLED' => 'bg-warning text-dark',
    'REJECTED'  => 'bg-danger',
];

function render_reservation_card(array $res, array $status_badge_map): void {
    $badge_class = $status_badge_map[$res['status']] ?? 'bg-secondary';
    ?>
    <div class="card border shadow-sm mb-3">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                <div>
                    <span class="fw-bold text-primary"><?= e(format_reservation_id($res['id'], $res['created_at'])) ?></span>
                    <span class="badge <?= $badge_class ?> ms-2"><?= e($res['status']) ?></span>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-calendar-event me-1"></i><?= e(format_date($res['reservation_date'])) ?>
                    &middot;
                    <i class="bi bi-clock me-1"></i><?= e(format_time($res['start_time'])) ?> &ndash; <?= e(format_time($res['end_time'])) ?>
                </div>
            </div>

            <p class="mb-2 text-dark"><?= nl2br(e($res['purpose'])) ?></p>
            <p class="mb-3 small text-muted">
                <i class="bi bi-people me-1"></i><?= e($res['project_team_office']) ?>
            </p>

            <?php if ($res['status'] !== 'CONFIRMED' && !empty($res['rejection_reason'])): ?>
                <div class="alert alert-light border small mb-3">
                    <strong>Reason:</strong> <?= e($res['rejection_reason']) ?>
                </div>
            <?php endif; ?>

            <?php if ($res['status'] === 'CONFIRMED'): ?>
                <?php if ($res['can_cancel']): ?>
                    <form class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="reservation_id" value="<?= (int)$res['id'] ?>">
                        <button type="button" class="btn btn-outline-danger btn-sm fw-semibold" onclick="confirmCancelMyReservation(this)">
                            <i class="bi bi-x-circle me-1"></i> Cancel Reservation
                        </button>
                    </form>
                <?php elseif ($res['is_upcoming']): ?>
                    <span class="small text-muted">
                        <i class="bi bi-lock-fill me-1"></i>Cancellation window closed &mdash; reservations can only be cancelled at least 24 hours before the start time.
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="mb-4">
                <h2 class="fw-bold text-dark mb-1">My Reservations</h2>
                <p class="text-muted small mb-0">Review your upcoming and past conference room bookings.</p>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="card border shadow-sm text-center p-5">
                    <div class="card-body">
                        <i class="bi bi-calendar-x fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark mb-2">No reservations yet</h5>
                        <p class="text-muted mb-4">You haven't booked the conference room yet.</p>
                        <a href="reserve" class="btn btn-primary px-4 fw-semibold">Reserve Room & Check Availability</a>
                    </div>
                </div>
            <?php else: ?>

                <h6 class="text-uppercase text-muted fw-bold small mb-3">Upcoming</h6>
                <?php if (empty($upcoming)): ?>
                    <p class="text-muted small mb-4">No upcoming reservations.</p>
                <?php else: ?>
                    <?php foreach ($upcoming as $res): render_reservation_card($res, $status_badge_map); endforeach; ?>
                <?php endif; ?>

                <h6 class="text-uppercase text-muted fw-bold small mb-3 mt-4">Past & Cancelled</h6>
                <?php if (empty($history)): ?>
                    <p class="text-muted small mb-4">No past reservations.</p>
                <?php else: ?>
                    <?php foreach ($history as $res): render_reservation_card($res, $status_badge_map); endforeach; ?>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
