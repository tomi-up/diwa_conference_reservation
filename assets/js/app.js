/**
 * Client-Side JavaScript for Conference Room Reservation System (jQuery & AJAX Edition)
 */

$(document).ready(function () {

    // -1. Show any server-side flash message (login/logout notices, action confirmations,
    // sitewide errors set via set_flash_message()) as a SweetAlert2 popup
    if (window.globalFlashMessage) {
        const flashIconMap = { success: 'success', danger: 'error', warning: 'warning', info: 'info' };
        const flashTitleMap = { success: 'Success', danger: 'Error', warning: 'Notice', info: 'Notice' };
        const flashType = window.globalFlashMessage.type;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: flashIconMap[flashType] || 'info',
                title: flashTitleMap[flashType] || 'Notice',
                text: window.globalFlashMessage.message,
                confirmButtonColor: '#951a1d',
                confirmButtonText: 'Got it',
                customClass: { confirmButton: 'btn btn-primary px-4' },
                buttonsStyling: false
            });
        } else {
            alert(window.globalFlashMessage.message);
        }
    }

    // 0. Show server-side reservation form validation errors as a SweetAlert2 popup
    if (window.reservationFormErrors && window.reservationFormErrors.length) {
        const errorListHtml = '<ul class="text-start ps-3 mb-0">' +
            window.reservationFormErrors.map(err => `<li>${escapeHtml(err)}</li>`).join('') +
            '</ul>';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Please correct the following issues',
                html: errorListHtml,
                confirmButtonColor: '#951a1d',
                confirmButtonText: 'Got it',
                customClass: { confirmButton: 'btn btn-primary px-4' },
                buttonsStyling: false
            });
        } else {
            alert(window.reservationFormErrors.join('\n'));
        }
    }

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
                            const isOccupied = slot.status === 'OCCUPIED';
                            const isPast = slot.status === 'PAST';
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
                                            <span class="badge bg-danger mt-1">BLOCKED</span>
                                        </div>
                                    </div>
                                `;
                            } else if (isPast) {
                                html += `
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <div style="height: 80px; cursor: not-allowed;"
                                             class="p-2 border rounded text-center bg-light text-muted transition-all d-flex flex-column align-items-center justify-content-center"
                                             title="${slot.label} has already passed today">
                                            <div class="fw-bold small text-muted">${slot.label}</div>
                                            <span class="badge bg-secondary mt-1">PAST</span>
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

    // Clear a field's invalid state as soon as the user starts correcting it
    $(document).on('input change', '#reservationForm .is-invalid', function () {
        $(this).removeClass('is-invalid');
    });

    // Shared SweetAlert2 popup for reservation submission failures (validation, conflict, rate limit, etc.)
    function showSubmitFailureAlert(title, messages, icon) {
        const listHtml = '<ul class="text-start ps-3 mb-0">' +
            messages.map(m => `<li>${escapeHtml(m)}</li>`).join('') +
            '</ul>';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: icon || 'warning',
                title: title,
                html: listHtml,
                confirmButtonColor: '#951a1d',
                confirmButtonText: 'Got it',
                customClass: { confirmButton: 'btn btn-primary px-4' },
                buttonsStyling: false
            });
        } else {
            alert(messages.join('\n'));
        }
    }

    // 3. jQuery / AJAX Form Submission (No Page Reload!)
    const $reservationForm = $('#reservationForm');
    if ($reservationForm.length) {
        $reservationForm.on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const originalBtnText = $btn.html();

            // Reset previous field-level validation state
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').text('');

            let $firstInvalid = null;
            function invalid($field, message) {
                if (!$field || !$field.length) return;

                $field.addClass('is-invalid');

                const $feedback = $field
                    .closest('.mb-3, .mb-4, .col-md-4, .col-md-6, .form-check')
                    .find('.invalid-feedback')
                    .first();

                if ($feedback.length) {
                    $feedback.text(message);
                }

                if (!$firstInvalid) {
                    $firstInvalid = $field;
                }
            }

            // Client-side quick field checks
            const $name = $('#requester_name');
            const $email = $('#requester_email');
            const $officeSelect = $('#project_team_office');
            const $officeOther = $('#project_team_office_other');
            const $purpose = $('#purpose');
            const $date = $('#reservation_date');
            const $startTime = $('#start_time');
            const $endTime = $('#end_time');
            const $terms = $('#terms_accepted');

            const name = $name.val() ? $name.val().trim() : '';
            const email = $email.val() ? $email.val().trim() : '';
            const officeValue = $officeSelect.val() ? $officeSelect.val().trim() : '';
            const officeOther = $officeOther.val() ? $officeOther.val().trim() : '';
            const purpose = $purpose.val() ? $purpose.val().trim() : '';
            const date = $date.val();
            const startTime = $startTime.val();
            const endTime = $endTime.val();
            const termsAccepted = $terms.is(':checked');

            if (!name) invalid($name, 'Name of Requesting Personnel is required.');

            if (!email) {
                invalid($email, 'Email Address is required.');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                invalid($email, 'Please enter a valid email address.');
            }

            if (!officeValue) {
                invalid($officeSelect, 'Project / Team / Office is required.');
            } else if (officeValue === 'Others' && !officeOther) {
                $('#project_team_office_other_wrapper').show();
                $officeOther.prop('required', true);

                invalid(
                    $officeOther,
                    'Please specify your Project / Team / Office.'
                );
            }

            if (!purpose) invalid($purpose, 'Purpose of Meeting / Activity is required.');

            if (!date) {
                invalid($date, 'Reservation Date is required.');
            } else {
                const maxAdvanceDate = new Date();
                maxAdvanceDate.setDate(maxAdvanceDate.getDate() + 30);
                const selectedDate = new Date(date);
                if (selectedDate > maxAdvanceDate) {
                    invalid($date, 'Reservations can only be booked up to 30 days in advance.');
                }
            }

            if (!startTime) invalid($startTime, 'Start Time is required.');
            if (!endTime) invalid($endTime, 'End Time is required.');

            if (startTime && endTime) {
                if (startTime < '07:00' || endTime > '18:00') {
                    invalid($startTime, 'Reservation hours are strictly between 7:00 AM and 6:00 PM.');
                } else if (startTime >= endTime) {
                    invalid($endTime, 'End time must be later than start time.');
                } else {
                    const startMin = parseInt(startTime.split(':')[0]) * 60 + parseInt(startTime.split(':')[1]);
                    const endMin = parseInt(endTime.split(':')[0]) * 60 + parseInt(endTime.split(':')[1]);
                    if (endMin - startMin > 240) {
                        invalid($endTime, 'Single reservation sessions cannot exceed 4 hours.');
                    }
                }
            }

            // Same-day past-time guard - mirrors the server-side rule in create_reservation()
            if (date && startTime && !$startTime.hasClass('is-invalid')) {
                const now = new Date();
                const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
                if (date === todayStr) {
                    const nowHHMM = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                    if (startTime <= nowHHMM) {
                        invalid($startTime, 'This time has already passed today. Please select a later time.');
                    }
                }
            }

            if (!termsAccepted) {
                invalid($terms, 'You must accept the Terms & Conditions and Responsible Use Policy.');
            }

            if ($firstInvalid && $firstInvalid.length) {
                const offset = $firstInvalid.offset();

                if (offset && typeof offset.top === 'number') {
                    $('html, body').animate({
                        scrollTop: offset.top - 140
                    }, 300);
                }

                $firstInvalid.trigger('focus');
                return;
            }

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
                        // Show the failure as a SweetAlert2 popup instead of an inline banner
                        const errList = (res.errors && res.errors.length) ? res.errors : [res.message || 'Failed to submit reservation request.'];
                        $btn.prop('disabled', false).html(originalBtnText);
                        showSubmitFailureAlert('Submission Failed', errList);
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalBtnText);
                    let errList = ['An unexpected error occurred while processing your request. Please try again.'];
                    if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.length) {
                        errList = xhr.responseJSON.errors;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errList = [xhr.responseJSON.message];
                    }

                    showSubmitFailureAlert('Submission Warning / Conflict', errList);
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
                        $results.html('');
                        showSubmitFailureAlert('Unable to Load Schedule', [data.message || 'An error occurred while fetching availability.'], 'error');
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
                        const isPast = slot.status === 'PAST';
                        if (isAvailable) {
                            html += `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="p-3 border rounded text-center slot-available">
                                        <div class="fw-bold mt-1">${slot.label}</div>
                                        <div class="small text-uppercase mt-1">AVAILABLE</div>
                                    </div>
                                </div>
                            `;
                        } else if (isPast) {
                            html += `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="p-3 border rounded text-center bg-light text-muted" title="${slot.label} has already passed today">
                                        <div class="fw-bold mt-1">${slot.label}</div>
                                        <div class="small text-uppercase mt-1">PAST</div>
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
                    $results.html('');
                    showSubmitFailureAlert('Unable to Load Schedule', ['An error occurred while fetching availability. Please try again.'], 'error');
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

// Global SweetAlert2 AJAX Cancel Confirmation Trigger (Self-Service - My Reservations page)
window.confirmCancelMyReservation = function(buttonElement) {
    const form = buttonElement.closest('form');
    const resIdInput = form ? form.querySelector('input[name="reservation_id"]') : null;
    const csrfInput  = form ? form.querySelector('input[name="csrf_token"]') : null;
    const reservationId = resIdInput ? resIdInput.value : '';
    const csrfToken = csrfInput ? csrfInput.value : '';

    const doCancel = function () {
        $.ajax({
            url: 'api/cancel_my_reservation.php',
            type: 'POST',
            data: {
                reservation_id: reservationId,
                csrf_token: csrfToken
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled',
                            text: res.message,
                            confirmButtonColor: '#951a1d',
                            customClass: { confirmButton: 'btn btn-primary px-4' },
                            buttonsStyling: false
                        }).then(() => window.location.reload());
                    } else {
                        window.location.reload();
                    }
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', res.message || 'Failed to cancel reservation.', 'error');
                } else {
                    alert(res.message || 'Failed to cancel reservation.');
                }
            },
            error: function (xhr) {
                const errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while processing cancellation.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', errMsg, 'error');
                } else {
                    alert(errMsg);
                }
            }
        });
    };

    if (typeof Swal === 'undefined') {
        if (confirm('Cancel this reservation? This will free up the time slot for other users and cannot be undone.')) {
            doCancel();
        }
        return;
    }

    Swal.fire({
        title: 'Cancel Reservation?',
        text: 'This will free up the time slot for other users. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#951a1d',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="bi bi-x-circle me-1"></i> Yes, Cancel Reservation',
        cancelButtonText: 'Keep Reservation',
        customClass: {
            confirmButton: 'btn btn-danger px-4 me-2',
            cancelButton: 'btn btn-secondary px-4'
        },
        buttonsStyling: false
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Cancelling Reservation...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => { Swal.showLoading(); }
        });

        doCancel();
    });
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
                element: '#terms_accepted',
                popover: {
                    title: 'Step 5: Accept Responsible Use Policy',
                    description: 'Read and agree to the <strong>Responsible Use Policy & Terms of Service</strong>. The system automatically remembers your acceptance so you only need to accept it once on your first visit!',
                    side: 'top',
                    align: 'start'
                }
            },
            {
                element: '#reservationForm button[type="submit"]',
                popover: {
                    title: 'Step 6: Submit Request',
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
    if (window.location.pathname.includes('reserve')) {
        window.startBookingTutorial();
    } else {
        // Tutorial steps target elements that only exist on the Reserve page
        // (schedule grid, form fields) - go there first, then auto-run it.
        sessionStorage.setItem('diwa_pending_tutorial', 'true');
        window.location.href = 'reserve';
    }
});

// Auto-suggest tutorial for first-time visitors on Reserve page, or when the
// user asked for it via the "How to Book?" button on another page.
if (window.location.pathname.includes('reserve')) {
    const cameFromTutorialLink = sessionStorage.getItem('diwa_pending_tutorial');
    if (cameFromTutorialLink) sessionStorage.removeItem('diwa_pending_tutorial');

    if (cameFromTutorialLink || !localStorage.getItem('diwa_tutorial_seen')) {
        setTimeout(function() {
            window.startBookingTutorial();
            localStorage.setItem('diwa_tutorial_seen', 'true');
        }, 1000);
    }
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
