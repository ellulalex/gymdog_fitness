<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'generated' ? 'info' : 'gray')
                    ->searchable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'published' => 'Published',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        'generated' => 'AI-generated',
                        'human' => 'Human',
                    ]),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Post $record): bool => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->modalHeading('Publish this post?')
                    ->action(fn (Post $record) => $record->publishNow()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('publish')
                    ->label('Publish selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publish the selected posts?')
                    ->modalDescription('Anything already published is left untouched.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $published = $records->reject(fn (Post $post) => $post->status === 'published');

                        $published->each(fn (Post $post) => $post->publishNow());

                        Notification::make()
                            ->success()
                            ->title($published->isEmpty()
                                ? 'Nothing to publish — those posts are already live.'
                                : $published->count().' post(s) published.')
                            ->send();
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
