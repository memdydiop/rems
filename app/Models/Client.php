<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, HasUuids, LogsActivity, Notifiable, SoftDeletes;

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim($this->first_name . ' ' . $this->last_name),
        );
    }

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'status',
        'user_id',
    ];

    protected $casts = [
        'status' => \App\Enums\ClientStatus::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function user(): BelongsTo
    {
        // This links to the Central User if we decide to sync them,
        // or Tenant User if we keep it simple.
        // For now, it constrained to 'users' table which is inside Tenant DB in standard configuration?
        // Wait, 'users' are usually in Tenant DB for single-db tenancy or multi-db.
        // In this app setup: User is in Tenant DB (referenced by `users` table).
        return $this->belongsTo(User::class);
    }

    /**
     * Route notifications for the Twilio channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForTwilio($notification)
    {
        if (empty($this->phone)) {
            return null;
        }

        // Nettoyage basique du numéro (enlever les espaces, tirets, etc.)
        $phone = preg_replace('/[^0-9+]/', '', $this->phone);

        // Ajouter le préfixe WhatsApp requis par Twilio
        // On assume que le numéro en BDD a l'indicatif international
        return 'whatsapp:' . $phone;
    }
}
