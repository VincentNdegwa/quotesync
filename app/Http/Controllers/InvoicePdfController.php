<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateInvoicePdf;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class InvoicePdfController extends Controller
{
    public function generate(Request $request, Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        if (! $invoice->pdf_url) {
            GenerateInvoicePdf::dispatchSync($invoice);
            $invoice->refresh();
        }

        if (! $invoice->pdf_url) {
            return response()->json([
                'message' => 'PDF generation is in progress. Please try again shortly.',
                'status' => 'pending',
            ], 202);
        }

        $downloadUrl = route('invoices.pdf.download', $invoice);

        return response()->json([
            'url' => $downloadUrl,
            'status' => 'ready',
        ]);
    }

    public function download(Request $request, Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        if (! $invoice->pdf_url) {
            abort(404, 'PDF not yet generated');
        }

        $file = Storage::disk('local')->get($invoice->pdf_url);

        return response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$invoice->invoice_number.'.pdf"',
        ]);
    }
}
