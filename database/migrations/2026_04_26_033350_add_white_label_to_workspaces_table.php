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
        Schema::table('workspaces', function (Blueprint $table) {
            $table->boolean('white_label_enabled')->default(false);
            $table->string('white_label_logo')->nullable();
            $table->string('white_label_company_name')->nullable();
            $table->string('white_label_primary_color')->nullable();
            $table->string('white_label_domain')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'white_label_enabled',
                'white_label_logo',
                'white_label_company_name',
                'white_label_primary_color',
                'white_label_domain',
            ]);
        });
    }
};
