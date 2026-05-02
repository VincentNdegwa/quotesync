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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('base_subtotal', 15, 2)->nullable()->after('fx_rate');
            $table->decimal('base_discount_amount', 15, 2)->nullable()->after('base_subtotal');
            $table->decimal('base_tax_amount', 15, 2)->nullable()->after('base_discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['base_subtotal', 'base_discount_amount', 'base_tax_amount']);
        });
    }
};
