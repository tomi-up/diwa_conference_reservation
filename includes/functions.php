<?php
/**
 * Global Helper & Utility Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

/**
 * Escape HTML output for XSS prevention
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize text input string
 */
function sanitize_input(?string $value): string {
    return trim($value ?? '');
}

/**
 * Validate email address format
 */
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format reservation ID into standard organizational code (e.g. CR-2026-0042)
 */
function format_reservation_id(int $id, ?string $created_at = null): string {
    $year = $created_at ? date('Y', strtotime($created_at)) : date('Y');
    return sprintf('CR-%s-%04d', $year, $id);
}

/**
 * Format date for display (e.g., "October 14, 2026")
 */
function format_date(string $date_str): string {
    $timestamp = strtotime($date_str);
    return $timestamp ? date('F j, Y', $timestamp) : $date_str;
}

/**
 * Format time for display (e.g., "09:00 AM")
 */
function format_time(string $time_str): string {
    $timestamp = strtotime($time_str);
    return $timestamp ? date('g:i A', $timestamp) : $time_str;
}

/**
 * Set flash session message
 */
function set_flash_message(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash session message
 */
function get_flash_message(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect browser to URL and terminate script execution
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Output JSON response with appropriate headers
 */
function json_response(array $data, int $status_code = 200): void {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Build URL preserving current GET parameters with updates
 */
function build_query_url(array $new_params = []): string {
    $current_params = $_GET;
    $merged = array_merge($current_params, $new_params);
    return '?' . http_build_query($merged);
}

/**
 * Retrieve key-value array of all system settings from database
 */
function get_system_settings(?PDO $pdo = null): array {
    try {
        if (!$pdo) {
            $pdo = get_db_connection();
        }
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (\Throwable $e) {
        error_log("Failed to fetch system settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Update a specific system setting in database
 */
function update_system_setting(string $key, string $value, ?PDO $pdo = null): bool {
    if (!$pdo) {
        $pdo = get_db_connection();
    }
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    return $stmt->execute(['key' => $key, 'value' => $value]);
}
