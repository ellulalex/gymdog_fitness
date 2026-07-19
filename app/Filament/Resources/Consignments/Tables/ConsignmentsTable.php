<?php

namespace App\Filament\Resources\Consignments\Tables;

use App\Models\Consignment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('received_at', 'desc')
            ->columns([
                TextColumn::make('received_at')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('variant.product.name')
                    ->label('Product')
                    ->description(fn (Consignment $r) => $r->variant?->name)
                    ->searchable(),
                TextColumn::make('unit_cost_cents')
                    ->label('Unit cost')
                    ->formatStateUsing(fn (int $state) => '€'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qty'),
                TextColumn::make('quantity_remaining')
                    ->label('Remaining')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('reference')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('supplier')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
