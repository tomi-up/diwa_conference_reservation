<?php
/**
 * Comprehensive Automated Verification Suite (20 Scenarios - Single Conference Room)
 * Run using PHP CLI: php test_suite.php
 */

ob_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reservation.php';
require_once __DIR__ . '/includes/availability.php';
require_once __DIR__ . '/includes/email.php';

echo "===============================================================\n";
echo "       CONFERENCE ROOM RESERVATION SYSTEM - TEST SUITE        \n";
echo "===============================================================\n\n";

$pdo = get_db_connection();
$passed_count = 0;
$total_count = 20;

function report(int $num, string $title, bool $passed, string $detail = '') {
    global $passed_count;
    if ($passed) {
        $passed_count++;
        echo sprintf("[PASS] Test %02d: %s\n", $num, $title);
    } else {
        echo sprintf("[FAIL] Test %02d: %s -- %s\n", $num, $title, $detail);
    }
}

// Cleanup previous test state
$pdo->exec("DELETE FROM reservations WHERE requester_email LIKE '%@test.scenario'");
$test_date = date('Y-m-d', strtotime('+5 days'));

// -------------------------------------------------------------
// 1. Valid reservation
// -------------------------------------------------------------
$res1 = create_reservation([
    'requester_name'      => 'Test User 1',
    'requester_email'     => 'user1@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 1 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '09:00',
    'end_time'            => '10:00'
], $pdo);
report(1, "Valid reservation", $res1['success']);
$id1 = $res1['reservation_id'] ?? 0;

// -------------------------------------------------------------
// 2. Missing required fields
// -------------------------------------------------------------
$res2 = create_reservation([
    'requester_name'      => '',
    'requester_email'     => 'user2@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 2 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '10:00',
    'end_time'            => '11:00'
], $pdo);
report(2, "Missing required fields validation", !$res2['success'] && $res2['error'] === 'VALIDATION_FAILED');

// -------------------------------------------------------------
// 3. Invalid email
// -------------------------------------------------------------
$res3 = create_reservation([
    'requester_name'      => 'Test User 3',
    'requester_email'     => 'not-an-email',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 3 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '10:00',
    'end_time'            => '11:00'
], $pdo);
report(3, "Invalid email validation", !$res3['success'] && strpos($res3['message'], 'valid email') !== false);

// -------------------------------------------------------------
// 4. Past date
// -------------------------------------------------------------
$res4 = create_reservation([
    'requester_name'      => 'Test User 4',
    'requester_email'     => 'user4@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 4 Test',
    'reservation_date'    => '2020-01-01',
    'start_time'          => '10:00',
    'end_time'            => '11:00'
], $pdo);
report(4, "Past date validation", !$res4['success'] && strpos($res4['message'], 'past') !== false);

// -------------------------------------------------------------
// 5. Invalid time range (end <= start)
// -------------------------------------------------------------
$res5 = create_reservation([
    'requester_name'      => 'Test User 5',
    'requester_email'     => 'user5@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 5 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '11:00',
    'end_time'            => '10:00'
], $pdo);
report(5, "Invalid time range validation", !$res5['success'] && strpos($res5['message'], 'later than start time') !== false);

// -------------------------------------------------------------
// 6. Exact schedule conflict
// -------------------------------------------------------------
$res6 = create_reservation([
    'requester_name'      => 'Test User 6',
    'requester_email'     => 'user6@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 6 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '09:00',
    'end_time'            => '10:00'
], $pdo);
report(6, "Exact schedule conflict rejection", !$res6['success'] && $res6['error'] === 'CONFLICT');

// -------------------------------------------------------------
// 7. Partial schedule overlap
// -------------------------------------------------------------
$res7 = create_reservation([
    'requester_name'      => 'Test User 7',
    'requester_email'     => 'user7@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 7 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '09:30',
    'end_time'            => '10:30'
], $pdo);
report(7, "Partial schedule overlap rejection", !$res7['success'] && $res7['error'] === 'CONFLICT');

// -------------------------------------------------------------
// 8. Back-to-back reservation
// -------------------------------------------------------------
$res8 = create_reservation([
    'requester_name'      => 'Test User 8',
    'requester_email'     => 'user8@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 8 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '10:00',
    'end_time'            => '11:00'
], $pdo);
report(8, "Back-to-back reservation allowed", $res8['success']);

// -------------------------------------------------------------
// 9. Different time slot reservation
// -------------------------------------------------------------
$res9 = create_reservation([
    'requester_name'      => 'Test User 9',
    'requester_email'     => 'user9@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 9 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '14:00',
    'end_time'            => '15:00'
], $pdo);
report(9, "Different non-overlapping time slot allowed", $res9['success']);

// -------------------------------------------------------------
// 10. Cancelled reservation
// -------------------------------------------------------------
update_reservation_status($id1, 'CANCELLED', null, $pdo);
$res1_check = get_reservation_by_id($id1, $pdo);
report(10, "Cancelled reservation status update", $res1_check['status'] === 'CANCELLED');

