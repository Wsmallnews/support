<?php

namespace Wsmallnews\Support\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ActionComponents
{
    public static function deleteAction(string $name = 'delete', ?string $label = null): Action
    {
        return Action::make($name)
            ->label(__('filament-actions::delete.single.label'))
            ->modalHeading(fn(): string => __('filament-actions::delete.single.modal.heading', ['label' => $label]))
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
            ->modalHeading(fn(): string => __('filament-actions::edit.single.modal.heading', ['label' => $label]))
            ->modalSubmitActionLabel(__('filament-actions::edit.single.modal.actions.save.label'))
            ->successNotificationTitle(__('filament-actions::edit.single.notifications.saved.title'))
            ->defaultColor('primary')
            ->tableIcon(Heroicon::PencilSquare)
            ->groupedIcon(Heroicon::PencilSquare);
    }
}
