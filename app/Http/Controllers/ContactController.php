<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contacts\ContactRequest;
use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request, Client $client): Response
    {
        abort_unless($client->workspace_id === $request->user()?->current_workspace_id, 404);

        $contacts = $client->contacts()->orderBy('is_primary', 'desc')->orderBy('name')->get();

        return Inertia::render('Clients/Contacts', [
            'client' => $client,
            'contacts' => $contacts,
        ]);
    }

    public function store(ContactRequest $request, Client $client): RedirectResponse
    {
        abort_unless($client->workspace_id === $request->user()?->current_workspace_id, 404);

        $validated = $request->validated();

        $contact = $client->contacts()->create([
            ...$validated,
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        if ($contact->is_primary) {
            $client->update(['primary_contact_id' => $contact->id]);
        }

        return back();
    }

    public function update(ContactRequest $request, Client $client, Contact $contact): RedirectResponse
    {
        abort_unless(
            $client->workspace_id === $request->user()?->current_workspace_id
            && $contact->client_id === $client->id,
            404
        );

        $validated = $request->validated();

        $contact->update([
            ...$validated,
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        if ($contact->is_primary) {
            $client->update(['primary_contact_id' => $contact->id]);
        }

        return back();
    }

    public function destroy(Request $request, Client $client, Contact $contact): RedirectResponse
    {
        abort_unless(
            $client->workspace_id === $request->user()?->current_workspace_id
            && $contact->client_id === $client->id,
            404
        );

        if ($client->primary_contact_id === $contact->id) {
            $client->update(['primary_contact_id' => null]);
        }

        $contact->delete();

        return back();
    }
}
