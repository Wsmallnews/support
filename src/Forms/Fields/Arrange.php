<?php

namespace Wsmallnews\Support\Forms\Fields;

use Closure;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Field;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Str;
use Wsmallnews\Support\Exceptions\SupportException;

class Arrange extends Field
{
    use Concerns\HasAffixes;
    use Concerns\HasExtraInputAttributes;
    use Concerns\HasPlaceholder;
    use HasExtraAlpineAttributes;

    protected string $view = 'sn-support::forms.fields.arrange';

    protected string $tableFieldsView = 'sn-support::arrange.table-fields';

    protected string | Closure $arrangeToRecursionKey = 'arrange_ids';

    protected array $arrangeTempIdToRealId = [];

    protected string | Closure | null $arrangePlaceholder = null;

    protected string | Closure | null $arrangeChildPlaceholder = null;

    protected string | Closure | null $addActionLabel = null;

    protected string | Closure | null $addChildActionLabel = null;

    protected array | Closure $tableFields = [];

    protected array $tableFieldsViewData = [];

    public function relationships(array $relations): static
    {
        $arrangeRelationshipInfo = $relations['arranges'] ?? [];
        $recursionRelationshipInfo = $relations['recursions'] ?? [];

        $this->afterStateHydrated(function (Arrange $component, $record) use ($arrangeRelationshipInfo, $recursionRelationshipInfo) {
            // hydrated 后 初始化
            $arranges = $this->getArrangeRelationship($arrangeRelationshipInfo);

            $recursions = $this->getRecursionRelationship($recursionRelationshipInfo);

            $state = collect([
                'arranges' => $arranges,
                'recursions' => $recursions,
            ]);
            $component->state($state);
        });

        // $this->dehydrateStateUsing(function ($state) use ($arrangeRelationshipInfo) {
        //     $arrangeRelationshipName = $this->getRelationshipName($arrangeRelationshipInfo['childrenRelationship'] ?? 'children');

        //     $state = $state->toArray();
        //     $arranges = $state['arranges'] ?? [];

        //     if ($arrangeRelationshipName != 'children') {
        //         // 处理结果
        //         foreach ($arranges as &$arrange) {
        //             $arrange['children'] = $arrange[Str::snake($arrangeRelationshipName)] ?? [];
        //             unset($arrange[Str::snake($arrangeRelationshipName)]);
        //         }
        //     }

        //     $state['arranges'] = $arranges;
        //     return $state;
        // });

        $this->saveRelationshipsUsing(function (Arrange $component, HasForms $livewire, ?array $state) use ($arrangeRelationshipInfo, $recursionRelationshipInfo) {
            // 保存 关联数据
            if (! is_array($state)) {
                $state = [];
            }

            // 保存 arranges
            $this->saveRelationshipArrange($component, $livewire, $arrangeRelationshipInfo, $state);

            // 保存 recursions
            $this->saveRelationshipRecursion($component, $livewire, $recursionRelationshipInfo, $state);
        });

        $this->dehydrated(false);

        return $this;
    }


