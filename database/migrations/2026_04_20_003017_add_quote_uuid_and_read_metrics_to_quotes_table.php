<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('quote_uuid')->nullable()->after('workspace_id');
            $table->unsignedInteger('view_count')->default(0)->after('viewed_at');
            $table->unsignedInteger('time_spent_seconds')->default(0)->after('view_count');
        });

        DB::table('quotes')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($quotes): void {
                foreach ($quotes as $quote) {
                    DB::table('quotes')
                        ->where('id', $quote->id)
                        ->update(['quote_uuid' => (string) Str::uuid()]);
                }
            });

        Schema::table('quotes', function (Blueprint $table) {
            $table->unique('quote_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['quote_uuid']);
            $table->dropColumn(['quote_uuid', 'view_count', 'time_spent_seconds']);
        });
    }
};
