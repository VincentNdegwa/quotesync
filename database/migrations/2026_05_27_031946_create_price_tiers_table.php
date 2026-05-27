<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('priceable_id');
            $table->string('priceable_type'); // 'quote_line_item' or 'invoice_line_item'
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedInteger('min_quantity');
            $table->integer('max_quantity')->default(-1); // -1 means unlimited
            $table->string('pricing_type'); // 'fixed_price' or 'discount_percent'
            $table->decimal('value', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['priceable_id', 'priceable_type']);
            $table->foreign('variant_id')->references('id')->on('catalog_item_variants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
