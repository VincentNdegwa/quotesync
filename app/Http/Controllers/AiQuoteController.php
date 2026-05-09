<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteGeneratorAgent;
use App\Http\Requests\Ai\GenerateAiQuoteRequest;
use App\Models\Workspace;

class AiQuoteController extends Controller
{
    public function generate(GenerateAiQuoteRequest $request)
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace, 403);

            $agent = new QuoteGeneratorAgent($workspace);
            $validated = $request->validated();
            $response = $agent->prompt($validated['description']);

            return response()->json([
                'sections' => $response['sections'] ?? [],
                'cover_message' => $response['cover_message'] ?? null,
                'payment_terms' => $response['payment_terms'] ?? null,
                'terms' => $response['terms'] ?? null,
                'timeline' => $response['timeline'] ?? null,
                'confidence_note' => $response['confidence_note'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate quote: '.$e->getMessage(),
            ], 500);
        }
    }
}
