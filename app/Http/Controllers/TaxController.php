<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Models\Tax;
use App\Models\Workspace;
use App\Services\Taxes\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxController extends Controller
{
    public function index(Request $request, TaxService $taxService): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        return response()->json([
            'data' => $taxService->allForWorkspace($workspace),
        ]);
    }

    public function store(StoreTaxRequest $request, TaxService $taxService): JsonResponse|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $payload = $request->validated();
        $payload['is_default'] = (bool) ($payload['is_default'] ?? false);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload['created_by'] = $request->user()?->id;

        $tax = $taxService->create($workspace, $payload);

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax created.')]);

            return back();
        }

        return response()->json(['data' => $tax], 201);
    }

    public function update(UpdateTaxRequest $request, Tax $tax, TaxService $taxService): JsonResponse|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $tax->workspace_id === $workspace->id, 404);

        $tax = $taxService->update($tax, $request->validated());

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax updated.')]);

            return back();
        }

        return response()->json(['data' => $tax]);
    }

    public function destroy(Request $request, Tax $tax, TaxService $taxService): JsonResponse|RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace && $tax->workspace_id === $workspace->id, 404);

        $taxService->delete($tax);

        if ($request->header('X-Inertia')) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Tax deleted.')]);

            return back();
        }

        return response()->json(status: 204);
    }
}
