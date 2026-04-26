<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteGeneratorAgent;
use App\Models\Workspace;
use Illuminate\Http\Request;

class AiQuoteController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $request->validate(['description' => 'required|string|max:2000']);

            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace, 403);

            $agent = new QuoteGeneratorAgent($workspace);
            $response = $agent->prompt($request->description);

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
                'message' => 'Failed to generate quote: ' . $e->getMessage(),
            ], 500);
        }
    }
}
