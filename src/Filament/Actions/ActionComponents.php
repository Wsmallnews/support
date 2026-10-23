<?php

namespace Wsmallnews\Support\Filament\Actions;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use InvalidArgumentException;
use Throwable;
use Wsmallnews\Support\Support\Utils;

class ActionComponents
{
    public static function deleteAction(string $name = 'delete', ?string $label = null): Action
    {
        return Action::make($name)
            ->label(__('filament-actions::delete.single.label'))
            ->modalHeading(fn (): string => __('filament-actions::delete.single.modal.heading', ['label' => $label]))
            ->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'))
            ->successNotificationTitle(__('filament-actions::delete.single.notifications.deleted.title'))
            ->failureNotificationTitle(__('sn-support::support.action.delete_failed'))
            ->defaultColor('danger')
            ->tableIcon(Heroicon::Trash)
            ->groupedIcon(Heroicon::Trash)
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedTrash)
            ->keyBindings(['mod+d']);
    }

    public static function editAction(string $name = 'edit', ?string $label = null): Action
    {
        return Action::make($name)
            ->label(__('filament-actions::edit.single.label'))
            ->modalHeading(fn (): string => __('filament-actions::edit.single.modal.heading', ['label' => $label]))
            ->modalSubmitActionLabel(__('filament-actions::edit.single.modal.actions.save.label'))
            ->successNotificationTitle(__('filament-actions::edit.single.notifications.saved.title'))
            ->defaultColor('primary')
            ->tableIcon(Heroicon::PencilSquare)
            ->groupedIcon(Heroicon::PencilSquare);
    }

    /**
     * Bulk action with per-record error isolation; it requires confirmation by default.
     *
     * @param  string  $name  Action name
     * @param  Closure(BulkAction, Model): void  $process  Per-record logic
     * @param  Closure(EloquentCollection<int, Model>): void|null  $prepare  Optional batch preflight (e.g. eager load); a throw fails the whole batch
     * @return BulkAction Ready for further UI chaining
     */
    public static function bulkAction(string $name, Closure $process, ?Closure $prepare = null): BulkAction
    {
        return BulkAction::make($name)
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->successNotificationTitle(__('sn-support::support.action.bulk_success'))
            ->failureNotificationTitle(function (int $successCount, int $totalCount): string {
                if ($successCount) {
                    return trans_choice('sn-support::support.action.bulk_partial_failure', $successCount, [
                        'count' => Number::format($successCount),
                        'total' => Number::format($totalCount),
                    ]);
                }

                return trans_choice('sn-support::support.action.bulk_total_failure', $totalCount, [
                    'count' => Number::format($totalCount),
                    'total' => Number::format($totalCount),
                ]);
            })
            ->action(function (BulkAction $action, EloquentCollection $records) use ($process, $prepare): void {
                static::safeBulkProcess($action, $records, $process, $prepare);
            });
    }

    /**
     * Run a bulk action record by record, catching failures per record.
     *
     * Only the first exception is reported, to avoid flooding the log.
     *
     * @param  BulkAction  $action  Action used to report failures
     * @param  EloquentCollection<int, Model>  $records  Records to process
     * @param  Closure(BulkAction, Model): void  $process  Per-record logic
     * @param  Closure(EloquentCollection<int, Model>): void|null  $prepare  Optional batch preflight; a throw fails the whole batch
     */
    public static function safeBulkProcess(BulkAction $action, EloquentCollection $records, Closure $process, ?Closure $prepare = null): void
    {
        if ($prepare) {
            try {
                $prepare($records);
            } catch (Throwable $exception) {
                $action->reportCompleteBulkProcessingFailure();

                report($exception);

                return;
            }
        }

        $isFirstException = true;

        foreach ($records as $record) {
            try {
                $process($action, $record);
            } catch (Throwable $exception) {
                $action->reportBulkProcessingFailure();

                if ($isFirstException) {
                    report($exception);

                    $isFirstException = false;
                }
            }
        }
    }

    /**
     * Wrap record actions in an ActionGroup when the configuration asks for it.
     *
     * @param  array<Action>  $actions
     * @param  array|null  $groupConfig  Per-table override, merged over the global config
     * @return array<Action|ActionGroup>
     */
    public static function recordActions(array $actions, ?array $groupConfig = null): array
    {
        $config = array_merge(
            Utils::getConfig('action_components.table_record_actions', []),
            $groupConfig ?? [],
        );

        if (($config['group'] ?? true) === false) {
            return $actions;
        }

        $group = ActionGroup::make($actions);

        match ($config['trigger'] ?? 'icon_button') {
            'button' => $group->button(),
            'link' => $group->link(),
            default => null, // icon_button is already the ActionGroup default
        };

        if (! empty($config['icon'])) {
            $group->icon($config['icon']);
        }

        if (! empty($config['label'])) {
            $group->label($config['label']);
        }

        if (! empty($config['color'])) {
            $group->color($config['color']);
        }

        if (! empty($config['size'])) {
            $group->size($config['size']);
        }

        if (! empty($config['outlined'])) {
            $group->outlined();
        }

        if (! empty($config['tooltip'])) {
            $group->tooltip($config['tooltip']);
        }

        return [$group];
    }

    /**
     * Wrap toolbar actions in a BulkActionGroup when the configuration asks for it.
     *
     * @param  array<Action>  $actions
     * @param  array|null  $groupConfig  Per-table override, merged over the global config
     * @return array<Action|BulkActionGroup>
     */
    public static function toolbarActions(array $actions, ?array $groupConfig = null): array
    {
        $config = array_merge(
            Utils::getConfig('action_components.table_toolbar_actions', []),
            $groupConfig ?? [],
        );

        if (($config['group'] ?? true) === false) {
            return $actions;
        }

        $group = BulkActionGroup::make($actions);

        match ($config['trigger'] ?? 'button') {
            'icon_button' => $group->iconButton(),
            'link' => $group->link(),
            default => null, // button is already the BulkActionGroup default
        };

        if (! empty($config['icon'])) {
            $group->icon($config['icon']);
        }

        if (! empty($config['label'])) {
            $group->label($config['label']);
        }

        if (! empty($config['color'])) {
            $group->color($config['color']);
        }

        if (! empty($config['size'])) {
            $group->size($config['size']);
        }

        if (! empty($config['outlined'])) {
            $group->outlined();
        }

        if (! empty($config['tooltip'])) {
            $group->tooltip($config['tooltip']);
        }

        return [$group];
    }

    /**
     * Two-state toggle; the enum must have exactly two cases implementing HasColor/HasIcon/HasLabel.
     * Label, icon and colour are derived from the opposite case at runtime.
     *
     * @param  string  $enumClass  Enum FQCN with HasColor, HasIcon and HasLabel
     * @param  string  $field  Model attribute
     * @param  Closure|null  $valueResolver  Extracts the current value from a record
     * @param  Closure|null  $process  Custom persistence logic
     *
     * @throws InvalidArgumentException When the enum is missing or does not have exactly two cases
     */
    public static function toggleAction(
        string $enumClass,
        string $field = 'status',
        ?Closure $valueResolver = null,
        ?Closure $process = null,
    ): Action {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("{$enumClass} is not a valid enum.");
        }

        $cases = $enumClass::cases();
        if (count($cases) !== 2) {
            throw new InvalidArgumentException(
                "toggleStatusAction requires exactly 2 enum cases, {$enumClass} has " . count($cases) . '. ' .
                'Use switchStatusAction() for enums with more than 2 cases.'
            );
        }

        $valueResolver = fn (Model $record) => $valueResolver ? $valueResolver($record) : ($record->{$field} ?? null);

        $action = Action::make($field . 'ToggleAction')
            ->label(function (Model $record) use ($cases, $valueResolver): string {
                return __('sn-support::support.action.toggle_status', [
                    'label' => (string) static::findOppositeCase($cases, $valueResolver($record))->getLabel(),
                ]);
            })
            ->icon(function (Model $record) use ($cases, $valueResolver) {
                return static::findOppositeCase($cases, $valueResolver($record))->getIcon();
            })
            ->color(function (Model $record) use ($cases, $valueResolver) {
                return static::findOppositeCase($cases, $valueResolver($record))->getColor();
            })
            ->successNotificationTitle(__('sn-support::support.action.toggle_success'))
            ->failureNotificationTitle(__('sn-support::support.action.toggle_failure'))
            ->requiresConfirmation()
            ->modalHeading(function (Model $record) use ($cases, $valueResolver): string {
                return __('sn-support::support.action.toggle_status', [
                    'label' => (string) static::findOppositeCase($cases, $valueResolver($record))->getLabel(),
                ]);
            })
            ->modalIcon(function (Model $record) use ($cases, $valueResolver) {
                return static::findOppositeCase($cases, $valueResolver($record))->getIcon();
            })
            ->modalDescription(function (Model $record) use ($cases, $valueResolver): ?string {
                return __('sn-support::support.action.toggle_status_description', [
                    'label' => (string) static::findOppositeCase($cases, $valueResolver($record))->getLabel(),
                ]);
            });

        $action->action($process ?? function (Action $action, Model $record) use ($field, $cases, $valueResolver): void {
            $targetCase = static::findOppositeCase($cases, $valueResolver($record));
            $record->update([$field => $targetCase->value]);
        });

        return $action;
    }

    /**
     * Find the case opposite to the current value; falls back to the first case on no match.
     *
     * @param  array<object>  $cases  Result of Enum::cases()
     * @param  BackedEnum  $currentValue  Current record value
     * @return object The opposite case
     */
    private static function findOppositeCase(array $cases, BackedEnum $currentValue): BackedEnum
    {
        foreach ($cases as $case) {
            if ($case !== $currentValue) {
                return $case;
            }
        }

        return $cases[0];
    }

    /**
     * Multi-state switch: a modal lists every other case and applies the chosen one.
     *
     * @param  string  $enumClass  Enum FQCN with HasColor, HasIcon and HasLabel
     * @param  string  $field  Model attribute
     * @param  string | Htmlable | Closure | null  $label  Label, defaults to sn-support::support.action.switch_status
     * @param  Closure|null  $valueResolver  fn(Model $record): BackedEnum, defaults to $record->{$field}
     * @param  Closure|null  $process  fn(Model $record, array $data): void, defaults to updating $field
     *
     * @throws InvalidArgumentException When the enum is missing
     */
    public static function switchAction(
        string $enumClass,
        string $field = 'status',
        string | Htmlable | Closure | null $label = null,
        ?Closure $valueResolver = null,
        ?Closure $process = null,
    ): Action {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("{$enumClass} is not a valid enum.");
        }

        $valueResolver = fn (Model $record) => $valueResolver ? $valueResolver($record) : ($record->{$field} ?? null);
        $label = $label ?? __('sn-support::support.action.switch_status');

        $action = Action::make($field . 'SwitchAction')
            ->label($label)
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('primary')
            ->successNotificationTitle(__('sn-support::support.action.switch_success'))
            ->failureNotificationTitle(__('sn-support::support.action.switch_failure'))
            ->schema(function (Model $record) use ($enumClass, $field, $label, $valueResolver): array {
                $currentValue = $valueResolver($record);

                return [
                    ToggleButtons::make($field)
                        ->label($label)
                        ->options($enumClass)
                        ->disableOptionWhen(fn ($value): bool => $value === $currentValue->value)
                        ->required()->inline()->grouped(),
                ];
            })
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading($label);

        $action->action($process ?? function (Model $record, array $data) use ($field): void {
            $record->update([$field => $data[$field]]);
        });

        return $action;
    }
}
