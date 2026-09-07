<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientServiceDiscount extends Model
{
    protected $fillable = ['client_user_id', 'service_type_id', 'discount_percentage'];

    protected $casts = ['discount_percentage' => 'float'];

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
