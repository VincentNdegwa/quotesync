<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
                $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->string('number');
                $table->string('title');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('USD');
                $table->text('reason');
                $table->date('credit_date');
                $table->string('status')->default('draft');
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->string('pdf_url')->nullable();
                $table->timestamps();

                $table->unique(['workspace_id', 'number']);
                $table->index(['workspace_id', 'status']);
                $table->index(['invoice_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
