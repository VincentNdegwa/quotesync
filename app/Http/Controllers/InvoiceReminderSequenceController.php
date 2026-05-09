<?php

namespace App\Http\Controllers;

use App\Models\InvoiceReminderSequence;
use App\Models\InvoiceReminderStep;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InvoiceReminderSequenceController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $sequences = InvoiceReminderSequence::where('workspace_id', $workspace->id)
            ->with('steps')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('configuration/invoice-reminders/Index', [
            'sequences' => $sequences,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'steps' => 'required|array',
            'steps.*.day_offset' => 'required|integer',
            'steps.*.channel' => 'required|string|in:email',
            'steps.*.reminder_type' => 'required|string|in:before_due,on_due,after_due',
            'steps.*.subject' => 'required|string|max:255',
            'steps.*.message_template' => 'required|string',
            'steps.*.send_automatically' => 'required|boolean',
            'steps.*.sort_order' => 'required|integer',
        ]);

        $sequence = InvoiceReminderSequence::create([
            'workspace_id' => $workspace->id,
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        foreach ($validated['steps'] as $stepData) {
            InvoiceReminderStep::create([
                'invoice_reminder_sequence_id' => $sequence->id,
                'day_offset' => $stepData['day_offset'],
                'channel' => $stepData['channel'],
                'reminder_type' => $stepData['reminder_type'],
                'subject' => $stepData['subject'],
                'message_template' => $stepData['message_template'],
                'send_automatically' => $stepData['send_automatically'],
                'sort_order' => $stepData['sort_order'],
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice reminder sequence created.')]);

        return back();
    }

    public function update(Request $request, InvoiceReminderSequence $sequence): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $sequence->workspace_id === $workspace->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'steps' => 'required|array',
            'steps.*.id' => 'sometimes|integer|exists:invoice_reminder_steps,id',
            'steps.*.day_offset' => 'required|integer',
            'steps.*.channel' => 'required|string|in:email',
            'steps.*.reminder_type' => 'required|string|in:before_due,on_due,after_due',
            'steps.*.subject' => 'required|string|max:255',
            'steps.*.message_template' => 'required|string',
            'steps.*.send_automatically' => 'required|boolean',
            'steps.*.sort_order' => 'required|integer',
        ]);

        $sequence->update([
            'name' => $validated['name'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        $existingStepIds = [];
        foreach ($validated['steps'] as $stepData) {
            if (isset($stepData['id'])) {
                $existingStepIds[] = $stepData['id'];
                InvoiceReminderStep::where('id', $stepData['id'])
                    ->where('invoice_reminder_sequence_id', $sequence->id)
                    ->update([
                        'day_offset' => $stepData['day_offset'],
                        'channel' => $stepData['channel'],
                        'reminder_type' => $stepData['reminder_type'],
                        'subject' => $stepData['subject'],
                        'message_template' => $stepData['message_template'],
                        'send_automatically' => $stepData['send_automatically'],
                        'sort_order' => $stepData['sort_order'],
                    ]);
            } else {
                InvoiceReminderStep::create([
                    'invoice_reminder_sequence_id' => $sequence->id,
                    'day_offset' => $stepData['day_offset'],
                    'channel' => $stepData['channel'],
                    'reminder_type' => $stepData['reminder_type'],
                    'subject' => $stepData['subject'],
                    'message_template' => $stepData['message_template'],
                    'send_automatically' => $stepData['send_automatically'],
                    'sort_order' => $stepData['sort_order'],
                ]);
            }
        }

        // Delete steps that were removed
        InvoiceReminderStep::where('invoice_reminder_sequence_id', $sequence->id)
            ->whereNotIn('id', $existingStepIds)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice reminder sequence updated.')]);

        return back();
    }

    public function destroy(Request $request, InvoiceReminderSequence $sequence): RedirectResponse
    {
        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace && $sequence->workspace_id === $workspace->id, 404);

        if ($sequence->is_default) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Cannot delete the default reminder sequence.')]);
            return back();
        }

        $sequence->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invoice reminder sequence deleted.')]);

        return back();
    }
}
