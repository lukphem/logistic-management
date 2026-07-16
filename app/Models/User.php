<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'is_active',
        'hub_id',
        'region_id',
        'account_status',
        'status_reason',
        'status_changed_at',
        'status_changed_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'status_changed_at' => 'datetime',
        ];
    }

    public function billingProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ClientBillingProfile::class, 'client_user_id');
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function statusAudits(): HasMany
    {
        return $this->hasMany(UserStatusAudit::class)->latest();
    }

    /**
     * The access scale, broadest to narrowest: Global > Region > Hub.
     * The three are mutually exclusive — a user has exactly one of them,
     * never a combination — so region_id and hub_id are never both set
     * at once (enforced in UserController::validateForm).
     */
    public function hasGlobalAccess(): bool
    {
        return $this->hub_id === null && $this->region_id === null;
    }

    public function hasRegionAccess(): bool
    {
        return $this->region_id !== null && $this->hub_id === null;
    }

    public function hasHubAccess(): bool
    {
        return $this->hub_id !== null;
    }

    /**
     * Resolves the scale down to the one thing every consumer actually
     * needs: which hub IDs this user is allowed to see. Global returns
     * every hub; Region returns every hub under that region; Hub returns
     * just the one. Callers (e.g. ShipmentController) never need to know
     * which scope level produced the list.
     */
    public function accessibleHubIds(): array
    {
        if ($this->hasGlobalAccess()) {
            return Hub::pluck('id')->all();
        }

        if ($this->hasRegionAccess()) {
            return Hub::where('region_id', $this->region_id)->pluck('id')->all();
        }

        return [$this->hub_id];
    }

    /**
     * Whichever of suspended/locked/terminated a status represents, all
     * three block login identically today — they exist as distinct values
     * for audit and reporting ("why can't this person sign in" should
     * never just say "inactive"), not because they currently behave
     * differently. If they ever need to (e.g. locked = temporary,
     * unlockable by the user themselves via a reset flow), this is the
     * one place that would change.
     */
    public function canSignIn(): bool
    {
        return $this->account_status === 'active';
    }

    /**
     * Records the change in user_status_audits AND updates the current
     * status in one call — the two should never happen separately, or
     * the audit trail can drift from what's actually stored.
     */
    public function changeStatus(string $toStatus, ?string $reason, ?self $changedBy): void
    {
        $fromStatus = $this->account_status;

        $this->statusAudits()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'changed_by' => $changedBy?->id,
        ]);

        $this->update([
            'account_status' => $toStatus,
            'status_reason' => $reason,
            'status_changed_at' => now(),
            'status_changed_by' => $changedBy?->id,
            'is_active' => $toStatus === 'active', // kept in sync for existing code that still reads is_active
        ]);
    }
}
