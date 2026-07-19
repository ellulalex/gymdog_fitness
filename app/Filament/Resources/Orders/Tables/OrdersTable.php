<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'refunded', 'partially_refunded' => 'gray',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('fulfilment_status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'fulfilled' ? 'success' : 'warning'),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state): string => '€'.number_format($state / 100, 2))
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
