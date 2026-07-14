<?php

namespace Wsmallnews\Support\Filament\Actions;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
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
     * 创建带自动错误处理的自定义批量操作。
     *
     * 自动通过 safeBulkProcess() 包裹 per-record 业务逻辑，
     * 调用方只需关注单条记录的处理，链式设置 label/icon/color/modal 等 UI 属性即可。
     *
     * 默认设置：->requiresConfirmation()
     *
     * @param  string  $name  批量操作名称
     * @param  \Closure(BulkAction, Model): void  $process  单条记录的业务逻辑
     * @param  \Closure(Collection|EloquentCollection): void|null  $prepare  可选：批量预处理（如 eager load）
     * @return BulkAction 可继续链式设置 UI 属性
     */
    public static function bulkAction(string $name, \Closure $process, ?\Closure $prepare = null): BulkAction
    {
        return BulkAction::make($name)
            ->requiresConfirmation()
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
            ->action(function (BulkAction $action, Collection | EloquentCollection $records) use ($process, $prepare): void {
                static::safeBulkProcess($action, $records, $process, $prepare);
            });
    }

    /**
     * 安全执行批量操作，自动处理 try/catch 和失败上报。
     *
     * - 遍历 $records，每条记录包裹 try/catch，单条失败不影响整批
     * - 异常时自动调用 $action->reportBulkProcessingFailure() 更新计数器
     * - 仅上报第一条异常的 stack trace，避免日志洪水
     * - $process 闭包中可手动调用 $action->reportBulkProcessingFailure() 处理非异常失败
     *
     * @param  BulkAction  $action  批量操作实例（用于调用 reportBulkProcessingFailure 等）
     * @param  Collection|EloquentCollection  $records  待处理的记录集合
     * @param  \Closure(BulkAction, Model): void  $process  单条记录的业务逻辑
     * @param  \Closure(Collection|EloquentCollection): void|null  $prepare  可选：批量预处理（如 eager load），异常导致整批标记失败
     */
    public static function safeBulkProcess(BulkAction $action, Collection | EloquentCollection $records, \Closure $process, ?\Closure $prepare = null): void
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
     * 根据配置，条件性地将 record actions 包裹在 ActionGroup 中。
     *
     * 当配置 'action_components.table_record_actions.group' 为 true 时，
     * actions 被包裹在 ActionGroup::make() 中；为 false 时原样返回。
     *
     * @param  array<Action>  $actions
     * @param  array|null  $groupConfig  单表覆盖配置，会与全局配置合并
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
            default => null, // icon_button 已是 ActionGroup 默认值
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
     * 根据配置，条件性地将 toolbar actions 包裹在 BulkActionGroup 中。
     *
     * 当配置 'action_components.table_toolbar_actions.group' 为 true 时，
     * actions 被包裹在 BulkActionGroup::make() 中；为 false 时原样返回。
     *
     * @param  array<Action>  $actions
     * @param  array|null  $groupConfig  单表覆盖配置，会与全局配置合并
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
            default => null, // button 已是 BulkActionGroup 默认值
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
}
