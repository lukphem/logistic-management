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
        'staff_id',
        'staff_short_code',
        'first_name',
        'last_name',
        'title',
        'email',
        'phone_number',
        'photo_path',
        'password',
        'user_type',
        'is_active',
        'hub_id',
        'region_id',
        'outlet_id',
        'unit_id',
        'account_status',
        'status_reason',
        'status_changed_at',
        'status_changed_by',
        'date_of_birth',
        'gender',
        'address',
        'job_title',
        'date_joined',
        'employment_type',
        'emergency_contact_name',
        'emergency_contact_phone',
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
            'date_of_birth' => 'date',
            'date_joined' => 'date',
        ];
    }

    /**
     * first_name/last_name are the source of truth for a person's name;
     * `name` is kept in sync automatically so the dozens of existing
     * places that read $user->name (assignedRider->name, handler->name,
     * etc.) never had to change. staff_id is generated once, only for
     * staff accounts, and never regenerated after that.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->first_name || $user->last_name) {
                $user->name = trim("{$user->first_name} {$user->last_name}");
            }
        });

        static::creating(function (User $user) {
            if ($user->user_type === 'staff' && ! $user->staff_id) {
                do {
                    $candidate = 'STF-' . strtoupper(\Illuminate\Support\Str::random(6));
                } while (static::where('staff_id', $candidate)->exists());

                $user->staff_id = $candidate;
            }

            // Separate from staff_id above (that one's random and
            // 10 characters — too long/unreadable to embed in a
            // compact client account number). staff_short_code is
            // purpose-built for that: first letters of the staff
            // member's own name where possible, so it's at least
            // somewhat recognizable, falling back to random on
            // collision.
            if ($user->user_type === 'staff' && ! $user->staff_short_code) {
                $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $user->name ?? ''));
                $candidate = str_pad(substr($base, 0, 3), 3, 'X');

                while (static::where('staff_short_code', $candidate)->exists()) {
                    $candidate = strtoupper(\Illuminate\Support\Str::random(3));
                }

                $user->staff_short_code = $candidate;
            }
        });
    }

    public function getPhotoUrlAttribute(): ?string
    {
        // Root-relative for the same reason as Setting::getLogoUrlAttribute()
        // — avoids the APP_URL port mismatch that was hiding the company
        // logo on local dev.
        return $this->photo_path ? '/storage/' . ltrim($this->photo_path, '/') : null;
    }

    public function billingProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ClientBillingProfile::class, 'client_user_id');
    }

    public function clientProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ClientProfile::class, 'client_user_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(\App\Models\ClientAccount::class, 'client_user_id');
    }

    /**
     * Every client has exactly one Default Account from the moment
     * they're created (auto-created for existing clients during the
     * Client -> Account restructure) — this is the account used
     * everywhere multi-account selection isn't wired up yet.
     */
    public function defaultAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ClientAccount::class, 'client_user_id')->where('is_default', true);
    }

    /**
     * Sub-user PROFILES across every account this client has — reached
     * through ClientAccount now (Client -> Account restructure), not
     * directly, since a sub-user is scoped to a specific Account, not
     * the client as a whole. ->clientUser on each result gets to the
     * actual User/login.
     */
    public function subUserProfiles(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(\App\Models\ClientProfile::class, \App\Models\ClientAccount::class, 'client_user_id', 'client_account_id');
    }

    public function departments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(\App\Models\Department::class, \App\Models\ClientAccount::class, 'client_user_id', 'client_account_id');
    }

    public function apiClient(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\ApiClient::class, 'client_user_id');
    }

    public function serviceDiscounts(): HasMany
    {
        return $this->hasMany(\App\Models\ClientServiceDiscount::class, 'client_user_id');
    }

    public function specialTariffs(): HasMany
    {
        return $this->hasMany(\App\Models\ClientSpecialTariff::class, 'client_user_id');
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Independent of the access scale — an optional organizational tag
     * ("which team is this person on"), not a scope level. Never affects
     * accessibleHubIds() or canAccessShipment().
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function statusAudits(): HasMany
    {
        return $this->hasMany(UserStatusAudit::class)->latest();
    }

    /**
     * The access scale, broadest to narrowest: Global > Region > Hub >
     * Outlet. Exactly one of region_id/hub_id/outlet_id is ever set —
     * never a combination — enforced in UserController::validateForm.
     */
    public function hasGlobalAccess(): bool
    {
        return $this->hub_id === null && $this->region_id === null && $this->outlet_id === null;
    }

    public function hasRegionAccess(): bool
    {
        return $this->region_id !== null && $this->hub_id === null && $this->outlet_id === null;
    }

    public function hasHubAccess(): bool
    {
        return $this->hub_id !== null && $this->outlet_id === null;
    }

    public function hasOutletAccess(): bool
    {
        return $this->outlet_id !== null;
    }

    /**
     * Resolves the scale down to the one thing every consumer actually
     * needs: which hub IDs this user is allowed to see. Global returns
     * every hub; Region returns every hub under that region; Hub returns
     * just the one; Outlet also resolves to just its parent hub, since
     * shipments are tracked at hub granularity, not outlet — an
     * outlet-scoped user sees the same shipment set as someone scoped to
     * their parent hub. Callers (e.g. ShipmentController) never need to
     * know which scope level produced the list.
     */
    public function accessibleHubIds(): array
    {
        if ($this->hasGlobalAccess()) {
            return Hub::pluck('id')->all();
        }

        if ($this->hasRegionAccess()) {
            return Hub::where('region_id', $this->region_id)->pluck('id')->all();
        }

        if ($this->hasOutletAccess()) {
            return [$this->outlet?->hub_id];
        }

        return [$this->hub_id];
    }

    /**
     * unit_id is independent of access_scope — an organizational tag,
     * not a scope level (see UserController::validateForm) — but it
     * does mean something specific for departure/transfer scanning: a
     * user pinned to one unit can only move shipments that have
     * actually arrived there, while a user whose access is hub-wide
     * (no unit_id) or broader (region/global) oversees every unit
     * inside whatever hub(s) they already have access to. This is the
     * one check that decides which case applies for a given unit.
     */
    public function canActOnUnit(?int $unitId): bool
    {
        if (! $unitId) {
            return true;
        }

        if ($this->hasGlobalAccess() || $this->hasRegionAccess()) {
            return true;
        }

        $unit = \App\Models\Unit::find($unitId);

        if (! $unit || ! in_array($unit->hub_id, $this->accessibleHubIds(), true)) {
            return false;
        }

        // Hub-wide (no unit_id of their own) oversees every unit in
        // that hub; pinned to a specific unit means only that one.
        return $this->unit_id === null || (int) $this->unit_id === (int) $unitId;
    }

    /**
     * The precise check for a single shipment — used by
     * ShipmentController::show(). Outlet-scoped users are checked against
     * current_outlet_id specifically (not just the parent hub), since
     * Increment 15 gave shipments real outlet-level tracking; every other
     * scope level still resolves through accessibleHubIds().
     */
    public function canAccessShipment(Shipment $shipment): bool
    {
        if ($this->hasGlobalAccess()) {
            return true;
        }

        if ($this->hasOutletAccess()) {
            return $shipment->current_outlet_id === $this->outlet_id;
        }

        return in_array($shipment->current_hub_id, $this->accessibleHubIds());
    }

    /**
     * A walk-in (no account at all) is never restricted — there's
     * nothing to check. shipments:cross-account is a deliberate
     * escape hatch, separate from the ordinary shipments
     * permissions, for staff who legitimately need to book across
     * outlet lines. Otherwise this mirrors canAccessShipment()'s own
     * scoping exactly: global access always passes, outlet-scoped
     * staff need an exact outlet match, hub/region-scoped staff need
     * the account's outlet to fall within one of their own hubs. An
     * account with no outlet of its own (never explicitly tied to
     * one) isn't restricted either — there's no boundary to enforce.
     */
    public function canBookForAccount(?ClientAccount $account): bool
    {
        if (! $account || $this->can('shipments:cross-account')) {
            return true;
        }

        if ($this->hasGlobalAccess()) {
            return true;
        }

        if (! $account->outlet_id) {
            return true;
        }

        if ($this->hasOutletAccess()) {
            return $account->outlet_id === $this->outlet_id;
        }

        return in_array($account->outlet?->hub_id, $this->accessibleHubIds());
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
