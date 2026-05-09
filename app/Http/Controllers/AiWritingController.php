<?php

namespace App\Http\Controllers;

use App\Ai\Agents\WritingAssistantAgent;
use App\Http\Requests\Ai\ImproveAiWritingRequest;

class AiWritingController extends Controller
{
    public function improve(ImproveAiWritingRequest $request)
    {
        $validated = $request->validated();

        $agent = new WritingAssistantAgent($validated['action'], $validated['locale'] ?? null);

        return $agent->stream($validated['content']);
    }
}
