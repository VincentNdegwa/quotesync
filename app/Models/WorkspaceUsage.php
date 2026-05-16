<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkspaceUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'period',
        'quotes_sent',
        'invoices_sent',
        'ai_credits_used',
    ];

    protected $casts = [
        'period' => 'date',
        'quotes_sent' => 'integer',
        'invoices_sent' => 'integer',
        'ai_credits_used' => 'integer',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
