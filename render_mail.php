<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mail = new App\Mail\QuoteSentMail(
    subjectLine: 'Your quote',
    messageBody: 'Hello world',
    companyName: 'Test Company',
    logoUrl: null,
    quoteNumber: 'Q-123',
    quoteTitle: 'Test Quote',
    quoteTotal: '100.00 USD',
    validUntil: '2026-05-01',
    coverMessage: 'Please find attached',
    lineItems: [
        ['name' => 'Item 1', 'quantity' => '1', 'total' => '100.00']
    ],
    viewUrl: 'http://localhost/quote',
    unsubscribeUrl: 'http://localhost/unsubscribe',
);

echo $mail->render();
