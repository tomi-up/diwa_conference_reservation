/**
 * Client-Side JavaScript for Conference Room Reservation System (jQuery & AJAX Edition)
 */

$(document).ready(function () {

    // 1. Enforce minimum date = today on reservation form date input
    const $dateInput = $('#reservation_date');
    if ($dateInput.length) {
        const today = new Date().toISOString().split('T')[0];
        $dateInput.attr('min', today);
    }

    // 2. Real-time Live Availability Badge Check & Schedule Matrix Grid
    function loadScheduleGridAndCheck(sourceId) {
        let date;
        if (sourceId === 'checker_date_input') {
            date = $('#checker_date_input').val();
            if (date && $('#reservation_date').length) {
                $('#reservation_date').val(date);
            }
        } else if (sourceId === 'reservation_date') {
            date = $('#reservation_date').val();
            if (date && $('#checker_date_input').length) {
                $('#checker_date_input').val(date);
            }
        } else {
            date = $('#checker_date_input').val() || $('#reservation_date').val();
        }

        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();
        const $badge = $('#liveAvailabilityBadge');
        const $grid = $('#reservationScheduleGrid');

        if (!date) return;

        // Ensure both date inputs are synced
        if ($('#checker_date_input').length) $('#checker_date_input').val(date);
        if ($('#reservation_date').length) $('#reservation_date').val(date);

        // Fetch daily schedule matrix for date
        if ($grid.length) {
            $.ajax({
                url: 'api/check_availability.php',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function (data) {
                    if (data.success && data.slots) {
                        const availableCount = data.slots.filter(s => s.status === 'AVAILABLE').length;
                        let html = `
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2">
                                <span class="fw-bold text-dark"><i class="bi bi-calendar-event text-primary me-1.5"></i> Schedule for <u class="text-primary">${data.formatted_date}</u></span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">${availableCount} Open Slots</span>
                            </div>
                            <div class="row g-2">
                        `;

                        data.slots.forEach(slot => {
                            console.log(slot);
                            const isOccupied = slot.status === 'OCCUPIED';
                            if (isOccupied) {
                                const reqName = slot.requester_name || 'Official Booking';
                                const office = slot.project_team_office || 'N/A';
                                const purpose = slot.purpose || 'Official Activity';
                                const timeFmt = (slot.occ_start_time && slot.occ_end_time) ? `${slot.occ_start_time} – ${slot.occ_end_time}` : slot.label;

                                html += `
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <div style="height: 80px; cursor: pointer;" 
                                             class="p-2 border rounded text-center bg-danger-subtle text-danger border-danger-subtle slot-blocked-chip transition-all d-flex flex-column align-items-center justify-content-center"
                                             data-date="${escapeHtml(data.formatted_date)}"
                                             data-time="${escapeHtml(timeFmt)}"
                                             data-requester="${escapeHtml(reqName)}"
                                             data-office="${escapeHtml(office)}"
                                             data-purpose="${escapeHtml(purpose)}"
                                             title="Click to view reservation details for ${slot.label}">
                                            <div class="fw-bold small text-dark">${slot.label}</div>
                                            <span class="badge bg-danger mt-1"><i class="bi bi-info-circle me-1"></i>BLOCKED</span>
                                        </div>
                                    </div>
                                `;
                            } else {
                                const isSelected = (startTime === slot.start_time && endTime === slot.end_time);
                                const selectedClass = isSelected ? 'border-2 border-success bg-success-subtle' : 'bg-success-subtle border-success-subtle';
                                html += `
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <div style="height: 80px; cursor: pointer;" class="p-2 border rounded text-center ${selectedClass} text-success slot-picker-chip transition-all d-flex flex-column align-items-center justify-content-center" 
                                             data-start="${slot.start_time}" data-end="${slot.end_time}" style="cursor: pointer;" title="Click to pick ${slot.label}">
                                            <div class="fw-bold small text-dark">${slot.label}</div>
                                            <span class="badge bg-success mt-1">AVAILABLE</span>
                                        </div>
                                    </div>
                                `;
                            }
                        });
                        html += '</div>';
                        $grid.html(html);
                    }
                }
            });
        }

        // Live badge check for selected start_time & end_time
        if (startTime && endTime && $badge.length) {
            $.ajax({
                url: 'api/check_availability.php',
                type: 'GET',
                data: { date: date, start_time: startTime, end_time: endTime },
                dataType: 'json',
                success: function (data) {
                    if (data.success && data.slot_check) {
                        $badge.html(data.slot_check.badge_html);
                    } else {
                        $badge.html('');
                    }
                },
                error: function () {
                    $badge.html('');
                }
            });
        }
    }

    // Helper to safely escape HTML attributes
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Click handler for Blocked / Occupied Slot Chips to display Reservation Pop-up Modal
    $(document).on('click', '.slot-blocked-chip', function(e) {
        e.preventDefault();
        const $chip = $(this).closest('.slot-blocked-chip');
        const date = $chip.attr('data-date') || '';
        const time = $chip.attr('data-time') || '';
        const requester = $chip.attr('data-requester') || 'Official Booking';
        const office = $chip.attr('data-office') || 'N/A';
        const purpose = $chip.attr('data-purpose') || 'Official Activity';

        const modalEl = document.getElementById('blockedSlotDetailModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            $('#blockedModalDate').text(date);
            $('#blockedModalTime').text(time);
            $('#blockedModalRequester').text(requester);
            $('#blockedModalOffice').text(office);
            $('#blockedModalPurpose').text(purpose);
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Time Slot Reserved',
                html: `
                    <div class="text-start p-2" style="font-size: 0.9rem; color: #1e293b;">
                        <div class="p-2.5 mb-3 rounded bg-light border">
                            <div class="row g-2">
                                <div class="col-6">
                                    <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Date</span>
                                    <strong class="text-dark">${escapeHtml(date)}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Time Slot</span>
                                    <strong class="text-dark">${escapeHtml(time)}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Requester</span>
                            <span class="fw-bold text-dark">${escapeHtml(requester)}</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Office / Team</span>
                            <span class="fw-medium text-dark">${escapeHtml(office)}</span>
                        </div>
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.68rem;">Purpose / Activity</span>
                            <div class="p-2 mt-1 bg-light rounded border text-dark">${escapeHtml(purpose)}</div>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonColor: '#951a1d',
                confirmButtonText: 'Close',
                customClass: { confirmButton: 'btn btn-primary px-4' },
                buttonsStyling: false
            });
        }
    });

    // Global SweetAlert2 Logout Confirmation Trigger
    $(document).on('click', 'a[href*="logout"], .btn-logout-confirm', function(e) {
        e.preventDefault();
        const logoutUrl = $(this).attr('href');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Log Out?',
                text: 'Are you sure you want to log out of your active session?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#951a1d',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Yes, Log Out',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-danger px-4 me-2',
                    cancelButton: 'btn btn-secondary px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutUrl;
                }
            });
        } else {
            if (confirm('Are you sure you want to log out of your active session?')) {
                window.location.href = logoutUrl;
            }
        }
    });

    // Click handler for Available Slot Picker Chips
    $(document).on('click', '.slot-picker-chip', function() {
        const start = $(this).data('start');
        const end = $(this).data('end');
        if (start && end) {
            $('#start_time').val(start);
            $('#end_time').val(end);
            $('.slot-picker-chip').removeClass('border-2 border-success bg-primary-subtle').addClass('bg-success-subtle border-success-subtle');
            $(this).removeClass('bg-success-subtle border-success-subtle').addClass('border-2 border-success bg-success-subtle');
            loadScheduleGridAndCheck();
        }
    });

    // Toggle "Others" text fill-up field when "Others" option is selected in dropdown
    $(document).on('change', '#project_team_office', function () {
        if ($(this).val() === 'Others') {
            $('#project_team_office_other_wrapper').slideDown(200);
            $('#project_team_office_other').prop('required', true).focus();
        } else {
            $('#project_team_office_other_wrapper').slideUp(200);
            $('#project_team_office_other').prop('required', false).val('');
        }
    });

    // Event listeners for date and time inputs to update calendar grid dynamically
    $(document).on('change input blur', '#checker_date_input', function () {
        loadScheduleGridAndCheck('checker_date_input');
    });

    $(document).on('change input blur', '#reservation_date', function () {
        loadScheduleGridAndCheck('reservation_date');
    });

    $(document).on('change input', '#start_time, #end_time', function () {
        loadScheduleGridAndCheck();
    });

    if ($('#reservation_date').val() || $('#checker_date_input').val()) {
        loadScheduleGridAndCheck();
    }

    // 3. jQuery / AJAX Form Submission (No Page Reload!)
    const $reservationForm = $('#reservationForm');
    if ($reservationForm.length) {
        $reservationForm.on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const $alertContainer = $('#formAlertContainer');
            const originalBtnText = $btn.html();

            // Client-side quick field checks
            const name = $('#requester_name').val() ? $('#requester_name').val().trim() : '';
            const email = $('#requester_email').val() ? $('#requester_email').val().trim() : '';
            let office = $('#project_team_office').val() ? $('#project_team_office').val().trim() : '';
            if (office === 'Others') {
                office = $('#project_team_office_other').val() ? $('#project_team_office_other').val().trim() : '';
            }
            const purpose = $('#purpose').val() ? $('#purpose').val().trim() : '';
            const date = $('#reservation_date').val();
            const startTime = $('#start_time').val();
            const endTime = $('#end_time').val();

            const termsAccepted = $('#terms_accepted').is(':checked');

            const errors = [];
            if (!name || !email || !office || !purpose || !date || !startTime || !endTime) {
                errors.push('All required fields must be completed.');
            }
            if (!termsAccepted) {
                errors.push('You must accept the Terms & Conditions and Responsible Use Policy before submitting.');
            }
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.push('Please enter a valid email address.');
            }
            if (date) {
                const maxAdvanceDate = new Date();
                maxAdvanceDate.setDate(maxAdvanceDate.getDate() + 30);
                const selectedDate = new Date(date);
                if (selectedDate > maxAdvanceDate) {
                    errors.push('Advance Booking Limit: Reservations can only be booked up to 30 days in advance.');
                }
            }
            if (startTime && endTime) {
                if (startTime >= endTime) {
                    errors.push('End time must be later than start time.');
                } else {
                    const startMin = parseInt(startTime.split(':')[0]) * 60 + parseInt(startTime.split(':')[1]);
                    const endMin = parseInt(endTime.split(':')[0]) * 60 + parseInt(endTime.split(':')[1]);
                    if (endMin - startMin > 240) {
                        errors.push('Duration Cap Exceeded: Single reservation sessions cannot exceed 4 hours.');
                    }
                }
            }
            if (startTime < '07:00' || endTime > '18:00') {
                errors.push('Reservation hours are strictly between 7:00 AM and 6:00 PM.');
            }

            if (errors.length > 0) {
                $alertContainer.html(`
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                        <strong>Please correct the following errors:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            ${errors.map(err => `<li>${err}</li>`).join('')}
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                $('html, body').animate({ scrollTop: $alertContainer.offset().top - 100 }, 300);
                return;
            }

            // Clear previous alerts
            $alertContainer.html('');

            // UI Loading state on Submit Button
            $btn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Submitting Reservation...
            `);

            // Perform jQuery AJAX POST Request
            $.ajax({
                url: 'api/submit_reservation.php',
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        // Render smooth inline Success Card without reloading page!
                        const d = res.details;
                        const successCardHtml = `
                            <div class="row">
                                <div class="col-lg-1 col-xl-1"></div>
                                <div class="col-lg-10 col-xl-10">
                                    <div class="border shadow-sm bg-white mb-5 overflow-hidden">
                                        <div class="card-header bg-success text-white py-3 text-center">
                                            <h4 class="mb-0 fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Reservation Confirmed!</h4>
                                        </div>
                                        <div class="card-body p-4 p-md-5">
                                            <div class="alert alert-success border-0 bg-success-subtle text-success-emphasis p-3 mb-4 rounded-3 d-flex align-items-center">
                                                <i class="bi bi-check-circle-fill fs-3 me-3 text-success"></i>
                                                <div>
                                                    <strong>Reservation Request Submitted!</strong><br>
                                                    Your reservation details have been successfully recorded for <u>${d.requester_email}</u>.
                                                </div>
                                            </div>

                                            <!-- DIWA Styled Callout Box -->
                                            <div style="background-color: #fdf2f2; border: 1px solid #fecaca; border-left: 5px solid #951a1d; padding: 20px 24px; border-radius: 6px;" class="mb-4">
                                                <h5 style="color: #951a1d;" class="fw-bold mb-3 border-bottom pb-2">Reservation Details</h5>
                                                <div class="row g-2 font-size-14">
                                                    <div class="col-sm-4 text-secondary fw-semibold">Reservation ID:</div>
                                                    <div class="col-sm-8 text-end fw-bold text-danger font-monospace fs-6">${d.formatted_id}</div>
                                                    
                                                    <div class="col-sm-4 text-secondary fw-semibold">Requesting Personnel:</div>
                                                    <div class="col-sm-8 text-end text-dark fw-bold">${d.requester_name}</div>

                                                    <div class="col-sm-4 text-secondary fw-semibold">Facility / Room:</div>
                                                    <div class="col-sm-8 text-end text-dark">${d.room_name}</div>

                                                    <div class="col-sm-4 text-secondary fw-semibold">Date:</div>
                                                    <div class="col-sm-8 text-end text-dark">${d.reservation_date}</div>

                                                    <div class="col-sm-4 text-secondary fw-semibold">Time:</div>
                                                    <div class="col-sm-8 text-end text-dark">${d.start_time} &ndash; ${d.end_time}</div>

                                                    <div class="col-sm-4 text-secondary fw-semibold">Project / Office:</div>
                                                    <div class="col-sm-8 text-end text-dark">${d.project_team_office}</div>

                                                    <div class="col-sm-4 text-secondary fw-semibold">Purpose:</div>
                                                    <div class="col-sm-8 text-end text-dark">${d.purpose}</div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-center gap-3 flex-wrap">
                                                <button type="button" id="btnBookAnother" class="btn btn-primary px-4 py-2">
                                                    <i class="bi bi-plus-circle me-1"></i> Make Another Reservation
                                                </button>
                                                <a href="availability.php?date=${d.raw_date}" class="btn btn-outline-secondary px-4 py-2">
                                                    <i class="bi bi-calendar-week me-1"></i> View Schedule Matrix
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-1 col-xl-1"></div>
                            </div>
                        `;

                        $('#reservationCardWrapper').html(successCardHtml);
                        $('html, body').animate({ scrollTop: $('#reservationCardWrapper').offset().top - 80 }, 300);

                        // Attach event to "Make Another Reservation" button
                        $(document).on('click', '#btnBookAnother', function() {
                            window.location.reload();
                        });

                    } else {
                        // Display error message inline without reloading
                        const errList = res.errors ? res.errors.map(e => `<li>${e}</li>`).join('') : `<li>${res.message}</li>`;
                        $alertContainer.html(`
                            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                                <strong>Submission Failed:</strong>
                                <ul class="mb-0 mt-2 ps-3">${errList}</ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                        $btn.prop('disabled', false).html(originalBtnText);
                        $('html, body').animate({ scrollTop: $alertContainer.offset().top - 100 }, 300);
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalBtnText);
                    let errMessage = 'An unexpected error occurred while processing your request. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMessage = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errMessage = xhr.responseJSON.errors.join('<br>');
                    }

                    $alertContainer.html(`
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i> Submission Warning / Conflict:</strong>
                            <div class="mt-1">${errMessage}</div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $('html, body').animate({ scrollTop: $alertContainer.offset().top - 100 }, 300);
                }
            });
        });
    }

    // 4. Public Schedule Matrix jQuery AJAX Checker (availability.php)
    const $availForm = $('#availabilityCheckForm');
    if ($availForm.length) {
        $availForm.on('submit', function (e) {
            e.preventDefault();

            const date = $('#avail_date').val();
            const $results = $('#availabilityResults');

            if (!date) {
                alert('Please select a date.');
                return;
            }

            $results.html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading availability...</span>
                    </div>
                    <p class="mt-2 text-muted small">Checking schedule availability...</p>
                </div>
            `);

            $.ajax({
                url: 'api/check_availability.php',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function (data) {
                    if (!data.success) {
                        $results.html(`<div class="alert alert-danger">${data.message}</div>`);
                        return;
                    }

                    let html = `
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0 text-dark fw-bold">${data.room_name}</h5>
                                <span class="badge bg-secondary fs-6 fw-normal">${data.formatted_date}</span>
                            </div>
                            <div class="card-body p-4">
                                <h6 class="card-subtitle mb-3 text-muted fw-semibold">Hourly Schedule Matrix (7:00 AM – 6:00 PM)</h6>
                                <div class="row g-2">
                    `;

                    data.slots.forEach(slot => {
                        const isAvailable = slot.status === 'AVAILABLE';
                        if (isAvailable) {
                            html += `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="p-3 border rounded text-center slot-available">
                                        <div class="fw-bold mt-1">${slot.label}</div>
                                        <div class="small text-uppercase mt-1">AVAILABLE</div>
                                    </div>
                                </div>
                            `;
                        } else {
                            const reqName = slot.requester_name || 'Official Booking';
                            const office = slot.project_team_office || 'N/A';
                            const purpose = slot.purpose || 'Official Activity';
                            const timeFmt = (slot.occ_start_time && slot.occ_end_time) ? `${slot.occ_start_time} – ${slot.occ_end_time}` : slot.label;

                            html += `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div style="cursor: pointer;" class="p-3 border rounded text-center slot-occupied slot-blocked-chip"
                                         data-date="${escapeHtml(data.formatted_date)}"
                                         data-time="${escapeHtml(timeFmt)}"
                                         data-requester="${escapeHtml(reqName)}"
                                         data-office="${escapeHtml(office)}"
                                         data-purpose="${escapeHtml(purpose)}"
                                         title="Click to view reservation details for ${slot.label}">
                                        <div class="fw-bold mt-1">${slot.label}</div>
                                        <div class="small text-uppercase mt-1"><i class="bi bi-info-circle me-1"></i>OCCUPIED</div>
                                    </div>
                                </div>
                            `;
                        }
                    });

                    html += `
                                </div>
                            </div>
                            <div class="card-footer bg-light text-end">
                                <a href="reserve?date=${date}" class="btn btn-primary fw-semibold">
                                    Reserve This Date
                                </a>
                            </div>
                        </div>
                    `;

                    $results.html(html);
                },
                error: function () {
                    $results.html(`<div class="alert alert-danger">An error occurred while fetching availability. Please try again.</div>`);
                }
            });
        });
    }

