<?php
/**
 * Admin Dashboard Overview & Statistics (Redesigned with Upcoming Reservations)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation.php';

$pdo = get_db_connection();
$today = date('Y-m-d');

// 1. Fetch Summary Metrics
$stmt_today = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = :today AND status = 'CONFIRMED'");
$stmt_today->execute(['today' => $today]);
$count_today = (int)$stmt_today->fetchColumn();

$stmt_upcoming = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date >= :today AND status = 'CONFIRMED'");
$stmt_upcoming->execute(['today' => $today]);
$count_upcoming = (int)$stmt_upcoming->fetchColumn();

$stmt_confirmed = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'CONFIRMED'");
$count_confirmed = (int)$stmt_confirmed->fetchColumn();

$stmt_cancelled = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'CANCELLED'");
$count_cancelled = (int)$stmt_cancelled->fetchColumn();

// 2. Fetch Today's Schedule
$stmt_today_list = $pdo->prepare("
    SELECT r.*
    FROM reservations r
    WHERE r.reservation_date = :today
      AND r.status = 'CONFIRMED'
    ORDER BY r.start_time ASC
");
$stmt_today_list->execute(['today' => $today]);
$today_reservations = $stmt_today_list->fetchAll();

// 3. Fetch Upcoming Reservations (Next 10 active bookings)
$upcoming_reservations = get_upcoming_reservations(10, $pdo);

$page_title = "Dashboard - Admin Portal";
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <h1 class="h3 fw-bold mb-0">Dashboard Overview</h1>
    <div hidden class="btn-toolbar mb-2 mb-md-0 gap-2">
        <a href="reservations" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i> View All Reservations
        </a>
        <a href="calendar" class="btn btn-sm btn-primary">
            <i class="bi bi-calendar3 me-1"></i> Calendar View
        </a>
    </div>
</div>

<!-- Top Metrics Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fw-semibold small mb-1">Today's Bookings</h6>
                    <h2 class="fw-bold mb-0 text-muted"><?= $count_today ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fw-semibold small mb-1">Upcoming Bookings</h6>
                    <h2 class="fw-bold mb-0 text-muted"><?= $count_upcoming ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fw-semibold small mb-1">Total Confirmed</h6>
                    <h2 class="fw-bold mb-0 text-muted"><?= $count_confirmed ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted fw-semibold small mb-1">Total Cancelled</h6>
                    <h2 class="fw-bold mb-0 text-muted"><?= $count_cancelled ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Today's Schedule Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold mb-0 text-dark">
            <i class="bi bi-clock-history text-primary me-2"></i>Today's Schedule (<?= format_date($today) ?>)
        </h6>
        <span class="badge bg-secondary rounded-pill fw-normal"><?= count($today_reservations) ?> Bookings</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($today_reservations)): ?>
            <div class="text-center py-4 text-muted small">
                No reservations scheduled for today.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>Requester</th>
                            <th>Office / Team</th>
                            <th>Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_reservations as $res): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-primary text-nowrap">
                                    <?= date('g:i', strtotime($res['start_time'])) ?>&ndash;<?= date('g:i A', strtotime($res['end_time'])) ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= e($res['requester_name']) ?></td>
                                <td class="text-secondary"><?= e($res['project_team_office']) ?></td>
                                <td class="small text-muted" title="<?= e($res['purpose']) ?>"><?= e(mb_strimwidth($res['purpose'], 0, 35, '...')) ?></td>
                                <td>
                                    <span class="badge bg-success">Confirmed</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom Section: Upcoming Reservations Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title fw-bold mb-0 text-dark">
            <i class="bi bi-calendar-event text-primary me-2"></i>Upcoming Reservations
        </h6>
        <a href="reservations" class="btn btn-sm btn-link text-decoration-none fw-semibold">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($upcoming_reservations)): ?>
            <div class="text-center py-4 text-muted small">
                No upcoming active reservations scheduled.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Time</th>
                            <th>Requester</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_reservations as $res): ?>
                            <tr>
                                <td class="ps-4 text-nowrap fw-semibold text-dark"><?= date('M j', strtotime($res['reservation_date'])) ?></td>
                                <td class="text-nowrap fw-medium">
                                    <?= date('g:i', strtotime($res['start_time'])) ?>&ndash;<?= date('g:i A', strtotime($res['end_time'])) ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= e($res['requester_name']) ?></td>
                                <td class="small text-muted" title="<?= e($res['purpose']) ?>"><?= e(mb_strimwidth($res['purpose'], 0, 45, '...')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
