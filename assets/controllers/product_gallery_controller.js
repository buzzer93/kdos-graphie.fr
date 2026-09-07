import { Controller } from '@hotwired/stimulus';

/**
 * Product page image slider (main image + clickable thumbnail strip),
 * on the model of a classic e-commerce slider (Amazon-like).
 *
 * Targets:
 *   main       — the large <img> shown above the thumbnails
 *   thumbnail  — each clickable thumbnail <button data-index="…">
 */
export default class extends Controller {
    static targets = ['main', 'thumbnail'];

    select(event) {
        const index = parseInt(event.currentTarget.dataset.index, 10);
        this.#show(index);
    }

    next() {
        this.#show(this.#currentIndex() + 1);
    }

    previous() {
        this.#show(this.#currentIndex() - 1);
    }

    #currentIndex() {
        return this.thumbnailTargets.findIndex((thumb) => thumb.classList.contains('is-active'));
    }

    #show(index) {
        const count = this.thumbnailTargets.length;
        if (count === 0) return;

        const safeIndex = ((index % count) + count) % count;
        const thumbnail = this.thumbnailTargets[safeIndex];

        this.mainTarget.src = thumbnail.dataset.fullSrc;

        this.thumbnailTargets.forEach((thumb, i) => {
            thumb.classList.toggle('is-active', i === safeIndex);
            thumb.classList.toggle('border-amber-500', i === safeIndex);
            thumb.classList.toggle('border-transparent', i !== safeIndex);
        });
    }
}
