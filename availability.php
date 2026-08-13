<?php
/**
 * Availability Checker Redirect to Unified Reservation & Availability Portal
 */
require_once __DIR__ . '/includes/functions.php';

$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS);
if ($date) {
    redirect('reserve.php?date=' . urlencode($date));
} else {
    redirect('reserve.php');
}
