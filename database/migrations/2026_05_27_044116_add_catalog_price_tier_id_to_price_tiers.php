<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_tiers', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_price_tier_id')->nullable()->after('id');
            $table->foreign('catalog_price_tier_id')->references('id')->on('catalog_item_price_tiers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('price_tiers', function (Blueprint $table) {
            $table->dropForeign(['catalog_price_tier_id']);
            $table->dropColumn('catalog_price_tier_id');
        });
    }
};
