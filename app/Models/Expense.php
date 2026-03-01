<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'property_id',
        'vendor_id',
        'description',
        'amount',
        'date',
        'category',
        'receipt_path',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
