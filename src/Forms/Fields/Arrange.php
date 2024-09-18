<?php

namespace Wsmallnews\Support\Forms\Fields;

use Closure;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Field;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
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

        $this->saveRelationshipsUsing(static function (Arrange $component, HasForms $livewire, ?array $state) {
            // 保存关系
        });

        return $this;
    }

    private function getArrangeRelationship($arrangeRelationshipInfo)
    {
        $arrangeRelationshipName = $this->getRelationshipName($arrangeRelationshipInfo['relationship'] ?? 'arrange');
        $arrangeChildrenRelationshipName = $this->getRelationshipName($arrangeRelationshipInfo['childrenRelationship'] ?? 'arrangeChildren');
        $arrangeModifyQueryUsing = $arrangeRelationshipInfo['modifyQueryUsing'] ?? null;
        $arrangeModifyChildrenQueryUsing = $arrangeRelationshipInfo['modifyChildrenQueryUsing'] ?? null;
        $orderColumn = $arrangeRelationshipInfo['orderColumn'] ?? null;
        $childrenOrderColumn = $arrangeRelationshipInfo['childrenOrderColumn'] ?? null;

        $arrangeRelationship = $this->getRelationship($arrangeRelationshipName);
        $arrangeRelationshipQuery = $arrangeRelationship->getQuery();

        if ($arrangeRelationship instanceof BelongsToMany) {        // @sn todo 这个里面的啥意思
            $arrangeRelationshipQuery->select([
                $arrangeRelationship->getTable() . '.*',
                $arrangeRelationshipQuery->getModel()->getTable() . '.*',
            ]);
        }

        if ($arrangeModifyQueryUsing) {
            $arrangeRelationshipQuery = $this->evaluate($arrangeModifyQueryUsing, [
                'query' => $arrangeRelationshipQuery,
            ]) ?? $arrangeRelationshipQuery;
        }

        if (filled($orderColumn)) {
            $arrangeRelationshipQuery->orderBy($orderColumn);
        }

        $arrangeRelationshipQuery->with([$arrangeChildrenRelationshipName => function ($query) use ($arrangeModifyChildrenQueryUsing, $childrenOrderColumn) {
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
        $recursionRelationshipName = $this->getRelationshipName($recursionRelationshipInfo['relationship'] ?? 'recursions');
        $recursionModifyQueryUsing = $recursionRelationshipInfo['modifyQueryUsing'] ?? null;

        $recursionRelationship = $this->getRelationship($recursionRelationshipName);
        $recursionRelationshipQuery = $recursionRelationship->getQuery();

        if ($recursionRelationship instanceof BelongsToMany) {        // @sn todo 这个里面的啥意思
            $recursionRelationshipQuery->select([
                $recursionRelationship->getTable() . '.*',
                $recursionRelationshipQuery->getModel()->getTable() . '.*',
            ]);
        }

        if ($recursionModifyQueryUsing) {
            $recursionRelationshipQuery = $this->evaluate($recursionModifyQueryUsing, [
                'query' => $recursionRelationshipQuery,
            ]) ?? $recursionRelationshipQuery;
        }

        $recursions = $recursionRelationshipQuery->get();

        return $recursions;
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
