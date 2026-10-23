/**
 * Number stepper logic shared by both input/step-number variants.
 * Decrementing below the minimum stops silently and fires sn-stepper-min so callers can
 * react (e.g. deselect an option); incrementing past the maximum clamps silently.
 */
export default function numberStepper({ minVal, maxVal, step, decimalPoints }) {
    return {
        minVal,
        maxVal,
        step,
        decimalPoints,
        currentVal: 0,

        init() {
            // Read the server-rendered input: wire:model may arrive after init. Bare, non-editable
            // variants have no input, so the parent value takes over through entangle.
            const raw = this.$el.querySelector('input')?.value;
            const num = Number(raw);
            this.currentVal = this.clamp(raw !== '' && raw !== undefined && Number.isFinite(num) ? num : this.minVal);
        },

        clamp(num) {
            const clamped = Math.min(this.maxVal, Math.max(this.minVal, num));
            // toFixed returns a string; convert back or later additions concatenate
            return Number(clamped.toFixed(this.decimalPoints));
        },

        updateValue(event) {        // Typed input
            const num = Number(event.target.value);
            this.currentVal = this.clamp(Number.isNaN(num) ? this.minVal : num);
            // Force the displayed value, falling back to the clamped one for non-numeric input
            event.target.value = this.currentVal;
        },

        decrement() {
            if (Number(this.currentVal) - this.step < this.minVal) {
                this.$dispatch('sn-stepper-min');

                return;
            }

            this.currentVal = this.clamp(Number(this.currentVal) - this.step);
        },

        increment() {
            this.currentVal = this.clamp(Number(this.currentVal) + this.step);
        },
    };
}
