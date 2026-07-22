<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentController extends Controller
{
    public function blog(): View
    {
        return view('storefront.blog-index', [
            'title' => 'Blog',
            'posts' => Post::published()->articles()->latestPublished()->paginate(9),
            'categories' => PostCategory::orderBy('name')->get(),
        ]);
    }

    public function category(PostCategory $postCategory): View
    {
        return view('storefront.blog-index', [
            'title' => $postCategory->name,
            'posts' => $postCategory->posts()->published()->latestPublished()->paginate(9),
            'categories' => PostCategory::orderBy('name')->get(),
        ]);
    }

    /**
     * Root-level content. Pages and posts (incl. CrossFit guides) both live at
     * /{slug}, matching the WordPress URLs. Pages win over posts on a clash.
     */
    public function show(string $slug): View
    {
        if ($page = Page::published()->where('slug', $slug)->first()) {
            return view('storefront.page', ['page' => $page]);
        }

        $post = Post::published()->where('slug', $slug)->first();

        if ($post) {
            return view('storefront.post', ['post' => $post->load('author', 'categories')]);
        }

        throw new NotFoundHttpException;
    }
}
