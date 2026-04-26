<?php

namespace App\Http\Controllers;

use App\Models\CustomDomain;
use App\Services\DomainVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomDomainController extends Controller
{
    public function __construct(
        private DomainVerificationService $verificationService
    ) {}

    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspace;
        
        $domains = CustomDomain::query()
            ->where('workspace_id', $workspace->id)
            ->get();

        return Inertia::render('custom-domain/Index', [
            'domains' => $domains,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $request->validate([
            'domain' => 'required|string|unique:workspace_custom_domains,domain',
        ]);

        $domain = CustomDomain::create([
            'workspace_id' => $workspace->id,
            'domain' => $request->domain,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $verification = $this->verificationService->initiateVerification($domain);

        return back()->with('verification', $verification);
    }

    public function verify(Request $request, CustomDomain $domain): RedirectResponse
    {
        $this->authorize('update', $domain);

        $verified = $this->verificationService->verifyDomain($domain);

        if ($verified) {
            return back()->with('success', 'Domain verified successfully.');
        }

        return back()->with('error', 'Domain verification failed. Please check your DNS records.');
    }

    public function setPrimary(Request $request, CustomDomain $domain): RedirectResponse
    {
        $this->authorize('update', $domain);

        $this->verificationService->setAsPrimary($domain);

        return back()->with('success', 'Primary domain updated.');
    }

    public function destroy(CustomDomain $domain): RedirectResponse
    {
        $this->authorize('delete', $domain);

        $domain->delete();

        return back()->with('success', 'Domain removed.');
    }
}
