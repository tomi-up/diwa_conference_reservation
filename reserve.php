<?php
/**
 * Public Conference Room Reservation & Availability Portal (Side-by-Side Unified Edition)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservation.php';
require_once __DIR__ . '/includes/email.php';

$pdo = get_db_connection();
$errors = [];
$is_logged_in = is_user_logged_in();

$form_data = [
    'reservation_date'    => filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS) ?: date('Y-m-d'),
    'requester_name'      => $is_logged_in ? $_SESSION['user_name'] : '',
    'requester_email'     => $is_logged_in ? $_SESSION['user_email'] : '',
    'project_team_office' => '',
    'purpose'             => '',
    'start_time'          => '09:00',
    'end_time'            => '11:00'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Security token validation failed. Please refresh the page and try again.';
    }

    if (!$is_logged_in) {
        $errors[] = 'You must sign in with your official UP Mail (@up.edu.ph) account before submitting a reservation.';
    } else {
        // Enforce identity directly from authenticated server session
        $form_data['user_id']         = (int)$_SESSION['user_id'];
        $form_data['requester_name']  = $_SESSION['user_name'];
        $form_data['requester_email'] = $_SESSION['user_email'];
    }

    $raw_office = sanitize_input($_POST['project_team_office'] ?? '');
    if ($raw_office === 'Others') {
        $other_office = sanitize_input($_POST['project_team_office_other'] ?? '');
        $form_data['project_team_office'] = !empty($other_office) ? $other_office : '';
    } else {
        $form_data['project_team_office'] = $raw_office;
    }
    $form_data['purpose']             = sanitize_input($_POST['purpose'] ?? '');
    $form_data['reservation_date']    = sanitize_input($_POST['reservation_date'] ?? '');
    $form_data['start_time']          = sanitize_input($_POST['start_time'] ?? '');
    $form_data['end_time']            = sanitize_input($_POST['end_time'] ?? '');

    if (empty($form_data['requester_name'])) {
        $errors[] = 'Name of Requesting Personnel is required.';
    }
    if (empty($form_data['requester_email']) || !is_valid_email($form_data['requester_email'])) {
        $errors[] = 'A valid Email Address is required.';
    }
    if (empty($form_data['project_team_office'])) {
        $errors[] = 'Project / Team / Office is required.';
    }
    if (empty($form_data['purpose'])) {
        $errors[] = 'Purpose of Meeting / Activity is required.';
    }
    if (empty($form_data['reservation_date'])) {
        $errors[] = 'Reservation Date is required.';
    } elseif ($form_data['reservation_date'] < date('Y-m-d')) {
        $errors[] = 'Reservation date cannot be in the past.';
    }
    if (empty($form_data['start_time']) || empty($form_data['end_time'])) {
        $errors[] = 'Start Time and End Time are required.';
    } elseif (strtotime($form_data['end_time']) <= strtotime($form_data['start_time'])) {
        $errors[] = 'End Time must be later than Start Time.';
    } elseif ($form_data['start_time'] < '07:00' || $form_data['end_time'] > '18:00') {
        $errors[] = 'Reservation hours are strictly between 7:00 AM and 6:00 PM.';
    }

    if (empty($errors)) {
        $result = create_reservation($form_data, $pdo);

        if ($result['success']) {
            $reservation_id = $result['reservation_id'];
            send_reservation_confirmation_email($reservation_id, $pdo);
            redirect('success.php?id=' . $reservation_id);
        } else {
            if ($result['error'] === 'CONFLICT') {
                redirect('conflict.php?date=' . urlencode($form_data['reservation_date']));
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$page_title = "Reserve Room & Availability - DIWA Center";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-lg-5 py-4">
    <div hidden class="text-center mb-4"><div class="col-lg-2 col-xl-2"></div>
        <h2 class="fw-bold text-dark mb-1">Conference Room Reservation & Availability</h2>
        <p class="text-muted small">Complete the form below to reserve the DIWA Center conference room and inspect live schedule availability.</p>
    </div>

    <div id="formAlertContainer">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
                <strong>Please correct the following issues:</strong>
                <ul class="mb-0 mt-2 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <div id="reservationCardWrapper" class="w-24">
        <div class="row g-4">
            <!-- Left Column: Availability Checker & Schedule Matrix -->
            <div class="col-lg-2 col-xl-2"></div>
            <div class="col-lg-8 col-xl-8">
                <div class="border shadow-sm h-100">
                    <div style="height: 80px;" class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history text-primary me-2"></i>Availability Checker
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <label for="checker_date_input" class="small text-muted fw-semibold mb-0 d-none d-sm-inline">Date:</label>
                            <input type="date" id="checker_date_input" class="form-control form-control-sm" value="<?= e($form_data['reservation_date']) ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="p-3 bg-light rounded border mb-3" hidden>
                            <div class="d-flex align-items-center text-secondary small">
                                <i class="bi bi-info-circle-fill text-primary me-2 fs-5"></i>
                                <div>
                                    <strong>Operating Hours:</strong> 7:00 AM &ndash; 6:00 PM.<br>
                                    Click any <span class="badge bg-success">AVAILABLE</span> slot chip below to automatically pick its start and end times.
                                </div>
                            </div>
                        </div>

                        <!-- Live Interactive Hourly Schedule Matrix -->
                        <div id="reservationScheduleGrid">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border text-primary me-2" role="status"></div> Loading schedule availability...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xl-2"></div>

            <!-- Right Column: Reservation Form -->
            <div class="col-lg-2 col-xl-2"></div>
            <div class="col-lg-8 col-xl-8">
                <div class="border shadow-sm h-100">
                    <div style="height: 80px;" class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-pencil-square text-primary me-2"></i>Reservation Form
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!$is_logged_in): ?>
                            <div id="tour_signin_callout" class="alert alert-warning border-0 shadow-sm mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-lock-fill fs-4 text-warning me-3 mt-1"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">UP Mail Authentication Required</h6>
                                        <p class="small mb-2 text-dark">
                                            To reserve the DIWA Center Conference Room, you must sign in with your official <strong>@up.edu.ph</strong> account.
                                        </p>
                                        <div class="mt-2">
                                            <div id="g_id_onload_form"
                                                 data-client_id="<?= GOOGLE_CLIENT_ID ?>"
                                                 data-callback="handleGoogleSignIn"
                                                 data-auto_prompt="false">
                                            </div>
                                            <div class="g_id_signin"
                                                 data-type="standard"
                                                 data-shape="rectangular"
                                                 data-theme="filled_blue"
                                                 data-text="signin_with"
                                                 data-size="large"
                                                 data-locale="en"
                                                 data-logo_alignment="left">
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form id="reservationForm" method="POST" action="reserve" novalidate>
                            <?= csrf_field() ?>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="requester_name" class="form-label fw-semibold">
                                        Name of Requesting Personnel <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control <?= $is_logged_in ? 'bg-light' : '' ?>" id="requester_name" name="requester_name" 
                                           value="<?= e($form_data['requester_name']) ?>" placeholder="Juan dela Cruz" 
                                           <?= $is_logged_in ? 'readonly' : 'disabled' ?> required>
                                </div>

                                <div class="col-md-6">
                                    <label for="requester_email" class="form-label fw-semibold">
                                        Email Address <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control <?= $is_logged_in ? 'bg-light' : '' ?>" id="requester_email" name="requester_email" 
                                           value="<?= e($form_data['requester_email']) ?>" placeholder="juan@up.edu.ph" 
                                           <?= $is_logged_in ? 'readonly' : 'disabled' ?> required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="project_team_office" class="form-label fw-semibold">
                                    Project / Team / Office <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="project_team_office" name="project_team_office" 
                                        <?= !$is_logged_in ? 'disabled' : '' ?> required>
                                    <option value="" <?= empty($form_data['project_team_office']) ? 'selected' : '' ?> disabled>-- Select Project / Team / Office --</option>
                                    <?php
                                    $offices = [
                                        'Ops Team',
                                        'RESCUE Project',
                                        'IRDSS Project',
                                        'Wolbachia Project',
                                        'Scaling Up of Diwa App Project',
                                        'Others'
                                    ];
                                    foreach ($offices as $off):
                                    ?>
                                        <option value="<?= e($off) ?>" <?= ($form_data['project_team_office'] === $off) ? 'selected' : '' ?>><?= e($off) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="project_team_office_other_wrapper" style="<?= ($form_data['project_team_office'] === 'Others') ? '' : 'display: none;' ?>">
                                <label for="project_team_office_other" class="form-label fw-semibold">
                                    Specify Project / Team / Office <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="project_team_office_other" name="project_team_office_other" 
                                       placeholder="Type your Project / Team / Office name..." 
                                       value="<?= e($_POST['project_team_office_other'] ?? '') ?>" 
                                       <?= !$is_logged_in ? 'disabled' : '' ?>>
                            </div>

                            <div class="mb-3">
                                <label for="purpose" class="form-label fw-semibold">
                                    Purpose of Meeting / Activity <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" 
                                          placeholder="Provide meeting agenda or objective details..." 
                                          <?= !$is_logged_in ? 'disabled' : '' ?> required><?= e($form_data['purpose']) ?></textarea>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="reservation_date" class="form-label fw-semibold">
                                        Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" id="reservation_date" name="reservation_date" 
                                           value="<?= e($form_data['reservation_date']) ?>" min="<?= date('Y-m-d') ?>" 
                                           <?= !$is_logged_in ? 'disabled' : '' ?> required>
                                </div>
                                <div class="col-md-4">
                                    <label for="start_time" class="form-label fw-semibold">
                                        Start Time <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" 
                                           value="<?= e($form_data['start_time']) ?>" min="07:00" max="18:00" step="1800" 
                                           <?= !$is_logged_in ? 'disabled' : '' ?> required>
                                </div>
                                <div class="col-md-4">
                                    <label for="end_time" class="form-label fw-semibold">
                                        End Time <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" 
                                           value="<?= e($form_data['end_time']) ?>" min="07:00" max="18:00" step="1800" 
                                           <?= !$is_logged_in ? 'disabled' : '' ?> required>
                                </div>
                            </div>

                            <!-- Real-time Availability Badge -->
                            <div class="mb-4 text-center">
                                <div id="liveAvailabilityBadge"></div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary py-2.5 fw-bold fs-6" <?= !$is_logged_in ? 'disabled' : '' ?>>
                                    <i class="bi bi-calendar-check me-2"></i> Submit Reservation Request
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
            <div class="col-lg-2 col-xl-2"></div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
