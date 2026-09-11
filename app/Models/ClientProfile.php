<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Slimmed down by the Client -> Account restructure — everything that
 * used to live here (address, account_type, business fields,
 * managerial services, etc.) now lives on ClientAccount, since it was
 * genuinely account-level, not client-level. This model's only
 * remaining job: mark which login belongs to which Account.
 *
 * client_account_id NULL means this is the PRIMARY client login (the
 * one that owns Accounts via ClientAccount.client_user_id); set means
 * this is a sub-user, scoped to that specific Account, optionally
 * further scoped to one of that Account's Departments.
 *
 * The old columns (account_type, address, business_manager_id, etc.)
 * still exist on the underlying table for now — not dropped until the
 * final cleanup phase of the restructure, once everything reading
 * from them elsewhere is confirmed moved over — but this model no
 * longer exposes them, since ClientAccount is their real source of
 * truth going forward.
 */
class ClientProfile extends Model
{
    protected $fillable = ['client_user_id', 'client_account_id', 'department_id'];

    public function isSubUser(): bool
    {
        return ! is_null($this->client_account_id) && $this->clientAccount?->client_user_id !== $this->client_user_id;
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
