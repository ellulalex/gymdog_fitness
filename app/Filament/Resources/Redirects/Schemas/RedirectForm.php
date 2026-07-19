<?php

namespace App\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('from_path')
                    ->required()
                    ->placeholder('/about-me/')
                    ->helperText('Old path, exactly as indexed (leading slash).'),
                TextInput::make('to_path')
                    ->required()
                    ->placeholder('/about-us'),
                Select::make('status_code')
                    ->required()
                    ->default(301)
                    ->options([301 => '301 (permanent)', 302 => '302 (temporary)']),
            ]);
    }
}
