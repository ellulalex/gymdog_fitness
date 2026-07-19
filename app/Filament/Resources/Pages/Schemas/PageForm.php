<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Lives at /{slug}'),
                        RichEditor::make('body')
                            ->columnSpanFull(),
                    ]),
                Section::make('Publishing')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->required()
                            ->default('draft')
                            ->options(['draft' => 'Draft', 'published' => 'Published']),
                        DateTimePicker::make('published_at'),
                    ]),
                Section::make('SEO')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta_title'),
                        Textarea::make('meta_description')->columnSpanFull(),
                    ]),
            ]);
    }
}
