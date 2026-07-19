<?php

use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
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

// Content routes arrive in Phase 3.
Route::view('/blog', 'placeholder', ['heading' => 'Blog'])->name('blog');