    private function getArrangeRelationship($arrangeRelationshipInfo)
    {
        $originalArrangeRelationshipName = $arrangeRelationshipInfo['relationship'] ?? 'arrange';
        $originalArrangeChildrenRelationshipName = $arrangeRelationshipInfo['childrenRelationship'] ?? 'children';
        $arrangeModifyQueryUsing = $arrangeRelationshipInfo['modifyQueryUsing'] ?? null;
        $arrangeModifyChildrenQueryUsing = $arrangeRelationshipInfo['modifyChildrenQueryUsing'] ?? null;
        $orderColumn = $arrangeRelationshipInfo['orderColumn'] ?? null;
        $childrenOrderColumn = $arrangeRelationshipInfo['childrenOrderColumn'] ?? null;

        $arrangeRelationship = $this->getRelationship($originalArrangeRelationshipName);
        $arrangeRelationshipQuery = $arrangeRelationship->getQuery();

        if ($arrangeModifyQueryUsing) {
            $arrangeRelationshipQuery = $this->evaluate($arrangeModifyQueryUsing, [
                'query' => $arrangeRelationshipQuery,
            ]) ?? $arrangeRelationshipQuery;
        }

        if (filled($orderColumn)) {
            $arrangeRelationshipQuery->orderBy($orderColumn);
        }

        $arrangeRelationshipQuery->with([$this->getRelationshipName($originalArrangeChildrenRelationshipName) => function ($query) use ($arrangeModifyChildrenQueryUsing, $childrenOrderColumn) {
            if ($arrangeModifyChildrenQueryUsing) {
                $query = $this->evaluate($arrangeModifyChildrenQueryUsing, [
                    'query' => $query,
                ]) ?? $query;
            }

            if (filled($childrenOrderColumn)) {
                $query->orderBy($childrenOrderColumn);
            }
        }]);

        $arranges = $arrangeRelationshipQuery->get();

        return $arranges;
    }


    public function getRecursionRelationship($recursionRelationshipInfo)
    {
        $originalRecursionRelationshipName = $recursionRelationshipInfo['relationship'] ?? 'recursions';
        $recursionModifyQueryUsing = $recursionRelationshipInfo['modifyQueryUsing'] ?? null;

        $recursionRelationship = $this->getRelationship($originalRecursionRelationshipName);
        $recursionRelationshipQuery = $recursionRelationship->getQuery();

        if ($recursionModifyQueryUsing) {
            $recursionRelationshipQuery = $this->evaluate($recursionModifyQueryUsing, [
                'query' => $recursionRelationshipQuery,
            ]) ?? $recursionRelationshipQuery;
        }

        $recursions = $recursionRelationshipQuery->get();

        return $recursions;
    }


    private function saveRelationshipArrange(Arrange $component, HasForms $livewire, $arrangeRelationshipInfo, ?array $state)
    {
        $arranges = $state['arranges'] ?? [];

        $originalArrangeRelationshipName = $arrangeRelationshipInfo['relationship'] ?? 'arrange';
        $originalArrangeChildrenRelationshipName = $arrangeRelationshipInfo['childrenRelationship'] ?? 'children';
        $orderColumn = $arrangeRelationshipInfo['orderColumn'] ?? null;
        $childrenOrderColumn = $arrangeRelationshipInfo['childrenOrderColumn'] ?? null;

        $arrangeRelationship = $this->getRelationship($originalArrangeRelationshipName);        // HasMany
        $arrangegetForeignKeyName = $arrangeRelationship->getForeignKeyName();
        $arrangePk = $arrangeRelationship->getModel()->getKeyName();

        $arrangeChildrenRelationship = $this->getModelRelationship($arrangeRelationship->getModel(), $originalArrangeChildrenRelationshipName);             // HasMany
        $arrangeChildrenPk = $arrangeChildrenRelationship->getModel()->getKeyName();

        $arrangeOldIds = [];
        $arrangeChildrenOldIds = [];
        foreach ($arranges as $arrange) {
            if (isset($arrange[$arrangePk]) && $arrange[$arrangePk]) {
                $arrangeOldIds[] = $arrange[$arrangePk];
            }

            foreach ($arrange['children'] as $children) {
                if (isset($children[$arrangeChildrenPk]) && $children[$arrangeChildrenPk]) {
                    $arrangeChildrenOldIds[] = $children[$arrangeChildrenPk];
                }
            }
        }

        $oldArranges = $this->getArrangeRelationship($arrangeRelationshipInfo);

        // 遍历删除已经不存在的 arranges
        foreach ($oldArranges as $oldArrange) {
            foreach ($oldArrange['children'] as $oldChildren) {
                if (!in_array($oldChildren->$arrangeChildrenPk, $arrangeChildrenOldIds)) {
                    // 删除已经不存在的 arrangeChildren
                    $oldChildren->delete();
                }
            }

            if (!in_array($oldArrange->$arrangePk, $arrangeOldIds)) {
                // 删除已经不存在的 arrange`
                $oldArrange->delete();
            }
        }

        // 添加编辑新的 arrange
        foreach ($arranges as $arrange) {
            $arrangePkId = $arrange[$arrangePk] ?? 0;
            
            $arrangeModel = null;
            if ($arrangePkId) {
                $arrangeModel = $this->getRelationship($originalArrangeRelationshipName)->where($arrangePk, $arrangePkId)->first();
            }

            if (blank($arrangeModel)) {
                $arrangeModel = new ($arrangeRelationship->getModel());
            }

            $arrangeModel->fill([
                'name' => $arrange['name'] ?? '',
                'image' => $arrange['image'] ?? null,
                $orderColumn => $arrange[$orderColumn],
            ]);

            $arrangeRelationship->save($arrangeModel);

            foreach ($arrange['children'] as $children) {
                $arrangeChildrenPkId = $children[$arrangeChildrenPk] ?? 0;
                
                $arrangeChildrenModel = null;
                if ($arrangeChildrenPkId) {
                    $arrangeChildrenModel = $arrangeModel->{$this->getRelationshipName($originalArrangeChildrenRelationshipName)}()->where($arrangeChildrenPk, $arrangeChildrenPkId)->first();
                }

                if (blank($arrangeChildrenModel)) {
                    $arrangeChildrenModel = new ($arrangeChildrenRelationship->getModel());
                }

                $arrangeChildrenModel->fill([
                    $arrangegetForeignKeyName => $arrangeModel->{$arrangegetForeignKeyName},        // 填充 record 的外键
                    'name' => $children['name'] ?? '',
                    'image' => $children['image'] ?? null,
                    $childrenOrderColumn => $children[$childrenOrderColumn],
                ]);

                $arrangeModel->{$this->getRelationshipName($originalArrangeChildrenRelationshipName)}()->save($arrangeChildrenModel);       // 关联保存
                $arrangeChildrenPkId = $arrangeChildrenModel->$arrangeChildrenPk;

                $this->arrangeTempIdToRealId[$children['temp_id']] = $arrangeChildrenPkId;
            }
        }
    }


