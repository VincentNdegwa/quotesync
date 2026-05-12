<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteContextWritingAgent;
use App\Ai\Agents\WritingAssistantAgent;
use App\Http\Requests\Ai\ImproveAiWritingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiWritingController extends Controller
{
    public function improve(ImproveAiWritingRequest $request)
    {
        $validated = $request->validated();

        $agent = new WritingAssistantAgent($validated['action'], $validated['locale'] ?? null);

        return $agent->stream($validated['content']);
    }

    public function write(Request $request)
    {
        try {
            $blockType = $request->query('block_type', 'notes');
            $quoteContext = $request->query('quote_context');
            $existingText = $request->query('existing_text');
            $customPrompt = $request->query('prompt');

            $quoteContextArray = [];
            if ($quoteContext) {
                try {
                    $decoded = json_decode($quoteContext, true);
                    if (is_array($decoded)) {
                        $quoteContextArray = $decoded;
                    }
                } catch (\JsonException $e) {
                    Log::error('Failed to decode quote_context', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $locale = app()->getLocale();

            $agent = new QuoteContextWritingAgent(
                $blockType,
                $quoteContextArray,
                $locale,
                $customPrompt,
            );

            return $agent->stream($existingText ?? '');
        } catch (\Throwable $e) {
            Log::error('AI write error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
