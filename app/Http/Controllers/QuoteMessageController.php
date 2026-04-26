<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\Quotes\QuoteMessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;

class QuoteMessageController extends Controller
{
    public function __construct(
        private QuoteMessageService $messageService,
    ) {}

    public function index(Request $request, Quote $quote): JsonResponse
    {
        $this->authorize('view', $quote);

        $messages = $this->messageService->getMessagesForQuote($quote);

        return response()->json($messages);
    }

    public function indexFromPortal(Request $request, string $uuid): JsonResponse
    {
        $quote = Quote::where('quote_uuid', $uuid)->firstOrFail();

        $messages = $this->messageService->getMessagesForQuote($quote);

        return response()->json($messages);
    }

    public function store(Request $request, Quote $quote): JsonResponse
    {
        $this->authorize('view', $quote);

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = $this->messageService->createMessage($quote, $request->message, 'user');

        $message->load(['sender:id,name', 'portalUser:id,name']);

        return response()->json($message, 201);
    }

    public function storeFromPortal(Request $request, string $uuid): JsonResponse
    {
        $quote = Quote::where('quote_uuid', $uuid)->firstOrFail();

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = $this->messageService->createMessage($quote, $request->message, 'portal_user');

        $message->load(['sender:id,name', 'portalUser:id,name']);

        return response()->json($message, 201);
    }
}
