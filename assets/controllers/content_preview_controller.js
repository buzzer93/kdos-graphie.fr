import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['zone'];

    highlight({ target }) {
        const key = target.dataset.fieldKey;
        for (const zone of this.zoneTargets) {
            const match = key && zone.dataset.zoneKey === key;
            zone.style.outline = match ? '2px solid #f59e0b' : '';
            zone.style.outlineOffset = match ? '2px' : '';
            zone.style.borderRadius = match ? '4px' : '';
            zone.style.backgroundColor = match ? 'rgba(245, 158, 11, 0.08)' : '';
            if (match) {
                zone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }

    update({ target }) {
        const key = target.dataset.fieldKey;
        if (!key) return;
        for (const zone of this.zoneTargets) {
            if (zone.dataset.zoneKey === key) {
                zone.textContent = target.value;
            }
        }
    }
}
