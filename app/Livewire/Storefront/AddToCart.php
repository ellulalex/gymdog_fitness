<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use App\Support\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddToCart extends Component
{
    public Product $product;

    public bool $added = false;

    public function add(?int $variantId, int $qty = 1): void
    {
        $this->added = false;

        $variant = $variantId
            ? $this->product->variants()->find($variantId)
            : $this->product->variants()->first();

        if (! $variant || $variant->stock_qty < 1) {
            return;
        }

        app(CartManager::class)->current()->add($variant, max(1, $qty));

        $this->added = true;
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $this->product->load(['brand', 'categories', 'options.values', 'variants.optionValues.option']);

        $variantsData = $this->product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'price' => $v->price_cents,
            'compare' => $v->compare_at_price_cents,
            'stock' => $v->stock_qty,
            'options' => $v->optionValues->mapWithKeys(fn ($ov) => [$ov->option->name => $ov->value]),
        ])->values();

        return view('storefront.add-to-cart', [
            'variantsData' => $variantsData,
        ]);
    }
}
