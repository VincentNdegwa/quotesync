<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteGeneratorAgent;
use App\Enums\Feature;
use App\Exceptions\LimitExceededException;
use App\Http\Requests\Ai\GenerateAiQuoteRequest;
use App\Models\Workspace;
use App\Services\UsageLimitService;

class AiQuoteController extends Controller
{
    public function __construct(
        private UsageLimitService $usageLimitService
    ) {}

    public function generate(GenerateAiQuoteRequest $request)
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace, 403);

            $limit = $this->usageLimitService->getLimit($workspace, Feature::AI_CREDITS_PER_MONTH);
            $currentUsage = $this->usageLimitService->getCurrentUsage($workspace, Feature::AI_CREDITS_PER_MONTH);

            if ($limit !== null && $currentUsage >= $limit) {
                throw new LimitExceededException('You have reached your AI credits limit. Please upgrade your plan to continue using AI features.');
            }

            $agent = new QuoteGeneratorAgent($workspace);
            $validated = $request->validated();
            $response = $agent->prompt($validated['description']);

            $workspace->incrementUsage('ai_credits_used', 1);

            return response()->json([
                'sections' => $response['sections'] ?? [],
                'cover_message' => $response['cover_message'] ?? null,
                'payment_terms' => $response['payment_terms'] ?? null,
                'terms' => $response['terms'] ?? null,
                'timeline' => $response['timeline'] ?? null,
                'confidence_note' => $response['confidence_note'] ?? null,
            ]);
        } catch (LimitExceededException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate quote: '.$e->getMessage(),
            ], 500);
        }
    }
}
