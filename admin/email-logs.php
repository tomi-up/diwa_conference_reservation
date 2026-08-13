<?php
/**
 * Admin Email Delivery Audit Logs View (With Action Icons)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email.php';

$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $res_id = (int)($_POST['reservation_id'] ?? 0);
    $token  = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (verify_csrf_token($token) && $action === 'resend_email' && $res_id > 0) {
        $res = get_reservation_by_id($res_id, $pdo);
        if ($res) {
            if ($res['status'] === 'CONFIRMED') {
                send_reservation_confirmation_email($res_id, $pdo);
            } elseif ($res['status'] === 'REJECTED') {
                send_reservation_rejection_email($res_id, $res['rejection_reason'] ?? 'Schedule unavailable', $pdo);
            } elseif ($res['status'] === 'CANCELLED') {
                send_reservation_cancellation_email($res_id, $pdo);
            }
            set_flash_message('success', "Email dispatch re-triggered for Reservation #" . format_reservation_id($res_id) . ".");
        } else {
            set_flash_message('danger', "Reservation no longer exists.");
        }
        redirect('email-logs.php');
    }
}

$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
$search_filter = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);

$sql = "SELECT * FROM email_logs WHERE 1=1";
$params = [];

if ($status_filter) {
    $sql .= " AND status = :status";
    $params['status'] = $status_filter;
}

if ($search_filter) {
    $sql .= " AND (recipient_email LIKE :search OR subject LIKE :search OR error_message LIKE :search)";
    $params['search'] = '%' . $search_filter . '%';
}

$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$page_title = "Email Logs Audit - Admin Portal";
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <h1 class="h3 fw-bold">Email Audit Logs</h1>
    <span class="badge bg-secondary fs-6 fw-normal"><?= count($logs) ?> Entries Logged</span>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="email-logs.php" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label for="search" class="form-label small text-muted mb-1">Search Recipient or Subject</label>
                <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?= e($search_filter) ?>" placeholder="user@example.com, subject...">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small text-muted mb-1">Delivery Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All Delivery Statuses</option>
                    <option value="SENT" <?= ($status_filter === 'SENT') ? 'selected' : '' ?>>SENT</option>
                    <option value="FAILED" <?= ($status_filter === 'FAILED') ? 'selected' : '' ?>>FAILED</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Filter Logs
                </button>
                <a href="email-logs.php" class="btn btn-light btn-sm text-secondary border" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Email Logs Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5 text-muted small">
                No email log entries found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Reservation</th>
                            <th>Recipient Email</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Attempted / Sent At</th>
                            <th>Error Details</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="fw-semibold text-secondary">#<?= $log['id'] ?></td>
                                <td>
                                    <?php if ($log['reservation_id']): ?>
                                        <a href="reservation-view.php?id=<?= $log['reservation_id'] ?>" class="fw-semibold">
                                            <?= format_reservation_id($log['reservation_id']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= e($log['recipient_email']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($log['email_type']) ?></span></td>
                                <td class="small"><?= e($log['subject']) ?></td>
                                <td>
                                    <?php if ($log['status'] === 'SENT'): ?>
                                        <span class="badge bg-success">SENT</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">FAILED</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted text-nowrap"><?= e($log['sent_at'] ?: $log['created_at']) ?></td>
                                <td class="small text-danger" style="max-width: 200px;">
                                    <?= e($log['error_message'] ?: 'None (OK)') ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($log['reservation_id']): ?>
                                        <form method="POST" action="email-logs.php" class="d-inline" onsubmit="return confirm('Trigger manual resend of email?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="resend_email">
                                            <input type="hidden" name="reservation_id" value="<?= $log['reservation_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Resend Email">
                                                <i class="bi bi-arrow-repeat me-1"></i> Resend
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
