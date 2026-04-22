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
        Schema::table('quote_templates', function (Blueprint $table): void {
            $table->json('layout')->nullable()->after('terms');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->json('layout_snapshot')->nullable()->after('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropColumn('layout_snapshot');
        });

        Schema::table('quote_templates', function (Blueprint $table): void {
            $table->dropColumn('layout');
        });
    }
};
