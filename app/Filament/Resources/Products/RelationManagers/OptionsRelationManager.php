<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Defines the option types for a product (Colour, Size) and their values.
 * Variants are then tagged with these values on the Variants tab.
 */
class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Options';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->helperText('e.g. Colour or Size'),
                TextInput::make('position')
                    ->numeric()
                    ->default(0),
                Repeater::make('values')
                    ->relationship()
                    ->label('Values')
                    ->columns(3)
                    ->schema([
                        TextInput::make('value')
                            ->required()
                            ->columnSpan(1),
                        ColorPicker::make('swatch')
                            ->helperText('Colours only')
                            ->columnSpan(1),
                        TextInput::make('position')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1),
                    ])
                    ->orderColumn('position')
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('values.value')
                    ->label('Values')
                    ->badge()
                    ->separator(','),
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
