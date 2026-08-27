<?php
/**
 * Admin Authentication & Session Security Handler
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if current session belongs to an authenticated administrator
 */
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in']);
}

/**
 * Middleware: Protect admin routes by redirecting unauthenticated users to login page
 */
function require_admin_login(): void {
    if (!is_admin_logged_in()) {
        set_flash_message('danger', 'Please sign in with your authorized UP Mail (Google) account to access the Admin Portal.');
        redirect(APP_URL . '/index');
    }
}

/**
 * Authenticate administrator credentials using password_verify()
 */
function login_admin(string $email, string $password, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => strtolower(trim($email))]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    if ((int)$admin['is_active'] !== 1) {
        return ['success' => false, 'message' => 'This administrator account has been deactivated.'];
    }

    if (!password_verify($password, $admin['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Regenerate session ID upon successful login to prevent session fixation attacks
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id']        = (int)$admin['id'];
    $_SESSION['admin_name']      = $admin['name'];
    $_SESSION['admin_email']     = $admin['email'];

    return ['success' => true];
}

/**
 * Logout current administrator and clear session data
 */
function logout_admin(): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
}

/**
 * =========================================================
 * Public User Authentication & Session Security Handler
 * (Google OAuth / UP Mail Users)
 * =========================================================
 */

/**
 * Check if current session belongs to an authenticated user
 */
function is_user_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_logged_in']);
}

/**
 * Retrieve authenticated user profile from database session
 */
function current_user(?PDO $pdo = null): ?array {
    if (!is_user_logged_in()) {
        return null;
    }

    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $stmt = $pdo->prepare("SELECT id, google_sub, name, email, created_at, last_login_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Middleware: Require authenticated public user login
 */
function require_user_login(): void {
    if (!is_user_logged_in()) {
        set_flash_message('warning', 'Please sign in with your UP Mail (@up.edu.ph) account to continue.');
        redirect(APP_URL . '/reserve');
    }
}

/**
 * Logout current public user and clear user session data
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    unset(
        $_SESSION['user_logged_in'],
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_email'],
        $_SESSION['user_google_sub'],
        $_SESSION['user_picture'],
        $_SESSION['admin_logged_in'],
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_email']
    );
}

