<?php
/**
 * Public Landing Homepage with DIWA Branding
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservation.php';
require_once __DIR__ . '/includes/email.php';

$page_title = "Home - Conference Room Reservation System";

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


require_once __DIR__ . '/includes/new_header.php';

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
<div class="position-relative w-100 d-flex align-items-center py-0"
     style="height: calc(100vh - 80px - 80px); z-index: 2;">

    <!-- Bottom white background -->
    <div class="position-absolute start-0 bottom-0 w-100 bg-white"
        style="height: 90px; z-index: 0; border-top: 10px solid #2C0707;">
    </div>
    
    <!-- background image -->
    <!--
    <img src="<?= APP_URL ?>/assets/images/diwa_header.jpg" class="position-absolute top-0 start-0 w-100 h-100" alt="Background" style="object-fit: cover; z-index: 1; filter: blur(5px);">
    -->

    <!-- dark tint overlay -->
    <!--
    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: 0.4; z-index: 2;"></div>
    -->

    <!-- main content section -->
    <!--
    <div class="container position-relative" style="z-index: 3; height: 100%;">
        <div class="row justify-content-center" style="height: 100%;">
            <div class="col-12" style="height: 100%;">

                <div class="position-relative text-center"
                    style="background-color: #ffffff; width: 70vw; height: 100%; margin: auto;">
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
    -->

    <div class="container position-relative h-100" style="z-index: 5; width: 70%;">

        <div id="reservationCardWrapper" class="row h-100 g-0 shadow-sm" style="z-index: 6;">

            <!-- left -->
            <div class="col-8 h-100">
                <div class="h-100 d-flex flex-column justify-content-between align-items-center"
                    style="
                      background-color: #ffffff;
                      border: 1px solid #9CC6DD;
                      border-right: 1px solid #DEE2E6;
                    ">

                    <!-- top -->
                    <div class="bg-black w-100" style="height: 60%;">
                        <div class="h-100 d-flex justify-content-center align-items-center">
                            <img src="<?= APP_URL ?>/assets/images/diwa_header.jpg"
                                alt="DIWA header"
                                class="w-100 h-100"
                                style="object-fit: cover;">
                        </div>
                    </div>

                    <!-- bottom -->
                    <div class="w-100 px-4 py-3 d-flex flex-column justify-content-between" style="height: 40%;">
                        <div>
                            <h1 class="fw-bold text-black mb-1" style="font-size: 36px;">
                                <?= e(CONFERENCE_ROOM_NAME) ?> Reservation
                            </h1>

                            <p class="text-muted mb-0" style="font-size: 16px;">
                                Do you have an upcoming meeting? Reserve the conference room now!
                            </p>
                        </div>

                        <style>
                            .view-calendar-link {
                                text-decoration: none;
                                color: #608498;
                            }

                            .view-calendar-link:hover {
                                color: #557485;
                            }
                        </style>

                        <a href="<?= APP_URL ?>/reserve" class="view-calendar-link">
                            VIEW CALENDAR >
                        </a>
                    </div>

                </div>
            </div>


            <!-- RIGHT COLUMN -->
            <div class="col-4 h-100">
                <div class="h-100 d-flex flex-column"
                    style="
                      background-color: #FFFDFD;
                      border: 1px solid #5b111199;
                      border-left: none;
                    ">

                    <!-- Header -->
                    <div style="height: 60px; min-height: 60px; background-color: #FFFEFE;"
                        class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                        <h5 class="card-title mb-0 fw-bold text-dark" style="font-size: 20px;">
                            FILL YOUR DETAILS
                        </h5>
                    </div>

                    <!-- form -->
                    <div class="card-body d-flex flex-column p-0" style="min-height: 0; overflow: hidden;">
                        <form id="reservationForm"
                            method="POST"
                            action="reserve"
                            novalidate
                            class="d-flex flex-column h-100"
                            style="min-height: 0;">

                            <?= csrf_field() ?>

                            <!-- SCROLLABLE FORM CONTENT -->
                            <div class="flex-grow-1 overflow-auto p-3" style="min-height: 0;">

                                <!-- Date -->
                                <div class="mb-2">
                                    <label for="reservation_date" class="form-label fw-normal">
                                        Date <span class="text-danger">*</span>
                                    </label>

                                    <input type="date"
                                        class="form-control"
                                        id="reservation_date"
                                        name="reservation_date"
                                        min="<?= date('Y-m-d') ?>"
                                        required>

                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Time -->
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="start_time" class="form-label fw-normal">
                                            Start Time <span class="text-danger">*</span>
                                        </label>

                                        <input type="time"
                                            class="form-control"
                                            id="start_time"
                                            name="start_time"
                                            min="07:00"
                                            max="18:00"
                                            step="1800"
                                            required>

                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-6">
                                        <label for="end_time" class="form-label fw-normal">
                                            End Time <span class="text-danger">*</span>
                                        </label>

                                        <input type="time"
                                            class="form-control"
                                            id="end_time"
                                            name="end_time"
                                            min="07:00"
                                            max="18:00"
                                            step="1800"
                                            required>

                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                <!-- Availability -->
                                <div class="mb-3 text-center" style="font-size: 14px;">
                                    <div id="liveAvailabilityBadge"></div>
                                </div>

                                <!-- Project -->
                                <div class="mb-2">
                                    <label for="project_team_office" class="form-label fw-normal">
                                        Project <span class="text-danger">*</span>
                                    </label>

                                    <select class="form-select"
                                            id="project_team_office"
                                            name="project_team_office"
                                            required>

                                        <option value="" selected disabled hidden>
                                            choose an option...
                                        </option>

                                        <?php
                                        $offices = [
                                            'DiWA Core',
                                            'Ops Team',
                                            'Scaling Up of Diwa App Project',
                                            'RabDash DC',
                                            'RESCUE Project',
                                            'Wolbachia Project',
                                            'IRDSS Project',
                                            'Others'
                                        ];

                                        foreach ($offices as $off):
                                        ?>
                                            <option value="<?= e($off) ?>">
                                                <?= e($off) ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Purpose -->
                                <div class="mb-4">
                                    <label for="purpose" class="form-label fw-normal">
                                        Purpose <span class="text-danger">*</span>
                                    </label>

                                    <textarea class="form-control"
                                            id="purpose"
                                            name="purpose"
                                            rows="4"
                                            style="resize: none;"
                                            required></textarea>

                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Terms -->
                                <div class="mb-2">
                                    <div class="d-flex align-items-start">
                                        <input
                                            class="form-check-input ms-0 me-2 flex-shrink-0"
                                            type="checkbox"
                                            value="1"
                                            id="terms_accepted"
                                            name="terms_accepted"
                                            <?= !$is_logged_in ? 'disabled' : '' ?>
                                            required
                                        >

                                        <label class="form-check-label small text-muted fw-normal mb-0"
                                            for="terms_accepted">
                                            I have read and agree to the <a href="#"
                                            class="fw-bold text-danger text-decoration-underline"
                                            data-bs-toggle="modal"
                                            data-bs-target="#termsModal">Responsible Use Policy & Terms of Service
                                            </a>
                                            <span class="text-danger">*</span>
                                        </label>
                                    </div>

                                    <div class="invalid-feedback"></div>
                                </div>

                            </div>

                            <!-- FIXED BOTTOM BUTTON -->
                            <button type="submit"
                                    id="reservationFormSubmitBtn"
                                    class="btn btn-primary w-100 fw-bold rounded-0 border-0 flex-shrink-0"
                                    style="
                                        height: 80px;
                                        font-size: 1.15rem;
                                        background-color: #CA3436;
                                    ">
                                <i class="bi bi-calendar-check me-2"></i>
                                RESERVE NOW
                            </button>

                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Terms & Responsible Use Policy Modal (Vantage Style UI with Original Policy Text) -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden p-3 p-md-4 bg-white">
            <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.75rem; letter-spacing: 0.1em; color: #64748b;">AGREEMENT & RESPONSIBLE USE POLICY</div>
                    <h2 class="modal-title fw-bold text-dark tracking-tight mb-0" id="termsModalLabel" style="font-size: 2rem; color: #0f172a; font-weight: 800;">
                        Terms of Service
                    </h2>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body pt-3 pb-4 px-3" style="max-height: 65vh; overflow-y: auto; color: #334155; font-size: 0.9375rem; line-height: 1.6;">
                <p class="text-secondary mb-3">
                    We know it's tempting to skip these Terms of Service, but it's important to establish what you can expect from us as you use the DiWA Center Conference Room Reservation system, and what we expect from you.
                </p>

                <div class="p-3 bg-light rounded-3 border mb-4">
                    <strong class="text-dark d-block mb-1">System Security & Monitoring Notice</strong>
                    <span class="small text-muted">
                        All reservation activities, user sessions, and IP logs are recorded. System abuse, spamming, fake bookings, or flooding will result in your account being blocked from accessing the site again.
                    </span>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-1">1. Authorized Personnel & Academic Purpose</h6>
                    <p class="small text-secondary mb-0">
                        Reservations are strictly reserved for official project, team, office, or academic activities within UP Mindanao and the DiWA Center. All users must sign in with their authenticated <strong>@up.edu.ph</strong> account.
                    </p>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-1">2. Anti-Spam & Fair Usage Booking Limits</h6>
                    <ul class="ps-3 mb-0 small text-secondary" style="line-height: 1.8;">
                        <li class="mb-1.5"><strong>Max 2 Active Bookings Per User Per Day:</strong> Restricted to 2 active reservations per calendar date.</li>
                        <li class="mb-1.5"><strong>Max 4 Hours Per Reservation:</strong> Single booking sessions cannot exceed 4 consecutive hours.</li>
                        <li class="mb-1.5"><strong>Max 30 Days Advance Booking:</strong> Reservations can only be scheduled up to 30 days in advance.</li>
                        <li class="mb-0"><strong>Rate Limiting Cooldown:</strong> A 5-minute cooldown is enforced between consecutive reservation submissions.</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-1">3. Strict Misuse & Account Block Policy</h6>
                    <p class="small text-secondary mb-0">
                        Attempting to flood the reservation system, double-book slots intentionally, submit fake details, or bypass security controls constitutes a violation of the system rules. Any detected violation will result in immediate revocation of reservation privileges and we will block your account from accessing the site again.
                    </p>
                </div>

                <div>
                    <h6 class="fw-bold text-dark mb-1">4. Cancellations & Facility Care</h6>
                    <p class="small text-secondary mb-0">
                        If a scheduled activity is cancelled, the requester must notify facility administrators or cancel the booking promptly to free the slot for other personnel.
                    </p>
                </div>
            </div>


            <div class="modal-footer border-0 pt-0 px-3 d-flex justify-content-start gap-3">
                <button type="button" class="btn btn-outline-secondary px-4 py-2.5 rounded-3 fw-medium" data-bs-dismiss="modal" style="font-size: 0.875rem;">Not right now...</button>
                <button type="button" id="btnAgreeTerms" class="btn btn-primary px-4 py-2.5 rounded-3 fw-bold" data-bs-dismiss="modal" style="font-size: 0.875rem;">I agree with terms</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/new_footer.php'; ?>
