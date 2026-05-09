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
        Schema::table('quotes', function (Blueprint $table) {
            $table->timestamp('won_at')->nullable()->after('declined_at');
            $table->timestamp('lost_at')->nullable()->after('won_at');
            $table->string('base_currency', 3)->nullable()->after('currency');
            $table->decimal('fx_rate', 15, 6)->nullable()->after('base_currency');
            $table->decimal('base_total', 15, 2)->nullable()->after('fx_rate');

            $table->index('won_at');
            $table->index('lost_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['won_at', 'lost_at', 'base_currency', 'fx_rate', 'base_total']);
        });
    }
};
