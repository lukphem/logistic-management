<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountWalletFunding extends Model
{
    protected $fillable = [
        'account_wallet_id', 'funding_method', 'amount', 'status',
        'payment_reference', 'bank_reference', 'initiated_by_user_id', 'paid_at',
    ];

    protected $casts = ['amount' => 'float', 'paid_at' => 'datetime'];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AccountWallet::class, 'account_wallet_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
}
