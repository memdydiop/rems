<?php

namespace App\Models;

use App\Enums\DepositStatus;
use App\Enums\DepositType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Deposit extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'lease_id',
        'type',
        'amount',
        'paid_at',
        'returned_amount',
        'returned_at',
        'deductions',
        'status',
        'notes',
    ];

    protected $casts = [
        'type' => DepositType::class,
        'status' => DepositStatus::class,
        'amount' => 'decimal:2',
        'returned_amount' => 'decimal:2',
        'paid_at' => 'date',
        'returned_at' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the remaining amount that can be returned.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->returned_amount);
    }
}
