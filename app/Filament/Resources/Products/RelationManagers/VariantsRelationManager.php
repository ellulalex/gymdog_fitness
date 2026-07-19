<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\VariantOptionValue;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->helperText('e.g. "Black / M". Leave blank for single-variant products.'),
                TextInput::make('sku'),
                TextInput::make('price_cents')
                    ->label('Price (€)')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->formatStateUsing(fn (?int $state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                TextInput::make('compare_at_price_cents')
                    ->label('Compare-at price (€)')
                    ->numeric()
                    ->prefix('€')
                    ->helperText('Original price, shown struck-through when on sale.')
                    ->formatStateUsing(fn (?int $state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                    ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (int) round(((float) $state) * 100) : null),
                TextInput::make('stock_qty')
                    ->label('Stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('weight_grams')
                    ->label('Weight (g)')
                    ->numeric(),
                TextInput::make('barcode'),
                TextInput::make('position')
                    ->numeric()
                    ->default(0),
                Select::make('optionValues')
                    ->label('Option values')
                    ->relationship(
                        'optionValues',
                        'value',
                        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                            'option',
                            fn (Builder $q) => $q->where('product_id', $this->getOwnerRecord()->getKey())
                        ),
                    )
                    ->getOptionLabelFromRecordUsing(fn (VariantOptionValue $record) => "{$record->option->name}: {$record->value}")
                    ->multiple()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText('Which colour/size this variant represents. Define options on the Options tab first.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->placeholder('—')
                    ->description(fn ($record) => $record->sku),
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state) => '€'.number_format($state / 100, 2)),
                IconColumn::make('on_sale')
                    ->label('Sale')
                    ->boolean()
                    ->state(fn ($record) => $record->isOnSale()),
                TextColumn::make('stock_qty')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
