<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuotePdf;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class QuotePdfController extends Controller
{
    public function generate(Request $request, Quote $quote)
    {
        Gate::authorize('view', $quote);

        if (!$quote->pdf_path) {
            GenerateQuotePdf::dispatch($quote);
            
            return response()->json([
                'message' => 'PDF generation started',
                'status' => 'pending',
            ], 202);
        }

        $downloadUrl = route('quotes.pdf.download', $quote);

        return response()->json([
            'url' => $downloadUrl,
            'status' => 'ready',
        ]);
    }

    public function download(Request $request, Quote $quote)
    {
        Gate::authorize('view', $quote);

        if (!$quote->pdf_path) {
            abort(404, 'PDF not yet generated');
        }

        $file = Storage::disk('local')->get($quote->pdf_path);
        
        return response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="quote-' . $quote->number . '.pdf"',
        ]);
    }
}
