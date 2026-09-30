import { Controller } from '@hotwired/stimulus';

const DAY_NAMES = ['LU', 'MA', 'ME', 'JE', 'VE', 'SA', 'DI'];
const MONTH_NAMES = [
    'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre',
];

export default class extends Controller {
    static targets = [
        'dialog', 'months', 'buttonLabel', 'pickupDate', 'returnDate',
        'duration', 'durationSelect', 'pickupTimeSelect', 'returnTimeSelect',
    ];

    connect() {
        this.isOpen = false;
        this.minDate = this.stripTime(new Date());
        this.viewDate = this.startOfMonth(this.addDays(new Date(), 1));
        this.pickup = null;
        this.return = null;
        this.rentalDuration = 'half_day';
        this.pickupHour = 8;
        this.returnHour = 16;
        this.calendarClickHandler = (event) => {
            const button = event.target.closest('button[data-date]');
            if (!button || button.disabled || !this.monthsTarget.contains(button)) return;
            this.selectDay({
                currentTarget: button,
                preventDefault: () => event.preventDefault(),
                stopPropagation: () => event.stopPropagation(),
            });
        };
        this.monthsTarget.addEventListener('click', this.calendarClickHandler);
        this.loadSelection();
        this.render();
        this.updateSummary();
    }

    disconnect() {
        this.monthsTarget.removeEventListener('click', this.calendarClickHandler);
    }

    open() {
        this.isOpen = true;
        this.element.classList.add('rental-period-open');
        this.dialogTarget.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    }

    close() {
        this.isOpen = false;
        this.element.classList.remove('rental-period-open');
        this.dialogTarget.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    }

    closeOnBackdrop(event) {
        if (event.target === this.dialogTarget) this.close();
    }

    apply() {
        if (!this.pickup) {
            this.close();
            return;
        }

        this.normalizeSelection();
        this.updateSummary();
        this.saveSelection();
        window.location.assign(this.availabilityUrl());
    }

    clear() {
        this.pickup = null;
        this.return = null;
        this.rentalDuration = 'half_day';
        this.pickupHour = 8;
        this.returnHour = 16;
        this.clearSelection();
        this.buttonLabelTarget.textContent = 'Select a rental period: View prices and availability';
        this.durationTarget.textContent = '-';
        this.pickupDateTarget.textContent = '-';
        this.returnDateTarget.textContent = '-';
        this.renderControls();
        this.render();
    }

