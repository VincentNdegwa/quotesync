<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable([
    'client_id',
    'name',
    'email',
    'phone',
    'position',
    'is_primary',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $query): void {
            $workspaceId = Auth::user()?->current_workspace_id;

            if ($workspaceId !== null) {
                $query->whereHas('client', function (Builder $q) use ($workspaceId) {
                    $q->where('workspace_id', $workspaceId);
                });
            }
        });

        static::saving(function (Contact $contact) {
            if ($contact->is_primary) {
                Contact::where('client_id', $contact->client_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);

                Client::where('id', $contact->client_id)
                    ->update(['primary_contact_id' => $contact->id]);
            }
        });
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
