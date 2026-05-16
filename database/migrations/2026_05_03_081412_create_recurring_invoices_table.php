<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recurring_invoices')) {
            Schema::create('recurring_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
                $table->foreignId('client_id')->constrained()->onDelete('cascade');
                $table->foreignId('template_id')->nullable()->constrained('invoice_templates')->onDelete('set null');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->string('title');
                $table->string('frequency');
                $table->integer('interval')->default(1);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->date('next_invoice_date');
                $table->string('status')->default('active');
                $table->decimal('base_amount', 10, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['workspace_id', 'status']);
                $table->index(['next_invoice_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
