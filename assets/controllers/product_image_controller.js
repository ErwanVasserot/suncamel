import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['image'];
    static values = {
        defaultScale: { type: Number, default: 2 },
        minScale: { type: Number, default: 1 },
        maxScale: { type: Number, default: 5 },
        step: { type: Number, default: 0.25 },
    };

    connect() {
        this.scale = this.defaultScaleValue;
        this.x = 0;
        this.y = 0;
        this.isDragging = false;
        this.startX = 0;
        this.startY = 0;
        this.originX = 0;
        this.originY = 0;
        this.applyTransform();
    }

    zoomIn() {
        this.setScale(this.scale + this.stepValue);
    }

    zoomOut() {
        this.setScale(this.scale - this.stepValue);
    }

    reset() {
        this.scale = this.defaultScaleValue;
        this.x = 0;
        this.y = 0;
        this.applyTransform();
    }

    wheel(event) {
        event.preventDefault();

        const direction = event.deltaY < 0 ? 1 : -1;
        this.setScale(this.scale + direction * this.stepValue);
    }

    startDrag(event) {
        if (event.target.closest('button')) {
            return;
        }

        if (event.button !== undefined && event.button !== 0) {
            return;
        }

        this.isDragging = true;
        this.startX = event.clientX;
        this.startY = event.clientY;
        this.originX = this.x;
        this.originY = this.y;
        this.element.setPointerCapture?.(event.pointerId);
        this.element.classList.add('cursor-grabbing');
    }

    drag(event) {
        if (!this.isDragging) {
            return;
        }

        this.x = this.originX + event.clientX - this.startX;
        this.y = this.originY + event.clientY - this.startY;
        this.clampPosition();
        this.applyTransform();
    }

    endDrag(event) {
        if (!this.isDragging) {
            return;
        }

        this.isDragging = false;
        this.element.releasePointerCapture?.(event.pointerId);
        this.element.classList.remove('cursor-grabbing');
    }

    setScale(scale) {
        this.scale = Math.min(this.maxScaleValue, Math.max(this.minScaleValue, scale));
        this.clampPosition();
        this.applyTransform();
    }

    clampPosition() {
        if (this.scale <= this.defaultScaleValue) {
            this.x = 0;
            this.y = 0;
            return;
        }

        const rect = this.element.getBoundingClientRect();
        const maxX = (rect.width * (this.scale - 1)) / 2;
        const maxY = (rect.height * (this.scale - 1)) / 2;

        this.x = Math.min(maxX, Math.max(-maxX, this.x));
        this.y = Math.min(maxY, Math.max(-maxY, this.y));
    }

    applyTransform() {
        this.imageTarget.style.transform = `translate3d(${this.x}px, ${this.y}px, 0) scale(${this.scale})`;
    }
}
