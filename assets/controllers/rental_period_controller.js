import { Controller } from '@hotwired/stimulus';

const DAY_NAMES = ['LU', 'MA', 'ME', 'JE', 'VE', 'SA', 'DI'];
const MONTH_NAMES = [
    'janvier',
    'fevrier',
    'mars',
    'avril',
    'mai',
    'juin',
    'juillet',
    'aout',
    'septembre',
    'octobre',
    'novembre',
    'decembre',
];

export default class extends Controller {
    static targets = [
        'dialog',
        'months',
        'buttonLabel',
        'pickupDate',
        'returnDate',
        'duration',
    ];

    connect() {
        this.isOpen = false;
        this.minDate = this.stripTime(new Date());
        this.viewDate = this.startOfMonth(this.addDays(new Date(), 1));
        this.pickup = null;
        this.return = null;
        this.loadSelection();
        this.render();
        this.updateSummary();
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
        if (event.target === this.dialogTarget) {
            this.close();
        }
    }

    apply() {
        if (!this.pickup) {
            this.close();
            return;
        }

        this.updateSummary();
        this.saveSelection();
        window.location.assign(this.availabilityUrl());
    }

    clear() {
        this.pickup = null;
        this.return = null;
        this.clearSelection();
        this.buttonLabelTarget.textContent = 'Select a rental period: View prices and availability';
        this.durationTarget.textContent = '-';
        this.pickupDateTarget.textContent = '-';
        this.returnDateTarget.textContent = '-';
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
        const selected = new Date(`${event.currentTarget.dataset.date}T08:00:00`);

        if (this.isPastDate(selected)) {
            return;
        }

        if (!this.pickup || (this.pickup && this.return) || selected < this.pickup) {
            this.pickup = this.withTime(selected, 8);
            this.return = null;
        } else {
            this.return = this.withTime(selected, 12);
        }

        this.updateSummary();
        this.render();
    }

    render() {
        const monthA = this.viewDate;
        const monthB = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);

        this.monthsTarget.innerHTML = `
            <button class="rental-calendar-nav rental-calendar-nav-left" type="button" data-action="rental-period#previousMonth" aria-label="Previous month">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            ${this.renderMonth(monthA)}
            ${this.renderMonth(monthB)}
            <button class="rental-calendar-nav rental-calendar-nav-right" type="button" data-action="rental-period#nextMonth" aria-label="Next month">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        `;
    }

    renderMonth(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1);
        const blanks = (firstDay.getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells = [];

        for (let index = 0; index < blanks; index += 1) {
            cells.push('<span class="rental-calendar-empty"></span>');
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const current = new Date(year, month, day);
            const isoDate = this.toIsoDate(current);
            const disabled = this.isPastDate(current);
            const selected = !disabled && (this.isSameDay(current, this.pickup) || this.isSameDay(current, this.return));
            const inRange = !disabled && this.pickup && this.return && current > this.pickup && current < this.return;
            const className = [
                'rental-calendar-day',
                disabled ? 'is-disabled' : '',
                selected ? 'is-selected' : '',
                inRange ? 'is-in-range' : '',
            ].filter(Boolean).join(' ');

            cells.push(`
                <button class="${className}" type="button" data-action="rental-period#selectDay" data-date="${isoDate}"${disabled ? ' disabled aria-disabled="true"' : ''}>
                    ${day}
                </button>
            `);
        }

        return `
            <section class="rental-calendar-month" aria-label="${MONTH_NAMES[month]} ${year}">
                <h3>${MONTH_NAMES[month]} ${year}</h3>
                <div class="rental-calendar-weekdays">
                    ${DAY_NAMES.map((day) => `<span>${day}</span>`).join('')}
                </div>
                <div class="rental-calendar-grid">
                    ${cells.join('')}
                </div>
            </section>
        `;
    }

    updateSummary() {
        if (!this.pickup) {
            return;
        }

        const returnDate = this.return || this.withTime(this.pickup, 12);
        const dayCount = Math.max(1, Math.ceil((this.stripTime(returnDate) - this.stripTime(this.pickup)) / 86400000) + 1);
        const summary = `${this.formatButtonDate(this.pickup)} 08:00 - ${this.formatButtonDate(returnDate)} 12:00`;

        this.durationTarget.textContent = `${dayCount} day${dayCount > 1 ? 's' : ''}`;
        this.pickupDateTarget.textContent = `${this.formatNumericDate(this.pickup)}   08:00`;
        this.returnDateTarget.textContent = `${this.formatNumericDate(returnDate)}   12:00`;
        this.buttonLabelTarget.textContent = summary;
    }

    loadSelection() {
        const query = new URLSearchParams(window.location.search);
        const pickup = query.get('pickup');
        const returnDate = query.get('return');

        if (pickup) {
            this.setSelection(this.parseDate(pickup), returnDate ? this.parseDate(returnDate) : null);
            return;
        }

        try {
            const stored = JSON.parse(window.localStorage.getItem('rentalPeriod'));
            if (stored?.pickup) {
                this.setSelection(this.parseDate(stored.pickup), stored.return ? this.parseDate(stored.return) : null);
            }
        } catch {
            this.clearSelection();
        }
    }

    setSelection(pickup, returnDate) {
        if (!this.isValidDate(pickup) || this.isPastDate(pickup)) {
            this.clearSelection();
            return;
        }

        this.pickup = this.withTime(pickup, 8);
        this.return = this.isValidDate(returnDate) && returnDate >= this.pickup
            ? this.withTime(returnDate, 12)
            : this.withTime(this.pickup, 12);
        this.saveSelection();
    }

    saveSelection() {
        const returnDate = this.return || this.withTime(this.pickup, 12);

        try {
            window.localStorage.setItem('rentalPeriod', JSON.stringify({
                pickup: this.toIsoDate(this.pickup),
                return: this.toIsoDate(returnDate),
            }));
        } catch {
            // Storage can be unavailable in restricted browser contexts.
        }
    }

    clearSelection() {
        try {
            window.localStorage.removeItem('rentalPeriod');
        } catch {
            // Storage can be unavailable in restricted browser contexts.
        }
    }

    availabilityUrl() {
        const returnDate = this.return || this.withTime(this.pickup, 12);
        const url = new URL('/collections/all', window.location.origin);
        url.searchParams.set('pickup', this.toIsoDate(this.pickup));
        url.searchParams.set('return', this.toIsoDate(returnDate));

        return `${url.pathname}${url.search}`;
    }

    formatButtonDate(date) {
        return `${MONTH_NAMES[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
    }

    formatNumericDate(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        return `${day}-${month}-${date.getFullYear()}`;
    }

    toIsoDate(date) {
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${date.getFullYear()}-${month}-${day}`;
    }

    addDays(date, days) {
        const nextDate = new Date(date);
        nextDate.setDate(nextDate.getDate() + days);
        return nextDate;
    }

    startOfMonth(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    }

    stripTime(date) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate());
    }

    isPastDate(date) {
        return this.stripTime(date) < this.minDate;
    }

    isValidDate(date) {
        return date instanceof Date && !Number.isNaN(date.getTime());
    }

    parseDate(value) {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return new Date(`${value}T08:00:00`);
        }

        return new Date(value);
    }

    withTime(date, hour) {
        return new Date(date.getFullYear(), date.getMonth(), date.getDate(), hour, 0, 0);
    }

    isSameDay(firstDate, secondDate) {
        return secondDate
            && firstDate.getFullYear() === secondDate.getFullYear()
            && firstDate.getMonth() === secondDate.getMonth()
            && firstDate.getDate() === secondDate.getDate();
    }
}
