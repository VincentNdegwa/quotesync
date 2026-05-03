<?php

namespace App\Services\Pdf;

use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CreditNotePdfService
{
    public function generate(CreditNote $creditNote): string
    {
        $creditNote->load(['client', 'invoice', 'workspace.owner']);

        $html = $this->renderHtml($creditNote);

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $filename = "credit-notes/{$creditNote->id}_{$creditNote->credit_note_number}.pdf";

        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }

    protected function renderHtml(CreditNote $creditNote): string
    {
        return view('pdf.credit-notes.index', [
            'creditNote' => $creditNote,
        ])->render();
    }
}
