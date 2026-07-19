<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The platform's root table. Every tenant-owned table carries a `tenant_id`
 * referencing this one. See App\Support\Tenancy\BelongsToTenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('domain')->nullable()->unique();
            $table->json('settings')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->string('plan')->default('free');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
