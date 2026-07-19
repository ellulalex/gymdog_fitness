<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Payments\PaymentGateway;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refund')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('This refunds the full amount to the customer via Stripe.')
                ->visible(fn (Order $record): bool => $record->payment_status === 'paid')
                ->action(function (Order $record): void {
                    app(PaymentGateway::class)->refund($record);
                    $record->update(['payment_status' => 'refunded']);

                    Notification::make()->title('Refund issued')->success()->send();
                }),
        ];
    }
}
