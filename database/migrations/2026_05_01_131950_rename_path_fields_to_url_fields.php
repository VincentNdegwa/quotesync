<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->renameColumn('logo_path', 'logo_url');
            $table->renameColumn('favicon_path', 'favicon_url');
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->renameColumn('image_path', 'image_url');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->renameColumn('signature_path', 'signature_url');
            $table->renameColumn('pdf_path', 'pdf_url');
        });

        // Convert existing paths to URLs
        $this->convertWorkspacePathsToUrls();
        $this->convertCatalogItemPathsToUrls();
        $this->convertQuotePathsToUrls();
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->renameColumn('logo_url', 'logo_path');
            $table->renameColumn('favicon_url', 'favicon_path');
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->renameColumn('image_url', 'image_path');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->renameColumn('signature_url', 'signature_path');
            $table->renameColumn('pdf_url', 'pdf_path');
        });
    }

    private function convertWorkspacePathsToUrls(): void
    {
        \DB::table('workspaces')->whereNotNull('logo_url')->get()->each(function ($workspace) {
            if (!str_starts_with($workspace->logo_url, 'http')) {
                \DB::table('workspaces')
                    ->where('id', $workspace->id)
                    ->update(['logo_url' => Storage::url($workspace->logo_url)]);
            }
        });

        \DB::table('workspaces')->whereNotNull('favicon_url')->get()->each(function ($workspace) {
            if (!str_starts_with($workspace->favicon_url, 'http')) {
                \DB::table('workspaces')
                    ->where('id', $workspace->id)
                    ->update(['favicon_url' => Storage::url($workspace->favicon_url)]);
            }
        });
    }

    private function convertCatalogItemPathsToUrls(): void
    {
        \DB::table('catalog_items')->whereNotNull('image_url')->get()->each(function ($item) {
            if (!str_starts_with($item->image_url, 'http')) {
                \DB::table('catalog_items')
                    ->where('id', $item->id)
                    ->update(['image_url' => Storage::url($item->image_url)]);
            }
        });
    }

    private function convertQuotePathsToUrls(): void
    {
        \DB::table('quotes')->whereNotNull('signature_url')->get()->each(function ($quote) {
            if (!str_starts_with($quote->signature_url, 'http')) {
                \DB::table('quotes')
                    ->where('id', $quote->id)
                    ->update(['signature_url' => Storage::url($quote->signature_url)]);
            }
        });

        \DB::table('quotes')->whereNotNull('pdf_url')->get()->each(function ($quote) {
            if (!str_starts_with($quote->pdf_url, 'http')) {
                \DB::table('quotes')
                    ->where('id', $quote->id)
                    ->update(['pdf_url' => Storage::url($quote->pdf_url)]);
            }
        });
    }
};
