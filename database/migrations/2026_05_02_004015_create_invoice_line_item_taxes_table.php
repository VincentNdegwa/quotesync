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
        Schema::create('invoice_line_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_line_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->onDelete('set null');
            $table->string('tax_label');
            $table->decimal('tax_rate', 5, 3);
            $table->boolean('inclusive')->default(false);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('base_tax_amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_line_item_taxes');
    }
};
