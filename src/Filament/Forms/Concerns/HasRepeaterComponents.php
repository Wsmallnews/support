<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;

/**
 * Optional repeater views shared by every package: native Repeater instances,
 * only the rendering template is swapped via ->view() (Filament's official
 * per-field channel — no global override, mechanisms stay 100% native).
 */
trait HasRepeaterComponents
{
    /**
     * Row-grid repeater: the table-mode alternative for column-shaped repeatable
     * forms (spec values, variant combinations, …). Columns come from the native
     * ->table(TableColumn...) API — labels and widths feed the row's column caps,
     * and every cell carries its data-label for narrow-container degradation.
     * Rows keep x-sortable, native add/delete/clone actions and column settings.
     */
    public static function gridRepeater(string | Closure | null $name = null): Repeater
    {
        return Repeater::make($name)
            ->view('sn-support::filament.forms.repeater.grid');
    }

    /**
     * Card repeater with an optional header field: the named schema field renders
     * inline in the item header (next to the drag handle / collapse / delete) and
     * is excluded from the body — the "name the group" pattern (spec items, media
     * groups). Without $headerField it is a plain card repeater (all fields in body).
     *
     * @param  string|null  $headerField  field name to lift into the item header
     */
    public static function cardsRepeater(string | Closure | null $name = null, ?string $headerField = null): Repeater
    {
        $repeater = Repeater::make($name)
            ->view('sn-support::filament.forms.repeater.cards');

        if (filled($headerField)) {
            $repeater->viewData(['headerField' => $headerField]);
        }

        return $repeater;
    }

    /**
     * Table column whose header carries a hint button: the place for
     * field-level guidance in table repeaters. Helper text under the input
     * would stretch the column (hidden at table width via sn-support css), so
     * wide screens read the hint off the header and narrow (stacked card)
     * screens get the field's own helperText back. Chain ->width() etc.
     * outside, exactly like the native TableColumn.
     *
     * @param  string|null  $hint  bubble tooltip text; null renders a plain column
     */
    public static function hintedTableColumn(string $label, ?string $hint = null): TableColumn
    {
        return TableColumn::make(filled($hint)
            ? new HtmlString(e($label) . static::tableColumnHintButton($hint))
            : $label);
    }

    /**
     * The header hint button: a bare icon button (official icon markup via
     * generate_icon_html, sn-th-hint styles it inline-sized and primary) whose
     * x-tooltip rides Filament's tippy bubble. type="button" with no wire
     * binding — a click explicitly shows the bubble via $el._tippy and never
     * reaches Livewire; hover shows it through tippy's own mouseenter trigger.
     */
    protected static function tableColumnHintButton(string $hint): string
    {
        $icon = \Filament\Support\generate_icon_html(Heroicon::OutlinedQuestionMarkCircle, size: IconSize::Small);

        return '<button type="button" class="sn-th-hint"'
            . ' x-tooltip="{ content: ' . Js::from($hint) . ', theme: $store.theme }"'
            . ' x-on:click="$el._tippy?.show()"'
            . ' aria-label="' . e($hint) . '">'
            . $icon->toHtml()
            . '</button>';
    }
}
