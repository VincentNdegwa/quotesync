<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LimitExceededException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function render(Request $request)
    {
        if ($this->isInertiaRequest($request)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $this->getMessage(),
            ]);

            return back()->withInput();
        }

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], 403);
        }

        return back()->with('error', $this->getMessage());
    }

    protected function isInertiaRequest(Request $request): bool
    {
        return $request->header('X-Inertia') === 'true';
    }
}
