<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'from_path', 'to_path', 'status_code'];
}
