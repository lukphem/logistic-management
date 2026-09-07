<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProfile extends Model
{
    protected $fillable = [
        'client_user_id', 'account_type',
        'parent_client_user_id', 'department_id',
        'account_number', 'created_by', 'business_manager_id',
        'id_type', 'id_number',
        'company_name', 'rc_number', 'tin', 'industry', 'contact_person_name', 'contact_person_role',
        'address', 'city_id', 'country_id', 'state_id', 'territory_id', 'express_center', 'business_objective',
        'alternate_phone', 'billing_address',
        'warehouse_access', 'cod_enabled',
        'insurance_agreement', 'insurance_agreement_date', 'insurance_agreement_notes',
        'invoice_due_days', 'sla_pickup_hours', 'sla_delivery_days',
    ];

    protected $casts = [
        'warehouse_access' => 'boolean',
        'cod_enabled' => 'boolean',
        'insurance_agreement' => 'boolean',
        'insurance_agreement_date' => 'date',
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

    public function isSubUser(): bool
    {
        return ! is_null($this->parent_client_user_id);
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function parentClient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_client_user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function businessManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_manager_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }
}
