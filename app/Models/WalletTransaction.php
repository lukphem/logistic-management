<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = ['client_wallet_id', 'type', 'amount', 'reference', 'description'];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(ClientWallet::class, 'client_wallet_id');
    }
}
