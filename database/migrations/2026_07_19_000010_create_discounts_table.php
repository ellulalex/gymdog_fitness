<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('type')->default('percentage'); // percentage | fixed
            $table->unsignedInteger('value');               // percent, or cents for fixed
            $table->unsignedInteger('min_subtotal_cents')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('active');    // active | disabled
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_code')->nullable()->after('discount_cents');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('discount_code');
        });
        Schema::dropIfExists('discounts');
    }
};
