<?php
/**
 * JSON API Endpoint for FullCalendar Events (Active Confirmed & Blocked Schedules Only)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_admin_logged_in()) {
    json_response(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json; charset=utf-8');

$start = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_SPECIAL_CHARS);
$end   = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_SPECIAL_CHARS);

$pdo = get_db_connection();

// Only fetch active CONFIRMED reservations and ADMIN_BLOCK entries (Hide Cancelled & Rejected from calendar grid)
$sql = "
    SELECT r.*
    FROM reservations r
    WHERE r.status = 'CONFIRMED'
";
$params = [];

if ($start && $end) {
    $sql .= " AND r.reservation_date BETWEEN :start AND :end";
    $params['start'] = date('Y-m-d', strtotime($start));
    $params['end']   = date('Y-m-d', strtotime($end));
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$events = [];
foreach ($reservations as $res) {
    $start_iso = $res['reservation_date'] . 'T' . $res['start_time'];
    $end_iso   = $res['reservation_date'] . 'T' . $res['end_time'];

    $is_blocked = ($res['project_team_office'] === 'ADMIN_BLOCK');

    if ($is_blocked) {
        $color = '#ef4444'; // Red for Blocked / Unavailable
        $title = 'Blocked: ' . $res['purpose'];
    } else {
        $color = '#166534'; // Green for Confirmed
        $title = date('g:i A', strtotime($res['start_time'])) . ' · ' . $res['requester_name'];
    }

    $events[] = [
        'id'              => (string)$res['id'],
        'title'           => $title,
        'start'           => $start_iso,
        'end'             => $end_iso,
        'backgroundColor' => $color,
        'borderColor'     => $color,
        'textColor'       => '#ffffff',
        'extendedProps'   => [
            'id'                  => $res['id'],
            'reservation_code'    => format_reservation_id($res['id'], $res['created_at']),
            'room_name'           => CONFERENCE_ROOM_NAME,
            'requester_name'      => $res['requester_name'],
            'requester_email'     => $res['requester_email'],
            'project_team_office' => $res['project_team_office'],
            'purpose'             => $res['purpose'],
            'status'              => $res['status'],
            'is_blocked'          => $is_blocked,
            'rejection_reason'    => $res['rejection_reason'] ?? '',
            'start_time_fmt'      => format_time($res['start_time']),
            'end_time_fmt'        => format_time($res['end_time']),
            'date_fmt'            => format_date($res['reservation_date'])
        ]
    ];
}

echo json_encode($events);
exit;
