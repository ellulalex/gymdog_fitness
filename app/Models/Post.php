<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'author_id', 'type', 'slug', 'title', 'excerpt', 'body',
        'featured_image', 'featured_image_credit',
        'status', 'published_at', 'source', 'generation_meta',
        'meta_title', 'meta_description', 'focus_keyword', 'faq',
    ];

    protected function casts(): array
    {
        return [
            'generation_meta' => 'array',
            'faq' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PostCategory::class, 'post_category_post');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * Newest first, but a published post with no published_at falls back to its
     * created_at instead of sorting to the very end (NULLs last in DESC).
     */
    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->orderByRaw('COALESCE(published_at, created_at) DESC');
    }

    public function scopeArticles(Builder $query): Builder
    {
        return $query->where('type', 'post');
    }

    public function scopeGuides(Builder $query): Builder
    {
        return $query->where('type', 'guide');
    }
}
