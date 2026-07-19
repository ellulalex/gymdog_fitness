<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every product has at least one variant, even simple ones. Uniform handling
 * avoids the "simple vs variable product" branching that plagues Woo. Money is
 * integer cents — never floats.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name')->nullable(); // e.g. "Black / M"
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('compare_at_price_cents')->nullable();
            $table->integer('stock_qty')->default(0);
            $table->unsignedInteger('weight_grams')->nullable();
            $table->string('barcode')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->index('sku');
        });

        // A variant is a combination of option values (Black + M). Many-to-many.
        Schema::create('product_variant_option_value', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_option_value_id')->constrained()->cascadeOnDelete();

            $table->primary(
                ['product_variant_id', 'variant_option_value_id'],
                'variant_option_value_primary'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_option_value');
        Schema::dropIfExists('product_variants');
    }
};
