<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Pdf\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class InvoiceBulkExportController extends Controller
{
    public function export(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);

        if ($invoiceIds === [] || ! is_array($invoiceIds)) {
            return response()->json(['error' => 'No invoices selected'], 400);
        }

        $workspace = $request->user()?->currentWorkspace;

        $invoices = Invoice::query()
            ->whereIn('id', $invoiceIds)
            ->where('workspace_id', $workspace?->id)
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['error' => 'No valid invoices found'], 404);
        }

        $pdfService = app(InvoicePdfService::class);
        $pdfPaths = [];

        foreach ($invoices as $invoice) {
            Gate::authorize('view', $invoice);

            if (! $invoice->pdf_url) {
                $pdfPath = $pdfService->generate($invoice);
                $invoice->pdf_url = $pdfPath;
                $invoice->save();
            }

            $pdfPaths[] = [
                'path' => $invoice->pdf_url,
                'name' => 'invoice-'.$invoice->invoice_number.'.pdf',
            ];
        }

        $zipFileName = 'invoices-export-'.now()->format('Y-m-d-His').'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Failed to create zip file'], 500);
        }

        foreach ($pdfPaths as $pdf) {
            $fullPath = Storage::disk('local')->path($pdf['path']);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $pdf['name']);
            }
        }

        $zip->close();

        return new StreamedResponse(function () use ($zipPath) {
            readfile($zipPath);
            unlink($zipPath);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$zipFileName.'"',
        ]);
    }
}
