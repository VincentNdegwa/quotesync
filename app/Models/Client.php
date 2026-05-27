<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'workspace_id',
    'company_name',
    'contact_name',
    'email',
    'phone',
    'whatsapp',
    'address',
    'city',
    'country',
    'currency',
    'language',
    'tax_number',
    'created_by',
    'primary_contact_id',
    'health_score',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query): void {
            $workspaceId = Auth::user()?->current_workspace_id;

            if ($workspaceId !== null) {
                $query->where('workspace_id', $workspaceId);
            }
        });
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<ConfigurationTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ConfigurationTag::class, 'client_tags')
            ->withTimestamps();
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<Quote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    /**
     * Calculate and update the client's health score (0-100)
     */
    public function calculateHealthScore(): void
    {
        $quotes = $this->quotes()->get();

        if ($quotes->isEmpty()) {
            $this->health_score = 50; // Default score for new clients
            $this->save();

            return;
        }

        $totalQuotes = $quotes->count();
        $wonQuotes = $quotes->where('status', 'won')->count();
        $winRate = $totalQuotes > 0 ? ($wonQuotes / $totalQuotes) * 100 : 0;

        // Calculate average time to close (in days)
        $closedQuotes = $quotes->whereIn('status', ['won', 'lost'])
            ->filter(fn ($q) => $q->accepted_at || $q->declined_at);

        $avgTimeToClose = 0;
        if ($closedQuotes->isNotEmpty()) {
            $totalDays = $closedQuotes->sum(function ($quote) {
                $closedAt = $quote->accepted_at ?? $quote->declined_at;
                $createdAt = $quote->created_at;

                return $closedAt ? $closedAt->diffInDays($createdAt) : 0;
            });
            $avgTimeToClose = $totalDays / $closedQuotes->count();
        }

        // Calculate total value
        $totalValue = $quotes->sum('total');

        // Calculate recent activity (quotes in last 90 days)
        $recentActivity = $quotes->where('created_at', '>=', now()->subDays(90))->count();
        $recentActivityScore = min(($recentActivity / 5) * 20, 20); // Max 20 points

        // Calculate score components
        $winRateScore = ($winRate / 100) * 30; // Max 30 points
        $timeToCloseScore = max(0, 30 - min($avgTimeToClose, 30)); // Max 30 points, lower is better
        $valueScore = min(($totalValue / 10000) * 20, 20); // Max 20 points

        $this->health_score = round($winRateScore + $timeToCloseScore + $valueScore + $recentActivityScore);
        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }
}
