<?php
/**
 * Admin Room Availability Control Panel & Interactive Calendar View (FullCalendar 6)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/email.php';

$pdo = get_db_connection();
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Handle Block Schedule Submission & Conflict Prevention
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (verify_csrf_token($token)) {
        if ($action === 'block_schedule') {
            $block_date       = sanitize_input($_POST['block_date'] ?? '');
            $block_start_time = sanitize_input($_POST['block_start_time'] ?? '');
            $block_end_time   = sanitize_input($_POST['block_end_time'] ?? '');
            $block_reason     = sanitize_input($_POST['block_reason'] ?? 'Official Office Activity');

            if (empty($block_date) || empty($block_start_time) || empty($block_end_time) || empty($block_reason)) {
                set_flash_message('danger', 'All block schedule fields are required.');
            } elseif ($block_date < $today) {
                set_flash_message('danger', 'Block date cannot be in the past.');
            } elseif (strtotime($block_end_time) <= strtotime($block_start_time)) {
                set_flash_message('danger', 'End time must be later than start time.');
            } elseif ($block_start_time < '07:00' || $block_end_time > '18:00') {
                set_flash_message('danger', 'Operating hours are strictly between 7:00 AM and 6:00 PM.');
            } elseif (has_schedule_conflict($block_date, $block_start_time, $block_end_time, null, $pdo)) {
                set_flash_message('danger', 'Cannot block schedule: A confirmed reservation already exists during this target time slot.');
            } else {
                // Insert Admin Block Reservation Record
                $stmt_block = $pdo->prepare("
                    INSERT INTO reservations (
                        requester_name, requester_email, project_team_office, purpose,
                        reservation_date, start_time, end_time, status, created_at, updated_at
                    ) VALUES (
                        'Admin Facility Control', 'admin@diwa.gov.ph', 'ADMIN_BLOCK', :purpose,
                        :reservation_date, :start_time, :end_time, 'CONFIRMED', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                ");

                $inserted = $stmt_block->execute([
                    'purpose'          => $block_reason,
                    'reservation_date' => $block_date,
                    'start_time'       => $block_start_time,
                    'end_time'         => $block_end_time
                ]);

                if ($inserted) {
                    set_flash_message('success', "Room schedule blocked successfully for " . format_date($block_date) . " (" . format_time($block_start_time) . " &ndash; " . format_time($block_end_time) . ").");
                } else {
                    set_flash_message('danger', "Failed to block schedule. Please try again.");
                }
            }
            redirect('calendar');
        } elseif ($action === 'cancel_reservation') {
            $res_id = (int)($_POST['reservation_id'] ?? 0);
            $reason = sanitize_input($_POST['cancellation_reason'] ?? 'Administrative cancellation');
            if ($res_id > 0) {
                update_reservation_status($res_id, 'CANCELLED', $reason, $pdo);
                send_reservation_cancellation_email($res_id, $reason, $pdo);
                set_flash_message('warning', "Reservation has been cancelled.");
            }
            redirect('calendar');
        }
    }
}

// Compute Summary Metrics
$stmt_today_count = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = :today AND status = 'CONFIRMED' AND project_team_office != 'ADMIN_BLOCK'");
$stmt_today_count->execute(['today' => $today]);
$count_today_bookings = (int)$stmt_today_count->fetchColumn();

$stmt_tomorrow_count = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = :tomorrow AND status = 'CONFIRMED' AND project_team_office != 'ADMIN_BLOCK'");
$stmt_tomorrow_count->execute(['tomorrow' => $tomorrow]);
$count_tomorrow_bookings = (int)$stmt_tomorrow_count->fetchColumn();

// Fetch Active Blocked Schedules List
$stmt_blocked_list = $pdo->query("
    SELECT *
    FROM reservations
    WHERE project_team_office = 'ADMIN_BLOCK'
      AND status = 'CONFIRMED'
    ORDER BY reservation_date ASC, start_time ASC
");
$blocked_schedules_list = $stmt_blocked_list->fetchAll();
$count_blocked_schedules = count($blocked_schedules_list);

$page_title = "Conference Room Calendar - Admin Portal";
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h3 fw-bold mb-1">Conference Room Calendar</h1>
        <p class="text-muted small mb-0">View availability and manage room schedules.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-danger fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#blockScheduleModal">
            <i class="bi bi-slash-circle me-1.5"></i> + Block Schedule
        </button>
        <a href="reservations" class="btn btn-outline-secondary btn-sm fw-semibold">
            <i class="bi bi-list-task me-1"></i> Reservations List
        </a>
    </div>
</div>

<!-- Actionable Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase d-block">Today (<?= date('M j') ?>)</span>
                <span class="badge bg-success-subtle text-success fs-7 border border-success-subtle px-2 py-0.5">Today</span>
            </div>
            <div class="mb-2">
                <?php if ($count_today_bookings === 0): ?>
                    <div class="fs-6 fw-bold text-success">Fully Available</div>
                <?php else: ?>
                    <div class="fs-6 fw-bold text-dark"><?= $count_today_bookings ?> Confirmed Booking(s)</div>
                <?php endif; ?>
            </div>
            <div>
                <button type="button" id="btnViewToday" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold">
                    View Schedule &rarr;
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase d-block">Tomorrow (<?= date('M j', strtotime('+1 day')) ?>)</span>
                <span class="badge bg-primary-subtle text-primary fs-7 border border-primary-subtle px-2 py-0.5">Tomorrow</span>
            </div>
            <div class="mb-2">
                <?php if ($count_tomorrow_bookings === 0): ?>
                    <div class="fs-6 fw-bold text-success">Fully Available</div>
                <?php else: ?>
                    <div class="fs-6 fw-bold text-dark"><?= $count_tomorrow_bookings ?> Confirmed Booking(s)</div>
                <?php endif; ?>
            </div>
            <div>
                <button type="button" id="btnViewTomorrow" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold">
                    View Schedule &rarr;
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 h-100 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-bold text-uppercase d-block">Unavailable Periods</span>
                <span class="badge bg-danger-subtle text-danger fs-7 border border-danger-subtle px-2 py-0.5">Admin Control</span>
            </div>
            <div class="mb-2">
                <div class="fs-6 fw-bold text-danger"><?= $count_blocked_schedules ?> Active Block(s)</div>
            </div>
            <div>
                <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2.5 fw-semibold" data-bs-toggle="modal" data-bs-target="#manageBlocksModal">
                    Manage Blocks
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inline Status Legend & Calendar Grid Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-2.5 d-flex align-items-center gap-2 flex-wrap">
        <span class="fw-bold text-dark small me-1"><i class="bi bi-info-circle text-primary me-1"></i> Schedule Status Legend:</span>
        <span class="badge bg-success px-2.5 py-1">Confirmed</span>
        <span class="badge bg-danger px-2.5 py-1">Blocked / Unavailable</span>
    </div>
    <div class="card-body p-4">
        <div id="calendar"></div>
    </div>
</div>

<!-- Manage Active Blocked Schedules Modal -->
<div class="modal fade" id="manageBlocksModal" tabindex="-1" aria-labelledby="manageBlocksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="manageBlocksModalLabel">
                    <i class="bi bi-slash-circle text-danger me-2"></i>Active Blocked Schedules
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.725rem; letter-spacing: 0.04em;">Currently Blocked Periods</span>
                    <button type="button" class="btn btn-danger btn-sm fw-bold px-3 py-1" data-bs-toggle="modal" data-bs-target="#blockScheduleModal">
                        <i class="bi bi-plus-circle me-1"></i> Add New Block
                    </button>
                </div>

                <?php if (empty($blocked_schedules_list)): ?>
                    <div class="text-center py-4 text-muted bg-light rounded border small">
                        No active room blocks set. The conference room is currently open for standard bookings.
                    </div>
                <?php else: ?>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Time Slot</th>
                                    <th>Reason / Activity</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blocked_schedules_list as $block): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark"><?= format_date($block['reservation_date']) ?></td>
                                        <td class="text-nowrap fw-bold text-danger">
                                            <?= format_time($block['start_time']) ?> &ndash; <?= format_time($block['end_time']) ?>
                                        </td>
                                        <td class="small text-muted"><?= e($block['purpose']) ?></td>
                                        <td class="text-end pe-3">
                                            <form method="POST" action="calendar" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="cancel_reservation">
                                                <input type="hidden" name="reservation_id" value="<?= $block['id'] ?>">
                                                <input type="hidden" name="cancellation_reason" class="cancellation-reason-input" value="Unblocked by Admin">
                                                <button type="button" class="btn btn-sm btn-outline-danger fw-semibold px-2 py-0.5" onclick="confirmCancelReservation(this)">
                                                    <i class="bi bi-trash me-1"></i> Unblock
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light justify-content-end py-2.5 px-4 border-top">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Block Conference Room Creation Modal -->
<div class="modal fade" id="blockScheduleModal" tabindex="-1" aria-labelledby="blockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title fw-bold fs-6" id="blockModalLabel">
                    <i class="bi bi-slash-circle me-2"></i>Block Conference Room
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="calendar">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="block_schedule">
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Mark conference room unavailable to prevent user bookings during official activities or maintenance.
                    </p>

                    <div class="mb-3">
                        <label for="block_date" class="form-label fw-semibold small">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="block_date" name="block_date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="block_start_time" class="form-label fw-semibold small">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control form-control-sm" id="block_start_time" name="block_start_time" value="13:00" min="07:00" max="18:00" step="1800" required>
                        </div>
                        <div class="col-md-6">
                            <label for="block_end_time" class="form-label fw-semibold small">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control form-control-sm" id="block_end_time" name="block_end_time" value="17:00" min="07:00" max="18:00" step="1800" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="block_reason" class="form-label fw-semibold small">Reason <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="block_reason" name="block_reason" value="Official Office Activity" placeholder="e.g., Official Office Activity, Facility Maintenance" required>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-end py-2.5 px-4 border-top">
                    <button type="button" class="btn btn-secondary btn-sm fw-semibold px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold px-4">
                        Block Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Interactive Event Detail Pop-up Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-white px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="modal-title fw-bold text-dark mb-0 fs-6" id="eventModalLabel">
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
                        <span id="modalEventCode" class="font-monospace fw-bold text-danger fs-6"></span>
                    </div>
                    <div class="text-end">
                        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Status</div>
                        <div id="modalEventStatusBadge"></div>
                    </div>
                </div>

                <!-- Schedule Details -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <div class="p-3 rounded border bg-white h-100">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">
                                <i class="bi bi-calendar-event me-1 text-primary"></i>Date
                            </div>
                            <div id="modalEventDate" class="fw-bold text-dark" style="font-size: 0.875rem;"></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded border bg-white h-100">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">
                                <i class="bi bi-clock me-1 text-primary"></i>Time Slot
                            </div>
                            <div id="modalEventTime" class="fw-bold text-dark" style="font-size: 0.875rem;"></div>
                        </div>
                    </div>
                </div>

                <!-- Requester Information -->
                <div id="modalRequesterWrapper" class="card border mb-3">
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Requester</div>
                                <div id="modalEventRequester" class="fw-bold text-dark" style="font-size: 0.925rem;"></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Email</div>
                                <div id="modalEventEmail" class="fw-medium text-dark text-break" style="font-size: 0.875rem;"></div>
                            </div>
                            <div id="modalOfficeWrapper" class="col-12 pt-2 border-top">
                                <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Office / Team</div>
                                <div id="modalEventOffice" class="text-dark fw-medium" style="font-size: 0.875rem;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purpose -->
                <div class="mb-3">
                    <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">Purpose / Activity</div>
                    <div id="modalEventPurpose" class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 0.875rem; min-height: 50px;"></div>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between py-2.5 px-4 border-top">
                <div id="modalEventActions">
                    <!-- Dynamic View/Cancel Actions -->
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-4 py-1 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let globalCalendar;

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const eventModal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
    const blockModal = new bootstrap.Modal(document.getElementById('blockScheduleModal'));

    globalCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek', // Default to Week View as requested
        slotMinTime: '07:00:00',
        slotMaxTime: '18:00:00',
        allDaySlot: false,
        displayEventTime: false, // Prevents duplicate raw time string header above clean title
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: 'auto',
        events: '../api/calendar_events',
        selectable: true,
        select: function(info) {
            const startDate = info.startStr.split('T')[0];
            const startTime = info.startStr.includes('T') ? info.startStr.split('T')[1].substring(0, 5) : '09:00';
            const endTime   = info.endStr.includes('T')   ? info.endStr.split('T')[1].substring(0, 5)   : '12:00';

            document.getElementById('block_date').value = startDate;
            document.getElementById('block_start_time').value = startTime;
            document.getElementById('block_end_time').value = endTime;
            
            blockModal.show();
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            const props = info.event.extendedProps;

            document.getElementById('modalEventCode').textContent = props.reservation_code;
            document.getElementById('modalEventTime').textContent = props.start_time_fmt + ' – ' + props.end_time_fmt;
            document.getElementById('modalEventDate').textContent = props.date_fmt;
            document.getElementById('modalEventPurpose').textContent = props.purpose;

            const badge = document.getElementById('modalEventStatusBadge');
            if (props.is_blocked) {
                document.getElementById('eventModalLabel').textContent = 'Blocked Schedule';
                badge.innerHTML = '<span class="badge bg-danger px-2.5 py-1 fw-semibold">Blocked</span>';
                document.getElementById('modalRequesterWrapper').style.display = 'none';
                document.getElementById('modalOfficeWrapper').style.display = 'none';
            } else {
                document.getElementById('eventModalLabel').textContent = 'Conference Room Reservation';
                document.getElementById('modalRequesterWrapper').style.display = 'block';
                document.getElementById('modalOfficeWrapper').style.display = 'block';
                document.getElementById('modalEventRequester').textContent = props.requester_name;
                document.getElementById('modalEventEmail').textContent = props.requester_email;
                document.getElementById('modalEventOffice').textContent = props.project_team_office;

                if (props.status === 'CONFIRMED') {
                    badge.innerHTML = '<span class="badge bg-success px-2.5 py-1 fw-semibold">Confirmed</span>';
                }
            }

            let actionsHtml = '';
            if (props.status === 'CONFIRMED') {
                actionsHtml = `
                    <a href="reservations" class="btn btn-sm btn-outline-primary fw-semibold px-3 me-2">
                        <i class="bi bi-eye me-1"></i> View Details
                    </a>
                    <form method="POST" action="calendar" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel_reservation">
                        <input type="hidden" name="reservation_id" value="${props.id}">
                        <input type="hidden" name="cancellation_reason" class="cancellation-reason-input" value="">
                        <button type="button" class="btn btn-sm btn-outline-danger fw-semibold px-3" onclick="confirmCancelReservation(this)">
                            <i class="bi bi-x-circle me-1"></i> Cancel Reservation
                        </button>
                    </form>
                `;
            }
            document.getElementById('modalEventActions').innerHTML = actionsHtml;

            eventModal.show();
        }
    });

    globalCalendar.render();

    // Card Action Click Handlers
    document.getElementById('btnViewToday').addEventListener('click', function() {
        globalCalendar.today();
    });

    document.getElementById('btnViewTomorrow').addEventListener('click', function() {
        globalCalendar.gotoDate('<?= $tomorrow ?>');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
