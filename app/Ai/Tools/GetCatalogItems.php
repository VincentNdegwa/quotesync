<?php

namespace App\Ai\Tools;

use App\Models\CatalogItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCatalogItems implements Tool
{
    public function __construct(
        private int $workspaceId
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get the workspace catalog items to match against the job description';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        return json_encode([
            'items' => CatalogItem::where('workspace_id', $this->workspaceId)
                ->where('is_active', true)
                ->get(['id', 'name', 'description', 'unit', 'unit_price', 'category'])
                ->toArray(),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
