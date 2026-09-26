import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['gallery', 'details'];

    connect() {
        this.syncHeight = this.syncHeight.bind(this);
        this.resizeObserver = new ResizeObserver(this.syncHeight);
        this.resizeObserver.observe(this.detailsTarget);
        window.addEventListener('resize', this.syncHeight);
        this.syncHeight();
    }

    disconnect() {
        this.resizeObserver?.disconnect();
        window.removeEventListener('resize', this.syncHeight);
    }

    syncHeight() {
        if (window.matchMedia('(min-width: 768px)').matches) {
            this.galleryTarget.style.maxHeight = `${this.detailsTarget.offsetHeight}px`;
            return;
        }

        this.galleryTarget.style.maxHeight = '';
    }
}
