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
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade')->after('invoice_id');
            $table->string('credit_note_number')->nullable()->after('created_by');
            $table->decimal('tax_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('total', 10, 2)->nullable()->after('tax_amount');
            $table->date('issue_date')->nullable()->after('reason');
            $table->date('due_date')->nullable()->after('issue_date');
            $table->string('number')->nullable()->change();
            $table->foreignId('invoice_id')->nullable()->change();
            $table->text('reason')->nullable()->change();
            $table->date('credit_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'credit_note_number', 'tax_amount', 'total', 'issue_date', 'due_date']);
            $table->string('number')->nullable(false)->change();
            $table->foreignId('invoice_id')->nullable(false)->change();
            $table->text('reason')->nullable(false)->change();
            $table->date('credit_date')->nullable(false)->change();
        });
    }
};
