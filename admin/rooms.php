<?php
/**
 * Room management is no longer needed for single conference room architecture.
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
redirect('index.php');
