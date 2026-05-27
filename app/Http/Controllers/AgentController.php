<?php

namespace App\Http\Controllers;

use App\Ai\Agents\QuoteAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    /**
     * Stream the agent's response.
     */
    public function stream(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'nullable|string',
        ]);

        $user = $request->user();
        $agent = new QuoteAssistant($user);

        $conversationId = $request->input('conversation_id') ?? session('ai_conversation_id');

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

    public function conversations(Request $request)
    {
        $user = $request->user();

        $conversations = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get(['id', 'title', 'created_at', 'updated_at'])
            ->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'created_at' => $conv->created_at,
                    'updated_at' => $conv->updated_at,
                ];
            });

        return response()->json($conversations);
    }

    public function newConversation(Request $request)
    {
        session()->forget('ai_conversation_id');

        return response()->json(['success' => true]);
    }

    public function conversationMessages(Request $request, $conversationId)
    {
        $user = $request->user();

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content'])
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            });

        return response()->json(['messages' => $messages]);
    }
}
