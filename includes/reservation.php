<?php
/**
 * Reservation Core Data Access & Double-Booking Prevention Engine (Single Room)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if the conference room has schedule conflicts for a target date and time range.
 *
 * Conflict Condition:
 * existing.start_time < requested.end_time AND existing.end_time > requested.start_time
 *
 * Returns true if CONFLICT exists, false if AVAILABLE.
 */
function has_schedule_conflict(string $date, string $start_time, string $end_time, ?int $exclude_id = null, ?PDO $pdo = null): bool {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $sql = "
        SELECT COUNT(*) AS conflict_count
        FROM reservations
        WHERE reservation_date = :reservation_date
          AND status NOT IN ('CANCELLED', 'REJECTED')
          AND start_time < :end_time
          AND end_time > :start_time
    ";

    $params = [
        'reservation_date' => $date,
        'start_time'       => $start_time,
        'end_time'         => $end_time
    ];

    if ($exclude_id !== null) {
        $sql .= " AND id != :exclude_id";
        $params['exclude_id'] = $exclude_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return ((int)$row['conflict_count']) > 0;
}

/**
 * Create a new reservation with strict atomic transaction locking to prevent race conditions.
 */
function create_reservation(array $data, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $user_id             = isset($data['user_id']) ? (int)$data['user_id'] : null;
    $requester_name      = sanitize_input($data['requester_name'] ?? '');
    $requester_email     = sanitize_input($data['requester_email'] ?? '');
    $project_team_office = sanitize_input($data['project_team_office'] ?? '');
    $purpose             = sanitize_input($data['purpose'] ?? '');
    $reservation_date    = sanitize_input($data['reservation_date'] ?? '');
    $start_time          = sanitize_input($data['start_time'] ?? '');
    $end_time            = sanitize_input($data['end_time'] ?? '');

    // Server-side validation
    if (empty($requester_name) || empty($requester_email) || empty($project_team_office) || empty($purpose) || empty($reservation_date) || empty($start_time) || empty($end_time)) {
        return ['success' => false, 'error' => 'VALIDATION_FAILED', 'message' => 'All required fields must be completed.'];
    }

    if (!is_valid_email($requester_email)) {
        return ['success' => false, 'error' => 'VALIDATION_FAILED', 'message' => 'Please provide a valid email address.'];
    }

    $today = date('Y-m-d');
    if ($reservation_date < $today) {
        return ['success' => false, 'error' => 'VALIDATION_FAILED', 'message' => 'Reservation date cannot be in the past.'];
    }

    if ($reservation_date === $today && $start_time <= date('H:i')) {
        return ['success' => false, 'error' => 'VALIDATION_FAILED', 'message' => 'This time has already passed today. Please select a later time.'];
    }

    if (strtotime($end_time) <= strtotime($start_time)) {
        return ['success' => false, 'error' => 'VALIDATION_FAILED', 'message' => 'End time must be later than start time.'];
    }

    if ($start_time < '07:00' || $end_time > '18:00') {
        return ['success' => false, 'error' => 'VALIDATION_FAILED', 'message' => 'Reservation hours are strictly between 7:00 AM and 6:00 PM.'];
    }

    try {
        $pdo->beginTransaction();

        // Lock existing active reservations for this date to check conflict
        $stmt_conflict = $pdo->prepare("
            SELECT COUNT(*) AS conflict_count
            FROM reservations
            WHERE reservation_date = :reservation_date
              AND status NOT IN ('CANCELLED', 'REJECTED')
              AND start_time < :end_time
              AND end_time > :start_time
            FOR UPDATE
        ");

        $stmt_conflict->execute([
            'reservation_date' => $reservation_date,
            'start_time'       => $start_time,
            'end_time'         => $end_time
        ]);

        $conflict_row = $stmt_conflict->fetch();

        if ((int)$conflict_row['conflict_count'] > 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'error'   => 'CONFLICT',
                'message' => 'This schedule overlaps with an existing reservation.'
            ];
        }

        // Insert reservation
        $stmt_insert = $pdo->prepare("
            INSERT INTO reservations (
                user_id, requester_name, requester_email, project_team_office,
                purpose, reservation_date, start_time, end_time, status
            ) VALUES (
                :user_id, :requester_name, :requester_email, :project_team_office,
                :purpose, :reservation_date, :start_time, :end_time, 'CONFIRMED'
            )
        ");

        $stmt_insert->execute([
            'user_id'             => $user_id,
            'requester_name'      => $requester_name,
            'requester_email'     => $requester_email,
            'project_team_office' => $project_team_office,
            'purpose'             => $purpose,
            'reservation_date'    => $reservation_date,
            'start_time'          => $start_time,
            'end_time'            => $end_time
        ]);

        $reservation_id = (int)$pdo->lastInsertId();

        $pdo->commit();


        return [
            'success'        => true,
            'reservation_id' => $reservation_id,
            'room_name'      => CONFERENCE_ROOM_NAME
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'error'   => 'DATABASE_ERROR',
            'message' => 'Database error during reservation processing: ' . $e->getMessage()
        ];
    }
}

/**
 * Fetch detailed reservation record by ID
 */
function get_reservation_by_id(int $id, ?PDO $pdo = null): ?array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }
    $stmt = $pdo->prepare("
        SELECT r.*
        FROM reservations r
        WHERE r.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $res = $stmt->fetch();
    if ($res) {
        $res['room_name'] = CONFERENCE_ROOM_NAME;
    }
    return $res ?: null;
}

/**
 * Fetch reservations list with optional filtering
 */
function get_reservations_list(array $filters = [], ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $sql = "
        SELECT r.*
        FROM reservations r
        WHERE 1=1
    ";
    $params = [];

    if (!empty($filters['status'])) {
        $sql .= " AND r.status = :status";
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['date'])) {
        $sql .= " AND r.reservation_date = :date";
        $params['date'] = $filters['date'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (r.requester_name LIKE :search OR r.requester_email LIKE :search OR r.project_team_office LIKE :search OR r.purpose LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }

    $sql .= " ORDER BY r.reservation_date DESC, r.start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['room_name'] = CONFERENCE_ROOM_NAME;
    }
    return $rows;
}

/**
 * Fetch paginated reservations list
 */
function get_reservations_paginated(array $filters = [], int $page = 1, int $per_page = 10, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $where_sql = " WHERE 1=1";
    $params = [];

    if (!empty($filters['status'])) {
        $where_sql .= " AND r.status = :status";
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['date'])) {
        $where_sql .= " AND r.reservation_date = :date";
        $params['date'] = $filters['date'];
    }

    if (!empty($filters['search'])) {
        $where_sql .= " AND (r.requester_name LIKE :search OR r.requester_email LIKE :search OR r.project_team_office LIKE :search OR r.purpose LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }

    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM reservations r" . $where_sql);
    $stmt_count->execute($params);
    $total_records = (int)$stmt_count->fetchColumn();

    $total_pages = max(1, (int)ceil($total_records / $per_page));
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;

    $sql = "SELECT r.* FROM reservations r" . $where_sql . " ORDER BY r.reservation_date DESC, r.start_time ASC LIMIT " . (int)$offset . ", " . (int)$per_page;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['room_name'] = CONFERENCE_ROOM_NAME;
    }

    return [
        'data'          => $rows,
        'total_records' => $total_records,
        'total_pages'   => $total_pages,
        'current_page'  => $page,
        'per_page'      => $per_page
    ];
}

