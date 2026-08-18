<?php
/**
 * Global Configuration Loader with .env File Support
 */

// Simple lightweight .env parser
function load_env_file(string $env_path): void {
    if (!file_exists($env_path)) {
        return;
    }
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("{$name}={$value}");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env from project root
load_env_file(__DIR__ . '/../.env');

// Dynamic APP_URL detection for local, network IP, ngrok tunnels, or shared hosting
if (getenv('APP_URL') && getenv('APP_URL') !== 'http://localhost/conference_reservation' && trim(getenv('APP_URL')) !== '') {
    $app_url = getenv('APP_URL');
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    
    // Automatically detect root path
    $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
    $proj_dir = str_replace('\\', '/', dirname(__DIR__));
    
    $sub_dir = '';
    if (!empty($doc_root) && strpos($proj_dir, $doc_root) === 0) {
        $sub_dir = substr($proj_dir, strlen($doc_root));
    }
    
    $app_url = $scheme . '://' . $host . rtrim($sub_dir, '/');
} else {
    $app_url = 'http://localhost/conference_reservation';
}

define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_NAME', getenv('APP_NAME') ?: 'Conference Room Reservation System');
define('APP_URL', rtrim($app_url, '/'));
define('CONFERENCE_ROOM_NAME', getenv('CONFERENCE_ROOM_NAME') ?: 'DIWA Center Conference Room');

// Database Connection Options with Automatic InfinityFree Fallback
$is_infinityfree = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'infinityfree') !== false || strpos($_SERVER['HTTP_HOST'], 'epizy.com') !== false));

define('DB_HOST', (getenv('DB_HOST') && getenv('DB_HOST') !== '127.0.0.1') ? getenv('DB_HOST') : ($is_infinityfree ? 'sql203.infinityfree.com' : '127.0.0.1'));
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', (getenv('DB_NAME') && getenv('DB_NAME') !== 'conference_reservation') ? getenv('DB_NAME') : ($is_infinityfree ? 'if0_42643421_conference' : 'conference_reservation'));
define('DB_USER', (getenv('DB_USER') && getenv('DB_USER') !== 'root') ? getenv('DB_USER') : ($is_infinityfree ? 'if0_42643421' : 'root'));
define('DB_PASS', getenv('DB_PASS') ? getenv('DB_PASS') : ($is_infinityfree ? 'FLMe8kR8XaUh' : ''));
define('DB_CHARSET', 'utf8mb4');

// SMTP Credentials (PHPMailer)
define('SMTP_HOST', getenv('SMTP_HOST') ?: '127.0.0.1');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'no-reply@diwacenter.example');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'DIWA Center Conference Reservations');

// Resend HTTP Email API (HTTPS cURL)
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: '');
define('RESEND_FROM_EMAIL', getenv('RESEND_FROM_EMAIL') ?: 'DIWA Center Reservations <onboarding@resend.dev>');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '721603219362-sa613qs2pejt4f8ekpep88k5pn9mp4dk.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('ALLOWED_GOOGLE_DOMAIN', getenv('ALLOWED_GOOGLE_DOMAIN') ?: 'up.edu.ph');

// Session Options

define('SESSION_LIFETIME', 86400); // 24 hours
define('CSRF_TOKEN_NAME', 'csrf_token');

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting control
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
