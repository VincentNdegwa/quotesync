<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStatuses\TaskStatusFormRequest;
use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TaskStatusController extends Controller
{
    public function store(TaskStatusFormRequest $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $slug = Str::slug($request->input('name'));

        $maxStatus = TaskStatus::where('workspace_id', $workspace->id)
            ->orderByDesc('sort_order')
            ->first();
        $sortOrder = $maxStatus ? $maxStatus->sort_order + 1 : 1;

        TaskStatus::query()->create([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'workspace_id' => $workspace->id,
            'is_default' => false,
            'is_system' => false,
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task status created.')]);

        return back();
    }

    public function update(TaskStatusFormRequest $request, TaskStatus $taskStatus): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $taskStatus->workspace_id === $workspace->id, 404);

        if ($taskStatus->is_system) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot edit system statuses (To Do and Done).')]);

            return back();
        }

        $taskStatus->update([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task status updated.')]);

        return back();
    }

    public function destroy(Request $request, TaskStatus $taskStatus): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $taskStatus->workspace_id === $workspace->id, 404);

        if ($taskStatus->is_system) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete system statuses (To Do and Done).')]);

            return back();
        }

        $taskStatus->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task status deleted.')]);

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'taskStatuses' => 'required|array',
            'taskStatuses.*.id' => 'required|integer|exists:task_statuses,id',
            'taskStatuses.*.sort_order' => 'required|integer|min:1',
        ]);

        foreach ($validated['taskStatuses'] as $item) {
            TaskStatus::where('id', $item['id'])
                ->where('workspace_id', $workspace->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task statuses reordered successfully')]);

        return back();
    }
}
