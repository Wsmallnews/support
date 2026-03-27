<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Wsmallnews\Support\Enums\ActivityLogEvent;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns\ActivityLogFormat;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Activity Details')
                    ->tabs([
                        Tab::make('Overview')
                            ->label(__('filament-activity-log::activity.infolist.tab.overview'))
                            ->icon(Heroicon::InformationCircle)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Group::make([
                                            TextEntry::make('event')
                                                ->badge()
                                                ->label(__('filament-activity-log::activity.infolist.entry.event'))
                                                ->formatStateUsing(fn ($state) => ucfirst($state))
                                                ->color(fn ($state) => ActivityLogEvent::tryFrom($state)?->getColor() ?? 'gray')
                                                ->icon(fn ($state) => ActivityLogEvent::tryFrom($state)?->getIcon()),

                                            TextEntry::make('created_at')
                                                ->label(__('filament-activity-log::activity.infolist.entry.created_at'))
                                                ->dateTime(),
                                        ]),

                                        Group::make([
                                            TextEntry::make('causer.name')
                                                ->label(__('filament-activity-log::activity.infolist.entry.causer'))
                                                ->getStateUsing(fn ($record) => $record->causer?->name ?? 'System')
                                                ->url(function ($record) {
                                                    return ActivityLogFormat::getUrl($record->causer);
                                                }),

                                            TextEntry::make('subject')
                                                ->label(__('filament-activity-log::activity.infolist.entry.subject'))
                                                ->getStateUsing(fn ($record) => ActivityLogFormat::getTitle($record->subject))
                                                ->url(function ($record) {
                                                    return ActivityLogFormat::getUrl($record->subject);
                                                }),

                                            TextEntry::make('properties.ip_address')
                                                ->label(__('filament-activity-log::activity.infolist.entry.ip_address')),

                                            TextEntry::make('properties.user_agent')
                                                ->label(__('filament-activity-log::activity.infolist.entry.browser')),
                                        ]),
                                    ]),

                                TextEntry::make('description')
                                    ->label(__('filament-activity-log::activity.infolist.entry.description'))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Changes')
                            ->label(__('filament-activity-log::activity.infolist.tab.changes'))
                            ->icon(Heroicon::ArrowsRightLeft)
                            ->schema([
                                KeyValueEntry::make('properties.attributes')
                                    ->label(__('filament-activity-log::activity.infolist.entry.attributes'))
                                    ->keyLabel(__('filament-activity-log::activity.infolist.entry.key'))
                                    ->valueLabel(__('filament-activity-log::activity.infolist.entry.value')),

                                KeyValueEntry::make('properties.old')
                                    ->label(__('filament-activity-log::activity.infolist.entry.old'))
                                    ->keyLabel(__('filament-activity-log::activity.infolist.entry.key'))
                                    ->valueLabel(__('filament-activity-log::activity.infolist.entry.value')),
                            ]),

                        Tab::make('Raw Data')
                            ->label(__('filament-activity-log::activity.infolist.tab.raw_data'))
                            ->icon(Heroicon::CodeBracket)
                            ->schema([
                                CodeEntry::make('properties')
                                    ->label(__('filament-activity-log::activity.infolist.entry.properties'))
                                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
