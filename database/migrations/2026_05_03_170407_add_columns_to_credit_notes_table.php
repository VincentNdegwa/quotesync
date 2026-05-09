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
            if (!Schema::hasColumn('credit_notes', 'type')) {
                $table->string('type')->after('title')->default('partial');
            }
            if (!Schema::hasColumn('credit_notes', 'fx_rate')) {
                $table->decimal('fx_rate', 15, 6)->default(1)->after('pdf_url');
            }
            if (!Schema::hasColumn('credit_notes', 'base_amount')) {
                $table->decimal('base_amount', 15, 2)->nullable()->after('fx_rate');
            }
            if (!Schema::hasColumn('credit_notes', 'base_total')) {
                $table->decimal('base_total', 15, 2)->nullable()->after('base_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $columnsToDrop = ['type', 'fx_rate', 'base_amount', 'base_total'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('credit_notes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
