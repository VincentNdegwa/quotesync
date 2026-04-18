<?php

namespace App\Services\WorkspaceSettings;

use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkspaceSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function groupDefinition(string $group): array
    {
        $groups = $this->groups();

        if (! isset($groups[$group])) {
            throw new InvalidArgumentException("Unknown settings group [{$group}].");
        }

        return $groups[$group];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function groups(bool $visibleOnly = false): array
    {
        $groups = config('workspace-settings.groups', []);

        if (! $visibleOnly) {
            return $groups;
        }

        return collect($groups)
            ->filter(fn (array $group): bool => (bool) ($group['visible'] ?? false))
            ->all();
    }

    public function isGroupVisible(string $group): bool
    {
        return (bool) ($this->groupDefinition($group)['visible'] ?? false);
    }

    public function initializeWorkspace(Workspace $workspace, bool $markOnboarded = false): void
    {
        $this->syncDefaults($workspace);

        if ($markOnboarded && $workspace->settings_onboarded_at === null) {
            $workspace->forceFill(['settings_onboarded_at' => now()])->save();
        }
    }

    public function syncDefaults(Workspace $workspace): void
    {
        $existing = $workspace->settings()
            ->get(['id', 'group', 'key'])
            ->mapWithKeys(fn (WorkspaceSetting $setting): array => ["{$setting->group}.{$setting->key}" => true]);

        $inserts = [];

        foreach ($this->groups() as $group => $definition) {
            /** @var array<string, array<string, mixed>> $fields */
            $fields = $definition['fields'] ?? [];

            foreach ($fields as $key => $field) {
                $lookup = "{$group}.{$key}";

                if ($existing->has($lookup)) {
                    continue;
                }

                $value = $field['default'] ?? null;
                $cast = $this->castForField($field);
                $encrypted = (bool) ($field['encrypted'] ?? false);

                $inserts[] = [
                    'workspace_id' => $workspace->id,
                    'group' => $group,
                    'key' => $key,
                    'value' => $this->encodeValue($value, $cast, $encrypted),
                    'cast' => $cast,
                    'encrypted' => $encrypted,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($inserts)) {
            WorkspaceSetting::query()->insert($inserts);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateGroup(Workspace $workspace, string $group, array $payload, bool $markOnboardingComplete = true): void
    {
        $definition = $this->groupDefinition($group);
        /** @var array<string, array<string, mixed>> $fields */
        $fields = $definition['fields'] ?? [];

        $this->syncDefaults($workspace);

        DB::transaction(function () use ($workspace, $group, $fields, $payload): void {
            foreach ($fields as $key => $field) {
                if (! Arr::has($payload, $key)) {
                    continue;
                }

                $cast = $this->castForField($field);
                $encrypted = (bool) ($field['encrypted'] ?? false);
                $value = Arr::get($payload, $key);

                if ($encrypted && ($value === null || $value === '')) {
                    continue;
                }

                WorkspaceSetting::query()->updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'group' => $group,
                        'key' => $key,
                    ],
                    [
                        'value' => $this->encodeValue($value, $cast, $encrypted),
                        'cast' => $cast,
                        'encrypted' => $encrypted,
                    ],
                );
            }
        });

        if ($markOnboardingComplete && $workspace->settings_onboarded_at === null && $this->isOnboardingComplete($workspace)) {
            $workspace->forceFill(['settings_onboarded_at' => now()])->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function groupForFrontend(Workspace $workspace, string $group): array
    {
        $this->syncDefaults($workspace);

        $definition = $this->groupDefinition($group);
        /** @var array<string, array<string, mixed>> $fields */
        $fields = $definition['fields'] ?? [];

        $settings = $workspace->settings()
            ->where('group', $group)
            ->get(['key', 'value', 'cast', 'encrypted'])
            ->keyBy('key');

        $fieldPayload = collect($fields)
            ->map(function (array $field, string $key) use ($settings): array {
                /** @var WorkspaceSetting|null $stored */
                $stored = $settings->get($key);
                $cast = $this->castForField($field);
                $encrypted = (bool) ($field['encrypted'] ?? false);

                $decoded = $stored
                    ? $this->decodeValue($stored->value, $stored->cast, (bool) $stored->encrypted)
                    : ($field['default'] ?? null);

                return [
                    'key' => $key,
                    'label' => $field['label'] ?? $key,
                    'description' => $field['description'] ?? null,
                    'type' => $field['type'] ?? 'string',
                    'cast' => $cast,
                    'required' => (bool) ($field['required'] ?? false),
                    'encrypted' => $encrypted,
                    'placeholder' => $field['placeholder'] ?? null,
                    'options' => $field['options'] ?? null,
                    'value' => $encrypted ? null : $decoded,
                    'has_value' => $encrypted ? ! empty($stored?->value) : ($decoded !== null && $decoded !== ''),
                ];
            })
            ->values()
            ->all();

        return [
            'group' => $group,
            'label' => $definition['label'] ?? ucfirst($group),
            'description' => $definition['description'] ?? null,
            'visible' => (bool) ($definition['visible'] ?? false),
            'onboarding' => (bool) ($definition['onboarding'] ?? false),
            'fields' => $fieldPayload,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function frontendGroups(bool $includeHidden = false): array
    {
        return collect($this->groups())
            ->filter(fn (array $group): bool => $includeHidden || (bool) ($group['visible'] ?? false))
            ->map(fn (array $group, string $key): array => [
                'key' => $key,
                'label' => $group['label'] ?? ucfirst($key),
                'description' => $group['description'] ?? null,
                'visible' => (bool) ($group['visible'] ?? false),
                'onboarding' => (bool) ($group['onboarding'] ?? false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function onboardingGroups(): array
    {
        return collect($this->groups())
            ->filter(fn (array $group): bool => (bool) ($group['onboarding'] ?? false))
            ->keys()
            ->values()
            ->all();
    }

    public function firstIncompleteOnboardingGroup(Workspace $workspace): ?string
    {
        foreach ($this->onboardingGroups() as $group) {
            if (! $this->isGroupComplete($workspace, $group)) {
                return $group;
            }
        }

        return null;
    }

    public function isGroupComplete(Workspace $workspace, string $group): bool
    {
        $this->syncDefaults($workspace);

        $definition = $this->groupDefinition($group);
        /** @var array<string, array<string, mixed>> $fields */
        $fields = $definition['fields'] ?? [];

        $requiredKeys = collect($fields)
            ->filter(fn (array $field): bool => (bool) ($field['required'] ?? false))
            ->keys();

        if ($requiredKeys->isEmpty()) {
            return true;
        }

        $settings = $workspace->settings()
            ->where('group', $group)
            ->whereIn('key', $requiredKeys->all())
            ->get(['key', 'value', 'cast', 'encrypted'])
            ->keyBy('key');

        foreach ($requiredKeys as $key) {
            /** @var array<string, mixed> $field */
            $field = $fields[$key];
            /** @var WorkspaceSetting|null $stored */
            $stored = $settings->get($key);
            $value = $stored
                ? $this->decodeValue($stored->value, $stored->cast, (bool) $stored->encrypted)
                : ($field['default'] ?? null);

            if (is_bool($value)) {
                continue;
            }

            if ($value === null || (is_string($value) && trim($value) === '')) {
                return false;
            }
        }

        return true;
    }

    public function isOnboardingComplete(Workspace $workspace): bool
    {
        return $this->firstIncompleteOnboardingGroup($workspace) === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rulesForGroup(string $group): array
    {
        $definition = $this->groupDefinition($group);
        /** @var array<string, array<string, mixed>> $fields */
        $fields = $definition['fields'] ?? [];

        return collect($fields)
            ->mapWithKeys(function (array $field, string $key): array {
                $rules = [];
                $isRequired = (bool) ($field['required'] ?? false);

                $rules[] = $isRequired ? 'required' : 'nullable';

                $type = $field['type'] ?? 'string';

                $rules = [
                    ...$rules,
                    ...$this->validationRulesForType($type, $field),
                ];

                return ["settings.{$key}" => $rules];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    private function validationRulesForType(string $type, array $field): array
    {
        return match ($type) {
            'boolean' => ['boolean'],
            'integer' => array_filter(['integer', isset($field['min']) ? 'min:'.$field['min'] : null, isset($field['max']) ? 'max:'.$field['max'] : null]),
            'float' => array_filter(['numeric', isset($field['min']) ? 'min:'.$field['min'] : null, isset($field['max']) ? 'max:'.$field['max'] : null]),
            'json' => ['array'],
            'email' => array_filter(['string', 'email', isset($field['max']) ? 'max:'.$field['max'] : null]),
            'url' => array_filter(['string', 'url', isset($field['max']) ? 'max:'.$field['max'] : null]),
            'timezone' => ['string', 'timezone'],
            'country' => ['string', 'size:2'],
            'currency' => ['string', 'size:3'],
            'select' => array_filter(['string', Arr::has($field, 'options') ? 'in:'.implode(',', Arr::get($field, 'options', [])) : null]),
            'color' => ['string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text' => ['string'],
            default => array_filter(['string', isset($field['max']) ? 'max:'.$field['max'] : null]),
        };
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function castForField(array $field): string
    {
        return match ($field['type'] ?? 'string') {
            'boolean' => 'boolean',
            'integer' => 'integer',
            'float' => 'float',
            'json' => 'json',
            default => 'string',
        };
    }

    /**
     * @param  mixed  $value
     */
    private function encodeValue($value, string $cast, bool $encrypted): ?string
    {
        if ($value === null) {
            return null;
        }

        $encoded = match ($cast) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) ((int) $value),
            'float' => (string) ((float) $value),
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            default => (string) $value,
        };

        if (! $encrypted) {
            return $encoded;
        }

        return encrypt($encoded);
    }

    /**
     * @return mixed
     */
    private function decodeValue(?string $value, string $cast, bool $encrypted)
    {
        if ($value === null) {
            return null;
        }

        $decoded = $encrypted ? decrypt($value) : $value;

        return match ($cast) {
            'boolean' => $decoded === '1',
            'integer' => (int) $decoded,
            'float' => (float) $decoded,
            'json' => json_decode($decoded, true, 512, JSON_THROW_ON_ERROR),
            default => $decoded,
        };
    }
}
