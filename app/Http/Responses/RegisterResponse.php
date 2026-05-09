<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 201);
        }

        $user = $request->user();

        if ($user && $user->currentWorkspace) {
            $settings = $user->currentWorkspace->settings()->where('group', 'quotes')->exists();

            if (! $settings) {
                return redirect()->route('configuration.index');
            }
        }

        return redirect()->intended(Fortify::redirects('register') ?? route('dashboard'));
    }
}