    previousMonth() {
        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() - 1, 1);
        this.render();
    }

    nextMonth() {
        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);
        this.render();
    }

    selectDay(event) {
        event.preventDefault();
        event.stopPropagation();
        const selected = new Date(`${event.currentTarget.dataset.date}T08:00:00`);
        if (this.isPastDate(selected)) return;

        if (!this.pickup || this.return || selected < this.pickup) {
            this.pickup = this.stripTime(selected);
            this.return = null;
            this.rentalDuration = 'half_day';
            this.pickupHour = 8;
            this.returnHour = 16;
        } else {
            this.return = this.stripTime(selected);
            if (!this.isSameDay(this.pickup, this.return)) {
                this.rentalDuration = 'full_day';
                this.returnHour = 16;
            }
        }

        this.normalizeSelection();
        this.updateSummary();
        this.render();
    }

    changeDuration(event) {
        if (this.dayCount() !== 1) return;

        this.rentalDuration = event.currentTarget.value;
        if (this.rentalDuration === 'full_day') {
            this.pickupHour = 8;
            this.returnHour = 16;
        } else if (![8, 12].includes(this.pickupHour)) {
            this.pickupHour = 8;
        }
        this.updateSummary();
    }

    changePickupTime(event) {
        const hour = Number(event.currentTarget.value);
        const days = this.dayCount();
        if (!this.pickup || (hour === 16 && days < 2)) return;
        if (days === 1 && this.rentalDuration === 'full_day' && hour !== 8) return;
        if (days === 1 && this.rentalDuration === 'half_day' && ![8, 12].includes(hour)) return;

        this.pickupHour = hour;
        this.updateSummary();
    }

    changeReturnTime(event) {
        if (this.dayCount() < 2) return;
        this.returnHour = Number(event.currentTarget.value);
        this.updateSummary();
    }

    normalizeSelection() {
        const days = this.dayCount();
        if (days > 1) {
            this.rentalDuration = 'full_day';
            if (![8, 12, 16].includes(this.pickupHour)) this.pickupHour = 8;
            if (![8, 12, 16].includes(this.returnHour)) this.returnHour = 16;
            return;
        }

        if (this.rentalDuration === 'full_day') {
            this.pickupHour = 8;
            this.returnHour = 16;
            return;
        }

        this.rentalDuration = 'half_day';
        if (![8, 12].includes(this.pickupHour)) this.pickupHour = 8;
        this.returnHour = this.pickupHour + 4;
    }

    render() {
        const monthA = this.viewDate;
        const monthB = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);
        this.monthsTarget.innerHTML = `
            <button class="rental-calendar-nav rental-calendar-nav-left" type="button" data-action="rental-period-v2#previousMonth" aria-label="Previous month"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
            ${this.renderMonth(monthA)}
            ${this.renderMonth(monthB)}
            <button class="rental-calendar-nav rental-calendar-nav-right" type="button" data-action="rental-period-v2#nextMonth" aria-label="Next month"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        `;
    }

    renderMonth(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1);
        const blanks = (firstDay.getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells = [];

        for (let index = 0; index < blanks; index += 1) cells.push('<span class="rental-calendar-empty"></span>');

        for (let day = 1; day <= daysInMonth; day += 1) {
            const current = new Date(year, month, day);
            const disabled = this.isPastDate(current);
            const selected = !disabled && (this.isSameDay(current, this.pickup) || this.isSameDay(current, this.return));
            const inRange = !disabled && this.pickup && this.return && current > this.pickup && current < this.return;
            const className = [
                'rental-calendar-day', disabled ? 'is-disabled' : '',
                selected ? 'is-selected' : '', inRange ? 'is-in-range' : '',
            ].filter(Boolean).join(' ');
            cells.push(`<button class="${className}" type="button" data-date="${this.toIsoDate(current)}"${disabled ? ' disabled aria-disabled="true"' : ''}>${day}</button>`);
        }

        return `
            <section class="rental-calendar-month" aria-label="${MONTH_NAMES[month]} ${year}">
                <h3>${MONTH_NAMES[month]} ${year}</h3>
                <div class="rental-calendar-weekdays">${DAY_NAMES.map((day) => `<span>${day}</span>`).join('')}</div>
                <div class="rental-calendar-grid">${cells.join('')}</div>
            </section>`;
    }

    updateSummary() {
        this.renderControls();
        if (!this.pickup) return;

        this.normalizeSelection();
        const returnDate = this.return || this.pickup;
        const days = this.dayCount();
        const startHour = this.hourLabel(this.pickupHour);
        const endHour = this.hourLabel(this.returnHour);
        const durationLabel = days > 1 ? `${days} days` : this.rentalDuration === 'full_day' ? 'Full day' : 'Half day';

        this.durationTarget.textContent = durationLabel;
        this.pickupDateTarget.textContent = this.formatNumericDate(this.pickup);
        this.returnDateTarget.textContent = this.formatNumericDate(returnDate);
        this.buttonLabelTarget.textContent = `${this.formatButtonDate(this.pickup)} ${startHour} - ${this.formatButtonDate(returnDate)} ${endHour}`;
        this.renderControls();
    }

    renderControls() {
        const days = this.dayCount();
        const singleDay = days === 1;
        const hasSelection = Boolean(this.pickup);
        this.durationSelectTarget.classList.toggle('hidden', !hasSelection || !singleDay);
        this.durationTarget.classList.toggle('hidden', hasSelection && singleDay);
        this.pickupTimeSelectTarget.classList.toggle('hidden', !hasSelection);
        this.returnTimeSelectTarget.classList.toggle('hidden', !hasSelection);

        this.durationSelectTarget.value = this.rentalDuration;
        this.pickupTimeSelectTarget.value = String(this.pickupHour);
        this.returnTimeSelectTarget.value = String(this.returnHour);
        this.returnTimeSelectTarget.disabled = days < 2;

        for (const option of this.pickupTimeSelectTarget.options) {
            const hour = Number(option.value);
            option.disabled = !hasSelection || (hour === 16 && days < 2)
                || (singleDay && this.rentalDuration === 'full_day' && hour !== 8)
                || (singleDay && this.rentalDuration === 'half_day' && ![8, 12].includes(hour));
        }
    }

    loadSelection() {
        const query = new URLSearchParams(window.location.search);
        if (query.get('pickup')) {
            this.setSelection(this.parseDate(query.get('pickup')), query.get('return') ? this.parseDate(query.get('return')) : null,
                query.get('duration'), Number(query.get('pickup_time')), Number(query.get('return_time')));
            return;
        }

        try {
            const stored = JSON.parse(window.localStorage.getItem('rentalPeriod'));
            if (stored?.pickup) {
                this.setSelection(this.parseDate(stored.pickup), stored.return ? this.parseDate(stored.return) : null,
                    stored.duration, Number(stored.pickupTime), Number(stored.returnTime));
            }
        } catch {
            this.clearSelection();
        }
    }

    setSelection(pickup, returnDate, duration = 'half_day', pickupHour = 8, returnHour = 16) {
        if (!this.isValidDate(pickup) || this.isPastDate(pickup)) {
            this.clearSelection();
            return;
        }
        this.pickup = this.stripTime(pickup);
        this.return = this.isValidDate(returnDate) && returnDate >= this.pickup ? this.stripTime(returnDate) : this.pickup;
        this.rentalDuration = ['half_day', 'full_day'].includes(duration) ? duration : 'half_day';
        this.pickupHour = [8, 12, 16].includes(pickupHour) ? pickupHour : 8;
        this.returnHour = [8, 12, 16].includes(returnHour) ? returnHour : 16;
        this.normalizeSelection();
        this.saveSelection();
    }

    saveSelection() {
        if (!this.pickup) return;
        const returnDate = this.return || this.pickup;
        try {
            window.localStorage.setItem('rentalPeriod', JSON.stringify({
                pickup: this.toIsoDate(this.pickup), return: this.toIsoDate(returnDate),
                duration: this.rentalDuration, pickupTime: this.pickupHour, returnTime: this.returnHour,
            }));
        } catch {
            // Storage can be unavailable in restricted browser contexts.
        }
    }

    clearSelection() {
        try { window.localStorage.removeItem('rentalPeriod'); } catch { /* Storage can be unavailable. */ }
    }

    availabilityUrl() {
        const returnDate = this.return || this.pickup;
        const url = new URL('/collections/all', window.location.origin);
        url.searchParams.set('pickup', this.toIsoDate(this.pickup));
        url.searchParams.set('return', this.toIsoDate(returnDate));
        url.searchParams.set('duration', this.rentalDuration);
        url.searchParams.set('pickup_time', String(this.pickupHour));
        url.searchParams.set('return_time', String(this.returnHour));
        return `${url.pathname}${url.search}`;
    }

    dayCount() {
        if (!this.pickup) return 1;
        const returnDate = this.return || this.pickup;
        return Math.max(1, Math.round((this.stripTime(returnDate) - this.stripTime(this.pickup)) / 86400000) + 1);
    }

    hourLabel(hour) { return `${String(hour).padStart(2, '0')}:00`; }
    formatButtonDate(date) { return `${MONTH_NAMES[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`; }
    formatNumericDate(date) { return `${String(date.getDate()).padStart(2, '0')}-${String(date.getMonth() + 1).padStart(2, '0')}-${date.getFullYear()}`; }
    toIsoDate(date) { return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`; }
    addDays(date, days) { const next = new Date(date); next.setDate(next.getDate() + days); return next; }
    startOfMonth(date) { return new Date(date.getFullYear(), date.getMonth(), 1); }
    stripTime(date) { return new Date(date.getFullYear(), date.getMonth(), date.getDate()); }
    isPastDate(date) { return this.stripTime(date) < this.minDate; }
    isValidDate(date) { return date instanceof Date && !Number.isNaN(date.getTime()); }
    parseDate(value) { return /^\d{4}-\d{2}-\d{2}$/.test(value) ? new Date(`${value}T08:00:00`) : new Date(value); }
    isSameDay(first, second) { return second && first.getFullYear() === second.getFullYear() && first.getMonth() === second.getMonth() && first.getDate() === second.getDate(); }
}
