<?php

namespace App\Http\Controllers;

use App\Ai\Agents\WritingAssistantAgent;
use Illuminate\Http\Request;

class AiWritingController extends Controller
{
    public function improve(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'action' => 'required|in:clearer,formal,friendly,shorter,rewrite',
            'locale' => 'nullable|string',
        ]);

        $agent = new WritingAssistantAgent($request->action, $request->locale);

        return $agent->stream($request->content);
    }
}
