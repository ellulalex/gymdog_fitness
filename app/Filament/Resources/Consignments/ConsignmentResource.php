<?php

namespace App\Filament\Resources\Consignments;

use App\Filament\Resources\Consignments\Pages\CreateConsignment;
use App\Filament\Resources\Consignments\Pages\EditConsignment;
use App\Filament\Resources\Consignments\Pages\ListConsignments;
use App\Filament\Resources\Consignments\Schemas\ConsignmentForm;
use App\Filament\Resources\Consignments\Tables\ConsignmentsTable;
use App\Models\Consignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConsignmentResource extends Resource
{
    protected static ?string $model = Consignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ConsignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsignments::route('/'),
            'create' => CreateConsignment::route('/create'),
            'edit' => EditConsignment::route('/{record}/edit'),
        ];
    }
}
