<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TemplateBuilderAgent;
use App\Models\Workspace;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

class AiTemplateController extends Controller
{
    public function generate(Request $request)
    {
        try {
            $request->validate([
                'description' => 'required|string|max:2000',
                'industry' => 'nullable|string|max:100',
            ]);

            $workspace = $request->user()?->currentWorkspace;
            abort_unless($workspace instanceof Workspace, 403);

            $agent = new TemplateBuilderAgent($workspace);

            $prompt = trim(implode("\n\n", array_filter([
                $request->filled('industry')
                    ? 'Industry: '.$request->string('industry')->toString()
                    : null,
                'Description: '.$request->string('description')->toString(),
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

            return response()->json([
                'layout' => $payload['layout'],
                'template_name' => $payload['template_name'],
                'template_description' => $payload['template_description'] ?? null,
                'industry' => $payload['industry'] ?? $request->input('industry'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate template: '.$e->getMessage(),
            ], 500);
        }
    }
}
