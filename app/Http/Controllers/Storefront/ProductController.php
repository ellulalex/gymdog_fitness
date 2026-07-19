<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        // Only active, published products are publicly visible.
        if ($product->status !== 'active' || ($product->published_at && $product->published_at->isFuture())) {
            throw new NotFoundHttpException;
        }

        $product->load(['brand', 'categories', 'options.values', 'variants.optionValues.option']);

        $variantsData = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'price' => $v->price_cents,
            'compare' => $v->compare_at_price_cents,
            'stock' => $v->stock_qty,
            'options' => $v->optionValues->mapWithKeys(fn ($ov) => [$ov->option->name => $ov->value]),
        ])->values();

        return view('storefront.product', [
            'product' => $product,
            'variantsData' => $variantsData,
        ]);
    }
}
