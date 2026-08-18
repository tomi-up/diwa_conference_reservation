<?php
/**
 * Admin Reservations Management View with Search, Filters, Pagination, and SweetAlert2 Cancel
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/email.php';

$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $res_id = (int)($_POST['reservation_id'] ?? 0);
    $token  = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (verify_csrf_token($token) && $res_id > 0) {
        if ($action === 'resend_email') {
            $sent = send_reservation_confirmation_email($res_id, $pdo);
            if ($sent) {
                set_flash_message('success', "Confirmation email successfully resent.");
            } else {
                set_flash_message('warning', "Email dispatch attempted, but failed.");
            }
        } elseif ($action === 'cancel') {
            $reason = sanitize_input($_POST['cancellation_reason'] ?? 'Administrative cancellation');
            update_reservation_status($res_id, 'CANCELLED', $reason, $pdo);
            send_reservation_cancellation_email($res_id, $reason, $pdo);
            set_flash_message('warning', "Reservation has been cancelled.");
        }
        redirect('reservations');
    }
}

$filters = [
    'status'  => filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS),
    'date'    => filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS),
    'search'  => filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS)
];

$page = (int)filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$per_page = 10;

$pagination    = get_reservations_paginated($filters, $page, $per_page, $pdo);
$reservations  = $pagination['data'];
$total_records = $pagination['total_records'];
$total_pages   = $pagination['total_pages'];
$paged_current = $pagination['current_page'];

$page_title = "Reservations Management - Admin Portal";
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <h1 class="h3 fw-bold mb-0">Reservations</h1>
    <span class="badge bg-secondary fs-6 fw-normal"><?= $total_records ?> Reservations Found</span>
</div>

<!-- Streamlined Search & Filters Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="reservations" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                       value="<?= e($filters['search']) ?>" placeholder="Search reservations...">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">Status ▼</option>
                    <option value="CONFIRMED" <?= ($filters['status'] === 'CONFIRMED') ? 'selected' : '' ?>>Confirmed</option>
                    <option value="CANCELLED" <?= ($filters['status'] === 'CANCELLED') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" id="date" name="date" value="<?= e($filters['date']) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                    Filter
                </button>
                <a href="reservations" class="btn btn-light btn-sm text-secondary border" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Streamlined Reservations Table with Pagination -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <?php if (empty($reservations)): ?>
            <div class="text-center py-5 text-muted small">
                No reservations found matching the specified criteria.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Requester</th>
                            <th>Office / Team</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-dark"><?= e($res['requester_name']) ?></td>
                                <td class="text-secondary"><?= e($res['project_team_office']) ?></td>
                                <td class="text-nowrap"><?= date('M j', strtotime($res['reservation_date'])) ?></td>
                                <td class="text-nowrap fw-medium">
                                    <?= date('g:i', strtotime($res['start_time'])) ?>&ndash;<?= date('g:i A', strtotime($res['end_time'])) ?>
                                </td>
                                <td class="small text-muted" title="<?= e($res['purpose']) ?>">
                                    <?= e(mb_strimwidth($res['purpose'], 0, 28, '...')) ?>
                                </td>
                                <td>
                                    <?php if ($res['status'] === 'CONFIRMED'): ?>
                                        <span class="badge bg-success">Confirmed</span>
                                    <?php elseif ($res['status'] === 'CANCELLED'): ?>
                                        <span class="badge bg-warning text-dark">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1.5 align-items-center justify-content-end">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary fw-semibold px-3 btn-open-view-modal"
                                                onclick="openReservationViewModal(this)"
                                                data-id="<?= $res['id'] ?>"
                                                data-code="<?= format_reservation_id($res['id'], $res['created_at']) ?>"
                                                data-name="<?= e($res['requester_name']) ?>"
                                                data-email="<?= e($res['requester_email']) ?>"
                                                data-office="<?= e($res['project_team_office']) ?>"
                                                data-purpose="<?= e($res['purpose']) ?>"
                                                data-date="<?= format_date($res['reservation_date']) ?>"
                                                data-time="<?= format_time($res['start_time']) ?> &ndash; <?= format_time($res['end_time']) ?>"
                                                data-status="<?= e($res['status']) ?>"
                                                data-reason="<?= e($res['rejection_reason'] ?? '') ?>">
                                            <i class="bi bi-eye me-1"></i>View
                                        </button>
                                        <?php if ($res['status'] === 'CONFIRMED'): ?>
                                            <form method="POST" action="reservations" class="d-inline form-cancel-reservation">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                <input type="hidden" name="cancellation_reason" class="cancellation-reason-input" value="">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger fw-semibold px-3"
                                                        onclick="confirmCancelReservation(this)">
                                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing Page <strong><?= $paged_current ?></strong> of <strong><?= $total_pages ?></strong> (<strong><?= $total_records ?></strong> total records)
                    </div>
                    <nav aria-label="Reservation Table Pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= ($paged_current <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= build_query_url(['page' => $paged_current - 1]) ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($i === $paged_current) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= build_query_url(['page' => $i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($paged_current >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= build_query_url(['page' => $paged_current + 1]) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- View Reservation Detail Pop-up Modal -->
<div class="modal fade" id="reservationDetailModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-white px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="modal-title fw-bold text-dark mb-0 fs-6" id="reservationModalLabel">
                    <i class="bi bi-bookmark-check text-primary me-2"></i>Reservation Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Status & Code Bar -->
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3 border mb-3">
                    <div>
                        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Reservation Code</div>
                        <span id="modalResCode" class="font-monospace fw-bold text-danger fs-6"></span>
                    </div>
                    <div class="text-end">
                        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Status</div>
                        <div id="modalStatusBadge"></div>
                    </div>
                </div>

                <!-- Requester Information -->
                <div class="card border mb-3">
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Requester</div>
                                <div id="modalRequesterName" class="fw-bold text-dark" style="font-size: 0.925rem;"></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Email</div>
                                <div id="modalRequesterEmail" class="fw-medium text-dark text-break" style="font-size: 0.875rem;"></div>
                            </div>
                            <div class="col-12 pt-2 border-top">
                                <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Office / Team</div>
                                <div id="modalOfficeTeam" class="text-dark fw-medium" style="font-size: 0.875rem;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Details -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <div class="p-3 rounded border bg-white h-100">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">
                                <i class="bi bi-calendar-event me-1 text-primary"></i>Date
                            </div>
                            <div id="modalDate" class="fw-bold text-dark" style="font-size: 0.875rem;"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded border bg-white h-100">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">
                                <i class="bi bi-clock me-1 text-primary"></i>Time Slot
                            </div>
                            <div id="modalTime" class="fw-bold text-dark" style="font-size: 0.875rem;"></div>
                        </div>
                    </div>
                </div>

                <!-- Purpose -->
                <div class="mb-3">
                    <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Purpose</div>
                    <div id="modalPurpose" class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 0.875rem; min-height: 50px;"></div>
                </div>

                <!-- Cancellation Reason (if applicable) -->
                <div id="modalReasonWrapper" class="mt-3" style="display: none;">
                    <div class="text-uppercase text-danger fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Reason for Cancellation</div>
                    <div id="modalReasonText" class="p-3 bg-danger-subtle text-danger rounded border border-danger-subtle fw-semibold" style="font-size: 0.85rem;"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light px-4 py-3 border-top d-flex justify-content-between align-items-center">
                <form method="POST" action="reservations" class="m-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="resend_email">
                    <input type="hidden" name="reservation_id" id="modalResendId" value="">
                    <button type="submit" class="btn btn-outline-primary btn-sm fw-semibold px-3 py-1.5">
                        <i class="bi bi-envelope-at me-1"></i> Resend Email
                    </button>
                </form>

                <button type="button" class="btn btn-secondary btn-sm px-4 py-1.5 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
