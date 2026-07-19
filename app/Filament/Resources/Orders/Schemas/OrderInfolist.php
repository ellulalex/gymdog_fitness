<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (?int $state): string => '€'.number_format((int) $state / 100, 2);

        return $schema
            ->components([
                Section::make('Order')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('number'),
                        TextEntry::make('email'),
                        TextEntry::make('placed_at')->dateTime('d M Y, H:i')->placeholder('—'),
                        TextEntry::make('payment_status')->badge()
                            ->color(fn (string $state) => match ($state) {
                                'paid' => 'success',
                                'refunded', 'partially_refunded' => 'gray',
                                'failed' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('fulfilment_status')->badge(),
                        TextEntry::make('stripe_payment_intent_id')->label('Stripe intent')->placeholder('—')->copyable(),
                    ]),

                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('name_snapshot')->label('Item')->columnSpan(2),
                                TextEntry::make('qty')->label('Qty'),
                                TextEntry::make('total_cents')->label('Total')->formatStateUsing($money),
                            ]),
                    ]),

                Section::make('Totals')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('subtotal_cents')->label('Subtotal')->formatStateUsing($money),
                        TextEntry::make('discount_cents')->label('Discount')->formatStateUsing($money),
                        TextEntry::make('shipping_cents')->label('Shipping')->formatStateUsing($money),
                        TextEntry::make('total_cents')->label('Total')->formatStateUsing($money)->weight('bold'),
                        TextEntry::make('tax_cents')->label('of which VAT')->formatStateUsing($money),
                        TextEntry::make('cogs')->label('Cost of goods')
                            ->state(fn (Order $record) => $money((int) $record->lines->sum('cost_cents'))),
                        TextEntry::make('margin')->label('Margin (ex-VAT, ex-shipping)')
                            ->state(fn (Order $record) => $money(
                                $record->subtotal_cents - $record->discount_cents - (int) $record->lines->sum('cost_cents')
                            ))->weight('bold'),
                    ]),

                Section::make('Shipping address')
                    ->schema([
                        TextEntry::make('shipping_address')
                            ->hiddenLabel()
                            ->placeholder('—')
                            ->state(fn (Order $record): string => collect($record->shipping_address ?? [])
                                ->filter()
                                ->join(', ') ?: '—'),
                    ]),
            ]);
    }
}
