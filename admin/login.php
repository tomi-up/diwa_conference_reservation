<?php
/**
 * Legacy Admin Login Route - Admin access is now handled entirely via Google SSO
 * (see api/google_login.php). This route just redirects to the homepage, where
 * an account listed in the `admins` table automatically lands in the dashboard
 * after signing in with Google.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    redirect(APP_URL . '/admin/calendar');
}

set_flash_message('info', 'Admin access is now via Google Sign-In. Please sign in with your authorized UP Mail account from the homepage.');
redirect(APP_URL . '/index');
