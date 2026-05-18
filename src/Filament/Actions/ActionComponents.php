<?php

namespace Wsmallnews\Support\Filament\Actions;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class ActionComponents
{
    public static function deleteAction(string $name = 'delete'): Action
    {
        return Action::make($name)
            ->label(__('filament-actions::delete.single.label'))
            ->modalHeading(fn(): string => __('filament-actions::delete.single.modal.heading', ['label' => 'aaabbb']))
            ->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'))
            ->successNotificationTitle(__('filament-actions::delete.single.notifications.deleted.title'))
            ->defaultColor('danger')
            ->tableIcon(Heroicon::Trash)
            ->groupedIcon(Heroicon::Trash)
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedTrash)
            ->keyBindings(['mod+d']);
    }
}
