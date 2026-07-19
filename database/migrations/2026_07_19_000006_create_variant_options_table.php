<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product option types (e.g. "Colour", "Size") and their possible values.
 * A variant is one combination of these values (see the pivot migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('variant_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_option_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('swatch')->nullable(); // hex colour for colour swatches
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('variant_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_option_values');
        Schema::dropIfExists('variant_options');
    }
};
