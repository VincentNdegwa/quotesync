<?php

use App\Enums\TrackingEventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->string('event_type', 30)->default(TrackingEventType::View->value);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('section_name', 100)->nullable();
            $table->unsignedInteger('scroll_depth_percent')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['quote_id', 'event_type']);
            $table->index(['quote_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_tracking_events');
    }
};
