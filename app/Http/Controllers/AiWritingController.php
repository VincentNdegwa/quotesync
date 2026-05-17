<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteContextWritingAgent;
use App\Ai\Agents\WritingAssistantAgent;
use App\Enums\Feature;
use App\Exceptions\LimitExceededException;
use App\Http\Requests\Ai\ImproveAiWritingRequest;
use App\Models\Workspace;
use App\Services\UsageLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiWritingController extends Controller
{
    public function __construct(
        private UsageLimitService $usageLimitService
    ) {}

    public function improve(ImproveAiWritingRequest $request)
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 403);

        $limit = $this->usageLimitService->getLimit($workspace, Feature::AI_CREDITS_PER_MONTH);
        $currentUsage = $this->usageLimitService->getCurrentUsage($workspace, Feature::AI_CREDITS_PER_MONTH);
        
        if ($limit !== null && $currentUsage >= $limit) {
            throw new LimitExceededException('You have reached your AI credits limit. Please upgrade your plan to continue using AI features.');
        }

        $validated = $request->validated();

        $agent = new WritingAssistantAgent($validated['action'], $validated['locale'] ?? null);

        $workspace->incrementUsage('ai_credits_used', 1);

        return $agent->stream($validated['content']);
    }

    public function write(Request $request)
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace, 403);

            $limit = $this->usageLimitService->getLimit($workspace, Feature::AI_CREDITS_PER_MONTH);
            $currentUsage = $this->usageLimitService->getCurrentUsage($workspace, Feature::AI_CREDITS_PER_MONTH);
            
            if ($limit !== null && $currentUsage >= $limit) {
                throw new LimitExceededException('You have reached your AI credits limit. Please upgrade your plan to continue using AI features.');
            }

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

            $workspace->incrementUsage('ai_credits_used', 1);

            return $agent->stream($existingText ?? '');
        } catch (LimitExceededException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('AI write error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
