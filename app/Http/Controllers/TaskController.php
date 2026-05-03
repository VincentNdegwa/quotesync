<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Map simple taskable type strings to full class names.
     */
    private function mapTaskableType(string $type): string
    {
        return match ($type) {
            'quote' => 'App\Models\Quote',
            'invoice' => 'App\Models\Invoice',
            default => throw new \InvalidArgumentException("Invalid taskable type: {$type}"),
        };
    }

    public function index(Request $request)
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $query = Task::query()
            ->where('workspace_id', $workspace->id)
            ->with(['assignedTo', 'assignedBy', 'status', 'taskable'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $statusSlug = $request->input('status');
            $taskStatus = TaskStatus::where('workspace_id', $workspace->id)
                ->where('slug', $statusSlug)
                ->first();
            if ($taskStatus) {
                $query->where('task_status_id', $taskStatus->id);
            }
        }

        // Apply sorting
        $sort = $request->input('sort', 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'due_date') {
            $query->orderBy('due_date', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $tasks = $query->with(['assignedTo', 'assignedBy', 'status', 'taskable'])->paginate(15);

        $taskStatuses = TaskStatus::where('workspace_id', $workspace->id)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'color']);

        $users = $workspace->members()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $todoStatus = TaskStatus::where('workspace_id', $workspace->id)
            ->where('slug', 'todo')
            ->first();

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'taskStatuses' => $taskStatuses,
            'users' => $users,
            'defaultStatusId' => $todoStatus?->id,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'sort' => $request->input('sort', 'newest'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'taskable_type' => 'required|string|in:quote,invoice',
            'taskable_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $taskableClass = $this->mapTaskableType($validated['taskable_type']);
        $taskableModel = $taskableClass::findOrFail($validated['taskable_id']);
        $workspaceId = $taskableModel->workspace_id;

        $taskStatusId = TaskStatus::where('workspace_id', $workspaceId)
            ->where('slug', 'todo')
            ->first()?->id;

        Task::create([
            'workspace_id' => $workspaceId,
            'taskable_type' => $taskableClass,
            'taskable_id' => $validated['taskable_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to' => $validated['assigned_to'],
            'assigned_by' => auth()->id(),
            'due_date' => $validated['due_date'] ?? null,
            'task_status_id' => $taskStatusId,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created successfully.')]);

        return back();
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'sometimes|exists:users,id',
            'due_date' => 'nullable|date',
            'task_status_id' => 'nullable|exists:task_statuses,id',
            'taskable_type' => 'sometimes|string|in:quote,invoice',
            'taskable_id' => 'sometimes|integer',
        ]);

        $data = $validated;

        // Map taskable type if provided
        if (isset($data['taskable_type'])) {
            $data['taskable_type'] = $this->mapTaskableType($data['taskable_type']);
        }

        $task->update($data);

        // Check if task is being marked as done
        if (isset($validated['task_status_id'])) {
            $taskStatus = TaskStatus::find($validated['task_status_id']);
            if ($taskStatus && $taskStatus->slug === 'done' && !$task->completed_at) {
                $task->completed_at = now();
                $task->save();
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated successfully.')]);

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted successfully.')]);

        return back();
    }
}
