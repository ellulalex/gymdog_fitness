<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PostCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'slug', 'name'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_category_post');
    }
}
