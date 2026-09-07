<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDocument extends Model
{
    protected $fillable = ['client_user_id', 'document_type', 'original_filename', 'file_path', 'uploaded_by'];

    public const DOCUMENT_TYPES = [
        'signed_agreement' => 'Signed Agreement',
        'id_proof' => 'ID Proof',
        'business_registration' => 'Business Registration',
        'insurance_certificate' => 'Insurance Certificate',
        'other' => 'Other',
    ];

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return '/storage/' . ltrim($this->file_path, '/');
    }
}
