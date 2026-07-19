<?php

namespace App\Filament\Resources\Consignments\Pages;

use App\Filament\Resources\Consignments\ConsignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsignments extends ListRecords
{
    protected static string $resource = ConsignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
