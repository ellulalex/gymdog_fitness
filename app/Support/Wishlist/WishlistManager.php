<?php

namespace App\Support\Wishlist;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * A lightweight guest wishlist kept in the session — a list of product ids.
 * Mirrors the cart's session approach; becomes customer-scoped when accounts
 * arrive.
 */
class WishlistManager
{
    /** @return int[] */
    public function ids(): array
    {
        return Session::get('wishlist', []);
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function toggle(int $productId): void
    {
        $ids = $this->ids();

        $ids = $this->has($productId)
            ? array_values(array_diff($ids, [$productId]))
            : [...$ids, $productId];

        Session::put('wishlist', $ids);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /** @return Collection<int,Product> */
    public function products(): Collection
    {
        if ($this->ids() === []) {
            return collect();
        }

        return Product::query()->with('brand', 'variants')->whereIn('id', $this->ids())->get();
    }
}
