<?php

namespace App\Models;

use App\Enums\UnitStatus;
use App\Enums\UnitType; // Added import
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'property_id',
        'name',
        'type',
        'rent_amount',
        'surface_area',
        'notes',
    ];

    protected $casts = [
        'type' => UnitType::class,
    ];
    protected function status(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn() => $this->activeLease()->exists()
            ? UnitStatus::Occupied
            : UnitStatus::Vacant,
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function leases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Lease::class)->where('status', 'active');
    }

    public function maintenanceRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /**
     * Determine if the unit is currently undergoing active maintenance.
     */
    public function isUnderMaintenance(): bool
    {
        // Check if there is a building-wide maintenance
        if ($this->property && $this->property->status === \App\Enums\PropertyStatus::Maintenance) {
            return true;
        }

        // Check if there are active maintenance requests for this specific unit
        return $this->maintenanceRequests()
            ->whereIn('status', [\App\Enums\MaintenanceStatus::Pending, \App\Enums\MaintenanceStatus::InProgress])
            ->exists();
    }

    /**
     * Determine if the unit is available for a new lease.
     * Rule: Must be Vacant AND not under active maintenance.
     */
    public function canBeLeased(): bool
    {
        return $this->status === UnitStatus::Vacant && !$this->isUnderMaintenance();
    }
}
