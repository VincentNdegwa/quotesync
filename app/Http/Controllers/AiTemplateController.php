<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TemplateBuilderAgent;
use App\Enums\Feature;
use App\Exceptions\LimitExceededException;
use App\Http\Requests\Ai\GenerateAiTemplateRequest;
use App\Models\Workspace;
use App\Services\UsageLimitService;
use Illuminate\Contracts\Support\Arrayable;

class AiTemplateController extends Controller
{
    public function __construct(
        private UsageLimitService $usageLimitService
    ) {}

    public function generate(GenerateAiTemplateRequest $request)
    {
        try {
            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace, 403);

            $limit = $this->usageLimitService->getLimit($workspace, Feature::AI_CREDITS_PER_MONTH);
            $currentUsage = $this->usageLimitService->getCurrentUsage($workspace, Feature::AI_CREDITS_PER_MONTH);

            if ($limit !== null && $currentUsage >= $limit) {
                throw new LimitExceededException('You have reached your AI credits limit. Please upgrade your plan to continue using AI features.');
            }

            $agent = new TemplateBuilderAgent($workspace);
            $validated = $request->validated();

            $prompt = trim(implode("\n\n", array_filter([
                ! empty($validated['industry'])
                    ? 'Industry: '.$validated['industry']
                    : null,
                'Description: '.$validated['description'],
            ])));

            $response = $agent->prompt($prompt);
            $payload = $response instanceof Arrayable
                ? $response->toArray()
                : (array) $response;

            if (empty($payload['layout']) || empty($payload['template_name'])) {
                return response()->json([
                    'message' => 'The AI provider returned an incomplete template. Please try again in a moment.',
                ], 502);
            }

            $workspace->incrementUsage('ai_credits_used', 1);

            return response()->json([
                'layout' => $payload['layout'],
                'template_name' => $payload['template_name'],
                'template_description' => $payload['template_description'] ?? null,
                'industry' => $payload['industry'] ?? $request->input('industry'),
            ]);
        } catch (LimitExceededException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate template: '.$e->getMessage(),
            ], 500);
        }
    }
}
