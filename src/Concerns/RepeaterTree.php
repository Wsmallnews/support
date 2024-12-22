<?php

namespace Wsmallnews\Support\Concerns;

use Filament\Forms\Components;

trait RepeaterTree
{
    public function repeaterField($fields = [], $relation_name = 'children', $name = 'children'): Components\Repeater
    {
        return Components\Repeater::make($name ?: $relation_name)
            ->relationship($relation_name)
            ->hiddenLabel()
            ->reorderable(true)
            ->schema($fields);
    }

    public function fields(): array
    {
        return [];
    }

    public function getFieldsTree($level = 1, $relation_name = 'children', $name = 'children')
    {
        $fields = $this->fields();

        if ($level > 1) {
            $fields = array_merge($fields, [
                Components\Group::make([
                    $this->repeaterField($this->getFieldsTree($level - 1, $relation_name, $name), $relation_name, $name),
                ]),
            ]);
        }

        return $fields;
    }
}
