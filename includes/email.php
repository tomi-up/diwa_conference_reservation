<?php
/**
 * PHPMailer Integration, Email Compiler & Audit Logging System
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/reservation.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Log email delivery status to email_logs table
 */
function log_email_attempt(?int $reservation_id, string $recipient_email, string $email_type, string $subject, string $status, ?string $error_message = null, ?PDO $pdo = null): int {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $stmt = $pdo->prepare("
        INSERT INTO email_logs (reservation_id, recipient_email, email_type, subject, status, error_message, sent_at)
        VALUES (:reservation_id, :recipient_email, :email_type, :subject, :status, :error_message, :sent_at)
    ");

    $stmt->execute([
        'reservation_id'  => $reservation_id,
        'recipient_email' => $recipient_email,
        'email_type'      => $email_type,
        'subject'         => $subject,
        'status'          => $status,
        'error_message'   => $error_message,
        'sent_at'         => ($status === 'SENT') ? date('Y-m-d H:i:s') : null
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * Compile email template with placeholders & organization signature
 */
function render_email_template(string $template_name, array $variables, ?PDO $pdo = null): string {
    $template_path = __DIR__ . '/../templates/emails/' . $template_name;
    if (!file_exists($template_path)) {
        return '';
    }

    $settings = get_system_settings($pdo);
    $signature_html = $settings['email_signature_html'] ?? '';

    // Check if signature_html is default text or custom
    if (empty($signature_html) || strpos($signature_html, 'cid:email_signature') !== false) {
        $signature_html = '<p style="margin-top: 15px; margin-bottom: 10px; color: #475569;">Thank you.</p><div style="margin-top: 5px; background-color: #ffffff; padding: 10px; border-radius: 8px; display: inline-block;"><img src="cid:email_signature" alt="Email Signature" style="max-width: 580px; width: 100%; height: auto; display: block; border: 0;"></div>';
    }

    // Default variable replacements
    $variables['email_signature'] = $signature_html;

    $content = file_get_contents($template_path);

    foreach ($variables as $key => $val) {
        $placeholder = '{{ ' . $key . ' }}';
        $content = str_replace($placeholder, (string)$val, $content);
    }

    return $content;
}

/**
 * Send email using PHPMailer and log result
 */
function dispatch_email(string $recipient_email, string $subject, string $html_body, string $email_type, ?int $reservation_id = null, ?PDO $pdo = null): bool {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $mail_cfg = get_mail_config();
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $mail_cfg['host'];
        $mail->Port       = (int)$mail_cfg['port'];
        $mail->Username   = $mail_cfg['username'];
        $mail->Password   = $mail_cfg['password'];
        $mail->SMTPSecure = $mail_cfg['encryption'];
        $mail->SMTPAuth   = !empty($mail_cfg['username']);

        $mail->setFrom($mail_cfg['from_email'], $mail_cfg['from_name']);
        $mail->addAddress($recipient_email);

        // Check for local DIWA Logo image to attach inline with CID
        $logo_paths = [
            __DIR__ . '/../assets/images/diwa_logo_landscape.png',
            __DIR__ . '/../assets/images/diwa_logo.png'
        ];

        foreach ($logo_paths as $logo_file) {
            if (file_exists($logo_file)) {
                $mail->addEmbeddedImage($logo_file, 'diwa_logo', basename($logo_file));
                break;
            }
        }

        // Check for local PNG Canva signature image to attach inline with CID
        $sig_paths = [
            __DIR__ . '/../assets/images/signature.png',
            __DIR__ . '/../assets/images/email-signature.png',
            __DIR__ . '/../assets/images/signature.jpg',
        ];

        foreach ($sig_paths as $sig_file) {
            if (file_exists($sig_file)) {
                $mail->addEmbeddedImage($sig_file, 'email_signature', basename($sig_file));
                break;
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;

        $mail->send();

        // Log SENT status
        log_email_attempt($reservation_id, $recipient_email, $email_type, $subject, 'SENT', null, $pdo);
        return true;

    } catch (Exception $e) {
        // Log FAILED status (DO NOT DELETE RESERVATION!)
        log_email_attempt($reservation_id, $recipient_email, $email_type, $subject, 'FAILED', $e->getMessage(), $pdo);
        return false;
    } catch (\Throwable $e) {
        log_email_attempt($reservation_id, $recipient_email, $email_type, $subject, 'FAILED', $e->getMessage(), $pdo);
        return false;
    }
}

/**
 * Trigger Reservation Confirmation Email
 */
function send_reservation_confirmation_email(int $reservation_id, ?PDO $pdo = null): bool {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $res = get_reservation_by_id($reservation_id, $pdo);
    if (!$res) {
        return false;
    }

    $subject = "Conference Room Reservation Confirmed";
    $html_body = render_email_template('reservation_confirmed.php', [
        'requester_name'      => e($res['requester_name']),
        'room_name'           => e($res['room_name']),
        'reservation_date'    => format_date($res['reservation_date']),
        'start_time'          => format_time($res['start_time']),
        'end_time'            => format_time($res['end_time']),
        'project_team_office' => e($res['project_team_office']),
        'purpose'             => e($res['purpose']),
        'reservation_id'      => format_reservation_id($res['id'], $res['created_at'])
    ], $pdo);

    return dispatch_email($res['requester_email'], $subject, $html_body, 'RESERVATION_CONFIRMED', $reservation_id, $pdo);
}

/**
 * Trigger Reservation Rejection Email
 */
function send_reservation_rejection_email(int $reservation_id, string $reason, ?PDO $pdo = null): bool {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $res = get_reservation_by_id($reservation_id, $pdo);
    if (!$res) {
        return false;
    }

    $subject = "Conference Room Reservation – Unavailable";
    $html_body = render_email_template('reservation_rejected.php', [
        'requester_name'   => e($res['requester_name']),
        'room_name'        => e($res['room_name']),
        'reservation_date' => format_date($res['reservation_date']),
        'start_time'       => format_time($res['start_time']),
        'end_time'         => format_time($res['end_time']),
        'rejection_reason' => e($reason)
    ], $pdo);

    return dispatch_email($res['requester_email'], $subject, $html_body, 'RESERVATION_REJECTED', $reservation_id, $pdo);
}

/**
 * Trigger Reservation Cancellation Email
 */
function send_reservation_cancellation_email(int $reservation_id, ?string $reason = null, ?PDO $pdo = null): bool {
    if (!$pdo) {
        $pdo = get_db_connection();
    }

    $res = get_reservation_by_id($reservation_id, $pdo);
    if (!$res) {
        return false;
    }

    $cancellation_reason = $reason ?: $res['rejection_reason'] ?: 'Administrative cancellation';

    $subject = "Conference Room Reservation Cancelled";
    $html_body = render_email_template('reservation_cancelled.php', [
        'requester_name'      => e($res['requester_name']),
        'room_name'           => e($res['room_name']),
        'reservation_date'    => format_date($res['reservation_date']),
        'start_time'          => format_time($res['start_time']),
        'end_time'            => format_time($res['end_time']),
        'purpose'             => e($res['purpose']),
        'reservation_id'      => format_reservation_id($res['id'], $res['created_at']),
        'cancellation_reason' => e($cancellation_reason)
    ], $pdo);

    return dispatch_email($res['requester_email'], $subject, $html_body, 'RESERVATION_CANCELLED', $reservation_id, $pdo);
}
