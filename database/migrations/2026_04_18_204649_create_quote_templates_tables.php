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
        Schema::create('quote_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('industry', 120)->nullable();
            $table->text('cover_message')->nullable();
            $table->text('notes')->nullable();
            $table->longText('terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'is_active']);
            $table->index(['workspace_id', 'name']);
        });

        Schema::create('quote_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_template_id')->constrained('quote_templates')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quote_template_id', 'sort_order']);
        });

        Schema::create('quote_template_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_template_id')->constrained('quote_templates')->cascadeOnDelete();
            $table->foreignId('quote_template_section_id')->constrained('quote_template_sections')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->nullable()->constrained('catalog_items')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_optional')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quote_template_id', 'sort_order']);
            $table->index(['quote_template_section_id', 'sort_order']);
        });

        Schema::create('quote_template_line_item_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_template_line_item_id')->constrained('quote_template_line_items')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('tax_label', 120);
            $table->decimal('tax_rate', 6, 3)->default(0);
            $table->timestamps();

            $table->index('quote_template_line_item_id');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreign('template_id')->references('id')->on('quote_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropForeign(['template_id']);
        });

        Schema::dropIfExists('quote_template_line_item_taxes');
        Schema::dropIfExists('quote_template_line_items');
        Schema::dropIfExists('quote_template_sections');
        Schema::dropIfExists('quote_templates');
    }
};
