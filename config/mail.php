<?php
/**
 * Active PHPMailer Configuration Loader
 */

require_once __DIR__ . '/config.php';

function get_mail_config(): array {
    return [
        'resend_api_key'    => RESEND_API_KEY,
        'resend_from_email' => RESEND_FROM_EMAIL,
        'host'              => SMTP_HOST,
        'port'              => SMTP_PORT,
        'username'          => SMTP_USERNAME,
        'password'          => SMTP_PASSWORD,
        'encryption'        => SMTP_ENCRYPTION,
        'from_email'        => SMTP_FROM_EMAIL,
        'from_name'         => SMTP_FROM_NAME,
    ];
}
