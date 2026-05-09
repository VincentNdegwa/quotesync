<?php

namespace App\Http\Controllers\Configuration;

use App\Enums\FollowUpChannel;
use App\Http\Controllers\Controller;
use App\Models\FollowUpSequence;
use App\Models\Workspace;
use App\Services\Quotes\QuotePlaceholderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowUpSequenceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $workspace = $this->workspaceFromRequest($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('follow_up_sequences', 'name')->where('workspace_id', $workspace->id)],
            'is_default' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.day_offset' => ['required', 'integer', 'min:0'],
            'steps.*.channel' => ['required', 'string', Rule::in(FollowUpChannel::values())],
            'steps.*.subject' => ['nullable', 'string', 'max:255'],
            'steps.*.message_template' => ['required', 'string', 'max:5000'],
            'steps.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['steps'] as $index => $step) {
            $validation = QuotePlaceholderService::validatePlaceholders($step['message_template']);
            if (! $validation['valid']) {
                return back()->withErrors([
                    "steps.{$index}.message_template" => 'Invalid placeholders: '.implode(', ', $validation['invalid']).'. Allowed: '.implode(', ', array_keys(QuotePlaceholderService::getAvailablePlaceholders())),
                ])->withInput();
            }
        }

        $sequence = $workspace->followUpSequences()->create([
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        if ($sequence->is_default) {
            $workspace->followUpSequences()
                ->whereKeyNot($sequence->id)
                ->update(['is_default' => false]);
        }

        foreach ($validated['steps'] as $stepData) {
            $sequence->steps()->create([
                'day_offset' => $stepData['day_offset'],
                'channel' => $stepData['channel'],
                'subject' => $stepData['subject'],
                'message_template' => $stepData['message_template'],
                'sort_order' => $stepData['sort_order'],
            ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Follow-up sequence created.']);
    }

    public function update(Request $request, FollowUpSequence $followUpSequence): RedirectResponse
    {
        $workspace = $this->workspaceFromRequest($request);
        abort_unless($followUpSequence->workspace_id === $workspace->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('follow_up_sequences', 'name')->where('workspace_id', $workspace->id)->ignore($followUpSequence->id)],
            'is_default' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.id' => ['nullable', 'integer', Rule::exists('follow_up_steps', 'id')->where('follow_up_sequence_id', $followUpSequence->id)],
            'steps.*.day_offset' => ['required', 'integer', 'min:0'],
            'steps.*.channel' => ['required', 'string', Rule::in(FollowUpChannel::values())],
            'steps.*.subject' => ['nullable', 'string', 'max:255'],
            'steps.*.message_template' => ['required', 'string', 'max:5000'],
            'steps.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        // Validate placeholders in message templates
        foreach ($validated['steps'] as $index => $step) {
            $validation = QuotePlaceholderService::validatePlaceholders($step['message_template']);
            if (! $validation['valid']) {
                return back()->withErrors([
                    "steps.{$index}.message_template" => 'Invalid placeholders: '.implode(', ', $validation['invalid']).'. Allowed: '.implode(', ', array_keys(QuotePlaceholderService::getAvailablePlaceholders())),
                ])->withInput();
            }
        }

        $followUpSequence->update([
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        if ($followUpSequence->is_default) {
            $workspace->followUpSequences()
                ->whereKeyNot($followUpSequence->id)
                ->update(['is_default' => false]);
        }

        $existingStepIds = collect($validated['steps'])
            ->filter(fn (array $step): bool => isset($step['id']))
            ->pluck('id')
            ->all();

        $followUpSequence->steps()
            ->whereKeyNot($existingStepIds)
            ->delete();

        foreach ($validated['steps'] as $stepData) {
            if (isset($stepData['id'])) {
                $followUpSequence->steps()->whereKey($stepData['id'])->update([
                    'day_offset' => $stepData['day_offset'],
                    'channel' => $stepData['channel'],
                    'subject' => $stepData['subject'],
                    'message_template' => $stepData['message_template'],
                    'sort_order' => $stepData['sort_order'],
                ]);
            } else {
                $followUpSequence->steps()->create([
                    'day_offset' => $stepData['day_offset'],
                    'channel' => $stepData['channel'],
                    'subject' => $stepData['subject'],
                    'message_template' => $stepData['message_template'],
                    'sort_order' => $stepData['sort_order'],
                ]);
            }
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Follow-up sequence updated.']);
    }

    public function destroy(Request $request, FollowUpSequence $followUpSequence): RedirectResponse
    {
        $workspace = $this->workspaceFromRequest($request);
        abort_unless($followUpSequence->workspace_id === $workspace->id, 404);

        $followUpSequence->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Follow-up sequence deleted.']);
    }

    private function workspaceFromRequest(Request $request): Workspace
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        return $workspace;
    }
}
