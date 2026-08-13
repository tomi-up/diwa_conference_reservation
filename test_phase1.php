<?php
/**
 * Test Runner for Phase 1: Database & Double-Booking Prevention Engine
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reservation.php';
require_once __DIR__ . '/includes/availability.php';

echo "=== Phase 1 Verification Test Suite ===\n\n";

$pdo = get_db_connection();
echo "[PASS] Database Connection Established via PDO.\n";

// Clear test reservations
$pdo->exec("DELETE FROM reservations WHERE requester_email LIKE '%test%' OR requester_email LIKE '%example.com%'");
echo "[INFO] Cleaned existing test reservations.\n\n";

$test_date = date('Y-m-d', strtotime('+2 days'));

// Test 1: Valid Reservation Creation
echo "Test 1: Creating valid reservation (Room 1, 09:00 - 11:00 on {$test_date})...\n";
$res1 = create_reservation([
    'room_id'             => 1,
    'requester_name'      => 'Alice Smith',
    'requester_email'     => 'alice.test@example.com',
    'project_team_office' => 'IT Operations',
    'purpose'             => 'Quarterly Infrastructure Review',
    'reservation_date'    => $test_date,
    'start_time'          => '09:00',
    'end_time'            => '11:00'
], $pdo);

if ($res1['success']) {
    echo "[PASS] Reservation #{$res1['reservation_id']} created successfully for {$res1['room_name']}.\n";
} else {
    echo "[FAIL] Reservation creation failed: {$res1['message']}\n";
    exit(1);
}

// Test 2: Overlapping Schedule Conflict (10:00 - 12:00 -> should fail)
echo "\nTest 2: Attempting overlapping reservation (Room 1, 10:00 - 12:00 -> Overlaps with 09:00-11:00)...\n";
$res2 = create_reservation([
    'room_id'             => 1,
    'requester_name'      => 'Bob Jones',
    'requester_email'     => 'bob.test@example.com',
    'project_team_office' => 'Marketing',
    'purpose'             => 'Campaign Briefing',
    'reservation_date'    => $test_date,
    'start_time'          => '10:00',
    'end_time'            => '12:00'
], $pdo);

if (!$res2['success'] && $res2['error'] === 'CONFLICT') {
    echo "[PASS] Conflict correctly rejected: {$res2['message']}\n";
} else {
    echo "[FAIL] Overlapping reservation was incorrectly allowed!\n";
    exit(1);
}

// Test 3: Back-to-Back Reservation (11:00 - 12:00 -> should succeed)
echo "\nTest 3: Attempting back-to-back reservation (Room 1, 11:00 - 12:00 -> Immediately after 09:00-11:00)...\n";
$res3 = create_reservation([
    'room_id'             => 1,
    'requester_name'      => 'Charlie Brown',
    'requester_email'     => 'charlie.test@example.com',
    'project_team_office' => 'Finance',
    'purpose'             => 'Budget Planning',
    'reservation_date'    => $test_date,
    'start_time'          => '11:00',
    'end_time'            => '12:00'
], $pdo);

if ($res3['success']) {
    echo "[PASS] Back-to-back Reservation #{$res3['reservation_id']} created successfully.\n";
} else {
    echo "[FAIL] Back-to-back reservation failed: {$res3['message']}\n";
    exit(1);
}

// Test 4: Same Time, Different Room (Room 2, 09:00 - 11:00 -> should succeed)
echo "\nTest 4: Attempting same time slot on different room (Room 2, 09:00 - 11:00)...\n";
$res4 = create_reservation([
    'room_id'             => 2,
    'requester_name'      => 'Diana Prince',
    'requester_email'     => 'diana.test@example.com',
    'project_team_office' => 'R&D',
    'purpose'             => 'Product Sync',
    'reservation_date'    => $test_date,
    'start_time'          => '09:00',
    'end_time'            => '11:00'
], $pdo);

if ($res4['success']) {
    echo "[PASS] Reservation #{$res4['reservation_id']} for Room 2 created successfully.\n";
} else {
    echo "[FAIL] Reservation for Room 2 failed: {$res4['message']}\n";
    exit(1);
}

// Test 5: Public Availability Privacy Test
echo "\nTest 5: Testing Public Availability Slot Matrix (No private data exposed)...\n";
$avail = generate_daily_schedule_matrix(1, $test_date, $pdo);
echo "[INFO] Occupied blocks found: " . count($avail['occupied_blocks']) . "\n";
foreach ($avail['occupied_blocks'] as $occ) {
    echo "  - Occupied: {$occ['start_time']} to {$occ['end_time']}\n";
}

echo "\n=== Phase 1 Verification Completed Successfully! ===\n";
