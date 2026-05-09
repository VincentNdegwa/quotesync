<?php

use App\Enums\FollowUpChannel;
use App\Enums\QuoteFollowUpStatus;
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
        Schema::create('follow_up_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['workspace_id', 'is_default']);
        });

        Schema::create('follow_up_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follow_up_sequence_id')->constrained('follow_up_sequences')->cascadeOnDelete();
            $table->unsignedInteger('day_offset')->default(0);
            $table->string('channel', 20)->default(FollowUpChannel::Email->value);
            $table->string('subject', 255)->nullable();
            $table->text('message_template');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['follow_up_sequence_id', 'sort_order']);
            $table->index(['channel']);
        });

        Schema::create('quote_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('follow_up_step_id')->constrained('follow_up_steps')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('status', 20)->default(QuoteFollowUpStatus::Pending->value);
            $table->timestamps();

            $table->unique(['quote_id', 'follow_up_step_id']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['quote_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_follow_ups');
        Schema::dropIfExists('follow_up_steps');
        Schema::dropIfExists('follow_up_sequences');
    }
};
