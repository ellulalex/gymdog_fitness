<?php

namespace Tests\Fixtures;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * A throwaway tenant-owned model used to exercise the BelongsToTenant trait
 * before any real tenant-owned tables (products, orders) exist. The backing
 * table is created and dropped by the tenancy test.
 */
class Widget extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public $timestamps = false;
}
