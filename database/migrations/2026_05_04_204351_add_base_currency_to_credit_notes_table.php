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
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->string('base_currency', 3)->nullable()->after('currency');
            $table->decimal('base_tax_amount', 15, 2)->nullable()->after('tax_amount');
            $table->renameColumn('amount', 'subtotal');
            $table->renameColumn('base_amount', 'base_subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropColumn(['base_currency', 'base_tax_amount']);
            $table->renameColumn('subtotal', 'amount');
            $table->renameColumn('base_subtotal', 'base_amount');
        });
    }
};
