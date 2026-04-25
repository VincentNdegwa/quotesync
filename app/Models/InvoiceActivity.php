<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

class InvoiceActivity extends Model
{
    #[Fillable([
        'invoice_id',
        'workspace_id',
        'user_id',
        'type',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ])]

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
