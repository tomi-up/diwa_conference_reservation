<?php
/**
 * Public Room Schedule Availability Utilities (Single Conference Room)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Fetch occupied time windows for the conference room on a given date.
 * Returns only start_time and end_time (NO PRIVATE DATA EXPOSED).
 */
function get_occupied_time_slots(string $date, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $stmt = $pdo->prepare("
        SELECT start_time, end_time
        FROM reservations
        WHERE reservation_date = :reservation_date
          AND status = 'CONFIRMED'
        ORDER BY start_time ASC
    ");

    $stmt->execute([
        'reservation_date' => $date
    ]);

    $occupied = [];
    while ($row = $stmt->fetch()) {
        $occupied[] = [
            'start_time' => substr($row['start_time'], 0, 5), // "HH:MM"
            'end_time'   => substr($row['end_time'], 0, 5),   // "HH:MM"
            'status'     => 'OCCUPIED'
        ];
    }

    return $occupied;
}

/**
 * Generate standard hourly schedule matrix from 07:00 to 19:00 for public inspection.
 */
function generate_daily_schedule_matrix(string $date, ?PDO $pdo = null): array {
    $occupied = get_occupied_time_slots($date, $pdo);

    $slots = [];
    $start_hour = 7;
    $end_hour = 18;

    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        $s_time = sprintf('%02d:00', $hour);
        $e_time = sprintf('%02d:00', $hour + 1);

        $is_occupied = false;
        foreach ($occupied as $occ) {
            if ($s_time < $occ['end_time'] && $e_time > $occ['start_time']) {
                $is_occupied = true;
                break;
            }
        }

        $slots[] = [
            'start_time' => $s_time,
            'end_time'   => $e_time,
            'label'      => date('g:i A', strtotime($s_time)) . ' - ' . date('g:i A', strtotime($e_time)),
            'status'     => $is_occupied ? 'OCCUPIED' : 'AVAILABLE'
        ];
    }

    return [
        'room_name'       => CONFERENCE_ROOM_NAME,
        'date'            => $date,
        'slots'           => $slots,
        'occupied_blocks' => $occupied
    ];
}
