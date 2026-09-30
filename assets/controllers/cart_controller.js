import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['pickup', 'return', 'duration', 'pickupTime', 'returnTime'];

    prepare(event) {
        try {
            const period = JSON.parse(window.localStorage.getItem('rentalPeriod'));
            if (period?.pickup && period?.return) {
                this.pickupTarget.value = this.dateValue(period.pickup);
                this.returnTarget.value = this.dateValue(period.return);
                this.durationTarget.value = period.duration || 'full_day';
                this.pickupTimeTarget.value = period.pickupTime || '8';
                this.returnTimeTarget.value = period.returnTime || '16';
                return;
            }
        } catch {
            // The server will display the validation error.
        }

        if (this.pickupTarget.value && this.returnTarget.value) {
            this.pickupTarget.value = this.dateValue(this.pickupTarget.value);
            this.returnTarget.value = this.dateValue(this.returnTarget.value);
            this.durationTarget.value ||= 'full_day';
            this.pickupTimeTarget.value ||= '8';
            this.returnTimeTarget.value ||= '16';
            return;
        }

        event.preventDefault();
        window.alert('Please select a rental period before adding a bike to the cart.');
    }

    dateValue(value) {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return value;
        }

        const date = new Date(value);
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${date.getFullYear()}-${month}-${day}`;
    }
}
