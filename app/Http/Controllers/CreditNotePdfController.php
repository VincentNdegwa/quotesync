<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateCreditNotePdf;
use App\Models\CreditNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CreditNotePdfController extends Controller
{
    public function generate(Request $request, CreditNote $creditNote)
    {
        Gate::authorize('view', $creditNote);

        if (! $creditNote->pdf_url) {
            GenerateCreditNotePdf::dispatchSync($creditNote);
            $creditNote->refresh();
        }

        if (! $creditNote->pdf_url) {
            return response()->json([
                'message' => 'PDF generation is in progress. Please try again shortly.',
                'status' => 'pending',
            ], 202);
        }

        $downloadUrl = route('credit-notes.pdf.download', $creditNote);

        return response()->json([
            'url' => $downloadUrl,
            'status' => 'ready',
        ]);
    }

    public function download(Request $request, CreditNote $creditNote)
    {
        Gate::authorize('view', $creditNote);

        if (! $creditNote->pdf_url) {
            abort(404, 'PDF not yet generated');
        }

        $file = Storage::disk('local')->get($creditNote->pdf_url);

        return response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="credit-note-'.$creditNote->credit_note_number.'.pdf"',
        ]);
    }
}
