<?php

namespace App\Livewire\Storefront;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Basic DB-LIKE search across products and content. Meilisearch can slot in
 * behind this later (spec §3) without changing the UI.
 */
class Search extends Component
{
    #[Url]
    public string $q = '';

    public function render(): View
    {
        $term = trim($this->q);
        $products = collect();
        $content = collect();

        if (mb_strlen($term) >= 2) {
            $products = Product::active()
                ->with(['brand', 'variants'])
                ->where(function (Builder $query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$term}%"));
                })
                ->limit(24)
                ->get();

            $content = Post::published()->where('title', 'like', "%{$term}%")->get()
                ->map(fn (Post $p) => [
                    'title' => $p->title,
                    'url' => url('/'.$p->slug),
                    'kind' => $p->type === 'guide' ? 'Guide' : 'Article',
                ])
                ->merge(
                    Page::published()->where('title', 'like', "%{$term}%")->get()
                        ->map(fn (Page $p) => ['title' => $p->title, 'url' => url('/'.$p->slug), 'kind' => 'Page'])
                );
        }

        return view('storefront.search', [
            'term' => $term,
            'products' => $products,
            'content' => $content,
        ]);
    }
}
