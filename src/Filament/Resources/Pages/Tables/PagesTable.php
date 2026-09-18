<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Filament\Actions\ActionComponents;
use Wsmallnews\Support\Filament\Filters\FilterComponents;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('sn-support::page.table.title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('sn-support::page.table.slug'))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('composition.title')
                    ->label(__('sn-support::page.table.composition'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order_column')
                    ->label(__('sn-support::page.table.order'))
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('sn-support::page.table.status'))
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('sn-support::page.table.created_at'))
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('sn-support::page.table.updated_at'))
                    ->toggleable()
                    ->sortable(),
            ])
            ->reorderable('order_column', direction: 'asc')
            ->defaultSort('order_column', 'asc')
            ->searchPlaceholder(__('sn-support::page.table.search_placeholder'))
            ->filtersFormWidth(Width::Medium)
            ->filters([
                FilterComponents::statusFilter(PageStatus::class),
                ...FilterComponents::createUpdateRangeFilter(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ...ActionComponents::recordActions([
                    EditAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                ...ActionComponents::toolbarActions([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
