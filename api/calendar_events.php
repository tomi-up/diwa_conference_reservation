<?php
/**
 * JSON API Endpoint for FullCalendar Events (Active Confirmed & Blocked Schedules Only)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

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
        $color = '#ef4444';
        $title = 'Blocked: ' . $res['purpose'];
    } else {
        $color = '#166534';

        if (is_admin_logged_in()) {
            $title = date('g:i A', strtotime($res['start_time']))
                . ' · '
                . $res['requester_name'];
        } else {
            $title = date('g:i A', strtotime($res['start_time']))
                . ' · Reserved';
        }
    }

    $extendedProps = [
        'id'               => $res['id'],
        'purpose'          => $res['purpose'],
        'status'           => $res['status'],
        'is_blocked'       => $is_blocked,
        'start_time_fmt'   => date('H:i', strtotime($res['start_time'])),
        'end_time_fmt'     => date('H:i', strtotime($res['end_time'])),
        'date_fmt'         => format_date($res['reservation_date']),
        'project_team_office' => $res['project_team_office']
    ];

    // only to signed-in viewers (regular users or admins) - anonymous public viewers should not see who reserved
    if (is_admin_logged_in() || is_user_logged_in()) {
        $extendedProps['requester_name'] = $res['requester_name'];
    }

    // only to admins
    if (is_admin_logged_in()) {
        $extendedProps['reservation_code'] =
            format_reservation_id($res['id'], $res['created_at']);

        $extendedProps['room_name'] =
            CONFERENCE_ROOM_NAME;

        $extendedProps['requester_email'] =
            $res['requester_email'];

        $extendedProps['rejection_reason'] =
            $res['rejection_reason'] ?? '';
    }

    $events[] = [
        'id'              => (string)$res['id'],
        'title'           => $title,
        'start'           => $start_iso,
        'end'             => $end_iso,
        'backgroundColor' => $color,
        'borderColor'     => $color,
        'textColor'       => '#ffffff',
        'extendedProps'   => $extendedProps
    ];
}

echo json_encode($events);
exit;
