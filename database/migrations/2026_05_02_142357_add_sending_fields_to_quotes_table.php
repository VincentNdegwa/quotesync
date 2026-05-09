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
        Schema::table('quotes', function (Blueprint $table) {
            $table->datetime('scheduled_at')->nullable()->after('sent_at');
            $table->datetime('delivered_at')->nullable()->after('scheduled_at');
            $table->datetime('bounced_at')->nullable()->after('delivered_at');
            $table->json('cc_recipients')->nullable()->after('bounced_at');
            $table->json('bcc_recipients')->nullable()->after('cc_recipients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'delivered_at', 'bounced_at', 'cc_recipients', 'bcc_recipients']);
        });
    }
};
