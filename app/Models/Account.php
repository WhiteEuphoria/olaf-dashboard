<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'number',
        'bank',
        'client_initials',
        'broker_initials',
        'term',
        'status',
        'balance',
        'currency',
        'is_default',
        'beneficiary',
        'investment_control',
        'organization',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'term' => 'date',
            'balance' => 'decimal:2',
            'is_default' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        // If currency not provided, default from owner's currency on creation.
        static::creating(function (Account $account): void {
            if (empty($account->currency) && ($account->user || $account->user_id)) {
                $user = $account->user ?: User::find($account->user_id);
                if ($user && $user->currency) {
                    $account->currency = $user->currency;
                }
            }
        });
    }
}
