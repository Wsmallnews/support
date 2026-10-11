/**
 * 数字步进器（input/step-number 组件的 Alpine 逻辑，control / bare 两种形态共用）。
 *
 * 钳制语义：decrement 到下限后再减不再变化，改为派发 sn-stepper-min 事件——
 * 调用方（如商品附加项「减到下限 = 取消选择」）经 @sn-stepper-min 监听处理；
 * increment 到上限静默钳制（bare 形态的 + 按钮自带 :disabled）。
 */
export default function numberStepper({ minVal, maxVal, step, decimalPoints }) {
    return {
        minVal,
        maxVal,
        step,
        decimalPoints,
        currentVal: 0,

        init() {
            // 从服务端渲染的输入框取初始值（wire:model 经 x-modelable 回填可能晚于 init；
            // bare 不可编辑形态无输入框，保持 minVal，由 x-model 父级值经 entangle 接管）
            const raw = this.$el.querySelector('input')?.value;
            const num = Number(raw);
            this.currentVal = this.clamp(raw !== '' && raw !== undefined && Number.isFinite(num) ? num : this.minVal);
        },

        clamp(num) {
            const clamped = Math.min(this.maxVal, Math.max(this.minVal, num));
            // toFixed 返回字符串，必须转回 number，否则后续 + 变字符串拼接
            return Number(clamped.toFixed(this.decimalPoints));
        },

        updateValue(event) {        // 输入框直接输入时触发
            const num = Number(event.target.value);
            this.currentVal = this.clamp(Number.isNaN(num) ? this.minVal : num);
            // 强制更新输入框显示的值（非数字输入回退为钳制后的值）
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
