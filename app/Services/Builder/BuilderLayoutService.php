<?php

namespace App\Services\Builder;

use Illuminate\Support\Arr;

class BuilderLayoutService
{

    public function normalizeLayoutForStorage(array $payload): ?array
    {
        $layoutSnapshot = Arr::pull($payload, 'layout_snapshot');
        $layout = Arr::pull($payload, 'layout');

        if (is_array($layout)) {
            $layoutSnapshot = $layout;
        } elseif (! is_array($layoutSnapshot)) {
            return null;
        }

        if (is_array($layoutSnapshot) && isset($layoutSnapshot['theme'])) {
            $layoutSnapshot['theme'] = [
                'primaryColor' => $layoutSnapshot['theme']['primaryColor'] ?? '#2563EB',
                'fontFamily' => $layoutSnapshot['theme']['fontFamily'] ?? 'inter',
            ];
        }

        return $layoutSnapshot;
    }


    public function normalizeLayoutForRead(?array $layoutSnapshot): ?array
    {
        if (! is_array($layoutSnapshot)) {
            return null;
        }

        if (isset($layoutSnapshot['theme'])) {
            $layoutSnapshot['theme'] = [
                'primaryColor' => $layoutSnapshot['theme']['primaryColor'] ?? '#2563EB',
                'fontFamily' => $layoutSnapshot['theme']['fontFamily'] ?? 'inter',
            ];
        }

        return $layoutSnapshot;
    }
}
