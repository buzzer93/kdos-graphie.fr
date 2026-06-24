import { Controller } from '@hotwired/stimulus';

/**
 * Handles dynamic price display when the user selects product options.
 *
 * Expected data attributes on the controller element:
 *   data-product-option-base-price-value  — base product price in cents (int)
 *   data-product-option-groups-value      — JSON array of option groups:
 *     [{ id, name, values: [{ id, label, priceAdjustment, isActive }] }]
 *
 * Targets:
 *   price        — element where the formatted total price is displayed
 *   submit       — submit button (disabled when selection is incomplete)
 *   hiddenInputs — container where hidden option_values[] inputs are injected
 */
export default class extends Controller {
    static values = {
        basePrice: Number,
        groups: Array,
    };

    static targets = ['price', 'submit', 'hiddenInputs'];

    connect() {
        this.#update();
    }

    onChange() {
        this.#update();
    }

    #update() {
        const selects = this.element.querySelectorAll('[data-option-group]');
        let adjustment = 0;
        const selectedIds = [];
        let allSelected = true;

        selects.forEach((select) => {
            const valueId = parseInt(select.value, 10);
            if (!valueId) {
                allSelected = false;
                return;
            }

            const groupId = parseInt(select.dataset.optionGroup, 10);
            const group = this.groupsValue.find((g) => g.id === groupId);
            if (!group) return;

            const optionValue = group.values.find((v) => v.id === valueId);
            if (optionValue) {
                adjustment += optionValue.priceAdjustment;
                selectedIds.push(valueId);
            }
        });

        const totalCents = this.basePriceValue + adjustment;
        const formatted = (totalCents / 100).toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        if (this.hasPriceTarget) {
            this.priceTarget.textContent = formatted + ' €';
        }

        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = !allSelected;
        }

        this.#syncHiddenInputs(selectedIds);
    }

    #syncHiddenInputs(selectedIds) {
        if (!this.hasHiddenInputsTarget) return;

        this.hiddenInputsTarget.innerHTML = '';
        selectedIds.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'option_values[]';
            input.value = String(id);
            this.hiddenInputsTarget.appendChild(input);
        });
    }
}
