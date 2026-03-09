<?php

namespace App\Models;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Enums\TransactionType;
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

    protected static function booted()
    {
        static::deleted(function ($unit) {
            $unit->leases()->delete();
        });

        static::restored(function ($unit) {
            $unit->leases()->withTrashed()->restore();
        });
    }

    protected $fillable = [
        'property_id',
        'name',
        'type',
        'transaction_type',
        'sale_price',
        'surface_area',
        'rooms_count',
        'bathrooms_count',
        'floor_number',
        'electricity_meter_number',
        'water_meter_number',
        'kitchen_type',
        'notes',
        'amenities',
    ];

    protected $casts = [
        'type' => UnitType::class,
        'transaction_type' => TransactionType::class,
        'amenities' => 'array',
        'sale_price' => 'float',
    ];

    public function getAmenityLabels(): array
    {
        return collect($this->amenities ?? [])
            ->map(fn($value) => \App\Enums\UnitAmenity::tryFrom($value)?->label() ?? $value)
            ->toArray();
    }

    public function getKitchenTypeLabel(): string
    {
        return match ($this->kitchen_type) {
            'independent' => 'Séparée',
            'open' => 'Américaine / Ouverte',
            default => '--'
        };
    }
    protected function status(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if ($this->sales()->exists()) {
                    return UnitStatus::Sold;
                }

                return $this->activeLease()->exists()
                    ? UnitStatus::Occupied
                    : UnitStatus::Vacant;
            },
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
        return $this->belongsTo(Property::class)->withTrashed();
    }

    public function leases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Lease::class)->where('status', 'active');
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function sale(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Sale::class)->latest('sold_at');
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
        return $this->transaction_type === TransactionType::Rental && $this->status === UnitStatus::Vacant && !$this->isUnderMaintenance();
    }

    public function canBeSold(): bool
    {
        return $this->isForSale() && $this->status === UnitStatus::Vacant;
    }

    public function isForSale(): bool
    {
        return $this->transaction_type === TransactionType::Sale;
    }

    public function isForRental(): bool
    {
        return $this->transaction_type === TransactionType::Rental;
    }

    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Financial statistics
     */
    public function getMonthlyNetIncome(): float
    {
        $rent = $this->activeLease?->rent_amount ?? 0;

        // Average monthly expenses (last 12 months)
        $annualExpenses = $this->expenses()
            ->where('date', '>=', now()->subYear())
            ->where('status', 'paid')
            ->sum('amount');

        return $rent - ($annualExpenses / 12);
    }

    public function getAnnualPerformance(): array
    {
        $oneYearAgo = now()->subYear();

        $revenue = $this->leases()
            ->with(['payments' => fn($q) => $q->where('status', 'paid')->where('paid_at', '>=', $oneYearAgo)])
            ->get()
            ->pluck('payments')
            ->flatten()
            ->sum('amount');

        $expenses = $this->expenses()
            ->where('date', '>=', $oneYearAgo)
            ->where('status', 'paid')
            ->sum('amount');

        $net = $revenue - $expenses;
        $margin = $revenue > 0 ? ($net / $revenue) * 100 : 0;

        return [
            'revenue' => (float) $revenue,
            'expenses' => (float) $expenses,
            'net' => (float) $net,
            'margin' => (float) $margin,
        ];
    }
}
