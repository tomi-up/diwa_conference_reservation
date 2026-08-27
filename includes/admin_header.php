<?php
/**
 * Shared Admin Header Template for Minimalist Dashboard Layout
 */

require_once __DIR__ . '/auth.php';
require_admin_login();

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? 'Admin Dashboard - Conference Room Reservation';
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <!-- Outfit Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- FullCalendar 6 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin-calendar.css">
</head>
<body style="background-color: #f8f8f8;">

<!-- High-Visibility Clean Top Navigation Bar -->
<header class="navbar navbar-light sticky-top flex-md-nowrap p-2 shadow-sm bg-white">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <a class="navbar-brand me-3" href="<?= APP_URL ?>/admin/index">
            <img src="<?= APP_URL ?>/assets/images/diwa_logo_landscape.png" alt="DIWA Logo" class="brand-logo-landscape">
        </a>
        <div class="navbar-nav flex-row gap-2 ms-auto">
            <a class="btn btn-outline-secondary btn-sm" href="<?= APP_URL ?>/reserve" target="_blank">
                <i class="bi bi-box-arrow-up-right me-1"></i> Public Site
            </a>
            <a class="btn btn-secondary btn-sm text-white" href="<?= APP_URL ?>/admin/logout">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse p-3">
            <div class="position-sticky pt-2">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'index.php' || $current_page === 'index') ? 'active' : '' ?>" href="index">
                            <i class="bi bi-grid-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'reservations.php' || $current_page === 'reservation-view.php' || $current_page === 'reservations') ? 'active' : '' ?>" href="reservations">
                            <i class="bi bi-list-task"></i> Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page === 'calendar.php' || $current_page === 'calendar') ? 'active' : '' ?>" href="calendar">
                            <i class="bi bi-calendar-range"></i> Calendar
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
