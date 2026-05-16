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
        Schema::table('quote_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('quote_messages', 'is_internal')) {
                $table->boolean('is_internal')->default(false)->after('message');
            }
            if (! Schema::hasColumn('quote_messages', 'attachments')) {
                $table->json('attachments')->nullable()->after('is_internal');
            }
            // typing_status removed - handled by Laravel Reverb/websockets
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_messages', function (Blueprint $table) {
            $columns = ['is_internal', 'attachments'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('quote_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