// Global SweetAlert2 AJAX Cancel Confirmation Trigger
window.confirmCancelReservation = function(buttonElement) {
    const form = buttonElement.closest('form');
    const resIdInput = form ? form.querySelector('input[name="reservation_id"]') : null;
    const csrfInput  = form ? form.querySelector('input[name="csrf_token"]') : null;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Cancel Reservation?',
            text: 'Please state the reason for cancelling this reservation.',
            input: 'textarea',
            inputPlaceholder: 'Type reason for cancellation here...',
            inputAttributes: {
                'aria-label': 'Reason for cancellation',
                'rows': '3'
            },
            showCancelButton: true,
            confirmButtonColor: '#951a1d',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-x-circle me-1"></i> Yes, Cancel Reservation',
            cancelButtonText: 'Keep Reservation',
            customClass: {
                confirmButton: 'btn btn-danger px-4 me-2',
                cancelButton: 'btn btn-secondary px-4'
            },
            buttonsStyling: false,
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'You must provide a reason for cancellation!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value.trim();
                const reservationId = resIdInput ? resIdInput.value : '';
                const csrfToken = csrfInput ? csrfInput.value : '';

                // Display loading spinner inside SweetAlert2 dialog while processing
                Swal.fire({
                    title: 'Cancelling Reservation...',
                    text: 'Updating status and processing cancellation...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX POST request to api/cancel_reservation
                $.ajax({
                    url: '../api/cancel_reservation',
                    type: 'POST',
                    data: {
                        reservation_id: reservationId,
                        cancellation_reason: reason,
                        csrf_token: csrfToken
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: res.message,
                                confirmButtonColor: '#951a1d',
                                customClass: { confirmButton: 'btn btn-primary px-4' },
                                buttonsStyling: false
                            }).then(() => {
                                // Refresh calendar or page smoothly without full browser reload
                                if (typeof globalCalendar !== 'undefined' && globalCalendar) {
                                    globalCalendar.refetchEvents();
                                    const modalEl = document.getElementById('eventDetailModal');
                                    if (modalEl && typeof bootstrap !== 'undefined') {
                                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                                        if (bsModal) bsModal.hide();
                                    }
                                } else {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Failed to cancel reservation.', 'error');
                        }
                    },
                    error: function (xhr) {
                        const errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while processing cancellation.';
                        Swal.fire('Error', errMsg, 'error');
                    }
                });
            }
        });
    } else {
        const reason = prompt('Please state the reason for cancelling this reservation:');
        if (reason && reason.trim()) {
            const reasonInput = form.querySelector('.cancellation-reason-input');
            if (reasonInput) {
                reasonInput.value = reason.trim();
            }
            form.submit();
        }
    }
};

