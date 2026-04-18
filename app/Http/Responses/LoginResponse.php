<?php

namespace App\Http\Responses;

use App\Models\Invitation;
use App\Services\Invitations\InvitationService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function __construct(private InvitationService $invitationService) {}

    public function toResponse($request): Response
    {
        $this->handleInvitationIfPresent($request);

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended(Fortify::redirects('login'));
    }

    private function handleInvitationIfPresent($request): void
    {
        $invitationCode = $request->string('invitation')->trim()->toString();

        if ($invitationCode === '' || ! $request->user()) {
            return;
        }

        $invitation = Invitation::query()
            ->where('code', $invitationCode)
            ->first();

        if (! $invitation || ! $invitation->isPending()) {
            return;
        }

        if (strcasecmp($invitation->email, $request->user()->email) !== 0) {
            return;
        }

        $this->invitationService->accept($invitation, $request->user());
    }
}
