<?php
/**
 * Redirect System Settings to Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
redirect('index.php');
