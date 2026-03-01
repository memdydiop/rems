<?php

namespace App\Models;

use App\Enums\LeaseStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lease extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'renter_id',
        'start_date',
        'end_date',
        'rent_amount',
        'deposit_amount',
        'status',
        'documents',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'documents' => 'array',
        'rent_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'status' => LeaseStatus::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function scopeOverdue($query)
    {
        // Grace period until the 5th of the month
        if (now()->day <= 5) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('status', LeaseStatus::Active)
            ->whereDoesntHave('payments', function ($q) {
                $q->whereYear('paid_at', now()->year)
                    ->whereMonth('paid_at', now()->month);
            });
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->payments()->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->exists()) {
            return 'paid';
        }

        if (now()->day > 5) {
            return 'overdue';
        }

        return 'pending';
    }
}
