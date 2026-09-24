<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountWallet extends Model
{
    protected $fillable = ['owner_type', 'owner_id', 'balance', 'currency'];

    protected $casts = ['balance' => 'float'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountWalletTransaction::class);
    }

    public function fundings(): HasMany
    {
        return $this->hasMany(AccountWalletFunding::class);
    }

    /**
     * Every balance change goes through here or debit() below — never
     * a raw ->update(['balance' => ...]) anywhere else — so the ledger
     * (account_wallet_transactions) can never drift out of sync with
     * the balance it's supposed to explain. balance_after is captured
     * at write time specifically so the ledger stays a trustworthy
     * audit trail even if a later correction changes the wallet's
     * current balance for some unrelated reason.
     */
    public function credit(float $amount, ?string $fundingMethod, ?string $reference, ?string $description, ?int $recordedByUserId): AccountWalletTransaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $this->fresh()->balance,
            'funding_method' => $fundingMethod,
            'reference' => $reference,
            'description' => $description,
            'recorded_by_user_id' => $recordedByUserId,
        ]);
    }

    /**
     * Guards against ever going negative — a wallet debit for a
     * shipment payment or an admin transfer must have the funds
     * actually available; there's no overdraft concept here.
     */
    public function debit(float $amount, ?string $reference, ?string $description, ?int $recordedByUserId): AccountWalletTransaction
    {
        if ($amount > $this->balance) {
            throw new \RuntimeException('Insufficient wallet balance — this wallet has ' . number_format($this->balance, 2) . ' but ' . number_format($amount, 2) . ' was requested.');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'balance_after' => $this->fresh()->balance,
            'funding_method' => null,
            'reference' => $reference,
            'description' => $description,
            'recorded_by_user_id' => $recordedByUserId,
        ]);
    }

    public function label(): string
    {
        $owner = $this->owner;

        return match (true) {
            $owner instanceof ClientAccount => $owner->account_name . ' (' . $owner->account_number . ')',
            $owner instanceof Outlet => $owner->name . ' outlet',
            default => 'Wallet #' . $this->id,
        };
    }
}
