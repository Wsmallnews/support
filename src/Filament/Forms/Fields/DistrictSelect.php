<?php

namespace Wsmallnews\Support\Filament\Forms\Fields;

use Filament\Forms\Components\Concerns\CanBeDisabled;
use Filament\Forms\Components\Concerns\CanBeSearchable;
use Filament\Forms\Components\Concerns\HasActions;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Concerns\HasOptions;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Contracts\HasAffixActions;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Features\District;

class DistrictSelect extends Field implements HasAffixActions
{
    use CanBeDisabled;
    use CanBeSearchable;
    use HasActions;
    use HasAffixes;
    use HasExtraAlpineAttributes;
    use HasExtraInputAttributes;
    use HasOptions;
    use HasPlaceholder;

    protected string $view = 'sn-support::forms.fields.district-select';

    protected function setUp(): void
    {
        $this->options(function () {
            $districtData = (new District)->getCascader();

            return is_array($districtData) ? $districtData : json_decode($districtData, true);
        });

        // 从 model 中获取数据
        $this->afterStateHydrated(function (DistrictSelect $component, ?array $state) {
            $record = $component->getRecord();

            if (! $record) {
                $component->state($state);

                return;
            }

            $component->state([
                'province_name' => $record->province_name,
                'province_id' => $record->province_id,
                'city_name' => $record->city_name,
                'city_id' => $record->city_id,
                'district_name' => $record->district_name,
                'district_id' => $record->district_id,
            ]);
        });
    }
}
