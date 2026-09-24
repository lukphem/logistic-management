<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountWalletTransaction extends Model
{
    protected $fillable = [
        'account_wallet_id', 'type', 'amount', 'balance_after',
        'funding_method', 'reference', 'description', 'recorded_by_user_id',
    ];

    protected $casts = ['amount' => 'float', 'balance_after' => 'float'];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AccountWallet::class, 'account_wallet_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
