<?php
/**
 * JSON API Endpoint for Submitting Conference Room Reservations via jQuery / AJAX
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
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

// Input Data Processing
$form_data = [
    'requester_name'      => sanitize_input($_POST['requester_name'] ?? ''),
    'requester_email'     => sanitize_input($_POST['requester_email'] ?? ''),
    'project_team_office' => sanitize_input($_POST['project_team_office'] ?? ''),
    'purpose'             => sanitize_input($_POST['purpose'] ?? ''),
    'reservation_date'    => sanitize_input($_POST['reservation_date'] ?? ''),
    'start_time'          => sanitize_input($_POST['start_time'] ?? ''),
    'end_time'            => sanitize_input($_POST['end_time'] ?? '')
];

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
}
if (empty($form_data['start_time']) || empty($form_data['end_time'])) {
    $errors[] = 'Start Time and End Time are required.';
} elseif (strtotime($form_data['end_time']) <= strtotime($form_data['start_time'])) {
    $errors[] = 'End Time must be later than Start Time.';
} elseif ($form_data['start_time'] < '07:00' || $form_data['end_time'] > '18:00') {
    $errors[] = 'Reservation hours are strictly between 7:00 AM and 6:00 PM.';
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
