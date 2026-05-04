@props([
    'content' => null,                    // 内容
    'contentType' => 'textarea',          // 内容类型，  textarea, markdown or richtext
    'maxHeight' => 350                    // 默认内容高度
])

<div
    class="sn-collapse-container"
    x-data="{
        collapsed: false,
        needCollapse: false,
        init() {
            $nextTick(() => {
                const el = this.$refs.content;
                this.needCollapse = el.scrollHeight > {{ $maxHeight }};
                this.collapsed = this.needCollapse;
            });
        },
        toggle() {
            this.collapsed = !this.collapsed;
        }
    }"
>
    <div
        x-ref="container"
        x-cloak
        class="sn-collapse-content"
        :class="collapsed ? 'sn-collapsed' : 'sn-expanded'"
        :style="collapsed ? 'max-height: {{ $maxHeight }}px' : 'max-height: none'"
    >
        <div x-ref="content">
            <x-sn-support::content
                :content-type="$contentType"
                :content="$content"
            />
        </div>
        <div
            class="sn-collapse-fade"
            x-show="collapsed"
            x-transition.opacity.duration.100ms
        ></div>
    </div>
    <div
        x-show="needCollapse"
        x-cloak
        class="sn-collapse-toggle"
        @click="toggle()"
    >
        <span x-text="collapsed ? '展开全部' : '收起'"></span>
        <x-filament::icon x-show="collapsed" icon="heroicon-m-chevron-down" class="size-4 font-semibold" />
        <x-filament::icon x-show="!collapsed" icon="heroicon-m-chevron-up" class="size-4 font-semibold" />
    </div>
</div>