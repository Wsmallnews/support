<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Tables;

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
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Facades\CompositionRegistry;
use Wsmallnews\Support\Filament\Actions\ActionComponents;
use Wsmallnews\Support\Filament\Filters\FilterComponents;

class CompositionsTable
{
    public static function configure(Table $table, ?string $moduleId = null): Table
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
                    ->label(__('sn-support::composition.table.title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->label(__('sn-support::composition.table.purpose'))
                    ->badge()
                    // null = 通用展示编排（可被 Page 绑定），灰色弱化
                    ->placeholder(__('sn-support::composition.table.purpose_generic'))
                    ->color(fn ($state): string => filled($state) ? 'primary' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order_column')
                    ->label(__('sn-support::composition.table.order'))
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('sn-support::composition.table.status'))
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('sn-support::composition.table.created_at'))
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('sn-support::composition.table.updated_at'))
                    ->toggleable()
                    ->sortable(),
            ])
            ->reorderable('order_column', direction: 'desc')
            ->defaultSort('order_column', 'desc')
            ->searchPlaceholder(__('sn-support::composition.table.search_placeholder'))
            ->filtersFormWidth(Width::Medium)
            ->filters([
                FilterComponents::statusFilter(CompositionStatus::class),
                Tables\Filters\SelectFilter::make('purpose')
                    ->label(__('sn-support::composition.table.purpose'))
                    ->options(array_merge([
                        '__generic__' => __('sn-support::composition.table.purpose_generic'),
                    ], filled($moduleId) ? CompositionRegistry::getPurposes($moduleId)->toArray() : []))
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === '__generic__') {
                            return $query->whereNull('purpose');
                        }

                        return filled($value) ? $query->where('purpose', $value) : $query;
                    }),
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
