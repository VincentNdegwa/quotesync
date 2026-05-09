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
        Schema::table('quote_line_item_taxes', function (Blueprint $table) {
            $table->decimal('tax_amount', 15, 2)->default(0)->after('inclusive');
            $table->decimal('base_tax_amount', 15, 2)->default(0)->after('tax_amount');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('base_subtotal', 15, 2)->default(0)->after('base_total');
            $table->decimal('base_discount_amount', 15, 2)->default(0)->after('base_subtotal');
            $table->decimal('base_tax_amount', 15, 2)->default(0)->after('base_discount_amount');
        });

        Schema::table('quote_line_items', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_line_item_taxes', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'base_tax_amount']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['base_subtotal', 'base_discount_amount', 'base_tax_amount']);
        });

        Schema::table('quote_line_items', function (Blueprint $table) {
            $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
        });
    }
};
