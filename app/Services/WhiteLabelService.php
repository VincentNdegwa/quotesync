<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Http\Request;

class WhiteLabelService
{
    public function getBrandingForRequest(Request $request): array
    {
        $workspace = $this->resolveWorkspaceFromRequest($request);

        if (!$workspace || !$workspace->isWhiteLabelEnabled()) {
            return $this->getDefaultBranding();
        }

        return [
            'enabled' => true,
            'logo_url' => $workspace->getWhiteLabelLogoUrl(),
            'company_name' => $workspace->getWhiteLabelCompanyName(),
            'primary_color' => $workspace->getWhiteLabelPrimaryColor(),
            'domain' => $workspace->getWhiteLabelDomain(),
        ];
    }

    private function resolveWorkspaceFromRequest(Request $request): ?Workspace
    {
        // Try to resolve from subdomain
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain) {
            $workspace = Workspace::where('white_label_domain', $subdomain)->first();
            if ($workspace) {
                return $workspace;
            }
        }

        // Try to resolve from authenticated user
        if ($request->user()?->currentWorkspace) {
            return $request->user()->currentWorkspace;
        }

        // Try to resolve from quote UUID (for public quote pages)
        if ($request->route('quoteUuid')) {
            $quote = \App\Models\Quote::where('uuid', $request->route('quoteUuid'))->first();
            if ($quote) {
                return $quote->workspace;
            }
        }

        return null;
    }

    private function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);
        
        // Skip www and extract the first subdomain
        if (count($parts) > 2) {
            $subdomain = $parts[0];
            return $subdomain === 'www' ? ($parts[1] ?? null) : $subdomain;
        }

        return null;
    }

    private function getDefaultBranding(): array
    {
        return [
            'enabled' => false,
            'logo_url' => null,
            'company_name' => config('app.name'),
            'primary_color' => null,
            'domain' => null,
        ];
    }

    public function updateWhiteLabelSettings(Workspace $workspace, array $data): void
    {
        $workspace->update([
            'white_label_enabled' => $data['enabled'] ?? false,
            'white_label_logo' => $data['logo_url'] ?? null,
            'white_label_company_name' => $data['company_name'] ?? null,
            'white_label_primary_color' => $data['primary_color'] ?? null,
            'white_label_domain' => $data['domain'] ?? null,
        ]);
    }
}
