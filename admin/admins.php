<?php
/**
 * Redirect Admins Management to Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
redirect('index.php');