/**
 * Fetch all reservations belonging to a specific user, most recent first
 */
function get_user_reservations(int $user_id, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }
    $stmt = $pdo->prepare("
        SELECT r.*
        FROM reservations r
        WHERE r.user_id = :user_id
        ORDER BY r.reservation_date DESC, r.start_time DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['room_name'] = CONFERENCE_ROOM_NAME;
    }
    return $rows;
}

/**
 * Fetch upcoming confirmed reservations (date >= today)
 */
function get_upcoming_reservations(int $limit = 10, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT *
        FROM reservations
        WHERE reservation_date >= :today
          AND status = 'CONFIRMED'
        ORDER BY reservation_date ASC, start_time ASC
        LIMIT " . (int)$limit . "
    ");
    $stmt->execute(['today' => $today]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['room_name'] = CONFERENCE_ROOM_NAME;
    }
    return $rows;
}

/**
 * Update reservation status (e.g. REJECTED or CANCELLED)
 */
function update_reservation_status(int $id, string $status, ?string $rejection_reason = null, ?PDO $pdo = null): bool {
    if (!$pdo) {
        $pdo = get_db_connection();
    }
    $allowed = ['CONFIRMED', 'REJECTED', 'CANCELLED'];
    if (!in_array($status, $allowed)) {
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET status = :status,
            rejection_reason = :rejection_reason,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

    return $stmt->execute([
        'status'           => $status,
        'rejection_reason' => $rejection_reason,
        'id'               => $id
    ]);
}
