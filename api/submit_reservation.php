<?php
/**
 * JSON API Endpoint for Submitting Conference Room Reservations via jQuery / AJAX
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/email.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method. POST required.'], 405);
}

$pdo = get_db_connection();
$errors = [];

// CSRF Validation
$token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($token)) {
    json_response([
        'success' => false,
        'error'   => 'CSRF_INVALID',
        'message' => 'Security token validation failed. Please refresh the page and try again.'
    ], 403);
}

// Identity comes from the authenticated session, never from client-submitted
// fields - required_name/email inputs are readonly client-side but that is
// not a trust boundary, and this is also where user_id gets attached so a
// reservation shows up on the requester's "My Reservations" page.
if (!is_user_logged_in()) {
    json_response([
        'success' => false,
        'error'   => 'NOT_LOGGED_IN',
        'message' => 'You must sign in with your official UP Mail (@up.edu.ph) account before submitting a reservation.',
        'errors'  => ['You must sign in with your official UP Mail (@up.edu.ph) account before submitting a reservation.']
    ], 401);
}

$raw_office = sanitize_input($_POST['project_team_office'] ?? '');
if ($raw_office === 'Others') {
    $other_office = sanitize_input($_POST['project_team_office_other'] ?? '');
    $project_office = !empty($other_office) ? $other_office : '';
} else {
    $project_office = $raw_office;
}

// Input Data Processing
$form_data = [
    'user_id'             => (int)$_SESSION['user_id'],
    'requester_name'      => $_SESSION['user_name'],
    'requester_email'     => $_SESSION['user_email'],
    'project_team_office' => $project_office,
    'purpose'             => sanitize_input($_POST['purpose'] ?? ''),
    'reservation_date'    => sanitize_input($_POST['reservation_date'] ?? ''),
    'start_time'          => sanitize_input($_POST['start_time'] ?? ''),
    'end_time'            => sanitize_input($_POST['end_time'] ?? '')
];

// Terms & Conditions Check
$terms_accepted = !empty($_POST['terms_accepted']);
if (!$terms_accepted) {
    $errors[] = 'You must accept the Terms & Conditions and Responsible Use Policy before submitting.';
}

// 5-Minute Rate Limit / Cooldown Check
$last_submit = $_SESSION['last_reservation_submit_time'] ?? 0;
if (time() - $last_submit < 0) {
    $wait_sec = 0 - (time() - $last_submit);
    $wait_min = ceil($wait_sec / 60);
    $errors[] = "Rate Limit: Please wait {$wait_min} minute(s) before submitting another reservation request.";
}

// Validation Rules
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
} else {
    // Max 30 Days Advance Limit
    $max_advance_date = date('Y-m-d', strtotime('+30 days'));
    if ($form_data['reservation_date'] > $max_advance_date) {
        $errors[] = 'Advance Booking Limit: Reservations can only be booked up to 30 days in advance (on or before ' . format_date($max_advance_date) . ').';
    }
}

if (empty($form_data['start_time']) || empty($form_data['end_time'])) {
    $errors[] = 'Start Time and End Time are required.';
} elseif (strtotime($form_data['end_time']) <= strtotime($form_data['start_time'])) {
    $errors[] = 'End Time must be later than Start Time.';
} elseif ($form_data['start_time'] < '07:00' || $form_data['end_time'] > '18:00') {
    $errors[] = 'Reservation hours are strictly between 7:00 AM and 6:00 PM.';
} else {
    // Max 4 Hours Duration Cap
    $duration_seconds = strtotime($form_data['end_time']) - strtotime($form_data['start_time']);
    if ($duration_seconds > 14400) {
        $errors[] = 'Duration Cap Exceeded: Single reservation sessions cannot exceed 4 hours.';
    }
}

// Daily 1 Active Booking Limit per User
if (empty($errors) && !empty($form_data['requester_email']) && !empty($form_data['reservation_date'])) {
    $stmt_check_daily = $pdo->prepare("
        SELECT COUNT(*) 
        FROM reservations 
        WHERE requester_email = :email 
          AND reservation_date = :res_date 
          AND status = 'CONFIRMED'
    ");
    $stmt_check_daily->execute([
        'email'    => $form_data['requester_email'],
        'res_date' => $form_data['reservation_date']
    ]);
    $daily_count = (int)$stmt_check_daily->fetchColumn();

    if ($daily_count >= 1) {
        $errors[] = 'Notice: You already have an active reservation request for this date. To prevent spam, only 1 active booking per user per day is allowed.';
    }
}

if (!empty($errors)) {
    json_response([
        'success' => false,
        'error'   => 'VALIDATION_FAILED',
        'message' => 'Please correct the validation errors listed below.',
        'errors'  => $errors
    ], 422);
}

// Create Reservation
$result = create_reservation($form_data, $pdo);

if (!$result['success']) {
    json_response([
        'success' => false,
        'error'   => $result['error'] ?? 'SUBMISSION_FAILED',
        'message' => $result['message'] ?? 'Failed to submit reservation request.',
        'errors'  => [$result['message'] ?? 'Failed to submit reservation request.']
    ], 409);
}

$reservation_id = $result['reservation_id'];
$_SESSION['last_reservation_submit_time'] = time();

// Send Email Notification
$email_sent = send_reservation_confirmation_email($reservation_id, $pdo);

// Retrieve Reservation Details for Response
$res = get_reservation_by_id($reservation_id, $pdo);

json_response([
    'success'        => true,
    'message'        => 'Reservation successfully confirmed!',
    'reservation_id' => format_reservation_id($res['id'], $res['created_at']),
    'email_sent'     => $email_sent,
    'details'        => [
        'id'                  => $res['id'],
        'formatted_id'        => format_reservation_id($res['id'], $res['created_at']),
        'requester_name'      => $res['requester_name'],
        'requester_email'     => $res['requester_email'],
        'room_name'           => $res['room_name'],
        'reservation_date'    => format_date($res['reservation_date']),
        'raw_date'            => $res['reservation_date'],
        'start_time'          => format_time($res['start_time']),
        'end_time'            => format_time($res['end_time']),
        'project_team_office' => $res['project_team_office'],
        'purpose'             => $res['purpose'],
        'status'              => $res['status']
    ]
]);
