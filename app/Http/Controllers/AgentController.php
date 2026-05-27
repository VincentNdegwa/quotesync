<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteAssistant;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * Stream the agent's response.
     */
    public function stream(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user();
        $agent = new QuoteAssistant($user);

        $conversationId = session('ai_conversation_id');
        if ($conversationId) {
            $agent->continue($conversationId, $user);
        } else {
            $agent->forUser($user);
        }

        $response = $agent->stream($request->input('message'));

        // Store the conversation ID for future requests
        if ($response->conversationId) {
            session(['ai_conversation_id' => $response->conversationId]);
        }

        return $response;
    }
}
