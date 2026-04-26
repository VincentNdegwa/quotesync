<?php

namespace App\Http\Controllers;

use App\Enums\TrackingEventType;
use App\Models\Quote;
use App\Models\QuoteTrackingEvent;
use App\Services\Quotes\QuoteShortCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteTrackingController extends Controller
{
    public function store(string $quoteUuid, Request $request, QuoteShortCodeService $quoteShortCodeService): JsonResponse
    {
        $quote = $quoteShortCodeService->resolveQuoteByIdentifier($quoteUuid);

        abort_unless($quote instanceof Quote, 404);

        // Handle FormData from sendBeacon (events comes as JSON string)
        $events = $request->input('events');
        if (is_string($events)) {
            $decoded = json_decode($events, true);
            if (!is_array($decoded)) {
                return response()->json(['error' => 'Invalid events format'], 422);
            }
            $request->merge(['events' => $decoded]);
        }

        $validated = $request->validate([
            'events' => ['required', 'array', 'max:50'],
            'events.*.event_type' => ['required', 'string', 'in:' . implode(',', TrackingEventType::values())],
            'events.*.duration_seconds' => ['sometimes', 'integer', 'min:0'],
            'events.*.section_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'events.*.scroll_depth_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'events.*.occurred_at' => ['sometimes', 'date'],
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent();

        foreach ($validated['events'] as $event) {
            QuoteTrackingEvent::query()->create([
                'quote_id' => $quote->id,
                'event_type' => $event['event_type'],
                'duration_seconds' => $event['duration_seconds'] ?? 0,
                'section_name' => $event['section_name'] ?? null,
                'scroll_depth_percent' => $event['scroll_depth_percent'] ?? 0,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'metadata' => null,
                'occurred_at' => isset($event['occurred_at']) ? $event['occurred_at'] : now(),
            ]);
        }

        return response()->json(['stored' => count($validated['events'])]);
    }
}
