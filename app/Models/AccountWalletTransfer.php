<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountWalletTransfer extends Model
{
    protected $fillable = [
        'from_account_wallet_id', 'to_account_wallet_id', 'amount',
        'reference', 'note', 'initiated_by_user_id',
    ];

    protected $casts = ['amount' => 'float'];

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(AccountWallet::class, 'from_account_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(AccountWallet::class, 'to_account_wallet_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
}
