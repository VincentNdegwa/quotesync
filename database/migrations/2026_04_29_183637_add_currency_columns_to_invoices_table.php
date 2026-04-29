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
            $table->string('base_currency', 3)->nullable()->after('currency');
            $table->decimal('fx_rate', 15, 6)->nullable()->after('base_currency');
            $table->decimal('base_total', 15, 2)->nullable()->after('fx_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['base_currency', 'fx_rate', 'base_total']);
        });
    }
};
