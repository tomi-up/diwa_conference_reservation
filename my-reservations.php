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

    // Project colors
    $project_colors = [
        'DiWA Core' => '#d1393e',
        'ISC' => '#d1393e',
        'Ops Team' => '#4fa576',
        'RESCUE' => '#da5b70',
        'IRDSS' => '#cfb767',
        'Wolbachia' => '#7b7ddb',
        'Scaling Up of Diwa App' => '#d1393e',
        'RabDash DC' => '#db7860',
        'MATALab' => '#d167c3',
        'Others' => '#8e6ad1'
    ];

    $circle_color = $project_colors[$res['project_team_office']] ?? '#6c757d';
    ?>

    <div class="mb-4">

        <!-- DATE HEADER -->
        <div class="d-flex align-items-center gap-3 mb-2">

            <div class="fw-bold text-dark text-nowrap">
                <?= e(format_date($res['reservation_date'])) ?>
            </div>

            <div class="flex-grow-1"
                style="height: 1px; background-color: #dee2e6;">
            </div>

        </div>


        <!-- RESERVATION -->
        <div class="d-flex align-items-start py-2">

            <!-- Start Time -->
            <div
                class="d-flex flex-column justify-content-center text-black flex-shrink-0"
                style="width: 55px;"
            >
                <h5 class="fw-bolder mb-0">
                    <?= e(format_time($res['start_time'])) ?>
                </h5>
            </div>


            <!-- Circle -->
            <div
                class="d-flex align-items-center justify-content-center flex-shrink-0"
                style="width: 30px; margin-right: 12px;"
            >
                <span
                    class="d-block rounded-circle"
                    style="
                        width: 10px;
                        height: 10px;
                        background-color: <?= e($circle_color) ?>;
                    "
                ></span>
            </div>


            <!-- Reservation Information -->
            <div class="flex-grow-1 min-width-0">

                <!-- Purpose -->
                <div class="fw-semibold text-black">
                    <h4 class="fw-bold m-0">
                        <?= e($res['purpose']) ?>
                    </h4>
                </div>


                <!-- Details -->
                <div class="small text-muted d-flex align-items-center gap-2 flex-wrap">

                    <span>
                        <?= e(format_time($res['start_time'])) ?>
                        &ndash;
                        <?= e(format_time($res['end_time'])) ?>
                    </span>

                    <span>
                        <?= e($res['project_team_office']) ?>
                    </span>

                    <span>
                        <?= e($res['requester_name'] ?? 'Reserved') ?>
                    </span>

                </div>


                <!-- Status -->
                <div class="mt-1">
                    <span class="badge <?= $badge_class ?>">
                        <?= e($res['status']) ?>
                    </span>
                </div>


                <!-- Rejection / Cancellation Reason -->
                <?php if (
                    $res['status'] !== 'CONFIRMED' &&
                    !empty($res['rejection_reason'])
                ): ?>

                    <div class="alert alert-light border small mt-3 mb-2">
                        <strong>Reason:</strong>
                        <?= e($res['rejection_reason']) ?>
                    </div>

                <?php endif; ?>


                <!-- Cancellation -->
                <?php if ($res['status'] === 'CONFIRMED'): ?>

                    <?php if ($res['can_cancel']): ?>

                        <form class="d-inline mt-2">
                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="reservation_id"
                                value="<?= (int)$res['id'] ?>"
                            >

                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm fw-semibold mt-2"
                                onclick="confirmCancelMyReservation(this)"
                            >
                                <i class="bi bi-x-circle me-1"></i>
                                Cancel Reservation
                            </button>
                        </form>

                    <?php elseif ($res['is_upcoming']): ?>

                        <div class="small text-muted mt-2">
                            <i class="bi bi-lock-fill me-1"></i>
                            Cancellation window closed.
                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

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
