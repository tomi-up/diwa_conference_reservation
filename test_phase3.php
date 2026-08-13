<?php
/**
 * Test Runner for Phase 3: Email Notification Engine & Logging
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reservation.php';
require_once __DIR__ . '/includes/email.php';

echo "=== Phase 3 Email System & Logging Verification ===\n\n";

$pdo = get_db_connection();

// 1. Create a dummy test reservation
$test_date = date('Y-m-d', strtotime('+3 days'));
$res = create_reservation([
    'room_id'             => 1,
    'requester_name'      => 'Test Mail User',
    'requester_email'     => 'testmail@example.com',
    'project_team_office' => 'Quality Assurance',
    'purpose'             => 'PHPMailer System Integration Test',
    'reservation_date'    => $test_date,
    'start_time'          => '14:00',
    'end_time'            => '15:00'
], $pdo);

if (!$res['success']) {
    echo "[FAIL] Reservation creation failed.\n";
    exit(1);
}

$reservation_id = $res['reservation_id'];
echo "[PASS] Test Reservation #{$reservation_id} created.\n";

// 2. Trigger Confirmation Email Dispatch
echo "Triggering Confirmation Email Dispatch...\n";
send_reservation_confirmation_email($reservation_id, $pdo);

// 3. Verify Log Entry in email_logs
$stmt = $pdo->prepare("SELECT * FROM email_logs WHERE reservation_id = :id ORDER BY id DESC LIMIT 1");
$stmt->execute(['id' => $reservation_id]);
$log = $stmt->fetch();

if ($log) {
    echo "[PASS] Email log entry created successfully!\n";
    echo "  - Log ID: #{$log['id']}\n";
    echo "  - Recipient: {$log['recipient_email']}\n";
    echo "  - Type: {$log['email_type']}\n";
    echo "  - Status: {$log['status']}\n";
    if ($log['error_message']) {
        echo "  - Captured Message/Error: {$log['error_message']}\n";
    }
} else {
    echo "[FAIL] No email_logs entry recorded!\n";
    exit(1);
}

// 4. Verify reservation was NOT deleted when email failed or succeeded
$res_check = get_reservation_by_id($reservation_id, $pdo);
if ($res_check) {
    echo "[PASS] Verified: Reservation #{$reservation_id} remains intact in database.\n";
} else {
    echo "[FAIL] Reservation was deleted after email attempt!\n";
    exit(1);
}

echo "\n=== Phase 3 Verification Completed Successfully! ===\n";
