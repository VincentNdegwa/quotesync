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
        Schema::table('credit_note_line_items', function (Blueprint $table) {
            $table->decimal('base_unit_price', 15, 2)->nullable()->after('unit_price');
            $table->decimal('base_subtotal', 15, 2)->nullable()->after('subtotal');
            $table->decimal('base_tax_amount', 15, 2)->nullable()->after('tax_amount');
            $table->decimal('base_total', 15, 2)->nullable()->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_note_line_items', function (Blueprint $table) {
            $table->dropColumn(['base_unit_price', 'base_subtotal', 'base_tax_amount', 'base_total']);
        });
    }
};
