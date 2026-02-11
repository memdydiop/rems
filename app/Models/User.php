<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, \Spatie\Activitylog\Traits\LogsActivity, TwoFactorAuthenticatable, \Illuminate\Database\Eloquent\Concerns\HasUuids, \Laravel\Sanctum\HasApiTokens;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function sendPasswordResetNotification($token)
    {
        // Safe URL generation for Console/Queue context
        if (tenancy()->tenant) {
            $domain = tenancy()->tenant->domains->first()?->domain;

            if ($domain) {
                $protocol = app()->isProduction() ? 'https' : 'http';

                // Parse port from APP_URL for dev environments
                $appUrl = config('app.url');
                $port = parse_url($appUrl, PHP_URL_PORT);
                $portSuffix = $port ? ":{$port}" : '';

                // Build URL manually to avoid Route collection issues in Job/Queue
                $url = "{$protocol}://{$domain}{$portSuffix}/reset-password/{$token}?email=" . urlencode($this->email);
            } else {
                $url = url(route('password.reset', [
                    'token' => $token,
                    'email' => $this->email,
                ], false));
            }
        } else {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $this->email,
            ]);
        }

        $this->notify(new \App\Notifications\TenantResetPassword($url));
    }

    /**
     * Scope a query to only include users that are not Ghost users.
     */
    public function scopeWhereNotGhost($query)
    {
        return $query->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Ghost');
        });
    }
}
