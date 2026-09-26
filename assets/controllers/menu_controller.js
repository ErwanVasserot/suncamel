import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel', 'backdrop', 'button'];

    connect() {
        this.isOpen = false;
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.element.classList.add('menu-open');
        this.panelTarget.setAttribute('aria-hidden', 'false');
    }

    close() {
        this.isOpen = false;
        this.element.classList.remove('menu-open');
        this.panelTarget.setAttribute('aria-hidden', 'true');
    }
}
