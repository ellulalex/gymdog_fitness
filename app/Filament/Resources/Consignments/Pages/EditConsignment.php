<?php

namespace App\Filament\Resources\Consignments\Pages;

use App\Filament\Resources\Consignments\ConsignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConsignment extends EditRecord
{
    protected static string $resource = ConsignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
