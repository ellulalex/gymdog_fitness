<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory batches. Each consignment is a delivery of a variant at a specific
 * unit cost, so cost of goods is tracked per batch (it changes between
 * deliveries). Sales draw down the oldest batch first (FIFO), recording the
 * true cost on the order line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();  // supplier invoice / PO / batch label
            $table->string('supplier')->nullable();
            $table->unsignedInteger('unit_cost_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('quantity_remaining');
            $table->date('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'received_at']);
        });

        // COGS snapshot for margin reporting — the cost of the units sold on
        // this line, drawn from consignments at the moment of sale.
        Schema::table('order_lines', function (Blueprint $table) {
            $table->unsignedInteger('cost_cents')->nullable()->after('total_cents');
        });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn('cost_cents');
        });
        Schema::dropIfExists('consignments');
    }
};
