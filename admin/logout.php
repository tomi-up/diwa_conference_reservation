<?php
/**
 * Admin Logout Endpoint
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logout_admin();
set_flash_message('info', 'You have been logged out successfully.');
redirect(APP_URL . '/index');
