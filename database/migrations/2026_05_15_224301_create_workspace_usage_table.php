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
        Schema::create('workspace_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->date('period')->index();
            $table->integer('quotes_sent')->default(0);
            $table->integer('invoices_sent')->default(0);
            $table->integer('ai_credits_used')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_usages');
    }
};
