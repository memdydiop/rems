<?php

namespace App\Models;

use App\Enums\LeaseStatus;
use App\Enums\LeaseType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Lease extends Model implements HasMedia
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'unit_id',
        'client_id',
        'start_date',
        'end_date',
        'notice_date',
        'move_out_date',
        'rent_amount',
        'charges_amount',
        'deposit_amount',
        'advance_amount',
        'status',
        'lease_type',
        'notes',
        'documents',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'notice_date' => 'date',
        'move_out_date' => 'date',
        'documents' => 'array',
        'rent_amount' => 'decimal:2',
        'charges_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'status' => LeaseStatus::class,
        'lease_type' => LeaseType::class,
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
        return $this->belongsTo(Unit::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(RentAdjustment::class);
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
