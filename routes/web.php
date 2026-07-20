<?php

use App\Http\Controllers\Content\RejectGeneratedPostController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\CheckoutConfirmationController;
use App\Http\Controllers\Storefront\ContentController;
use App\Http\Controllers\Storefront\CustomerAuthController;
use App\Http\Controllers\Storefront\CustomerPasswordController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Controllers\Storefront\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Catalogue storefront. URL patterns are preserved from the WordPress site
// (docs/site-audit.md) so existing SEO carries over.
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/shop/{parent:slug}/{category:slug}', [ShopController::class, 'subcategory'])->name('subcategory.show');
Route::get('/shop/{category:slug}', [ShopController::class, 'category'])->name('category.show');
Route::get('/product-brands/{brand:slug}', [ShopController::class, 'brand'])->name('brand.show');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::view('/cart', 'storefront.cart')->name('cart');
Route::view('/wishlist', 'storefront.wishlist')->name('wishlist');
Route::view('/search', 'storefront.search-page')->name('search');

// Customer accounts (storefront 'customer' guard — separate from staff).
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [CustomerAuthController::class, 'login']);
Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
Route::get('/account', [AccountController::class, 'dashboard'])->name('account')->middleware('auth:customer');

// Password reset (customers).
Route::get('/forgot-password', [CustomerPasswordController::class, 'request'])->name('password.request');
Route::post('/forgot-password', [CustomerPasswordController::class, 'email'])->name('password.email');
Route::get('/reset-password/{token}', [CustomerPasswordController::class, 'reset'])->name('password.reset');
Route::post('/reset-password', [CustomerPasswordController::class, 'update'])->name('password.update');
Route::view('/checkout', 'storefront.checkout-page')->name('checkout');
Route::get('/checkout/confirmation', CheckoutConfirmationController::class)->name('checkout.confirmation');

// Stripe webhooks are the source of truth for payment state (spec §7).
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

// One-click reject for a scheduled AI-generated post (signed link from the
// review email — the signature is the authorization).
Route::get('/content/{post}/reject', RejectGeneratedPostController::class)
    ->middleware('signed')
    ->name('content.reject');

// Content.
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/blog', [ContentController::class, 'blog'])->name('blog');
Route::get('/category/{postCategory:slug}', [ContentController::class, 'category'])->name('post-category.show');

// Root-level pages & posts — registered LAST so it never shadows the routes
// above; constrained to a single segment that isn't a reserved app prefix.
Route::get('/{slug}', [ContentController::class, 'show'])
    ->where('slug', '^(?!admin|livewire|up|storage|api|sitemap\.xml|shop|cart|checkout|product|product-brands|category|blog|stripe|search|login|register|logout|account|wishlist|horizon|forgot-password|reset-password)[A-Za-z0-9\-]+$')
    ->name('content.show');