// Global Vanilla JS & jQuery Pop-up Modal View Details Handler
window.openReservationViewModal = function(btn) {
    const id = btn.getAttribute('data-id');
    const code = btn.getAttribute('data-code');
    const name = btn.getAttribute('data-name');
    const email = btn.getAttribute('data-email');
    const office = btn.getAttribute('data-office');
    const purpose = btn.getAttribute('data-purpose');
    const date = btn.getAttribute('data-date');
    const time = btn.getAttribute('data-time');
    const status = btn.getAttribute('data-status');
    const reason = btn.getAttribute('data-reason');

    const resCodeEl = document.getElementById('modalResCode');
    if (resCodeEl) resCodeEl.textContent = code;

    const nameEl = document.getElementById('modalRequesterName');
    if (nameEl) nameEl.textContent = name;

    const emailEl = document.getElementById('modalRequesterEmail');
    if (emailEl) emailEl.textContent = email;

    const officeEl = document.getElementById('modalOfficeTeam');
    if (officeEl) officeEl.textContent = office;

    const purposeEl = document.getElementById('modalPurpose');
    if (purposeEl) purposeEl.textContent = purpose;

    const dateEl = document.getElementById('modalDate');
    if (dateEl) dateEl.textContent = date;

    const timeEl = document.getElementById('modalTime');
    if (timeEl) timeEl.innerHTML = time;

    const resendIdEl = document.getElementById('modalResendId');
    if (resendIdEl) resendIdEl.value = id;

    let statusBadge = '';
    if (status === 'CONFIRMED') {
        statusBadge = '<span class="badge bg-success px-2.5 py-1 fw-semibold">Confirmed</span>';
    } else if (status === 'CANCELLED') {
        statusBadge = '<span class="badge bg-warning text-dark px-2.5 py-1 fw-semibold"><i class="bi bi-x-circle me-1"></i>Cancelled</span>';
    } else {
        statusBadge = '<span class="badge bg-danger px-2.5 py-1 fw-semibold">Rejected</span>';
    }
    const statusBadgeEl = document.getElementById('modalStatusBadge');
    if (statusBadgeEl) statusBadgeEl.innerHTML = statusBadge;

    const reasonWrapper = document.getElementById('modalReasonWrapper');
    const reasonText = document.getElementById('modalReasonText');
    if (reason && reason.trim()) {
        if (reasonText) reasonText.textContent = reason;
        if (reasonWrapper) reasonWrapper.style.display = 'block';
    } else {
        if (reasonWrapper) reasonWrapper.style.display = 'none';
    }

    const modalEl = document.getElementById('reservationDetailModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

// 5. Interactive Step-by-Step Guided Tutorial (Driver.js)
window.startBookingTutorial = function() {
    if (typeof window.driver === 'undefined' || typeof window.driver.js === 'undefined') {
        return;
    }

    const driverObj = window.driver.js.driver({
        showProgress: true,
        animate: true,
        allowClose: true,
        doneBtnText: 'Awesome! Got it',
        closeBtnText: 'Skip Tutorial',
        nextBtnText: 'Next →',
        prevBtnText: '← Back',
        steps: [
            {
                element: '#btnStartTutorial',
                popover: {
                    title: 'Welcome to Conference Reservation System',
                    description: 'Let\'s take a quick 30-second tour on how to check room availability and make a reservation.',
                    side: 'bottom',
                    align: 'center'
                }
            },
            {
                element: $('#tour_signin_callout').length ? '#tour_signin_callout' : '.navbar',
                popover: {
                    title: 'Step 1: UP Mail Authentication',
                    description: 'To reserve the conference room, sign in with your official <strong>@up.edu.ph</strong> account using the Google Sign-In button.',
                    side: 'bottom',
                    align: 'start'
                }
            },
            {
                element: '#checker_date_input',
                popover: {
                    title: 'Step 2: Select Date',
                    description: 'Use this date picker to select your target meeting date. The schedule matrix below updates live.',
                    side: 'bottom',
                    align: 'start'
                }
            },
            {
                element: '#reservationScheduleGrid',
                popover: {
                    title: 'Step 3: Interactive Time Slots',
                    description: 'Click any green <strong>AVAILABLE</strong> slot chip to automatically pick its start and end times for your form!',
                    side: 'top',
                    align: 'center'
                }
            },
            {
                element: '#project_team_office',
                popover: {
                    title: 'Step 4: Select Project / Team / Office',
                    description: 'Select your Project, Team, or Office from the drop-down menu and provide your meeting agenda details.',
                    side: 'top',
                    align: 'start'
                }
            },
            {
                element: '#reservationForm button[type="submit"]',
                popover: {
                    title: 'Step 5: Submit Request',
                    description: 'Click <strong>Submit Reservation Request</strong> to confirm your booking and receive an instant email notification!',
                    side: 'top',
                    align: 'center'
                }
            }
        ]
    });

    driverObj.drive();
};

$(document).on('click', '#btnStartTutorial', function (e) {
    e.preventDefault();
    window.startBookingTutorial();
});

// Auto-suggest tutorial for first-time visitors on Reserve page
if (window.location.pathname.includes('reserve') && !localStorage.getItem('diwa_tutorial_seen')) {
    setTimeout(function() {
        window.startBookingTutorial();
        localStorage.setItem('diwa_tutorial_seen', 'true');
    }, 1000);
}

// 6. Terms & Conditions Auto-Check & Persistence Handler
function syncTermsAcceptanceState() {
    const email = $('#requester_email').val() ? $('#requester_email').val().trim().toLowerCase() : '';
    const key = email ? 'diwa_terms_accepted_' + email : 'diwa_terms_accepted_global';
    if (localStorage.getItem(key) === 'true') {
        $('#terms_accepted').prop('checked', true);
    }
}

syncTermsAcceptanceState();

$(document).on('click', '#btnAgreeTerms', function () {
    const email = $('#requester_email').val() ? $('#requester_email').val().trim().toLowerCase() : '';
    const key = email ? 'diwa_terms_accepted_' + email : 'diwa_terms_accepted_global';
    
    localStorage.setItem(key, 'true');
    localStorage.setItem('diwa_terms_accepted_global', 'true');
    $('#terms_accepted').prop('checked', true);
});

});
