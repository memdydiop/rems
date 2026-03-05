<?php

namespace App\Models;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'internal_notes',
        'status',
        'category',
        'priority',
        'property_id',
        'unit_id',
        'renter_id',
        'user_id',
        'photo_path',
        'reported_by',
        'reporter_phone',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $casts = [
        'status' => MaintenanceStatus::class,
        'category' => \App\Enums\MaintenanceCategory::class,
        'priority' => MaintenancePriority::class,
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    public function isEditable(): bool
    {
        return !in_array($this->status->value, [
            MaintenanceStatus::Resolved->value,
            MaintenanceStatus::Cancelled->value,
        ]);
    }
}
