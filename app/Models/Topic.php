<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = ['tenant_id', 'title', 'angle', 'status', 'position', 'used_at'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->where('status', 'queued')->orderBy('position')->orderBy('id');
    }

    public function markUsed(): void
    {
        $this->update(['status' => 'used', 'used_at' => now()]);
    }
}
