<?php

namespace App\Models;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected static function booted()
    {
        static::deleted(function ($property) {
            $property->units()->delete();
        });

        static::restored(function ($property) {
            $property->units()->withTrashed()->restore();
        });
    }

    protected $fillable = [
        'name',
        'address',
        'type',
        'status',
        'owner_id',
        'amenities',
        'transaction_type',
    ];

    protected $casts = [
        'type' => PropertyType::class,
        'status' => PropertyStatus::class,
        'amenities' => 'array',
        'transaction_type' => \App\Enums\TransactionType::class,
    ];

    public function getAmenityLabels(): array
    {
        return collect($this->amenities ?? [])
            ->map(fn($value) => \App\Enums\PropertyAmenity::tryFrom($value)?->label() ?? $value)
            ->toArray();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function leases(): HasManyThrough
    {
        return $this->hasManyThrough(Lease::class, Unit::class);
    }

    public function maintenanceRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getFinancialStats(): array
    {
        // Revenue from all units in the last 12 months
        $revenue = Lease::whereIn('unit_id', $this->units()->pluck('id'))
            ->with(['payments' => fn($q) => $q->where('status', 'paid')->where('paid_at', '>=', now()->subYear())])
            ->get()
            ->pluck('payments')
            ->flatten()
            ->sum('amount');

        // All expenses (property-wide + unit-specific)
        $expenses = $this->expenses()
            ->where('date', '>=', now()->subYear())
            ->where('status', 'paid')
            ->sum('amount');

        return [
            'revenue' => (float) $revenue,
            'expenses' => (float) $expenses,
            'net' => (float) ($revenue - $expenses),
            'occupancy_rate' => $this->units()->count() > 0
                ? ($this->units()->whereHas('activeLease')->count() / $this->units()->count()) * 100
                : 0,
        ];
    }
}
