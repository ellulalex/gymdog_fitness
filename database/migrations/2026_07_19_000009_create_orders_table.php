<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->foreignId('customer_id')->nullable();
            $table->string('email');

            $table->string('status')->default('pending');            // pending | completed | cancelled
            $table->string('payment_status')->default('unpaid');     // unpaid | paid | refunded | partially_refunded | failed
            $table->string('fulfilment_status')->default('unfulfilled'); // unfulfilled | fulfilled

            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('discount_cents')->default(0);
            $table->unsignedInteger('shipping_cents')->default(0);
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);

            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'number']);
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot everything: an order must render correctly forever, even
            // if the product is later edited or deleted (spec §5).
            $table->string('name_snapshot');
            $table->string('sku_snapshot')->nullable();
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('qty');
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->timestamps();
        });

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // reject duplicate webhook deliveries
            $table->string('type');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
