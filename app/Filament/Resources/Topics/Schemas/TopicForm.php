<?php

namespace App\Filament\Resources\Topics\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('angle')
                    ->rows(2)
                    ->columnSpanFull()
                    ->helperText('Optional steer for the generator.'),
                Select::make('status')
                    ->required()
                    ->default('queued')
                    ->options([
                        'queued' => 'Queued',
                        'used' => 'Used',
                        'skipped' => 'Skipped',
                    ]),
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower is picked first.'),
            ]);
    }
}
