import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['card', 'filter', 'count'];

    connect() {
        this.selectCategory('All');
    }

    filter(event) {
        this.selectCategory(event.currentTarget.dataset.category);
    }

    selectCategory(category) {
        let count = 0;
        this.cardTargets.forEach((card) => {
            card.hidden = category !== 'All' && card.dataset.category !== category;
            if (!card.hidden) count += 1;
        });
        this.filterTargets.forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.category === category));
        });
        this.countTarget.textContent = `${count} ${count === 1 ? 'place' : 'places'} to explore`;
    }

    jump(event) {
        const target = this.cardTargets.find((card) => card.id === event.currentTarget.dataset.place);
        if (!target) return;
        event.preventDefault();
        this.selectCategory('All');
        target.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
            block: 'start',
        });
        target.querySelector('h3')?.focus({ preventScroll: true });
        const url = new URL(window.location.href);
        url.hash = target.id;
        window.history.replaceState(null, '', url);
    }
}
