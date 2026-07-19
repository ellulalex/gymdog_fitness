<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
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
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Lives at /{slug}'),
                        Select::make('type')
                            ->required()
                            ->default('post')
                            ->options(['post' => 'Article', 'guide' => 'CrossFit guide']),
                        Select::make('author_id')
                            ->label('Author')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload(),
                        Textarea::make('excerpt')
                            ->rows(2)
                            ->columnSpanFull(),
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
