<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_item_price_tiers', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->nullable()->after('pricing_type');
        });

        DB::statement('
            UPDATE catalog_item_price_tiers 
            SET value = CASE 
                WHEN pricing_type = \'fixed_price\' THEN unit_price 
                WHEN pricing_type = \'discount_percent\' THEN discount_percent 
                ELSE 0 
            END
        ');

        Schema::table('catalog_item_price_tiers', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->nullable(false)->change();
            $table->dropColumn(['unit_price', 'discount_percent']);
        });
    }

    public function down(): void
    {
        Schema::table('catalog_item_price_tiers', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->after('pricing_type');
            $table->decimal('discount_percent', 5, 2)->after('unit_price');
        });

        DB::statement('
            UPDATE catalog_item_price_tiers 
            SET unit_price = CASE 
                WHEN pricing_type = \'fixed_price\' THEN value 
                ELSE 0 
            END,
            discount_percent = CASE 
                WHEN pricing_type = \'discount_percent\' THEN value 
                ELSE 0 
            END
        ');

        Schema::table('catalog_item_price_tiers', function (Blueprint $table) {
            $table->dropColumn('value');
        });
    }
};
