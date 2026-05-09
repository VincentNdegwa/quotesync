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
        Schema::table('catalog_item_price_tiers', function (Blueprint $table) {
            $table->enum('pricing_type', ['fixed_price', 'discount_percent'])->default('fixed_price')->after('max_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_item_price_tiers', function (Blueprint $table) {
            $table->dropColumn('pricing_type');
        });
    }
};
