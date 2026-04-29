<?php

use App\Jobs\GenerateQuotePdf;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use App\Services\Pdf\QuotePdfService;
use Illuminate\Support\Facades\Queue;

test('pdf can be generated for a quote', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'title' => 'Test Quote',
        'status' => 'draft',
        'currency' => 'USD',
        'total' => 1000,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'discount_amount' => 0,
    ]);

    $pdfService = app(QuotePdfService::class);
    $job = new GenerateQuotePdf($quote);
    $job->handle($pdfService);

    expect($quote->pdf_path)->not->toBeNull();
    expect($quote->pdf_path)->toContain('.pdf');
});

test('pdf generation job can be dispatched', function () {
    Queue::fake();

    $user = User::factory()->create(['email_verified_at' => now()]);
    $workspace = $user->currentWorkspace;
    $client = Client::factory()->for($workspace, 'workspace')->create();

    $quote = Quote::query()->create([
        'workspace_id' => $workspace->id,
        'client_id' => $client->id,
        'title' => 'Test Quote',
        'status' => 'draft',
        'currency' => 'USD',
        'total' => 1000,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'discount_amount' => 0,
    ]);

    GenerateQuotePdf::dispatch($quote);

    Queue::assertPushed(GenerateQuotePdf::class);
});
