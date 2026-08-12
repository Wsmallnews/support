<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks\Schemas;

use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Phiki\Grammar\Grammar;
use Wsmallnews\Support\Helpers\FilamentModelHelper;

class ScheduledTaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Scheduled Task Details')
                    ->tabs([
                        Tab::make('Overview')
                            ->label(__('sn-support::scheduled_task.infolist.tab.overview'))
                            ->icon(Heroicon::InformationCircle)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Group::make([
                                            TextEntry::make('id')
                                                ->label('ID'),

                                            TextEntry::make('action')
                                                ->label(__('sn-support::scheduled_task.infolist.entry.action'))
                                                ->badge(),

                                            TextEntry::make('status')
                                                ->label(__('sn-support::scheduled_task.infolist.entry.status'))
                                                ->badge(),

                                            TextEntry::make('scheduled_at')
                                                ->label(__('sn-support::scheduled_task.infolist.entry.scheduled_at'))
                                                ->dateTime(),

                                            TextEntry::make('executed_at')
                                                ->label(__('sn-support::scheduled_task.infolist.entry.executed_at'))
                                                ->placeholder(__('sn-support::scheduled_task.infolist.entry.no_executed'))
                                                ->dateTime(),
                                        ]),

                                        Group::make([
                                            TextEntry::make('schedulable')
                                                ->label(__('sn-support::scheduled_task.infolist.entry.schedulable'))
                                                ->getStateUsing(fn ($record) => FilamentModelHelper::getTitle($record->schedulable))
                                                ->url(function ($record) {
                                                    return FilamentModelHelper::getUrl($record->schedulable);
                                                }),

                                            TextEntry::make('created_at')
                                                ->label(__('sn-support::scheduled_task.infolist.entry.created_at'))
                                                ->dateTime(),

                                            TextEntry::make('updated_at')
                                                ->label(__('sn-support::scheduled_task.infolist.entry.updated_at'))
                                                ->dateTime(),
                                        ]),
                                    ]),

                                TextEntry::make('result')
                                    ->label(__('sn-support::scheduled_task.infolist.entry.result'))
                                    ->placeholder(__('sn-support::scheduled_task.infolist.entry.no_result'))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Payload')
                            ->label(__('sn-support::scheduled_task.infolist.tab.payload'))
                            ->icon(Heroicon::CodeBracket)
                            ->schema([
                                KeyValueEntry::make('payload')
                                    ->label(__('sn-support::scheduled_task.infolist.entry.payload'))
                                    ->keyLabel(__('sn-support::scheduled_task.infolist.entry.key'))
                                    ->valueLabel(__('sn-support::scheduled_task.infolist.entry.value'))
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn ($record) => $record->payload),

                        Tab::make('Raw Data')
                            ->label(__('sn-support::scheduled_task.infolist.tab.raw_data'))
                            ->icon(Heroicon::CommandLine)
                            ->schema([
                                CodeEntry::make('payload')
                                    ->label(__('sn-support::scheduled_task.infolist.entry.payload'))
                                    ->jsonFlags(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                                    ->copyable()
                                    ->grammar(Grammar::Json)
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn ($record) => $record->payload),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
