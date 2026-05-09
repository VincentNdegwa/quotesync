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
        Schema::create('invoice_reminder_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_reminder_sequence_id')->constrained()->onDelete('cascade');
            $table->integer('day_offset'); // -3 = 3 days before, 0 = on due, 7 = 7 days after
            $table->string('channel')->default('email'); // email, future: whatsapp, sms
            $table->string('reminder_type'); // before_due, on_due, after_due
            $table->string('subject');
            $table->text('message_template');
            $table->boolean('send_automatically')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_reminder_steps');
    }
};
