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
        Schema::table('invoice_reminders', function (Blueprint $table) {
            $table->foreignId('invoice_reminder_step_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('days_offset')->nullable()->after('reminder_type');
            $table->string('channel')->default('email')->after('days_offset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_reminders', function (Blueprint $table) {
            $table->dropForeign(['invoice_reminder_step_id']);
            $table->dropColumn(['invoice_reminder_step_id', 'days_offset', 'channel']);
        });
    }
};
