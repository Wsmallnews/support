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
use Illuminate\Support\Arr;
use Phiki\Grammar\Grammar;
use Wsmallnews\Support\Enums\ActivityLogEvent;
use Wsmallnews\Support\Helpers\FilamentModelHelper;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Activity Details')
                    ->tabs([
                        Tab::make('Overview')
                            ->label(__('sn-support::activity.infolist.tab.overview'))
                            ->icon(Heroicon::InformationCircle)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Group::make([
                                            TextEntry::make('event')
                                                ->badge()
                                                ->label(__('sn-support::activity.infolist.entry.event'))
                                            // ->formatStateUsing(fn ($state) => ucfirst($state))
                                            // ->color(fn ($state) => ActivityLogEvent::tryFrom($state)?->getColor() ?? 'gray')
                                            // ->icon(fn ($state) => ActivityLogEvent::tryFrom($state)?->getIcon())
                                            ,

                                            TextEntry::make('created_at')
                                                ->label(__('sn-support::activity.infolist.entry.created_at'))
                                                ->dateTime(),
                                        ]),

                                        Group::make([
                                            TextEntry::make('causer.name')
                                                ->label(__('sn-support::activity.infolist.entry.causer'))
                                                ->getStateUsing(fn ($record) => $record->causer?->name ?? 'System')
                                                ->url(function ($record) {
                                                    return FilamentModelHelper::getUrl($record->causer);
                                                }),

                                            TextEntry::make('subject')
                                                ->label(__('sn-support::activity.infolist.entry.subject'))
                                                ->getStateUsing(fn ($record) => FilamentModelHelper::getTitle($record->subject))
                                                ->url(function ($record) {
                                                    return FilamentModelHelper::getUrl($record->subject);
                                                }),

                                            TextEntry::make('properties.ip_address')
                                                ->label(__('sn-support::activity.infolist.entry.ip_address')),

                                            TextEntry::make('properties.user_agent')
                                                ->label(__('sn-support::activity.infolist.entry.browser')),
                                        ]),
                                    ]),

                                TextEntry::make('description')
                                    ->label(__('sn-support::activity.infolist.entry.description'))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Changes')
                            ->label(__('sn-support::activity.infolist.tab.changes'))
                            ->icon(Heroicon::ArrowsRightLeft)
                            ->schema([
                                KeyValueEntry::make('attribute_changes.attributes')
                                    ->label(__('sn-support::activity.infolist.entry.attributes'))
                                    ->keyLabel(__('sn-support::activity.infolist.entry.key'))
                                    ->valueLabel(__('sn-support::activity.infolist.entry.value'))
                                    ->state(fn ($record) => Arr::dot($record->attribute_changes->get('attributes', []))),

                                KeyValueEntry::make('attribute_changes.old')
                                    ->label(__('sn-support::activity.infolist.entry.old'))
                                    ->keyLabel(__('sn-support::activity.infolist.entry.key'))
                                    ->valueLabel(__('sn-support::activity.infolist.entry.value'))
                                    ->state(fn ($record) => Arr::dot($record->attribute_changes->get('old', []))),
                            ]),

                        Tab::make('Raw Data')
                            ->label(__('sn-support::activity.infolist.tab.raw_data'))
                            ->icon(Heroicon::CodeBracket)
                            ->schema([
                                CodeEntry::make('attribute_changes')
                                    ->label(__('sn-support::activity.infolist.entry.attribute_changes'))
                                    ->jsonFlags(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                                    ->copyable()
                                    ->grammar(Grammar::Json)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
