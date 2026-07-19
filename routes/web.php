<?php

use App\Http\Controllers\Storefront\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Placeholder storefront routes — fleshed out in later phases (catalogue in
// Phase 1, content in Phase 3). Present now so the themed nav does not 404.
Route::view('/shop', 'placeholder', ['heading' => 'Shop'])->name('shop');
Route::view('/blog', 'placeholder', ['heading' => 'Blog'])->name('blog');
