<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notification;
use Laratrust\Models\Team as LaratrustTeam;

class Workspace extends LaratrustTeam
{
    public $guarded = [];

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
     * Route notifications to the workspace owner by default.
     */
    public function routeNotificationForMail(Notification $notification): array|string|null
    {
        return $this->owner?->email;
    }
}
