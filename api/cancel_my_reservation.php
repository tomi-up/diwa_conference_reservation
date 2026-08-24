<?php
/**
 * JSON API Endpoint for AJAX Self-Service Reservation Cancellation
 * Only the owning user may cancel, and only more than 24 hours before start time.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_user_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized access.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($token)) {
    json_response(['success' => false, 'message' => 'Security token validation failed.'], 400);
}

$res_id = (int)($_POST['reservation_id'] ?? 0);

if ($res_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
}

$pdo = get_db_connection();
$reservation = get_reservation_by_id($res_id, $pdo);

if (!$reservation) {
    json_response(['success' => false, 'message' => 'Reservation record not found.'], 404);
}

if ((int)($reservation['user_id'] ?? 0) !== (int)$_SESSION['user_id']) {
    json_response(['success' => false, 'message' => 'You are not authorized to cancel this reservation.'], 403);
}

if ($reservation['status'] !== 'CONFIRMED') {
    json_response(['success' => false, 'message' => 'This reservation is already ' . strtolower($reservation['status']) . '.'], 400);
}

$start_timestamp = strtotime($reservation['reservation_date'] . ' ' . $reservation['start_time']);
if ($start_timestamp === false || ($start_timestamp - time()) < 86400) {
    json_response(['success' => false, 'message' => 'Reservations can only be cancelled at least 24 hours before the scheduled start time.'], 400);
}

$reason = 'Cancelled by requester.';
$updated = update_reservation_status($res_id, 'CANCELLED', $reason, $pdo);

if ($updated) {
    send_reservation_cancellation_email($res_id, $reason, $pdo);
    json_response(['success' => true, 'message' => 'Your reservation has been cancelled.']);
} else {
    json_response(['success' => false, 'message' => 'Failed to update reservation status.'], 500);
}
