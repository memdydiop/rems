<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use \Stancl\Tenancy\Database\Concerns\CentralConnection;

    protected $fillable = [
        'name',
        'paystack_code',
        'amount',
        'trial_period_days',
        'currency',
        'interval',
        'description',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function getFormattedPriceAttribute()
    {
        $value = $this->amount / 100;

        if ($this->currency === 'XOF') {
            return number_format($value, 0, ',', ' ') . ' FCFA';
        }

        return '$' . number_format($value, 2);
    }

    public function getDisplayFeaturesAttribute()
    {
        $features = $this->features ?? [];

        // If it's a simple list of strings, return as is
        if (array_is_list($features)) {
            return $features;
        }

        // Otherwise format keys
        return collect($features)->map(function ($value, $key) {
            $label = ucwords(str_replace(['_', '-'], ' ', $key));

            // Handle Limits
            if (is_numeric($value)) {
                if ($value == -1)
                    return str_replace('Max ', '', $label) . " Illimité(e)s";
                return "{$label}: {$value}";
            }

            // Handle Boolean Features
            if ($value === true)
                return $label;
            if ($value === false)
                return null;

            return "{$label}: {$value}";
        })->filter()->values()->all();
    }
}
