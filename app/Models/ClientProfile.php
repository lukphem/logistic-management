<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProfile extends Model
{
    protected $fillable = [
        'client_user_id', 'account_type',
        'id_type', 'id_number',
        'company_name', 'rc_number', 'tin', 'industry', 'contact_person_name', 'contact_person_role',
        'address', 'city_id', 'alternate_phone', 'billing_address',
    ];

    public const ID_TYPES = [
        'national_id' => 'National ID',
        'passport' => 'Passport',
        'drivers_license' => "Driver's License",
        'voters_card' => "Voter's Card",
    ];

    public function isOrganization(): bool
    {
        return $this->account_type === 'organization';
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