    private function saveRelationshipRecursion(Arrange $component, HasForms $livewire, $recursionRelationshipInfo, ?array $state)
    {
        $recursions = $state['recursions'] ?? [];

        $originalRecursionRelationshipName = $recursionRelationshipInfo['relationship'] ?? 'recursions';
        $recursionSavingUsing = $recursionRelationshipInfo['savingUsing'] ?? null;

        $recursionRelationship = $this->getRelationship($originalRecursionRelationshipName);
        $recursionPk = $recursionRelationship->getModel()->getKeyName();

        // 需要更新的 recursion ids
        $recursionOldIds = array_column($recursions, $recursionPk);
        $recursionOldIds = array_values(array_filter(array_unique($recursionOldIds)));

        $oldRecursions = $this->getRecursionRelationship($recursionRelationshipInfo);
        foreach ($oldRecursions as $oldRecursion) {
            if (!in_array($oldRecursion->$recursionPk, $recursionOldIds)) {
                // 删除已经不存在的 recursions
                $oldRecursion->delete();
            }
        }

        $record = $this->getRecord();

        foreach ($recursions as $key => $recursion) {
            $recursion[$this->getArrangeToRecursionKey()] = $this->getArrangeRealId($recursion['arrange_temp_ids']);

            if ($recursionSavingUsing) {
                // 设置自定义的 recursion 字段内容
                $recursion = $this->evaluate($recursionSavingUsing, [
                    'record' => $record,
                    'recursion' => $recursion,
                    'recursions' => $recursions,
                    'component' => $component,
                    'livewire' => $livewire,
                ]);
            }

            $recursionPkId = $recursion[$recursionPk] ?? 0;
            unset($recursion['temp_id'], $recursion['arrange_temp_ids']);

            $recursionModel = null;
            if ($recursionPkId) {
                $recursionModel = $this->getRelationship($originalRecursionRelationshipName)->where($recursionPk, $recursionPkId)->first();
            }

            if (blank($recursionModel)) {
                $recursionModel = new ($recursionRelationship->getModel());
            }

            $recursionModel->fill($recursion);

            $recursionRelationship->save($recursionModel);
        }
    }


