<?php
/**
 * JSON API Endpoint for Schedule Availability & Real-time Slot Checking
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/reservation.php';
require_once __DIR__ . '/../includes/availability.php';

header('Content-Type: application/json; charset=utf-8');

$date       = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS) ?: ($_GET['date'] ?? null);
$start_time = filter_input(INPUT_GET, 'start_time', FILTER_SANITIZE_SPECIAL_CHARS) ?: ($_GET['start_time'] ?? null);
$end_time   = filter_input(INPUT_GET, 'end_time', FILTER_SANITIZE_SPECIAL_CHARS) ?: ($_GET['end_time'] ?? null);

if (!$date) {
    json_response(['success' => false, 'message' => 'Invalid or missing date parameter.'], 400);
}

$pdo = get_db_connection();
$matrix = generate_daily_schedule_matrix($date, $pdo);

$response = [
    'success'        => true,
    'room_name'      => CONFERENCE_ROOM_NAME,
    'formatted_date' => format_date($date),
    'date'           => $date,
    'slots'          => $matrix['slots']
];

// If specific time range is requested, evaluate exact availability
if (!empty($start_time) && !empty($end_time)) {
    if (strtotime($end_time) <= strtotime($start_time)) {
        $response['slot_check'] = [
            'is_valid'   => false,
            'status'     => 'INVALID_TIME',
            'message'    => 'End time must be later than start time.',
            'badge_html' => '<div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis p-3 rounded-3 text-center mb-0">End time must be later than start time.</div>'
        ];
    } elseif ($start_time < '07:00' || $end_time > '18:00') {
        $response['slot_check'] = [
            'is_valid'   => false,
            'status'     => 'OUT_OF_BOUNDS',
            'message'    => 'Reservation hours are strictly between 7:00 AM and 6:00 PM.',
            'badge_html' => '<div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis p-3 rounded-3 text-center mb-0">Reservation hours are strictly between 7:00 AM and 6:00 PM.</div>'
        ];
    } elseif ($date === date('Y-m-d') && $start_time <= date('H:i')) {
        $response['slot_check'] = [
            'is_valid'   => false,
            'status'     => 'PAST',
            'message'    => 'This time has already passed today.',
            'badge_html' => '<div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis p-3 rounded-3 text-center mb-0">This time has already passed today. Please select a later time.</div>'
        ];
    } else {
        $conflict = has_schedule_conflict($date, $start_time, $end_time, null, $pdo);
        if ($conflict) {
            $response['slot_check'] = [
                'is_valid'   => true,
                'is_available' => false,
                'status'     => 'UNAVAILABLE',
                'message'    => 'Selected time is not valid or unavailable due to schedule overlap.',
                'badge_html' => '<div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis p-3 rounded-3 text-center mb-0">Selected time is unavailable due to schedule overlap. Please select an available slot.</div>'
            ];
        } else {
            $response['slot_check'] = [
                'is_valid'   => true,
                'is_available' => true,
                'status'     => 'AVAILABLE',
                'message'    => 'Available',
                'badge_html' => ''
            ];
        }
    }
}

json_response($response);
