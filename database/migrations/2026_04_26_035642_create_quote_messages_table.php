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
        Schema::create('quote_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('portal_user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('message');
            $table->string('sender_type')->default('user'); // 'user' or 'portal_user'
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            
            $table->index(['quote_id', 'created_at']);
            $table->index('sender_id');
            $table->index('portal_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_messages');
    }
};
