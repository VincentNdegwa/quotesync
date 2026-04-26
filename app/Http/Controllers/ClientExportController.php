<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $clients = Client::query()
            ->where('workspace_id', $workspace->id)
            ->get(['company_name', 'contact_name', 'email', 'phone', 'country']);

        $headers = ['Company Name', 'Contact Name', 'Email', 'Phone', 'Country'];

        return response()->streamDownload(function () use ($clients, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($clients as $client) {
                fputcsv($file, [
                    $client->company_name,
                    $client->contact_name,
                    $client->email,
                    $client->phone,
                    $client->country,
                ]);
            }

            fclose($file);
        }, 'clients-export.csv');
    }

    public function exportSelected(Request $request): StreamedResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $ids = $request->input('ids', []);

        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }

        $clients = Client::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $ids)
            ->get(['company_name', 'contact_name', 'email', 'phone', 'country']);

        $headers = ['Company Name', 'Contact Name', 'Email', 'Phone', 'Country'];

        return response()->streamDownload(function () use ($clients, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($clients as $client) {
                fputcsv($file, [
                    $client->company_name,
                    $client->contact_name,
                    $client->email,
                    $client->phone,
                    $client->country,
                ]);
            }

            fclose($file);
        }, 'clients-selected-export.csv');
    }
}
