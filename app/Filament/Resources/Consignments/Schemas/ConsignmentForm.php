<?php

namespace App\Filament\Resources\Consignments\Schemas;

use App\Models\ProductVariant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConsignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_variant_id')
                    ->label('Product variant')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn () => ProductVariant::with('product')->get()
                        ->mapWithKeys(fn (ProductVariant $v) => [
                            $v->id => trim($v->product?->name.' — '.($v->name ?: 'default')),
                        ]))
                    ->helperText('Adding a consignment adds its quantity to the variant’s stock.'),
                TextInput::make('unit_cost_cents')
                    ->label('Unit cost (€)')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->formatStateUsing(fn (?int $state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                DatePicker::make('received_at')
                    ->required()
                    ->default(now()),
                TextInput::make('reference')
                    ->label('Reference / invoice')
                    ->helperText('Optional — supplier invoice or batch label.'),
                TextInput::make('supplier'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
