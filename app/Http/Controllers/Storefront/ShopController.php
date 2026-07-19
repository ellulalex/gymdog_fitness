<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Contracts\View\View;

class ShopController extends Controller
{
    public function index(): View
    {
        return view('storefront.shop', [
            'title' => "Malta's CrossFit shop",
        ]);
    }

    public function category(Category $category): View
    {
        return view('storefront.shop', [
            'title' => $category->name,
            'crumb' => $category->name,
            'categorySlug' => $category->slug,
        ]);
    }

    /** Two-segment /shop/{parent}/{category}. The final slug identifies it. */
    public function subcategory(Category $parent, Category $category): View
    {
        return view('storefront.shop', [
            'title' => $category->name,
            'crumb' => $category->name,
            'categorySlug' => $category->slug,
        ]);
    }

    public function brand(Brand $brand): View
    {
        return view('storefront.shop', [
            'title' => $brand->name,
            'crumb' => $brand->name,
            'brandSlug' => $brand->slug,
        ]);
    }
}
