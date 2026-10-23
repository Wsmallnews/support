<?php

namespace Wsmallnews\Support\Enums\Traits;

trait EnumHelper
{
    /**
     * Enum cases as an array.
     *
     * @param  bool  $is_kv  Return value => label pairs instead of a plain list
     */
    public static function labels($is_kv = false): array
    {
        $values = collect(self::cases())
            ->map(function ($enum) {
                return [
                    'name' => $enum->getLabel(),
                    'value' => $enum->value,
                ];
            })->toArray();

        if ($is_kv) {
            $values = array_column($values, 'name', 'value');
        }

        return $values;
    }
}
