<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect();

        $urls->push(['loc' => url('/'), 'lastmod' => null]);

        Product::active()->get()->each(fn (Product $p) => $urls->push([
            'loc' => route('product.show', $p->slug), 'lastmod' => $p->updated_at,
        ]));

        Category::all()->each(fn (Category $c) => $urls->push([
            'loc' => route('category.show', $c->slug), 'lastmod' => $c->updated_at,
        ]));

        Brand::all()->each(fn (Brand $b) => $urls->push([
            'loc' => route('brand.show', $b->slug), 'lastmod' => $b->updated_at,
        ]));

        Page::published()->get()->each(fn (Page $p) => $urls->push([
            'loc' => url('/'.$p->slug), 'lastmod' => $p->updated_at,
        ]));

        Post::published()->get()->each(fn (Post $p) => $urls->push([
            'loc' => url('/'.$p->slug), 'lastmod' => $p->updated_at,
        ]));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>';
            if (! empty($url['lastmod'])) {
                $xml .= '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>';
            }
            $xml .= "</url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
