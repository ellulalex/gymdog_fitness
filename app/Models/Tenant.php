<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Tenant extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'domain',
        'settings',
        'stripe_account_id',
        'plan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * Read a value from the tenant's settings JSON using dot notation,
     * falling back to $default. Templates and the domain layer read tenant
     * configuration through here rather than hardcoding gymdog anywhere.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }
}
