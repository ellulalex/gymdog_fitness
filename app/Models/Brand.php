<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'slug', 'name', 'website'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
