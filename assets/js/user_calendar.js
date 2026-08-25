document.addEventListener('DOMContentLoaded', function () {

    const calendarTab = document.getElementById('calendarTab');
    const myReservationsTab = document.getElementById('myReservationsTab');

    const calendarView = document.getElementById('calendarView');
    const myReservationsView = document.getElementById('myReservationsView');


    function showCalendar() {

        // Show calendar
        calendarView.style.display = 'flex';
        myReservationsView.style.display = 'none';

        // Active tab
        calendarTab.classList.add('active');
        myReservationsTab.classList.remove('active');
    }


    function showMyReservations() {

        // Show reservations
        calendarView.style.display = 'none';
        myReservationsView.style.display = 'block';

        // Active tab
        calendarTab.classList.remove('active');
        myReservationsTab.classList.add('active');
    }


    calendarTab.addEventListener('click', function () {
        showCalendar();
    });


    myReservationsTab.addEventListener('click', function () {
        showMyReservations();
    });

    /** CALENDAR */
    const calendarDays = document.getElementById('calendarDays');
    const calendarMonth = document.getElementById('calendarMonth');
    const calendarYear = document.getElementById('calendarYear');

    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');

    let currentDate = new Date();
    let selectedDate = new Date();

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
        * Add reservation indicator
        */
        if (dayReservations.length > 0) {
            cell.classList.add('has-reservations');
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


        /*
        * No reservations
        */
        if (dayReservations.length === 0) {

            reservationContainer.innerHTML = `
                <div class="text-muted">
                    No reservations scheduled for this date.
                </div>
            `;

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
            
            const projectColors = {
                'DiWA Core': '#DB7877',
                'Ops Team': '#4fa576',
                'RESCUE Project': '#8A94D8',
                'IRDSS Project': '#dbc57b',
                'Wolbachia Project': '#8E8E8E',
                'Scaling Up of Diwa App Project': '#ad4d72',
                'RabDash DC': '#db7860',
                'Others': '#8e6ad1'
            };

            const circleColor = props.is_blocked
                ? '#c5303f'
                : projectColors[props.project_team_office] ?? '#6c757d';

            return `
                <div class="d-flex flex-col align-items-center py-2">

                    <!-- Start Time -->
                    <div class="d-flex flex-column justify-content-center text-black" style="width: 50px;">
                        <h5 class="fw-bolder mb-0">${props.start_time_fmt}</h5>
                    </div>

                    <!-- Circle -->
                    <div class="me-2 ms-4">
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
                        <div class="fw-semibold text-dark">
                            <h4 class="fw-bold m-0">${props.is_blocked ? 'Unavailable' : props.purpose}</h4>
                        </div>

                        <!-- Details -->
                        <div class="small text-muted d-flex align-items-center gap-2">
                            <span>${props.start_time_fmt}–${props.end_time_fmt}</span>

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