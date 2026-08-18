<?php
/**
 * Public Room Schedule Availability Utilities (Single Conference Room)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Fetch occupied time windows for the conference room on a given date.
 * Returns only start_time and end_time (NO PRIVATE DATA EXPOSED).
 */
/**
 * Fetch occupied time windows for the conference room on a given date with reservation details.
 */
function get_occupied_time_slots(string $date, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $stmt = $pdo->prepare("
        SELECT id, requester_name, project_team_office, purpose, start_time, end_time
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
            'id'                  => (int)$row['id'],
            'requester_name'      => $row['requester_name'],
            'project_team_office' => $row['project_team_office'],
            'purpose'             => $row['purpose'],
            'start_time'          => substr($row['start_time'], 0, 5), // "HH:MM"
            'end_time'            => substr($row['end_time'], 0, 5),   // "HH:MM"
            'status'              => 'OCCUPIED'
        ];
    }

    return $occupied;
}

/**
 * Generate standard hourly schedule matrix from 07:00 to 18:00 for public inspection.
 */
function generate_daily_schedule_matrix(string $date, ?PDO $pdo = null): array {
    $occupied = get_occupied_time_slots($date, $pdo);

    $slots = [];
    $start_hour = 7;
    $end_hour = 18;

    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        $s_time = sprintf('%02d:00', $hour);
        $e_time = sprintf('%02d:00', $hour + 1);

        $matching_occ = null;
        foreach ($occupied as $occ) {
            if ($s_time < $occ['end_time'] && $e_time > $occ['start_time']) {
                $matching_occ = $occ;
                break;
            }
        }

        $is_occupied = ($matching_occ !== null);

        $slot_data = [
            'start_time' => $s_time,
            'end_time'   => $e_time,
            'label'      => date('gA', strtotime($s_time)) . ' - ' . date('gA', strtotime($e_time)),
            'status'     => $is_occupied ? 'OCCUPIED' : 'AVAILABLE'
        ];

        if ($is_occupied && $matching_occ) {
            $slot_data['requester_name']      = $matching_occ['requester_name'];
            $slot_data['project_team_office'] = $matching_occ['project_team_office'];
            $slot_data['purpose']             = $matching_occ['purpose'];
            $slot_data['occ_start_time']      = date('g:i A', strtotime($matching_occ['start_time']));
            $slot_data['occ_end_time']        = date('g:i A', strtotime($matching_occ['end_time']));
        }

        $slots[] = $slot_data;
    }

    return [
        'room_name'       => CONFERENCE_ROOM_NAME,
        'date'            => $date,
        'slots'           => $slots,
        'occupied_blocks' => $occupied
    ];
}