    private function getArrangeRealId($arrangeTempIds)
    {
        $realIdsArray = [];
        foreach ($arrangeTempIds as $temp_id) {
            $realIdsArray[] = $this->arrangeTempIdToRealId[$temp_id];
        }

        sort($realIdsArray);     // 升序排列

        return $realIdsArray;
    }


    public function getOrderColumn($orderColumn): ?string
    {
        return $this->evaluate($orderColumn);
    }

    public function getRelationship($relationship): HasOneOrMany | BelongsToMany | null
    {
        if (! $this->hasRelationship($relationship)) {
            return null;
        }

        return $this->getModelInstance()->{$this->getRelationshipName($relationship)}();
    }


    public function getModelRelationship($model, $relationship)
    {
        if (! $this->hasRelationship($relationship)) {
            return null;
        }

        return $model->{$this->getRelationshipName($relationship)}();
    }


    public function hasRelationship($relationship): bool
    {
        return filled($this->getRelationshipName($relationship));
    }

    public function getRelationshipName($relationship): ?string
    {
        return $this->evaluate($relationship);
    }

    public function tableFields(array | Closure $tableFields = []): static
    {
        $this->tableFields = $tableFields;

        return $this;
    }

    public function getTableFields(): array
    {
        return (array) $this->evaluate($this->tableFields);
    }

    public function tableFieldsView(string $tableFieldsView, array $viewData = []): static
    {
        $this->tableFieldsView = $tableFieldsView;

        if ($viewData !== []) {
            $this->tableFieldsViewData($viewData);
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function tableFieldsViewData(array $data): static
    {
        $this->tableFieldsViewData = [
            ...$this->tableFieldsViewData,
            ...$data,
        ];

        return $this;
    }

    public function getTableFieldsViewData(): array
    {
        return (array) $this->tableFieldsViewData;
    }

    /**
     * @return view-string
     */
    public function getTableFieldsView(): string
    {
        if ($this->tableFieldsView) {
            return $this->tableFieldsView;
        }

        throw new SupportException('Class [' . static::class . '] but does not have a [$tableFieldsView] property defined.');
    }

    public function arrangeToRecursionKey(string | Closure $name = 'arrange_ids'): static
    {
        $this->arrangeToRecursionKey = $name;

        return $this;
    }

    public function getArrangeToRecursionKey(): ?string
    {
        return (string) $this->evaluate($this->arrangeToRecursionKey);
    }

    public function arrangePlaceholder(string | Closure | null $arrangePlaceholder): static
    {
        $this->arrangePlaceholder = $arrangePlaceholder;

        return $this;
    }

    public function getArrangePlaceholder(): ?string
    {
        return $this->evaluate($this->arrangePlaceholder);
    }

    public function arrangeChildPlaceholder(string | Closure | null $arrangeChildPlaceholder): static
    {
        $this->arrangeChildPlaceholder = $arrangeChildPlaceholder;

        return $this;
    }

    public function getArrangeChildPlaceholder(): ?string
    {
        return $this->evaluate($this->arrangeChildPlaceholder);
    }

    public function addActionLabel(string | Closure | null $label): static
    {
        $this->addActionLabel = $label;

        return $this;
    }

    public function getAddActionLabel(): string
    {
        return $this->evaluate($this->addActionLabel) ?? __('sn-support::forms.fields.arrange.actions.add_label');
    }

    public function addChildActionLabel(string | Closure | null $label): static
    {
        $this->addChildActionLabel = $label;

        return $this;
    }

    public function getChildAddActionLabel(): string
    {
        return $this->evaluate($this->addChildActionLabel) ?? __('sn-support::forms.fields.arrange.actions.add_child_label');
    }
}
