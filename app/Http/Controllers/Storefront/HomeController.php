<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'featured' => Product::active()->with(['brand', 'variants'])->latest()->take(8)->get(),
            'articles' => Post::published()->articles()->latestPublished()->take(3)->get(),
            'promo' => Discount::where('status', 'active')->orderBy('id')->first(),
        ]);
    }
}
