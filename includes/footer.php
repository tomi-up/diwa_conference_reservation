<?php
/**
 * Shared Footer Template for Public Interface
 */
?>

<!-- Public Blocked Slot Info Pop-up Modal -->
<div class="modal fade" id="blockedSlotDetailModal" tabindex="-1" aria-labelledby="blockedSlotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bg-danger text-white px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="modal-title fw-bold fs-6 mb-0" id="blockedSlotModalLabel">
                    <i class="bi bi-info-circle-fill me-2"></i>Time Slot Reserved
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger-subtle border border-danger-subtle text-danger p-3 rounded-3 mb-3 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
                    <div class="small fw-semibold">
                        This conference room time slot is currently occupied and unavailable for new bookings.
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2.5 rounded border bg-light">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="bi bi-calendar-event me-1 text-danger"></i>Date</div>
                            <div id="blockedModalDate" class="fw-bold text-dark" style="font-size: 0.875rem;"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2.5 rounded border bg-light">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="bi bi-clock me-1 text-danger"></i>Time Slot</div>
                            <div id="blockedModalTime" class="fw-bold text-dark" style="font-size: 0.875rem;"></div>
                        </div>
                    </div>
                </div>

                <div class="card border mb-3">
                    <div class="card-body p-3">
                        <div class="mb-2">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Reserved By / Requester</div>
                            <div id="blockedModalRequester" class="fw-bold text-dark" style="font-size: 0.925rem;"></div>
                        </div>
                        <div class="pt-2 border-top">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Office / Team</div>
                            <div id="blockedModalOffice" class="text-dark fw-medium" style="font-size: 0.875rem;"></div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">Purpose / Activity</div>
                    <div id="blockedModalPurpose" class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 0.875rem; min-height: 50px;"></div>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-2.5 border-top justify-content-end">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Shared Footer -->
<footer class="footer py-4 mt-auto bg-white border-top">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-md-between gap-3 text-center text-md-end">
            
            <!-- Picture -->
            <div class="footer-logo">
                <img src="<?= APP_URL ?>/assets/images/diwa_logo_landscape.png"
                     alt="DIWA Center Logo"
                     class="img-fluid"
                     style="max-height: 48px; width: auto;">
            </div>

            <!-- Text -->
            <div class="footer-text">
                <p style="color: #32444e;" class="mb-1 fw-semibold">
                    &copy; <?= date('Y') ?> DIWA Center Conference Services. All rights reserved.
                </p>
                <span class="small" style="color: #32444e70;">
                    Made by <strong style="color: #f87171;">Maylotechy</strong> and <strong style="color: #f87171;">Centuriee</strong>
                </span>
            </div>

        </div>
    </div>
</footer>

<!-- jQuery 3.7.1 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Driver.js Guided Tour JS -->
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
<!-- Custom Client JS -->
<script src="<?= APP_URL ?>/assets/js/app.js?v=<?= filemtime(__DIR__ . '/../assets/js/app.js') ?>"></script>
</body>
</html>
