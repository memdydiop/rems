<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Enums\OwnerStatus;

class Owner extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'account_details',
        'status',
    ];

    protected $casts = [
        'status' => OwnerStatus::class,
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
