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
        Schema::create('quote_win_probability_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('win_probability_id')->constrained('quote_win_probabilities')->onDelete('cascade');
            $table->string('key');
            $table->string('label');
            $table->decimal('probability', 5, 2)->nullable(); // 0-1
            $table->decimal('weight', 5, 2)->nullable();
            $table->integer('sample_size')->default(0);
            $table->string('direction')->default('positive');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_win_probability_signals');
    }
};
