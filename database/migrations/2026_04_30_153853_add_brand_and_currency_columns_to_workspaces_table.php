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
        Schema::table('workspaces', function (Blueprint $table) {
            // Drop old duplicate white_label columns
            if (Schema::hasColumn('workspaces', 'white_label_enabled')) {
                $table->dropColumn('white_label_enabled');
            }
            if (Schema::hasColumn('workspaces', 'white_label_logo')) {
                $table->dropColumn('white_label_logo');
            }
            if (Schema::hasColumn('workspaces', 'white_label_company_name')) {
                $table->dropColumn('white_label_company_name');
            }
            if (Schema::hasColumn('workspaces', 'white_label_primary_color')) {
                $table->dropColumn('white_label_primary_color');
            }
            if (Schema::hasColumn('workspaces', 'white_label_domain')) {
                $table->dropColumn('white_label_domain');
            }

            // Add new columns
            if (! Schema::hasColumn('workspaces', 'logo_path')) {
                $table->string('logo_path')->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'primary_color')) {
                $table->string('primary_color', 7)->default('#2563EB');
            }
            if (! Schema::hasColumn('workspaces', 'accent_color')) {
                $table->string('accent_color', 7)->default('#F59E0B');
            }
            if (! Schema::hasColumn('workspaces', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'website')) {
                $table->string('website')->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'country')) {
                $table->char('country', 2)->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'tax_number')) {
                $table->string('tax_number')->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'currency')) {
                $table->char('currency', 3)->default('USD');
            }
            if (! Schema::hasColumn('workspaces', 'white_label_mode')) {
                $table->boolean('white_label_mode')->default(false);
            }
            if (! Schema::hasColumn('workspaces', 'favicon_path')) {
                $table->string('favicon_path', 512)->nullable();
            }
            if (! Schema::hasColumn('workspaces', 'custom_domain')) {
                $table->string('custom_domain')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $columnsToDrop = [
                'logo_path',
                'primary_color',
                'accent_color',
                'address',
                'phone',
                'email',
                'website',
                'country',
                'tax_number',
                'currency',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('workspaces', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
