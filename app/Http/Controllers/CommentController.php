<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comments\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Quote;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, string $commentableType, int $commentableId): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $commentable = match ($commentableType) {
            'quote' => Quote::query(),
            'invoice' => Invoice::query(),
            default => abort(404),
        };

        $model = $commentable
            ->where('id', $commentableId)
            ->where('workspace_id', $workspace->id)
            ->firstOrFail();

        $comments = $model->comments()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json($comments->map(fn ($comment) => [
            'id' => $comment->id,
            'content' => $comment->content,
            'mentions' => $comment->mentions,
            'is_internal' => $comment->is_internal,
            'created_at' => $comment->created_at->toISOString(),
            'updated_at' => $comment->updated_at->toISOString(),
            'user' => $comment->user ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ] : null,
        ]));
    }

    public function store(StoreCommentRequest $request, string $type, int $id): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $commentable = match ($type) {
            'quote' => Quote::query(),
            'invoice' => Invoice::query(),
            default => abort(404),
        };

        $model = $commentable
            ->where('id', $id)
            ->where('workspace_id', $workspace->id)
            ->firstOrFail();

        $validated = $request->validated();

        $comment = $model->comments()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()?->id,
            'content' => $validated['content'],
            'mentions' => $validated['mentions'] ?? [],
            'is_internal' => $validated['is_internal'] ?? true,
        ]);

        if (! empty($validated['mentions'])) {
            $mentionedUsers = User::query()
                ->whereIn('id', $validated['mentions'])
                ->whereHas('workspaces', fn ($q) => $q->where('workspace_id', $workspace->id))
                ->get();

            foreach ($mentionedUsers as $mentionedUser) {
                $mentionedUser->notify(new \App\Notifications\MentionedNotification($comment, $commentable));
            }
        }

        $comment->load('user:id,name');

        return response()->json([
            'id' => $comment->id,
            'content' => $comment->content,
            'mentions' => $comment->mentions,
            'is_internal' => $comment->is_internal,
            'created_at' => $comment->created_at->toISOString(),
            'updated_at' => $comment->updated_at->toISOString(),
            'user' => $comment->user ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ] : null,
        ], 201);
    }

    public function destroy(Request $request, string $commentableType, int $commentableId, Comment $comment): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);
        abort_unless($comment->workspace_id === $workspace->id, 403);
        abort_unless($comment->user_id === $request->user()?->id || $request->user()?->current_workspace?->owner_id === $request->user()?->id, 403);

        $comment->delete();

        return response()->json(null, 204);
    }
}
