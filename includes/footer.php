<?php
/**
 * Shared Footer Template for Public Interface
 */
?>

<!-- Shared Footer -->
<footer class="footer py-4 mt-auto" style="background-color: #ffffff; color: #f8fafc;">
    <div class="container">
        <div class="d-flex align-items-center justify-content-evenly gap-3">
            
            <!-- Picture -->
            <img src="<?= APP_URL ?>/assets/images/diwa_logo_landscape.png"
                 alt="DIWA Center Logo"
                 style="height: 50px; width: auto;">

            <!-- Text -->
            <div class="text-end">
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
<!-- Custom Client JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
