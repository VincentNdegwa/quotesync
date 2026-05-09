<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('invoice_uuid')->nullable()->after('workspace_id');
        });

        DB::table('invoices')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($invoices): void {
                foreach ($invoices as $invoice) {
                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update(['invoice_uuid' => (string) Str::uuid()]);
                }
            });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('invoice_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_uuid']);
            $table->dropColumn('invoice_uuid');
        });
    }
};
