<?php

namespace App\Services\WorkspaceSettings;

use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class WorkspaceSettingsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_VERSION = 'v1'; // Increment this when cache format changes

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

    /**
     * Get all settings for a workspace, cached
     * @return array<string, array<string, mixed>>
     */
    private function getWorkspaceSettings(Workspace $workspace)
    {
        $cacheKey = "workspace_settings:{$workspace->id}:" . self::CACHE_VERSION;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($workspace) {
            return $workspace->settings()
                ->get(['key', 'value', 'group', 'cast', 'encrypted'])
                ->map(fn ($setting) => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'group' => $setting->group,
                    'cast' => $setting->cast,
                    'encrypted' => $setting->encrypted,
                ])
                ->keyBy(fn ($item) => "{$item['group']}.{$item['key']}")
                ->toArray();
        });
    }

    /**
     * Clear cache for a workspace
     */
    public function clearCache(Workspace $workspace): void
    {
        // Clear cache for all versions to ensure stale data is removed
        foreach (['v0', 'v1'] as $version) {
            $cacheKey = "workspace_settings:{$workspace->id}:{$version}";
            Cache::forget($cacheKey);
        }
    }

    public function syncDefaults(Workspace $workspace): void
    {
        $existing = collect($this->getWorkspaceSettings($workspace))
            ->mapWithKeys(fn ($setting): array => ["{$setting['group']}.{$setting['key']}" => true]);

        $inserts = [];

        foreach ($this->groups() as $group => $definition) {
            // Skip groups with subsections - they're handled separately
            if (isset($definition['subsections'])) {
                continue;
            }

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
            $this->clearCache($workspace);
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

        $this->clearCache($workspace);

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

        // Get all settings for this workspace in one query (cached)
        $allSettings = collect($this->getWorkspaceSettings($workspace));

        // Handle subsections
        if (isset($definition['subsections'])) {
            $subsections = $definition['subsections'];
            $allFields = [];

            foreach ($subsections as $subsectionKey => $subsection) {
                $subsectionFields = $subsection['fields'] ?? [];
                
                // Filter settings for this subsection from the cached all settings
                $subsectionSettings = $allSettings->filter(fn ($setting) => $setting['group'] === $subsectionKey)
                    ->keyBy('key');

                $fieldPayload = collect($subsectionFields)
                    ->map(function (array $field, string $key) use ($subsectionSettings, $subsectionFields): array {
                        /** @var array<string, mixed>|null $stored */
                        $stored = $subsectionSettings->get($key);
                        $cast = $this->castForField($field);
                        $encrypted = (bool) ($field['encrypted'] ?? false);

                        $decoded = $stored
                            ? $this->decodeValue($stored['value'], $stored['cast'], (bool) $stored['encrypted'])
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
                            'value' => $decoded,
                            'has_value' => $stored !== null,
                        ];
                    })
                    ->values()
                    ->all();

                $allFields[$subsectionKey] = [
                    'label' => $subsection['label'],
                    'fields' => $fieldPayload,
                ];
            }

            return [
                'group' => $group,
                'label' => $definition['label'],
                'description' => $definition['description'] ?? null,
                'subsections' => $allFields,
                'fields' => [],
            ];
        }

        // Filter settings for this group from the cached all settings
        $settings = $allSettings->filter(fn ($setting) => $setting['group'] === $group)
            ->keyBy('key');

        $fieldPayload = collect($fields)
            ->map(function (array $field, string $key) use ($settings): array {
                /** @var array<string, mixed>|null $stored */
                $stored = $settings->get($key);
                $cast = $this->castForField($field);
                $encrypted = (bool) ($field['encrypted'] ?? false);

                $decoded = $stored
                    ? $this->decodeValue($stored['value'], $stored['cast'], (bool) $stored['encrypted'])
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
                    'value' => $decoded,
                    'has_value' => $stored !== null,
                ];
            })
            ->values()
            ->all();

        return [
            'group' => $group,
            'label' => $definition['label'],
            'description' => $definition['description'] ?? null,
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

        // Handle subsections
        if (isset($definition['subsections'])) {
            $subsections = $definition['subsections'];
            foreach ($subsections as $subsectionKey => $subsection) {
                if (!$this->isGroupComplete($workspace, $subsectionKey)) {
                    return false;
                }
            }
            return true;
        }

        $requiredKeys = collect($fields)
            ->filter(fn (array $field): bool => (bool) ($field['required'] ?? false))
            ->keys();

        if ($requiredKeys->isEmpty()) {
            return true;
        }

        // Brand and localization fields are now in the workspace table
        if ($group === 'brand' || $group === 'localization') {
            foreach ($requiredKeys as $key) {
                $value = null;

                if ($group === 'brand' && $key === 'company_name') {
                    $value = $workspace->name;
                } elseif ($group === 'brand' && $key === 'logo_path') {
                    $value = $workspace->logo_path;
                } elseif ($group === 'localization' && $key === 'country') {
                    $value = $workspace->country;
                } elseif ($group === 'localization' && $key === 'currency') {
                    $value = $workspace->currency;
                }

                if ($value === null || (is_string($value) && trim($value) === '')) {
                    return false;
                }
            }

            return true;
        }

        // Use cached settings
        $allSettings = collect($this->getWorkspaceSettings($workspace));
        $settings = $allSettings->filter(fn ($setting) => $setting['group'] === $group)
            ->whereIn('key', $requiredKeys->all())
            ->keyBy('key');

        foreach ($requiredKeys as $key) {
            /** @var array<string, mixed> $field */
            $field = $fields[$key];
            /** @var array<string, mixed>|null $stored */
            $stored = $settings->get($key);

            if ($stored === null) {
                return false;
            }

            $value = $this->decodeValue($stored['value'], $stored['cast'], (bool) $stored['encrypted']);

            if (is_bool($value)) {
                continue;
            }

            if ($value === null || (is_string($value) && trim($value) === '')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all workspace settings needed for the builder/frontend
     * @return array<string, mixed>
     */
    public function builderSettings(Workspace $workspace): array
    {
        $this->syncDefaults($workspace);

        // Get quotes settings
        $quoteFields = collect($this->groupForFrontend($workspace, 'quotes')['fields'] ?? [])->keyBy('key');

        return [
            'quotes' => [
                'quote_prefix' => $quoteFields->get('quote_prefix')['value'] ?? 'QS',
                'quote_number_sequence' => (int) ($quoteFields->get('quote_number_sequence')['value'] ?? 1),
                'quote_number_reset_yearly' => (bool) ($quoteFields->get('quote_number_reset_yearly')['value'] ?? true),
                'quote_validity_days' => (int) ($quoteFields->get('quote_validity_days')['value'] ?? 30),
                'require_cover_message' => (bool) ($quoteFields->get('require_cover_message')['value'] ?? false),
                'default_cover_message' => $quoteFields->get('default_cover_message')['value'] ?? null,
                'default_terms' => $quoteFields->get('default_terms')['value'] ?? null,
                'default_payment_terms' => $quoteFields->get('default_payment_terms')['value'] ?? null,
                'default_notes' => $quoteFields->get('default_notes')['value'] ?? null,
                'allow_client_negotiation' => (bool) ($quoteFields->get('allow_client_negotiation')['value'] ?? false),
                'allow_optional_items' => (bool) ($quoteFields->get('allow_optional_items')['value'] ?? true),
                'require_deposit' => (bool) ($quoteFields->get('require_deposit')['value'] ?? false),
                'default_deposit_percent' => (float) ($quoteFields->get('default_deposit_percent')['value'] ?? 50),
            ],
            'workspace' => [
                'name' => $workspace->name,
                'currency' => $workspace->currency ?? 'USD',
                'country' => $workspace->country ?? null,
                'logo_url' => $workspace->logo_path ? url(Storage::url($workspace->logo_path)) : null,
                'primary_color' => $workspace->primary_color ?? '#4F46E5',
                'accent_color' => $workspace->accent_color ?? '#F5A623',
                'company_address' => $workspace->address ?? null,
                'company_phone' => $workspace->phone ?? null,
                'company_email' => $workspace->email ?? null,
                'company_website' => $workspace->website ?? null,
                'tax_number' => $workspace->tax_number ?? null,
                'company_name' => $workspace->name,
                'company_tagline' => null,
            ],
        ];
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

        // Handle subsections (e.g., quotes_invoices)
        if (isset($definition['subsections'])) {
            $subsections = $definition['subsections'];
            $allFields = [];

            foreach ($subsections as $subsection) {
                $allFields = array_merge($allFields, $subsection['fields'] ?? []);
            }

            $fields = $allFields;
        }

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

                $fieldRules = ["settings.{$key}" => $rules];

                if ($type === 'array' && Arr::has($field, 'options')) {
                    $fieldRules["settings.{$key}.*"] = [
                        'string',
                        'in:'.implode(',', Arr::get($field, 'options', [])),
                    ];
                }

                return $fieldRules;
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
            'array' => ['array'],
            'json' => ['array'],
            'file' => array_filter([
                'file',
                isset($field['image']) && $field['image'] === true ? 'image' : null,
                isset($field['mimes']) ? 'mimes:'.implode(',', (array) $field['mimes']) : null,
                isset($field['max']) ? 'max:'.$field['max'] : null,
            ]),
            'email' => array_filter(['string', 'email', isset($field['max']) ? 'max:'.$field['max'] : null]),
            'url' => array_filter(['string', 'url', isset($field['max']) ? 'max:'.$field['max'] : null]),
            'timezone' => ['string', 'timezone'],
            'country' => ['string', 'size:2'],
            'currency' => ['string', 'size:3'],
            'select' => array_filter(['string', Arr::has($field, 'options') ? 'in:'.implode(',', array_map(fn ($opt) => '"'.$opt.'"', Arr::get($field, 'options', []))) : null]),
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
            'array' => 'json',
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
