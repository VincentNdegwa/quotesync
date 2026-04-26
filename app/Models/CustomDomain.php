<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomDomain extends Model
{
    protected $table = 'workspace_custom_domains';

    protected $fillable = [
        'workspace_id',
        'domain',
        'verification_token',
        'verified_at',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function generateVerificationToken(): string
    {
        $this->verification_token = Str::random(32);
        $this->save();
        
        return $this->verification_token;
    }

    public function markAsVerified(): void
    {
        $this->verified_at = now();
        $this->verification_token = null;
        $this->save();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function getVerificationRecordName(): string
    {
        return '_quoteync-domain-verification=' . $this->verification_token;
    }

    public function getVerificationRecordValue(): string
    {
        return 'quoteync-verify';
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('verified_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
