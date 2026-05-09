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
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->nullable()->after('discount_percent');
            $table->decimal('tax_amount', 15, 2)->nullable()->after('subtotal');
            $table->text('notes')->nullable()->after('total');
            $table->boolean('is_optional')->default(false)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_amount', 'notes', 'is_optional']);
        });
    }
};
