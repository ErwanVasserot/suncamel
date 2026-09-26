import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['track'];

    prev() {
        this.scrollBy(-1);
    }

    next() {
        this.scrollBy(1);
    }

    scrollBy(direction) {
        const track = this.trackTarget;
        const card = track.querySelector('.snap-start');
        const step = card ? card.getBoundingClientRect().width + 20 : 280;
        track.scrollBy({ left: direction * step, behavior: 'smooth' });
    }
}
