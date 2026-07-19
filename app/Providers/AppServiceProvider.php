<?php

namespace App\Providers;

use App\Domain\Content\ClaudeContentGenerator;
use App\Domain\Content\ContentGenerator;
use App\Domain\Payments\PaymentGateway;
use App\Domain\Payments\StripeGateway;
use App\Support\Cart\CartManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CartManager::class);
        $this->app->bind(PaymentGateway::class, StripeGateway::class);
        $this->app->bind(ContentGenerator::class, ClaudeContentGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
