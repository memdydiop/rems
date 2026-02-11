<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use \Stancl\Tenancy\Database\Concerns\CentralConnection;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'paystack_id',
        'paystack_code',
        'email_token',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function onTrial()
    {
        return $this->on_trial;
    }

    public function active()
    {
        return $this->status === 'active' || $this->onTrial();
    }

    public function canceled()
    {
        return $this->status === 'canceled';
    }
}
