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
        Schema::create('workspace_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('group');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('cast', 30)->default('string');
            $table->boolean('encrypted')->default(false);
            $table->timestamps();

            $table->unique(['workspace_id', 'group', 'key'], 'workspace_settings_unique_scope');
            $table->index(['workspace_id', 'group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_settings');
    }
};
