<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $items = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->with('category')
            ->get();

        $headers = ['Name', 'SKU', 'Unit', 'Unit Price', 'Cost Price', 'Category', 'Active'];

        return response()->streamDownload(function () use ($items, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($items as $item) {
                fputcsv($file, [
                    $item->name,
                    $item->sku,
                    $item->unit,
                    $item->unit_price,
                    $item->cost_price,
                    $item->category?->name,
                    $item->is_active ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        }, 'catalog-export.csv');
    }

    public function exportSelected(Request $request): StreamedResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        $items = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $ids)
            ->with('category')
            ->get();

        $headers = ['Name', 'SKU', 'Unit', 'Unit Price', 'Cost Price', 'Category', 'Active'];

        return response()->streamDownload(function () use ($items, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($items as $item) {
                fputcsv($file, [
                    $item->name,
                    $item->sku,
                    $item->unit,
                    $item->unit_price,
                    $item->cost_price,
                    $item->category?->name,
                    $item->is_active ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        }, 'catalog-selected-export.csv');
    }
}
