<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CatalogItem;
use App\Models\QuoteTemplate;
use App\Models\Tax;
use App\Models\ConfigurationUnit;
use App\Models\Workspace;
use App\Services\Clients\ClientService;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BuilderDataController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorageService
    ) {}

    public function clients(Request $request, ClientService $clientService): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $search = $request->string('search')->toString();

        $query = Client::query()
            ->where('workspace_id', $workspace->id)
            ->when($search, fn ($q) => $q->where('company_name', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%"))
            ->orderBy('company_name')
            ->limit(50);

        $clients = $query->get(['id', 'company_name', 'contact_name', 'email', 'phone', 'address', 'currency']);

        return response()->json([
            'data' => $clients->map(fn ($client) => [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'contact_name' => $client->contact_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'currency' => $client->currency,
            ]),
        ]);
    }

    public function templates(Request $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $templates = QuoteTemplate::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'layout_snapshot']);

        return response()->json([
            'data' => $templates->map(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'layout_snapshot' => $template->layout_snapshot,
            ]),
        ]);
    }

    public function catalogItems(Request $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $search = $request->string('search')->toString();

        $query = CatalogItem::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"))
            ->with(['taxes', 'configurationUnit', 'variants', 'priceTiers.variant'])
            ->orderBy('name')
            ->limit(100);

        $items = $query->get();

        return response()->json([
            'data' => $items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'description' => $item->description,
                'unit_price' => $item->unit_price,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->configurationUnit?->name,
                'taxes' => $item->taxes->map(fn ($tax) => [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'rate' => $tax->rate,
                    'inclusive' => $tax->inclusive,
                ]),
                'variants' => $item->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'unit_price' => $variant->unit_price,
                    'cost_price' => $variant->cost_price,
                    'is_default' => $variant->is_default,
                ]),
                'priceTiers' => $item->priceTiers->map(fn ($tier) => [
                    'id' => $tier->id,
                    'variant_id' => $tier->variant_id,
                    'variant_name' => $tier->variant?->name,
                    'min_quantity' => $tier->min_quantity,
                    'max_quantity' => $tier->max_quantity,
                    'pricing_type' => $tier->pricing_type->value,
                    'value' => $tier->value,
                ]),
            ]),
        ]);
    }

    public function taxes(Request $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $taxes = Tax::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_compound']);

        return response()->json([
            'data' => $taxes->map(fn ($tax) => [
                'id' => $tax->id,
                'name' => $tax->name,
                'rate' => $tax->rate,
                'is_compound' => $tax->is_compound,
            ]),
        ]);
    }

    public function units(Request $request): JsonResponse
    {
        $workspace = $request->user()?->currentWorkspace;

        abort_unless($workspace instanceof Workspace, 404);

        $units = ConfigurationUnit::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'symbol']);

        return response()->json([
            'data' => $units->map(fn ($unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
            ]),
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|image|max:5120',
        ]);

        $workspace = $request->user()?->currentWorkspace;
        abort_unless($workspace instanceof Workspace, 404);

        $file = $request->file('file');
        $result = $this->fileStorageService->store($file, 'logos');

        if ($result['error']) {
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json([
            'url' => $result['url'],
        ]);
    }
}
