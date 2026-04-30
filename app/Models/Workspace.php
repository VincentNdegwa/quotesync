<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notification;
use Laratrust\Models\Team as LaratrustTeam;

class Workspace extends LaratrustTeam
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_id',
        'currency',
        'industry_id',
        'agency_mode_enabled',
        'agency_commission_rate',
        'agency_commission_type',
        'logo_path',
        'primary_color',
        'accent_color',
        'address',
        'phone',
        'email',
        'website',
        'country',
        'tax_number',
        'white_label_mode',
        'favicon_path',
        'custom_domain',
    ];

    /**
     * The owner/direct contact for this workspace.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The industry this workspace belongs to.
     *
     * @return BelongsTo<Industry, $this>
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Get workspace members through Laratrust's role pivot.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'workspace_id', 'user_id')
            ->wherePivot('user_type', User::class)
            ->distinct();
    }

    /**
     * Get pending and historical workspace invitations.
     *
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get key-value settings for this workspace.
     *
     * @return HasMany<WorkspaceSetting, $this>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(WorkspaceSetting::class);
    }

    /**
     * @return HasMany<FollowUpSequence, $this>
     */
    public function followUpSequences(): HasMany
    {
        return $this->hasMany(FollowUpSequence::class);
    }

    /**
     * Route notifications to the workspace owner by default.
     */
    public function routeNotificationForMail(Notification $notification): array|string|null
    {
        return $this->owner?->email;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings_onboarded_at' => 'datetime',
            'white_label_mode' => 'boolean',
            'agency_mode_enabled' => 'boolean',
            'agency_commission_rate' => 'decimal:2',
        ];
    }

    public function isWhiteLabelEnabled(): bool
    {
        return $this->white_label_mode ?? true;
    }

    public function getWhiteLabelLogoUrl(): ?string
    {
        return $this->logo_path;
    }

    public function getWhiteLabelCompanyName(): ?string
    {
        return $this->name;
    }

    public function getWhiteLabelPrimaryColor(): ?string
    {
        return $this->primary_color;
    }

    public function getWhiteLabelDomain(): ?string
    {
        return $this->custom_domain;
    }

    public function isAgencyModeEnabled(): bool
    {
        return $this->agency_mode_enabled;
    }

    public function getAgencyCommissionRate(): ?float
    {
        return $this->agency_commission_rate;
    }

    public function getAgencyCommissionType(): string
    {
        return $this->agency_commission_type ?? 'percentage';
    }

    public function calculateAgencyCommission(float $quoteTotal): float
    {
        if (! $this->isAgencyModeEnabled() || ! $this->agency_commission_rate) {
            return 0;
        }

        return match ($this->getAgencyCommissionType()) {
            'percentage' => $quoteTotal * ($this->agency_commission_rate / 100),
            'fixed' => $this->agency_commission_rate,
            default => 0,
        };
    }
}
