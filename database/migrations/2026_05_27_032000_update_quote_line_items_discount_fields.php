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
        Schema::table('quote_line_items', function (Blueprint $table) {
            // Remove old discount_percent column
            $table->dropColumn('discount_percent');

            // Add new discount columns
            $table->string('discount_type')->nullable()->after('unit_price'); // 'percent' or 'fixed'
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_line_items', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn(['discount_type', 'discount_value']);

            // Restore old column
            $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_price');
        });
    }
};
