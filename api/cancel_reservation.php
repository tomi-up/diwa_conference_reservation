<?php
/**
 * JSON API Endpoint for AJAX Reservation Cancellation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_admin_logged_in()) {
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
$reason = sanitize_input($_POST['cancellation_reason'] ?? 'Administrative cancellation');

if ($res_id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid reservation ID.'], 400);
}

$pdo = get_db_connection();
$reservation = get_reservation_by_id($res_id, $pdo);

if (!$reservation) {
    json_response(['success' => false, 'message' => 'Reservation record not found.'], 404);
}

// 1. Update reservation status to CANCELLED
$updated = update_reservation_status($res_id, 'CANCELLED', $reason, $pdo);

if ($updated) {
    // 2. Dispatch cancellation email in background
    send_reservation_cancellation_email($res_id, $reason, $pdo);

    json_response([
        'success' => true,
        'message' => 'Reservation has been cancelled successfully.'
    ]);
} else {
    json_response([
        'success' => false,
        'message' => 'Failed to update reservation status.'
    ], 500);
}
