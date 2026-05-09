<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\Pdf\QuotePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class QuoteBulkExportController extends Controller
{
    public function export(Request $request)
    {
        $quoteIds = $request->input('quote_ids', []);

        if (empty($quoteIds)) {
            return response()->json(['error' => 'No quotes selected'], 400);
        }

        $workspace = $request->user()?->currentWorkspace;

        $quotes = Quote::query()
            ->whereIn('id', $quoteIds)
            ->where('workspace_id', $workspace?->id)
            ->get();

        if ($quotes->isEmpty()) {
            return response()->json(['error' => 'No valid quotes found'], 404);
        }

        $pdfPaths = [];
        $pdfService = app(QuotePdfService::class);

        foreach ($quotes as $quote) {
            Gate::authorize('view', $quote);

            if (! $quote->pdf_url) {
                $pdfPath = $pdfService->generate($quote);
                $quote->pdf_url = $pdfPath;
                $quote->save();
            }

            $pdfPaths[] = [
                'path' => $quote->pdf_url,
                'name' => "quote-{$quote->number}.pdf",
            ];
        }

        $zipFileName = 'quotes-export-'.now()->format('Y-m-d-His').'.zip';
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
