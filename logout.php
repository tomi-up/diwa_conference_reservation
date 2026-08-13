<?php
/**
 * Public User Logout Handler
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';

logout_user();

set_flash_message('success', 'You have been successfully logged out.');
redirect(APP_URL . '/index.php');
