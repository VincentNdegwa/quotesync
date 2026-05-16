<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LoadWorkspaceWithPlan;
use App\Http\Middleware\RedirectIfPortalAuthenticated;
use App\Http\Middleware\RedirectIfPortalGuest;
use App\Http\Middleware\SetPortalWorkspaceContext;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('portal')
                ->name('portal.')
                ->group(base_path('routes/portal.php'));
        },
    )
    ->withProviders([
        AppServiceProvider::class,
        AuthServiceProvider::class,
        FortifyServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->preventRequestForgery(except: [
            'paddle/*',
        ]);

        $middleware->alias([
            'portal.guest' => RedirectIfPortalGuest::class,
            'portal.auth' => RedirectIfPortalAuthenticated::class,
            'portal.workspace' => SetPortalWorkspaceContext::class,
        ]);

        $middleware->web(append: [
            LoadWorkspaceWithPlan::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                if ($request->header('X-Inertia') === 'true') {
                    \Inertia\Inertia::flash('toast', [
                        'type' => 'error',
                        'message' => __('The requested resource was not found.'),
                    ]);

                    return back();
                }

                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'message' => __('The requested resource was not found.'),
                    ], 404);
                }
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                if ($request->header('X-Inertia') === 'true') {
                    \Inertia\Inertia::flash('toast', [
                        'type' => 'error',
                        'message' => __('The requested page was not found.'),
                    ]);

                    return back();
                }

                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'message' => __('The requested page was not found.'),
                    ], 404);
                }
            }
        });
    })->create();
