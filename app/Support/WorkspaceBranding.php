<?php

namespace App\Support;

use App\Models\Workspace;
use App\Services\WorkspaceSettings\WorkspaceSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class WorkspaceBranding
{
    public function __construct(private readonly WorkspaceSettingsService $workspaceSettingsService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function forWorkspace(Workspace $workspace): array
    {
        /** @var Collection<int, array<string, mixed>> $fields */
        $fields = collect($this->workspaceSettingsService->groupForFrontend($workspace, 'brand')['fields'] ?? [])
            ->keyBy('key');

        $logoPath = $this->stringValue($fields, 'logo_path');
        $logoDataUri = $this->resolveDataUri($logoPath);
        $logoUrl = $logoDataUri ? null : $this->publicUrl($logoPath);

        return [
            'company_name' => $this->stringValue($fields, 'company_name') ?? ($workspace->display_name ?? $workspace->name),
            'logo_url' => $logoUrl,
            'logo_data_uri' => $logoDataUri,
            'primary_color' => $this->stringValue($fields, 'primary_color') ?? '#2563EB',
            'accent_color' => $this->stringValue($fields, 'accent_color') ?? '#F59E0B',
            'company_email' => $this->stringValue($fields, 'company_email') ?? $workspace->owner?->email,
            'company_phone' => $this->stringValue($fields, 'company_phone'),
            'company_address' => $this->stringValue($fields, 'company_address'),
            'company_tagline' => $this->stringValue($fields, 'company_tagline'),
        ];
    }

    private function stringValue(Collection $fields, string $key): ?string
    {
        $value = $fields->get($key)['value'] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function publicUrl(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return app('url')->to(Storage::url($path));
    }

    private function resolveDataUri(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (Storage::exists($path)) {
            $mime = mime_content_type(Storage::path($path)) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(Storage::get($path));
        }

        if (Storage::disk('public')->exists($path)) {
            $mime = mime_content_type(Storage::disk('public')->path($path)) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
        }

        if (file_exists($path)) {
            $mime = mime_content_type($path) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        }

        return null;
    }
}