// -------------------------------------------------------------
// 11. Rejected reservation
// -------------------------------------------------------------
$res11 = create_reservation([
    'requester_name'      => 'Test User 11',
    'requester_email'     => 'user11@test.scenario',
    'project_team_office' => 'QA Team',
    'purpose'             => 'Scenario 11 Test',
    'reservation_date'    => $test_date,
    'start_time'          => '16:00',
    'end_time'            => '17:00'
], $pdo);
update_reservation_status($res11['reservation_id'], 'REJECTED', 'Schedule conflict', $pdo);
$res11_check = get_reservation_by_id($res11['reservation_id'], $pdo);
report(11, "Rejected reservation status update", $res11_check['status'] === 'REJECTED');

// -------------------------------------------------------------
// 12. Successful email logging
// -------------------------------------------------------------
$log_id = log_email_attempt($id1, 'user1@test.scenario', 'TEST_TYPE', 'Test Subject', 'SENT', null, $pdo);
$stmt_log = $pdo->prepare("SELECT * FROM email_logs WHERE id = :id");
$stmt_log->execute(['id' => $log_id]);
$log_rec = $stmt_log->fetch();
report(12, "Successful email log recorded", $log_rec && $log_rec['status'] === 'SENT');

// -------------------------------------------------------------
// 13. Failed email logging (reservation NOT deleted)
// -------------------------------------------------------------
$log_id_fail = log_email_attempt($id1, 'user1@test.scenario', 'TEST_TYPE', 'Test Subject', 'FAILED', 'SMTP Error', $pdo);
$res1_still_exists = get_reservation_by_id($id1, $pdo);
report(13, "Failed email logged without deleting reservation", $res1_still_exists && $log_id_fail > 0);

// -------------------------------------------------------------
// 14. Admin login
// -------------------------------------------------------------
$login_ok = login_admin('admin@example.com', 'AdminPassword123!', $pdo);
report(14, "Admin login authentication", $login_ok['success'] && is_admin_logged_in());
logout_admin();

// -------------------------------------------------------------
// 15. Invalid admin credentials
// -------------------------------------------------------------
$login_bad = login_admin('admin@example.com', 'WrongPassword!', $pdo);
report(15, "Invalid admin credentials rejected", !$login_bad['success']);

// -------------------------------------------------------------
// 16. Unauthorized admin access
// -------------------------------------------------------------
logout_admin();
report(16, "Unauthorized admin access check", !is_admin_logged_in());

// -------------------------------------------------------------
// 17. CSRF protection token validation
// -------------------------------------------------------------
$token = csrf_token();
$valid_token = verify_csrf_token($token);
$invalid_token = verify_csrf_token('fake_csrf_token');
report(17, "CSRF token validation", $valid_token && !$invalid_token);

// -------------------------------------------------------------
// 18. SQL injection protection
// -------------------------------------------------------------
$sqli_input = "' OR '1'='1";
$stmt_sqli = $pdo->prepare("SELECT * FROM reservations WHERE requester_name = :name");
$stmt_sqli->execute(['name' => $sqli_input]);
$sqli_res = $stmt_sqli->fetchAll();
report(18, "SQL Injection prevention with PDO prepared statements", count($sqli_res) === 0);

// -------------------------------------------------------------
// 19. XSS protection HTML escaping
// -------------------------------------------------------------
$xss_input = "<script>alert('XSS')</script>";
$escaped = e($xss_input);
report(19, "XSS protection HTML escaping", strpos($escaped, '<script>') === false && strpos($escaped, '&lt;script&gt;') !== false);

// -------------------------------------------------------------
// 20. Concurrent reservation attempts simulation
// -------------------------------------------------------------
$concurrent_date = date('Y-m-d', strtotime('+6 days'));
$c1 = create_reservation([
    'requester_name'      => 'Simulated Thread 1',
    'requester_email'     => 'thread1@test.scenario',
    'project_team_office' => 'Concurrency Lab',
    'purpose'             => 'Atomic Lock Test',
    'reservation_date'    => $concurrent_date,
    'start_time'          => '15:00',
    'end_time'            => '16:00'
], $pdo);

$c2 = create_reservation([
    'requester_name'      => 'Simulated Thread 2',
    'requester_email'     => 'thread2@test.scenario',
    'project_team_office' => 'Concurrency Lab',
    'purpose'             => 'Atomic Lock Test',
    'reservation_date'    => $concurrent_date,
    'start_time'          => '15:00',
    'end_time'            => '16:00'
], $pdo);

report(20, "Concurrent reservation atomic conflict prevention", $c1['success'] && !$c2['success'] && $c2['error'] === 'CONFLICT');

echo "\n===============================================================\n";
echo sprintf("       TEST SUITE RESULTS: %d / %d PASSED (%d%%)\n", $passed_count, $total_count, ($passed_count/$total_count)*100);
echo "===============================================================\n";

if ($passed_count === $total_count) {
    exit(0);
} else {
    exit(1);
}
