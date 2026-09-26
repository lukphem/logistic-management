<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientUpgradeRequest extends Model
{
    protected $fillable = [
        'client_account_id', 'company_name', 'rc_number', 'tin', 'industry',
        'contact_person_name', 'contact_person_role', 'status',
        'reviewed_by_user_id', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
