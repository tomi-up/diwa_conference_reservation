document.addEventListener('DOMContentLoaded', function () {

    const calendarTab = document.getElementById('calendarTab');
    const myReservationsTab = document.getElementById('myReservationsTab');

    const projectSelect = document.getElementById('project_team_office');
    const otherProjectContainer = document.getElementById('otherProjectContainer');
    const otherProjectInput = document.getElementById('otherProject');

    projectSelect.addEventListener('change', function () {
        if (this.value === 'Others') {
            otherProjectContainer.style.display = 'block';
            otherProjectInput.required = true;
        } else {
            otherProjectContainer.style.display = 'none';
            otherProjectInput.required = false;
            otherProjectInput.value = '';
        }
    });

    const calendarView = document.getElementById('calendarView');
    const myReservationsView = document.getElementById('myReservationsView');
    const reservationFilter = document.getElementById('reservationFilter');

    const urlParams = new URLSearchParams(window.location.search);
    const initialCalendarDate = urlParams.get('date');

    const projectColors = {
        'DiWA Core': '#d1393e',
        'ISC': '#d1393e',
        'Ops Team': '#4fa576',
        'RESCUE': '#da5b70',
        'IRDSS': '#cfb767',
        'Wolbachia': '#7b7ddb',
        'Scaling Up of Diwa App': '#d1393e',
        'RabDash DC': '#db7860',
        'MATALab': '#d167c3',
        'Others': '#8e6ad1'
    };

    function reservationDotColor(props) {
        return props.is_blocked
            ? '#444'
            : projectColors[props.project_team_office] ?? '#6c757d';
    }


    function showCalendar() {
        calendarView.classList.remove('d-none');
        calendarView.classList.add('d-flex');

        myReservationsView.classList.add('d-none');
        myReservationsView.classList.remove('d-flex');

        calendarTab.classList.add('active');
        myReservationsTab.classList.remove('active');

        reservationFilter.classList.add('d-none');
    }


    function showMyReservations() {
        calendarView.classList.add('d-none');
        calendarView.classList.remove('d-flex');

        myReservationsView.classList.remove('d-none');
        myReservationsView.classList.add('d-flex');

        calendarTab.classList.remove('active');
        myReservationsTab.classList.add('active');

        reservationFilter.classList.remove('d-none');
    }


    calendarTab.addEventListener('click', function () {
        showCalendar();
    });


    myReservationsTab.addEventListener('click', function () {
        showMyReservations();
    });

    const reservationFilterButtons =
    document.querySelectorAll('.reservation-filter-btn');

        reservationFilterButtons.forEach(function (button) {

            button.addEventListener('click', function () {

                const selectedFilter = this.dataset.filter;

                // Update active button
                reservationFilterButtons.forEach(function (btn) {
                    btn.classList.remove('active');
                });

                this.classList.add('active');

                // Filter reservations
                const reservationItems =
                    document.querySelectorAll('.reservation-item');

                reservationItems.forEach(function (item) {

                    const itemFilter = item.dataset.reservationFilter;
                    const isEmptyMessage = item.classList.contains('empty-filter-message');

                    if (isEmptyMessage) {
                        // empty messages only on specific filter
                        item.style.display =
                            selectedFilter === itemFilter ? '' : 'none';

                    } else {
                        item.style.display =
                            selectedFilter === 'all' ||
                            selectedFilter === itemFilter
                                ? ''
                                : 'none';
                    }

                });

            });

        });

    /** CALENDAR */
    const calendarDays = document.getElementById('calendarDays');
    const calendarMonth = document.getElementById('calendarMonth');
    const calendarYear = document.getElementById('calendarYear');

    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');

    let currentDate = initialCalendarDate
        ? new Date(initialCalendarDate + 'T00:00:00')
        : new Date();

    let selectedDate = initialCalendarDate
        ? new Date(initialCalendarDate + 'T00:00:00')
        : new Date();

    let reservations = [];

    const monthNames = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];

    /* Populate month dropdown */
    monthNames.forEach((month, index) => {
        const option = document.createElement('option');

        option.value = index;
        option.textContent = month;

        calendarMonth.appendChild(option);
    });

    /* Populate year dropdown */
    const currentYear = new Date().getFullYear();

    for (let year = currentYear - 5; year <= currentYear + 5; year++) {

        const option = document.createElement('option');

        option.value = year;
        option.textContent = year;

        calendarYear.appendChild(option);
    }

    async function loadReservations() {
        try {

            const response = await fetch('api/calendar_events');

            console.log('API status:', response.status);

            if (!response.ok) {
                throw new Error(
                    `API request failed: ${response.status} ${response.statusText}`
                );
            }

            reservations = await response.json();

            /** console.log('Reservations loaded:', reservations); */

            renderCalendar();
            showReservations(selectedDate);

        } catch (error) {

            console.error('Error loading reservations:', error);

            document.getElementById('selectedDateReservations').innerHTML = `
                <div class="text-danger">
                    Unable to load reservation data.
                </div>
            `;
        }
    }

    function renderCalendar() {

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        calendarMonth.value = month;
        calendarYear.value = year;

        calendarDays.innerHTML = '';

        /*
         * First day of current month
         *
         * 0 = Sunday
         * 1 = Monday
         * ...
         */
        const firstDay = new Date(year, month, 1).getDay();

        /* Number of days in current month */
        const daysInMonth = new Date(
            year,
            month + 1,
            0
        ).getDate();

        /*
         * Determine whether this month requires
         * 5 or 6 calendar rows.
         */
        const totalCells = firstDay + daysInMonth;

        const numberOfWeeks = Math.ceil(totalCells / 7);

        calendarDays.classList.toggle(
            'six-weeks',
            numberOfWeeks === 6
        );

        /*
         * Fill previous month's trailing days
         */
        const previousMonthDays = new Date(
            year,
            month,
            0
        ).getDate();

        for (let i = firstDay - 1; i >= 0; i--) {

            const day = previousMonthDays - i;

            const cell = createDayCell(
                day,
                'previous'
            );

            calendarDays.appendChild(cell);
        }


        /*
         * Current month
         */
        for (let day = 1; day <= daysInMonth; day++) {

            const cell = createDayCell(
                day,
                'current'
            );

            calendarDays.appendChild(cell);
        }


        /*
         * Fill remaining cells with next month
         */
        const remainingCells =
            (numberOfWeeks * 7) - totalCells;

        for (let day = 1; day <= remainingCells; day++) {

            const cell = createDayCell(
                day,
                'next'
            );

            calendarDays.appendChild(cell);
        }
    }


    function createDayCell(day, type) {

        const cell = document.createElement('button');

        cell.type = 'button';
        cell.classList.add('calendar-day');


        /*
        * Days belonging to adjacent months
        */
        if (type !== 'current') {
            cell.classList.add('other-month');

            cell.textContent = day;

            return cell;
        }


        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        const cellDate = new Date(
            year,
            month,
            day
        );


        /*
        * Check if this date has reservations
        */
        const dateString =
            year + '-' +
            String(month + 1).padStart(2, '0') + '-' +
            String(day).padStart(2, '0');

        const dayReservations = reservations.filter(reservation =>
            reservation.start.startsWith(dateString)
        );


        /*
        * Day number
        */
        cell.textContent = day;


        /*
        * reservation indicator, colored to match the first reservation's project
        */
        if (dayReservations.length > 0) {
            cell.classList.add('has-reservations');

            const dotsContainer = document.createElement('div');
            dotsContainer.classList.add('reservation-dots');

            dayReservations.slice(0, 3).forEach(reservation => {

                const dot = document.createElement('span');

                dot.classList.add('reservation-dot');

                dot.style.backgroundColor =
                    reservationDotColor(reservation.extendedProps);

                dotsContainer.appendChild(dot);
            });

            cell.appendChild(dotsContainer);
        }


        /*
        * Today
        */
        const today = new Date();

        if (
            cellDate.getFullYear() === today.getFullYear() &&
            cellDate.getMonth() === today.getMonth() &&
            cellDate.getDate() === today.getDate()
        ) {
            cell.classList.add('today');
        }


        /*
        * Selected date
        */
        if (
            selectedDate &&
            cellDate.getFullYear() === selectedDate.getFullYear() &&
            cellDate.getMonth() === selectedDate.getMonth() &&
            cellDate.getDate() === selectedDate.getDate()
        ) {
            cell.classList.add('selected');
        }


        /*
        * Clicking a date
        */
        cell.addEventListener('click', function () {

            selectedDate = cellDate;

            renderCalendar();

            showReservations(cellDate);
        });


        return cell;
    }


    /*
     * Previous month
     */
    prevMonth.addEventListener('click', function () {

        currentDate.setMonth(
            currentDate.getMonth() - 1
        );

        renderCalendar();
    });


    /*
     * Next month
     */
    nextMonth.addEventListener('click', function () {

        currentDate.setMonth(
            currentDate.getMonth() + 1
        );

        renderCalendar();
    });


    /*
     * Month dropdown
     */
    calendarMonth.addEventListener('change', function () {

        currentDate.setMonth(
            parseInt(this.value)
        );

        renderCalendar();
    });


    /*
     * Year dropdown
     */
    calendarYear.addEventListener('change', function () {

        currentDate.setFullYear(
            parseInt(this.value)
        );

        renderCalendar();
    });


    /*
     * Convert 24-hour "HH:MM" time to 12-hour "H:MM AM/PM" for display
     */
    function formatTime12h(time24) {
        if (!time24) return '';
        const [hStr, mStr] = time24.split(':');
        let h = parseInt(hStr, 10);
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${mStr} ${ampm}`;
    }


    /*
     * display reservations for selected date
     *
     *
     */
    function showReservations(date) {

        const selectedDayNumberLabel =
            document.getElementById('selectedDayNumberLabel');

        const selectedWeekdayLabel =
            document.getElementById('selectedWeekdayLabel');

        const reservationContainer =
            document.getElementById('selectedDateReservations');


        /*
        * Update selected date heading
        */
        selectedDayNumberLabel.textContent = date.getDate();

        selectedWeekdayLabel.textContent =
            date.toLocaleDateString('en-US', {
                weekday: 'long'
            });


        /*
        * Convert selected date to YYYY-MM-DD
        */
        const dateString =
            date.getFullYear() + '-' +
            String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0');


        /*
        * Find reservations for selected date
        */
        const dayReservations = reservations.filter(reservation =>
            reservation.start.startsWith(dateString)
        );

        const isLoggedIn = window.isLoggedIn;


        /*
        * No reservations
        */
        if (dayReservations.length === 0) {

            if (!isLoggedIn) {
                reservationContainer.innerHTML = `
                    <div class="text-center text-muted">
                        <div class="fw-semibold mb-1">Sign in to view your reservations</div>
                        <div class="small">
                            Please sign in with your UP Mail account.
                        </div>
                    </div>
                `;
            } else {
                reservationContainer.innerHTML = `
                    <div class="text-muted py-2">
                        No reservations scheduled for this date.
                    </div>
                `;
            }

            return;
        }


        /*
        * Display reservations
        */
        

        dayReservations.sort((a, b) => {
            return new Date(a.start) - new Date(b.start);
        });

        reservationContainer.innerHTML = dayReservations.map(reservation => {

            const props = reservation.extendedProps;

            const circleColor = reservationDotColor(props);

            return `
                <div class="d-flex flex-col align-items-center py-2 mb-2 border-top">

                    <!-- Start Time -->
                    <div class="d-flex flex-column justify-content-center text-black" style="width: 90px;">
                        <h5 class="fw-bolder mb-0">${formatTime12h(props.start_time_fmt)}</h5>
                    </div>

                    <!-- Circle -->
                    <div class="mx-2">
                        <span
                            class="d-block rounded-circle"
                            style="
                                width: 10px;
                                height: 10px;
                                background-color: ${circleColor};
                            ">
                        </span>
                    </div>

                    <!-- Reservation Information -->
                    <div class="flex-grow-1">

                        <!-- Title -->
                        <div class="fw-semibold text-black">
                            <h4 class="fw-bold m-0">${props.is_blocked ? 'Unavailable' : props.purpose}</h4>
                        </div>

                        <!-- Details -->
                        <div class="small text-muted d-flex align-items-center gap-2">
                            <span>${formatTime12h(props.start_time_fmt)}–${formatTime12h(props.end_time_fmt)}</span>

                            ${!props.is_blocked ? `
                                <span>${props.project_team_office ?? ''}</span>
                                <span>${props.requester_name ?? 'Reserved'}</span>
                            ` : ''}
                        </div>

                    </div>

                </div>
            `;

        }).join('');
    }


    /*
     * Initial render
     */
    renderCalendar();
    showReservations(selectedDate);

    /*
    * Load reservations
    */
    loadReservations();

});