<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TemplateBuilderAgent;
use App\Models\Workspace;
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
            $response = $agent->prompt($request->description);

            return response()->json([
                'layout' => $response['layout'] ?? null,
                'template_name' => $response['template_name'] ?? null,
                'template_description' => $response['template_description'] ?? null,
                'industry' => $response['industry'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate template: ' . $e->getMessage(),
            ], 500);
        }
    }
}
