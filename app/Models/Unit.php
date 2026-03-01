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
        'type', // Added type
        'rent_amount',
        'status',
    ];

    protected $casts = [
        'status' => UnitStatus::class,
        'type' => UnitType::class, // Added cast
        'rent_amount' => 'decimal:2',
    ];

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
}
